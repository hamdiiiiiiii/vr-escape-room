<?php

namespace App\Services;

use App\Models\PuzzleResult;
use App\Models\Room;
use Illuminate\Support\Collection;

/**
 * Turns a wrong answer into a named misconception, using fixed rules.
 * No AI and no API key needed, so it always works and is easy to explain.
 *
 * The rules read the puzzle itself (the code snippet or the options), so they
 * work for any puzzle of the two known types, including approved AI puzzles
 * once their results are stored under the same puzzle id.
 *
 *  observation (find the buggy line): the line the student picked tells us
 *      what they looked at (a comment, a return, the function name, ...).
 *  assembly (pick input + expected output): a far-away input means they test
 *      a typical value, not the edge; the right input with the wrong output
 *      means they mix up "or higher" (inclusive) and "higher" (exclusive).
 */
class MisconceptionService
{
    /** code => [label, advice for the student] */
    public const CATALOG = [
        'reads_comment' => [
            'Reads the comment instead of the code',
            'A comment says what the code should do. The bug is in the code itself, so compare the two.',
        ],
        'looks_at_result' => [
            'Looks at the result, not the decision',
            'The bug is usually in the condition that decides which result happens, not in the result line.',
        ],
        'looks_at_name' => [
            'Looks at the function name',
            'The first line only names the function. Look for the line that makes a decision.',
        ],
        'off_by_one_line' => [
            'Close, but one line off',
            'You were one line away. Trace the code with the edge value to find the exact line.',
        ],
        'unsure_where_decision' => [
            'Unsure which line decides',
            'Find the line with the comparison (if, <, >, =). That line decides what happens.',
        ],
        'tests_typical_value' => [
            'Tests a typical value, not the edge',
            'Bugs hide at the edge where the rule changes. Pick the number exactly on that edge.',
        ],
        'inclusive_exclusive' => [
            'Mixes up "or higher" and "higher"',
            'Knows the edge, but re-read the rule: "or higher" includes the number itself.',
        ],
    ];

    /** @var array<string, array<string,mixed>>|null puzzle_id => puzzle config */
    private ?array $puzzles = null;

    /**
     * Misconceptions behind the wrong answers in these results.
     *
     * @param Collection<int, PuzzleResult> $results
     * @return array<int, array{code:string,label:string,advice:string,concept:string,count:int}>
     */
    public function detect(Collection $results): array
    {
        $found = [];

        foreach ($results as $r) {
            foreach ($this->forResult($r) as $code) {
                $key = $code.'|'.$r->concept_tag;
                $found[$key] ??= [
                    'code' => $code,
                    'label' => self::CATALOG[$code][0],
                    'advice' => self::CATALOG[$code][1],
                    'concept' => (string) $r->concept_tag,
                    'count' => 0,
                ];
                $found[$key]['count']++;
            }
        }

        $list = array_values($found);
        usort($list, fn ($a, $b) => [$b['count'], $a['code']] <=> [$a['count'], $b['code']]);

        return $list;
    }

    /**
     * Misconception codes for one result (one per wrong answer, no duplicates
     * within the same result).
     *
     * @return array<int, string>
     */
    public function forResult(PuzzleResult $result): array
    {
        $puzzle = $this->puzzle((string) $result->puzzle_id);

        return $puzzle === null ? [] : $this->forAnswers($puzzle, $result->wrong_answers ?? []);
    }

    /**
     * Misconception codes for a list of wrong answers on a given puzzle config.
     *
     * @param array<string,mixed> $puzzle
     * @param array<int,mixed> $answers
     * @return array<int, string>
     */
    public function forAnswers(array $puzzle, array $answers): array
    {
        $codes = [];
        foreach ($answers as $answer) {
            $code = match ($puzzle['type'] ?? null) {
                'observation' => $this->observation($puzzle, $answer),
                'assembly' => $this->assembly($puzzle, $answer),
                default => null,
            };
            if ($code !== null) {
                $codes[$code] = true;
            }
        }

        return array_keys($codes);
    }

    /** Short labels for one result, for the report page. */
    public function labelsFor(PuzzleResult $result): array
    {
        return array_map(fn ($c) => self::CATALOG[$c][0], $this->forResult($result));
    }

    private function observation(array $puzzle, mixed $answer): ?string
    {
        $lines = $puzzle['data']['codeSnippet'] ?? [];
        $picked = $this->lineNumber($answer);
        $buggy = $puzzle['answerKey']['buggyLineNumber'] ?? null;

        if ($picked === null || $picked === $buggy) {
            return null;
        }

        $text = null;
        foreach ($lines as $l) {
            if (($l['lineNumber'] ?? null) === $picked) {
                $text = trim((string) ($l['text'] ?? ''));
            }
        }
        if ($text === null) {
            return 'unsure_where_decision';
        }

        return match (true) {
            str_starts_with($text, '#') => 'reads_comment',
            str_starts_with($text, 'def ') => 'looks_at_name',
            str_starts_with($text, 'return') => 'looks_at_result',
            $buggy !== null && abs($picked - $buggy) === 1 => 'off_by_one_line',
            default => 'unsure_where_decision',
        };
    }

    private function assembly(array $puzzle, mixed $answer): ?string
    {
        $inputs = $puzzle['data']['options']['inputs'] ?? [];
        $outputs = $puzzle['data']['options']['expectedOutputs'] ?? [];
        $key = $puzzle['answerKey'] ?? [];

        $input = $output = null;
        if (is_array($answer)) {
            $input = $answer['input'] ?? null;
            $output = $answer['expectedOutput'] ?? null;
        } elseif ($this->has($inputs, $answer)) {
            $input = $answer;
        } elseif ($this->has($outputs, $answer)) {
            $output = $answer;
        }

        if ($input !== null && ! $this->same($input, $key['input'] ?? null)) {
            return 'tests_typical_value';
        }
        if ($output !== null && ! $this->same($output, $key['expectedOutput'] ?? null)) {
            return 'inclusive_exclusive';
        }

        return null;
    }

    /** The line number in "4", 4, "line 4" or {"line": 4}. */
    private function lineNumber(mixed $answer): ?int
    {
        if (is_array($answer)) {
            $answer = $answer['lineNumber'] ?? $answer['line'] ?? null;
        }
        if (is_int($answer)) {
            return $answer;
        }
        if (is_string($answer) && preg_match('/\d+/', $answer, $m)) {
            return (int) $m[0];
        }

        return null;
    }

    private function same(mixed $a, mixed $b): bool
    {
        return is_scalar($a) && is_scalar($b) && strtolower(trim((string) $a)) === strtolower(trim((string) $b));
    }

    private function has(array $options, mixed $value): bool
    {
        foreach ($options as $o) {
            if ($this->same($o, $value)) {
                return true;
            }
        }

        return false;
    }

    private function puzzle(string $id): ?array
    {
        if ($this->puzzles === null) {
            $this->puzzles = [];
            foreach (Room::all() as $room) {
                foreach (($room->config['puzzles'] ?? []) as $p) {
                    if (isset($p['id'])) {
                        $this->puzzles[$p['id']] = $p;
                    }
                }
            }
        }

        return $this->puzzles[$id] ?? null;
    }
}
