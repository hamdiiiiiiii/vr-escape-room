<?php

namespace App\Services;

class ScoringService
{
    public function puzzleScore(bool $solved, int $attempts, int $hintsUsed): int
    {
        if (! $solved) {
            return 0;
        }

        $base = match (true) {
            $attempts <= 1 => 100,
            $attempts === 2 => 70,
            default => 40,
        };

        return (int) round($hintsUsed > 0 ? $base * 0.5 : $base);
    }

    public function level(int $score): string
    {
        return match (true) {
            $score >= 80 => 'Advanced',
            $score >= 50 => 'Proficient',
            default => 'Needs support',
        };
    }

    /**
     * @param array $puzzles each item needs 'concept_tag' and 'score'
     */
    public function summarize(array $puzzles): array
    {
        $count = count($puzzles);
        $sessionScore = $count ? (int) round(array_sum(array_column($puzzles, 'score')) / $count) : 0;

        $concepts = [];
        foreach ($puzzles as $p) {
            $concepts[$p['concept_tag']][] = $p['score'];
        }
        $conceptScores = [];
        foreach ($concepts as $tag => $scores) {
            $conceptScores[$tag] = (int) round(array_sum($scores) / count($scores));
        }

        return [
            'session_score' => $sessionScore,
            'level' => $this->level($sessionScore),
            'concept_scores' => $conceptScores,
        ];
    }
}