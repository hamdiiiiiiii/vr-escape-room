<?php

namespace App\Services;

use App\Models\GeneratedPuzzle;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Asks the AI for ONE new puzzle on a concept, checks the reply strictly,
 * and stores it as "pending" for an instructor to approve.
 *
 * Privacy: only the concept, the type, the difficulty and the wrong answers
 * are sent. Never names, emails or ids.
 *
 * Any failure (no key, network, bad JSON, failed checks) returns null and the
 * app carries on with the normal puzzles.
 */
class PuzzleGeneratorService
{
    public const TYPES = ['observation', 'assembly'];
    public const DIFFICULTIES = ['easier', 'same'];

    public function __construct(private InsightsService $insights)
    {
    }

    public function isConfigured(): bool
    {
        return ! empty(config('services.anthropic.key')) && ! empty(config('services.anthropic.model'));
    }

    /** Concepts that exist in the content pack. */
    public function concepts(): array
    {
        return collect($this->insights->puzzleIndex())->pluck('concept')->filter()->unique()->values()->all();
    }

    public function generate(User $instructor, string $concept, string $type, string $difficulty, ?User $student = null): ?GeneratedPuzzle
    {
        if (! $this->isConfigured()
            || ! in_array($type, self::TYPES, true)
            || ! in_array($difficulty, self::DIFFICULTIES, true)) {
            return null;
        }

        $request = [
            'conceptTag' => $concept,
            'type' => $type,
            'difficulty' => $difficulty,
            'commonWrongAnswers' => $this->wrongAnswersFor($concept, $student, $instructor),
            'misconceptionsToFix' => $this->misconceptionsFor($concept, $student, $instructor),
            'exampleOfTheFormat' => $this->example($type),
        ];

        try {
            $response = Http::timeout((int) config('services.anthropic.generate_timeout', 30))
                ->withHeaders(['x-api-key' => config('services.anthropic.key'), 'anthropic-version' => '2023-06-01'])
                ->acceptJson()
                ->post(config('services.anthropic.url'), [
                    'model' => config('services.anthropic.model'),
                    'max_tokens' => 1200,
                    'system' => $this->systemPrompt(),
                    'messages' => [['role' => 'user', 'content' => json_encode($request)]],
                ]);

            if (! $response->successful()) {
                Log::warning('AI puzzle generation failed', ['status' => $response->status()]);

                return null;
            }

            $puzzle = $this->validate($response->json('content.0.text'), $concept, $type);
        } catch (Throwable $e) {
            Log::warning('AI puzzle generation error: '.$e->getMessage());

            return null;
        }

        if ($puzzle === null) {
            return null;
        }

        return GeneratedPuzzle::create([
            'requested_by' => $instructor->id,
            'student_id' => $student?->id,
            'concept_tag' => $concept,
            'type' => $type,
            'difficulty' => $difficulty,
            'content' => $puzzle,
            'status' => GeneratedPuzzle::PENDING,
        ]);
    }

