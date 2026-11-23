<?php

namespace App\Console\Commands;

use App\Services\LearnerModelService;
use Illuminate\Console\Command;

/**
 * Does choosing puzzles with the learner model help? (simulation)
 * Run: php artisan learner:evaluate
 *
 * The same synthetic students practise with three policies:
 *   fixed    - every student gets the concepts in the same fixed order
 *   random   - a random concept each round
 *   adaptive - the concept the learner model thinks is weakest (what the
 *              recommender does), moving on once it looks mastered
 * A student only learns a concept by practising it. We compare how many
 * concepts each student truly knows at the end. Students are identical
 * across policies, so the comparison is paired.
 *
 * Honest limit: this is a simulation. It checks that the mechanism helps if
 * students learn the way we assume. The real evidence is the pilot with
 * real students (see docs/AI_EVALUATION.md).
 */
class EvaluateAdaptive extends Command
{
    protected $signature = 'learner:evaluate {--students=1000} {--concepts=4} {--rounds=12} {--seed=7}';

    protected $description = 'Simulation: compare fixed, random and adaptive puzzle choice';

    public function handle(LearnerModelService $model): int
    {
        $n = (int) $this->option('students');
        $k = (int) $this->option('concepts');
        $rounds = (int) $this->option('rounds');
        $seed = (int) $this->option('seed');
        $policies = ['fixed', 'random', 'adaptive'];

        $known = $wasted = $all = array_fill_keys($policies, []);

        for ($s = 0; $s < $n; $s++) {
            // Hidden truth of this student, identical for all three policies.
            mt_srand($seed * 100003 + $s);
            $start = [];
            for ($c = 0; $c < $k; $c++) {
                $start[$c] = $this->rand() < 0.25;
            }
            $learn = 0.08 + 0.12 * $this->rand();
            $slip = 0.05 + 0.10 * $this->rand();
            $guess = 0.15 + 0.15 * $this->rand();

            foreach ($policies as $pi => $policy) {
                mt_srand($seed * 7919 + $s * 31 + $pi);
                [$knownCount, $wastedRounds] = $this->practise($model, $policy, $start, $learn, $slip, $guess, $rounds);
                $known[$policy][] = $knownCount / $k;
                $wasted[$policy][] = $wastedRounds / $rounds;
                $all[$policy][] = $knownCount === $k ? 1 : 0;
            }
        }

        $rows = [];
        foreach ($policies as $p) {
            $rows[] = [
                $p,
                number_format(array_sum($known[$p]) / $n * 100, 1).'%',
                number_format(array_sum($all[$p]) / $n * 100, 1).'%',
                number_format(array_sum($wasted[$p]) / $n * 100, 1).'%',
            ];
        }

        $this->info("Simulated {$n} students, {$k} concepts, {$rounds} puzzles each (seed {$seed}).");
        $this->table(['Policy', 'Concepts known at end', 'Students who know all', 'Puzzles spent on known concepts'], $rows);

        foreach (['fixed', 'random'] as $base) {
            $diff = array_map(fn ($a, $b) => $a - $b, $known['adaptive'], $known[$base]);
            $mean = array_sum($diff) / $n;
            $var = array_sum(array_map(fn ($d) => ($d - $mean) ** 2, $diff)) / max(1, $n - 1);
            $se = sqrt($var / $n);
            $this->line(sprintf('Adaptive minus %s: %+.1f points of concepts known (95%% CI %+.1f to %+.1f).',
                $base, $mean * 100, ($mean - 1.96 * $se) * 100, ($mean + 1.96 * $se) * 100));
        }

        $this->warn('Simulation only: it shows the mechanism, not real learning. Use the pilot for real evidence.');

        return self::SUCCESS;
    }

    /** @return array{0:int,1:int} concepts known at the end, rounds spent on already-known concepts */
    private function practise(LearnerModelService $model, string $policy, array $known, float $learn, float $slip, float $guess, int $rounds): array
    {
        $k = count($known);
        $p = array_fill(0, $k, LearnerModelService::P_INIT);
        $wasted = 0;

        for ($t = 0; $t < $rounds; $t++) {
            $c = match ($policy) {
                'fixed' => $t % $k,
                'random' => mt_rand(0, $k - 1),
                default => $this->weakest($p),
            };

            if ($known[$c]) {
                $wasted++;
            }

            $correct = $this->rand() < ($known[$c] ? 1 - $slip : $guess);
            $p[$c] = $model->observe($p[$c], $correct);
            if (! $known[$c] && $this->rand() < $learn) {
                $known[$c] = true;
            }
        }

        return [count(array_filter($known)), $wasted];
    }

    /** The concept with the lowest estimated mastery (first one on ties). */
    private function weakest(array $p): int
    {
        $best = 0;
        foreach ($p as $i => $v) {
            if ($v < $p[$best]) {
                $best = $i;
            }
        }

        return $best;
    }

    private function rand(): float
    {
        return mt_rand() / mt_getrandmax();
    }
}
