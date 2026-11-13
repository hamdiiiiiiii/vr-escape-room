<?php

namespace App\Console\Commands;

use App\Services\LearnerModelService;
use Illuminate\Console\Command;

/**
 * Simulates synthetic students to check that the learner model works.
 * Run: php artisan learner:simulate
 *
 * Each fake student has a hidden "knows the concept" state that can flip to
 * true while practising. Their puzzle outcomes are generated from hidden
 * parameters that differ from the model's, so the model is not tested on
 * its own assumptions. We compare the model's predictions with a simple
 * baseline (the student's average so far).
 */
class SimulateLearners extends Command
{
    protected $signature = 'learner:simulate {--students=500} {--rounds=12} {--seed=42}';

    protected $description = 'Simulate synthetic students and evaluate the learner model';

    public function handle(LearnerModelService $model): int
    {
        $students = (int) $this->option('students');
        $rounds = (int) $this->option('rounds');
        mt_srand((int) $this->option('seed'));

        $brierModel = $brierBase = array_fill(0, $rounds, 0.0);
        $hitModel = $hitBase = array_fill(0, $rounds, 0);
        $massKnow = array_fill(0, $rounds, 0.0);
        $massNot = array_fill(0, $rounds, 0.0);
        $nKnow = array_fill(0, $rounds, 0);
        $nNot = array_fill(0, $rounds, 0);

        for ($s = 0; $s < $students; $s++) {
            // Hidden truth (differs from the model's own parameters).
            $knows = $this->rand() < 0.25;
            $learn = 0.08 + 0.12 * $this->rand();
            $slip = 0.05 + 0.10 * $this->rand();
            $guess = 0.15 + 0.15 * $this->rand();

            $p = LearnerModelService::P_INIT;
            $wins = 0;
            for ($t = 0; $t < $rounds; $t++) {
                $predModel = $model->predictSuccess($p);
                $predBase = $t === 0 ? 0.5 : $wins / $t;

                $correct = $this->rand() < ($knows ? 1 - $slip : $guess);

                $brierModel[$t] += ($predModel - (int) $correct) ** 2;
                $brierBase[$t] += ($predBase - (int) $correct) ** 2;
                $hitModel[$t] += (($predModel >= 0.5) === $correct) ? 1 : 0;
                $hitBase[$t] += (($predBase >= 0.5) === $correct) ? 1 : 0;

                $p = $model->observe($p, $correct);
                $wins += (int) $correct;
                if (! $knows && $this->rand() < $learn) {
                    $knows = true;
                }

                if ($knows) {
                    $massKnow[$t] += $p;
                    $nKnow[$t]++;
                } else {
                    $massNot[$t] += $p;
                    $nNot[$t]++;
                }
            }
        }

        $rows = [];
        for ($t = 0; $t < $rounds; $t++) {
            $rows[] = [
                $t + 1,
                number_format($brierModel[$t] / $students, 3),
                number_format($brierBase[$t] / $students, 3),
                number_format($hitModel[$t] / $students * 100, 1).'%',
                number_format($hitBase[$t] / $students * 100, 1).'%',
                $nKnow[$t] ? number_format($massKnow[$t] / $nKnow[$t], 2) : '-',
                $nNot[$t] ? number_format($massNot[$t] / $nNot[$t], 2) : '-',
            ];
        }

        $this->info("Simulated {$students} students, {$rounds} puzzles each (seed ".$this->option('seed').').');
        $this->table(
            ['Puzzle #', 'Brier model', 'Brier baseline', 'Accuracy model', 'Accuracy baseline', 'Mastery (knows)', 'Mastery (does not)'],
            $rows
        );
        $this->line('Brier score: lower is better (0 = perfect). Baseline = the student\'s average so far.');

        $avgModel = array_sum($brierModel) / ($students * $rounds);
        $avgBase = array_sum($brierBase) / ($students * $rounds);
        $this->info(sprintf('Overall Brier: model %.3f vs baseline %.3f (%s).', $avgModel, $avgBase, $avgModel < $avgBase ? 'model better' : 'baseline better'));

        return self::SUCCESS;
    }

    private function rand(): float
    {
        return mt_rand() / mt_getrandmax();
    }
}
