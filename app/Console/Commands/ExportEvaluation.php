<?php

namespace App\Console\Commands;

use App\Models\GeneratedPuzzle;
use App\Models\PuzzleResult;
use App\Models\Report;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Exports anonymous data for the pilot study and prints the headline numbers.
 * Run: php artisan evaluation:export
 *
 * Student ids are replaced by a short hash, and no names or emails are
 * written, so the files can go into the thesis appendix.
 */
class ExportEvaluation extends Command
{
    protected $signature = 'evaluation:export {--path= : folder for the CSV files (default storage/app/evaluation)}';

    protected $description = 'Export anonymous pilot data (CSV) and print the AI evaluation numbers';

    public function handle(): int
    {
        $dir = $this->option('path') ?: storage_path('app/evaluation');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $results = PuzzleResult::with('gameSession')->orderBy('id')->get();
        $this->csv($dir.'/puzzle_results.csv',
            ['student', 'session', 'puzzle_id', 'concept', 'solved', 'attempts', 'time_seconds', 'hints_used', 'score', 'wrong_answer_count'],
            $results->map(fn ($r) => [
                $this->anon($r->gameSession->user_id), $r->game_session_id, $r->puzzle_id, $r->concept_tag,
                (int) $r->solved, $r->attempts, $r->time_seconds, $r->hints_used, $r->score, count($r->wrong_answers ?? []),
            ])->all());

        // Learning gain: first finished session vs last finished session per student.
        $gains = [];
        $reports = Report::with('gameSession')->orderBy('id')->get()->groupBy(fn ($r) => $r->gameSession->user_id);
        foreach ($reports as $userId => $rs) {
            $gains[] = [$this->anon($userId), $rs->count(), $rs->first()->session_score, $rs->last()->session_score, $rs->last()->session_score - $rs->first()->session_score];
        }
        $this->csv($dir.'/learning_gain.csv', ['student', 'sessions', 'first_score', 'last_score', 'gain'], $gains);

        $this->info('Files written to '.$dir);
        $this->line('  puzzle_results.csv: '.$results->count().' rows');
        $this->line('  learning_gain.csv:  '.count($gains).' students');

        // Headline numbers
        $multi = collect($gains)->where(1, '>', 1);
        $this->newLine();
        $this->info('Headline numbers');
        $this->line('Students: '.User::where('role', 'student')->count().', with at least one finished session: '.count($gains));
        if ($multi->isNotEmpty()) {
            $this->line(sprintf('Average score gain, first to last session (%d students with 2+ sessions): %+.1f points', $multi->count(), $multi->avg(4)));
        } else {
            $this->line('Average score gain: not enough repeat sessions yet.');
        }

        $withWrong = $results->filter(fn ($r) => ! empty($r->wrong_answers))->count();
        $this->line("Results with recorded wrong answers: {$withWrong} of {$results->count()}");

        $approved = GeneratedPuzzle::where('status', 'approved')->count();
        $rejected = GeneratedPuzzle::where('status', 'rejected')->count();
        $pending = GeneratedPuzzle::where('status', 'pending')->count();
        $reviewed = $approved + $rejected;
        $this->line(sprintf('AI puzzles: %d approved, %d rejected, %d pending. Instructor approval rate: %s',
            $approved, $rejected, $pending, $reviewed ? number_format($approved / $reviewed * 100, 0).'%' : 'n/a'));

        return self::SUCCESS;
    }

    private function anon(int|string $id): string
    {
        return substr(hash('sha256', $id.'|'.config('app.key')), 0, 8);
    }

    private function csv(string $path, array $header, array $rows): void
    {
        $f = fopen($path, 'w');
        fputcsv($f, $header);
        foreach ($rows as $row) {
            fputcsv($f, $row);
        }
        fclose($f);
    }
}
