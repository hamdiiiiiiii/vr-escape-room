<?php

namespace App\Services;

use App\Models\PuzzleResult;
use App\Models\User;

/**
 * Learner model: estimates how well a student has mastered each concept.
 *
 * Method: Bayesian Knowledge Tracing (BKT). For every concept we keep one
 * number, P(L) = the probability that the student knows the concept. After
 * each puzzle we update it with Bayes' rule, then allow for learning.
 *
 * The four BKT parameters:
 *   P_INIT     P(L) before the first puzzle (prior knowledge)
 *   P_TRANSIT  chance of learning the concept after each puzzle
 *   P_SLIP     chance a student who knows it still gets the puzzle wrong
 *   P_GUESS    chance a student who does not know it still gets it right
 *
 * Nothing is stored: mastery is recomputed from puzzle_results each time,
 * so there is no migration and it always matches the data.
 */
class LearnerModelService
{
    public const P_INIT = 0.30;
    public const P_TRANSIT = 0.15;
    public const P_SLIP = 0.10;
    public const P_GUESS = 0.20;

    /** A puzzle counts as "correct" when its score is at least this. */
    public const CORRECT_AT = 70;

    /** Mastery at or above this is "mastered", below WEAK_BELOW is "weak". */
    public const MASTERED_AT = 0.85;
    public const WEAK_BELOW = 0.60;

    /** The recommender aims for puzzles the student solves about this often. */
    public const TARGET_SUCCESS = 0.70;

    public function __construct(private InsightsService $insights)
    {
    }

    // ------------------------------------------------------------
    // The model itself (pure maths, no database)
    // ------------------------------------------------------------

    /** Update P(L) after seeing one puzzle outcome. */
    public function observe(float $pL, bool $correct): float
    {
        if ($correct) {
            $posterior = $pL * (1 - self::P_SLIP)
                / ($pL * (1 - self::P_SLIP) + (1 - $pL) * self::P_GUESS);
        } else {
            $posterior = $pL * self::P_SLIP
                / ($pL * self::P_SLIP + (1 - $pL) * (1 - self::P_GUESS));
        }

        // The student may also have learned something from the puzzle itself.
        return $posterior + (1 - $posterior) * self::P_TRANSIT;
    }

    /** Probability the student solves the next puzzle of this concept. */
    public function predictSuccess(float $pL): float
    {
        return $pL * (1 - self::P_SLIP) + (1 - $pL) * self::P_GUESS;
    }

    public function status(float $pL): string
    {
        return match (true) {
            $pL >= self::MASTERED_AT => 'mastered',
            $pL < self::WEAK_BELOW => 'weak',
            default => 'learning',
        };
    }

    // ------------------------------------------------------------
    // From a student's history
    // ------------------------------------------------------------

    /**
     * Mastery per concept, weakest first.
     *
     * @return array<int, array{concept:string,mastery:float,percent:int,puzzles_seen:int,predicted_success:float,status:string}>
     */
    public function masteryFor(User $student): array
    {
        $results = PuzzleResult::whereHas('gameSession', fn ($q) => $q->where('user_id', $student->id))
            ->orderBy('id')
            ->get();

        $state = [];
        foreach ($results as $r) {
            $tag = (string) $r->concept_tag;
            $state[$tag] ??= ['p' => self::P_INIT, 'n' => 0];
            $state[$tag]['p'] = $this->observe($state[$tag]['p'], (int) $r->score >= self::CORRECT_AT);
            $state[$tag]['n']++;
        }

        $out = [];
        foreach ($state as $tag => $s) {
            $out[] = [
                'concept' => $tag,
                'mastery' => round($s['p'], 3),
                'percent' => (int) round($s['p'] * 100),
                'puzzles_seen' => $s['n'],
                'predicted_success' => round($this->predictSuccess($s['p']), 3),
                'status' => $this->status($s['p']),
            ];
        }
        usort($out, fn ($a, $b) => $a['mastery'] <=> $b['mastery']);

        return $out;
    }

    // ------------------------------------------------------------
    // Recommender
    // ------------------------------------------------------------

    /**
     * What should this student do next?
     *
     * - next_puzzles: the puzzles whose predicted success is closest to the
     *   target (about 70%), so they are neither too easy nor too hard.
     *   Concepts the student has not met yet start from the prior.
     * - needs_new_content: weak concepts, with the difficulty the future
     *   AI generator should aim for. This is the generator's input.
     *
     * @return array{next_puzzles: array<int, array<string,mixed>>, needs_new_content: array<int, array<string,mixed>>}
     */
    public function recommend(User $student, int $limit = 3): array
    {
        $mastery = collect($this->masteryFor($student))->keyBy('concept');

        $candidates = [];
        foreach ($this->insights->puzzleIndex() as $puzzleId => $meta) {
            $concept = (string) ($meta['concept'] ?? '');
            $pL = $mastery[$concept]['mastery'] ?? self::P_INIT;
            $success = $this->predictSuccess($pL);

            $candidates[] = [
                'puzzle_id' => $puzzleId,
                'title' => $meta['title'],
                'type' => $meta['type'],
                'concept' => $concept,
                'room' => $meta['room'],
                'predicted_success' => round($success, 2),
                'reason' => $this->reason($pL, $success, isset($mastery[$concept])),
                // Lower is better: distance from the target success rate.
                '_distance' => abs($success - self::TARGET_SUCCESS),
            ];
        }

        usort($candidates, fn ($a, $b) => [$a['_distance'], $a['puzzle_id']] <=> [$b['_distance'], $b['puzzle_id']]);
        $next = array_map(function ($c) {
            unset($c['_distance']);

            return $c;
        }, array_slice($candidates, 0, $limit));

        $needs = [];
        foreach ($mastery as $m) {
            if ($m['status'] === 'weak') {
                $needs[] = [
                    'concept' => $m['concept'],
                    'mastery' => $m['mastery'],
                    // Very low mastery: start easier. Otherwise same level.
                    'target_difficulty' => $m['mastery'] < 0.35 ? 'easier' : 'same',
                    'puzzles_seen' => $m['puzzles_seen'],
                ];
            }
        }

        return ['next_puzzles' => $next, 'needs_new_content' => $needs];
    }

    private function reason(float $pL, float $success, bool $seen): string
    {
        if (! $seen) {
            return 'New concept for this student.';
        }

        return match (true) {
            $pL < self::WEAK_BELOW => 'Weak concept: more practice here helps most.',
            $pL >= self::MASTERED_AT => 'Already strong; a refresher.',
            default => 'Still learning this concept: good practice level.',
        };
    }
}