    /**
     * Strict check of the AI reply. Returns a clean puzzle (same format as the
     * content pack) or null. Public so tests can use it directly.
     */
    public function validate(?string $text, string $concept, string $type): ?array
    {
        if (! is_string($text)) {
            return null;
        }

        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($text)));
        $d = json_decode($text, true);

        if (! is_array($d) || ($d['type'] ?? null) !== $type || ($d['conceptTag'] ?? null) !== $concept) {
            return null;
        }

        if (! $this->text($d['prompt'] ?? null, 20, 400) || ! $this->text($d['explanation'] ?? null, 10, 500)) {
            return null;
        }

        $hints = $d['hints'] ?? null;
        if (! is_array($hints) || count($hints) !== 2 || ! array_is_list($hints)) {
            return null;
        }
        foreach ($hints as $h) {
            if (! $this->text($h, 10, 300)) {
                return null;
            }
        }

        $data = $type === 'observation' ? $this->observation($d) : $this->assembly($d);
        if ($data === null) {
            return null;
        }

        return [
            'id' => 'gen-'.Str::lower(Str::random(8)),
            'type' => $type,
            'conceptTag' => $concept,
            'prompt' => $d['prompt'],
            'data' => $data['data'],
            'hints' => array_values($hints),
            'answerKey' => $data['answerKey'],
            'explanation' => $d['explanation'],
        ];
    }

    /** Observation: find the buggy line. The key must be a real line number. */
    private function observation(array $d): ?array
    {
        $lines = $d['data']['codeSnippet'] ?? null;
        $line = $d['answerKey']['buggyLineNumber'] ?? null;

        if (! is_array($lines) || ! array_is_list($lines) || count($lines) < 3 || count($lines) > 12 || ! is_int($line)) {
            return null;
        }

        $clean = [];
        foreach ($lines as $i => $l) {
            if (! is_array($l) || ($l['lineNumber'] ?? null) !== $i + 1 || ! is_string($l['text'] ?? null)
                || mb_strlen($l['text']) > 120) {
                return null;
            }
            $clean[] = ['lineNumber' => $i + 1, 'text' => $l['text']];
        }

        // The buggy line must exist and must be real code, not a blank or comment.
        $text = trim($clean[$line - 1]['text'] ?? '');
        if ($line < 1 || $line > count($clean) || $text === '' || str_starts_with($text, '#')) {
            return null;
        }

        return ['data' => ['codeSnippet' => $clean], 'answerKey' => ['buggyLineNumber' => $line]];
    }

    /** Assembly: pick input + expected output. The key must be one of the options. */
    private function assembly(array $d): ?array
    {
        $inputs = $d['data']['options']['inputs'] ?? null;
        $outputs = $d['data']['options']['expectedOutputs'] ?? null;
        $input = $d['answerKey']['input'] ?? null;
        $output = $d['answerKey']['expectedOutput'] ?? null;

        if (! is_array($inputs) || ! is_array($outputs) || ! array_is_list($inputs) || ! array_is_list($outputs)
            || count($inputs) < 3 || count($inputs) > 5 || count($outputs) < 2 || count($outputs) > 4) {
            return null;
        }

        foreach ($inputs as $v) {
            if (! is_int($v) && ! is_string($v)) {
                return null;
            }
        }
        foreach ($outputs as $v) {
            if (! is_string($v) || $v === '' || mb_strlen($v) > 60) {
                return null;
            }
        }

        // No duplicate options, and exactly one correct pick.
        if (count(array_unique($inputs, SORT_REGULAR)) !== count($inputs) || count(array_unique($outputs)) !== count($outputs)) {
            return null;
        }
        if (! in_array($input, $inputs, true) || ! in_array($output, $outputs, true)) {
            return null;
        }

        return [
            'data' => ['options' => ['inputs' => $inputs, 'expectedOutputs' => $outputs]],
            'answerKey' => ['input' => $input, 'expectedOutput' => $output],
        ];
    }

    private function text(mixed $v, int $min, int $max): bool
    {
        return is_string($v) && mb_strlen(trim($v)) >= $min && mb_strlen($v) <= $max;
    }

    /** Results with wrong answers on this concept (one student, or the instructor's whole class). */
    private function resultsFor(string $concept, ?User $student, User $instructor)
    {
        return \App\Models\PuzzleResult::query()
            ->where('concept_tag', $concept)
            ->whereNotNull('wrong_answers')
            ->whereHas('gameSession.user', function ($q) use ($student, $instructor) {
                $student ? $q->where('users.id', $student->id) : $q->where('instructor_id', $instructor->id);
            })->get();
    }

    private function wrongAnswersFor(string $concept, ?User $student, User $instructor): array
    {
        return $this->insights->commonWrongAnswers($this->resultsFor($concept, $student, $instructor), 5);
    }

    /** Names of the misconceptions the new puzzle should help fix (labels only). */
    private function misconceptionsFor(string $concept, ?User $student, User $instructor): array
    {
        return array_column(
            array_slice(app(MisconceptionService::class)->detect($this->resultsFor($concept, $student, $instructor)), 0, 3),
            'label'
        );
    }

    private function example(string $type): ?array
    {
        foreach (Room::orderBy('id')->get() as $room) {
            foreach (($room->config['puzzles'] ?? []) as $p) {
                if (($p['type'] ?? null) === $type) {
                    return collect($p)->only(['type', 'conceptTag', 'prompt', 'data', 'hints', 'answerKey', 'explanation'])->all();
                }
            }
        }

        return null;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You write ONE new puzzle for a VR escape room that teaches children software testing.
You receive: conceptTag, type ("observation" or "assembly"), difficulty ("easier" or "same"),
commonWrongAnswers and misconceptionsToFix (mistakes students made, may be empty) and an example of the exact format.
Write a puzzle on the same concept with a NEW story and NEW numbers (never copy the example).
If difficulty is "easier", use simpler code and clearer wording. Aim to correct the misconceptions and the common mistakes.
Reply with ONLY one JSON object in exactly the example's format, no other text:
- "type" and "conceptTag" exactly as given
- "prompt": a short story and the question (a child reads it)
- "data": for observation: "codeSnippet" = 4 to 8 lines of Python, each {"lineNumber": 1.., "text": ".."},
  exactly ONE line has a bug, and it is a real code line (not a comment or blank).
  for assembly: "options" with "inputs" (3 to 5 numbers) and "expectedOutputs" (2 to 4 strings).
- "hints": exactly 2 strings, the first gentle, the second more direct
- "answerKey": observation {"buggyLineNumber": n}; assembly {"input": x, "expectedOutput": "y"}
  The answer must be one of the options and must be correct for the story.
- "explanation": 1-2 sentences explaining the answer
Keep it kind and suitable for children.
PROMPT;
    }
}
