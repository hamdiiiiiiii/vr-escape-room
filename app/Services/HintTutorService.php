<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Gives a student a nudge on one puzzle, based on the mistake they just made.
 *
 * Order of preference:
 *  1. The AI (only if a key is set and the student already gave a wrong answer).
 *     It never receives the answer key, and its reply is thrown away if it
 *     seems to give the answer away.
 *  2. The advice for the detected misconception (fixed rules).
 *  3. The puzzle's own written hint.
 * So a hint always comes back, with or without AI.
 */
class HintTutorService
{
    public function __construct(private MisconceptionService $misconceptions)
    {
    }

    /**
     * @param array<string,mixed> $puzzle a puzzle config (with answerKey)
     * @param array<int,mixed> $wrongAnswers
     * @return array{hint:string, source:string}
     */
    public function hint(array $puzzle, array $wrongAnswers, int $level): array
    {
        $codes = $this->misconceptions->forAnswers($puzzle, $wrongAnswers);

        if ($wrongAnswers !== [] && ! empty(config('services.anthropic.key')) && ! empty(config('services.anthropic.model'))) {
            $text = $this->askAi($puzzle, $wrongAnswers, $codes);
            if ($text !== null) {
                return ['hint' => $text, 'source' => 'ai'];
            }
        }

        if ($codes !== []) {
            return ['hint' => MisconceptionService::CATALOG[$codes[0]][1], 'source' => 'rules'];
        }

        $hints = array_values($puzzle['hints'] ?? []);
        $hint = $hints[min(max($level, 0), max(count($hints) - 1, 0))] ?? 'Read the puzzle again and test the edge values.';

        return ['hint' => $hint, 'source' => 'puzzle'];
    }

    private function askAi(array $puzzle, array $wrongAnswers, array $codes): ?string
    {
        // The answer key and the written hints are deliberately NOT sent.
        $request = [
            'puzzleType' => $puzzle['type'] ?? null,
            'concept' => $puzzle['conceptTag'] ?? null,
            'question' => $puzzle['prompt'] ?? null,
            'puzzleData' => $puzzle['data'] ?? null,
            'studentWrongAnswers' => $wrongAnswers,
            'detectedMistakes' => array_map(fn ($c) => MisconceptionService::CATALOG[$c][0], $codes),
        ];

        try {
            $response = Http::timeout((int) config('services.anthropic.timeout', 10))
                ->withHeaders(['x-api-key' => config('services.anthropic.key'), 'anthropic-version' => '2023-06-01'])
                ->acceptJson()
                ->post(config('services.anthropic.url'), [
                    'model' => config('services.anthropic.model'),
                    'max_tokens' => 200,
                    'system' => $this->systemPrompt(),
                    'messages' => [['role' => 'user', 'content' => json_encode($request)]],
                ]);

            if (! $response->successful()) {
                Log::warning('AI hint failed', ['status' => $response->status()]);

                return null;
            }

            return $this->safe($response->json('content.0.text'), $puzzle);
        } catch (Throwable $e) {
            Log::warning('AI hint error: '.$e->getMessage());

            return null;
        }
    }

    /** Accept the AI text only if it is short plain text that does not give the answer. Public for tests. */
    public function safe(mixed $text, array $puzzle): ?string
    {
        if (! is_string($text)) {
            return null;
        }
        $text = trim($text);
        if (mb_strlen($text) < 10 || mb_strlen($text) > 300 || str_starts_with($text, '{')) {
            return null;
        }

        $key = $puzzle['answerKey'] ?? [];
        if (isset($key['buggyLineNumber']) && preg_match('/\bline\s*#?\s*'.(int) $key['buggyLineNumber'].'\b/i', $text)) {
            return null;
        }
        if (isset($key['input'], $key['expectedOutput'])
            && preg_match('/\b'.preg_quote((string) $key['input'], '/').'\b/', $text)
            && stripos($text, (string) $key['expectedOutput']) !== false) {
            return null;
        }

        return $text;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a kind tutor for a child playing a software testing escape room.
You receive the puzzle (without its answer) and the wrong answers the child already tried.
Write ONE or TWO short, friendly sentences that nudge the child to rethink their mistake.
NEVER state the answer, the line number of the bug, or the exact input and output to pick.
Ask a guiding question or point at what to look at. Plain text only.
PROMPT;
    }
}
