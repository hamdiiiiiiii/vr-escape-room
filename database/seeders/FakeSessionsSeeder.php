<?php

namespace Database\Seeders;

use App\Models\GameSession;
use App\Models\PuzzleResult;
use App\Models\Report;
use App\Models\Room;
use App\Models\User;
use App\Services\ScoringService;
use Illuminate\Database\Seeder;

class FakeSessionsSeeder extends Seeder
{
    public function run(): void
    {
        // Do not duplicate fake data when run twice
        if (GameSession::where('status', 'finished')->exists()) {
            return;
        }

        $room = Room::first();
        if (! $room) {
            return;
        }

        $scoring = new ScoringService();

        // [puzzleId, concept, solved, attempts, seconds, hints] per puzzle
        $scenarios = [
            'escaped_good' => ['escaped', [
                ['puzzle-obs-01', 'Boundary Testing', true, 1, 60, 0],
                ['puzzle-asm-02', 'Boundary Testing', true, 2, 90, 0],
            ]],
            'escaped_helped' => ['escaped', [
                ['puzzle-obs-01', 'Boundary Testing', true, 3, 150, 1],
                ['puzzle-asm-02', 'Boundary Testing', true, 1, 80, 0],
            ]],
            'timed_out' => ['timed_out', [
                ['puzzle-obs-01', 'Boundary Testing', true, 2, 200, 0],
                ['puzzle-asm-02', 'Boundary Testing', false, 3, 400, 2],
            ]],
        ];
        $keys = array_keys($scenarios);

        $students = User::where('role', 'student')->orderBy('id')->get();

        foreach ($students as $i => $student) {
            foreach ([0, 1] as $n) {
                [$endReason, $rows] = $scenarios[$keys[($i + $n) % count($keys)]];

                $started = now()->subDays(($i * 2) + $n + 1);
                $totalTime = array_sum(array_column($rows, 4));

                $session = GameSession::create([
                    'user_id' => $student->id,
                    'room_id' => $room->id,
                    'code' => null,
                    'status' => 'finished',
                    'end_reason' => $endReason,
                    'expires_at' => $started->copy()->addMinutes(10),
                    'started_at' => $started,
                    'ended_at' => $started->copy()->addSeconds($totalTime),
                ]);

                $scored = [];
                foreach ($rows as [$pid, $tag, $solved, $attempts, $secs, $hints]) {
                    $score = $scoring->puzzleScore($solved, $attempts, $hints);
                    PuzzleResult::create([
                        'game_session_id' => $session->id,
                        'puzzle_id' => $pid,
                        'concept_tag' => $tag,
                        'solved' => $solved,
                        'attempts' => $attempts,
                        'time_seconds' => $secs,
                        'hints_used' => $hints,
                        'score' => $score,
                    ]);
                    $scored[] = ['concept_tag' => $tag, 'score' => $score];
                }

                $summary = $scoring->summarize($scored);
                Report::create([
                    'game_session_id' => $session->id,
                    'total_time_seconds' => $totalTime,
                    'session_score' => $summary['session_score'],
                    'level' => $summary['level'],
                    'concept_scores' => $summary['concept_scores'],
                    'ai_analysis' => null,
                ]);
            }
        }
    }
}
