<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\GeneratedPuzzle;

/**
 * The server checks the player's answer against the puzzle's answer key,
 * so a modified game cannot simply say "solved: true".
 */
class AnswerCheckService
{
    /** Finds a puzzle by id in the room, or among approved generated puzzles. */
    public function findPuzzle(GameSession $session, string $puzzleId): ?array
    {
        $puzzle = collect($session->room?->config['puzzles'] ?? [])->firstWhere('id', $puzzleId);

        if ($puzzle === null) {
            $generated = GeneratedPuzzle::where('status', GeneratedPuzzle::APPROVED)->get()
                ->first(fn ($g) => ($g->content['id'] ?? null) === $puzzleId);
            $puzzle = $generated?->content;
        }

        return is_array($puzzle) ? $puzzle : null;
    }

    public function hasKey(?array $puzzle): bool
    {
        return is_array($puzzle) && ! empty($puzzle['answerKey']) && is_array($puzzle['answerKey']);
    }

    /**
     * observation: answer is the line number (7) or {"buggyLineNumber": 7}
     * assembly:    answer is {"input": 10, "expectedOutput": "UNLOCK"}
     */
    public function isCorrect(array $puzzle, mixed $answer): bool
    {
        $key = $puzzle['answerKey'] ?? [];

        if (array_key_exists('buggyLineNumber', $key)) {
            $line = is_array($answer) ? ($answer['buggyLineNumber'] ?? null) : $answer;

            return $this->same($line, $key['buggyLineNumber']);
        }

        if (array_key_exists('input', $key) && array_key_exists('expectedOutput', $key)) {
            return is_array($answer)
                && $this->same($answer['input'] ?? null, $key['input'])
                && $this->same($answer['expectedOutput'] ?? null, $key['expectedOutput']);
        }

        return false;
    }

    private function same(mixed $given, mixed $expected): bool
    {
        if (! is_scalar($given) || ! is_scalar($expected)) {
            return false;
        }

        if (is_numeric($given) && is_numeric($expected)) {
            return (float) $given === (float) $expected;
        }

        return mb_strtolower(trim((string) $given)) === mb_strtolower(trim((string) $expected));
    }
}
