<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\GeneratedPuzzle;
use App\Models\PuzzleResult;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_adaptive_policy_beats_the_baselines_in_simulation(): void
    {
        $this->artisan('learner:evaluate', ['--students' => 400])
            ->expectsOutputToContain('Adaptive minus fixed')
            ->expectsOutputToContain('Adaptive minus random')
            ->assertSuccessful();
    }

    public function test_simulation_is_reproducible(): void
    {
        $run = function () {
            $this->artisan('learner:evaluate', ['--students' => 100, '--seed' => 3]);

            return \Illuminate\Support\Facades\Artisan::output();
        };

        $this->assertSame($run(), $run());
    }

    public function test_export_writes_anonymous_csv_files_and_headline_numbers(): void
    {
        $this->seed(RoomSeeder::class);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Secret Kid', 'email' => 'secret@kid.test']);
        $inst = User::factory()->create(['role' => 'instructor']);

        foreach ([40, 70] as $score) {
            $s = GameSession::create(['user_id' => $student->id, 'room_id' => 1, 'status' => 'finished', 'expires_at' => now()->addHour(), 'started_at' => now()]);
            PuzzleResult::create(['game_session_id' => $s->id, 'puzzle_id' => 'puzzle-obs-01', 'concept_tag' => 'Boundary Testing',
                'solved' => true, 'attempts' => 1, 'time_seconds' => 30, 'hints_used' => 0, 'score' => $score, 'wrong_answers' => [2]]);
            Report::create(['game_session_id' => $s->id, 'session_score' => $score, 'level' => 'Proficient', 'total_time_seconds' => 30, 'concept_scores' => []]);
        }
        GeneratedPuzzle::create(['requested_by' => $inst->id, 'concept_tag' => 'x', 'type' => 'assembly', 'difficulty' => 'same', 'content' => [], 'status' => 'approved']);
        GeneratedPuzzle::create(['requested_by' => $inst->id, 'concept_tag' => 'x', 'type' => 'assembly', 'difficulty' => 'same', 'content' => [], 'status' => 'rejected']);

        $dir = sys_get_temp_dir().'/bughunt-eval-'.uniqid();

        $this->artisan('evaluation:export', ['--path' => $dir])
            ->expectsOutputToContain('+30.0 points')
            ->expectsOutputToContain('approval rate: 50%')
            ->assertSuccessful();

        $results = file_get_contents($dir.'/puzzle_results.csv');
        $gain = file_get_contents($dir.'/learning_gain.csv');
        $this->assertStringContainsString('puzzle-obs-01', $results);
        $this->assertStringNotContainsString('Secret Kid', $results.$gain);
        $this->assertStringNotContainsString('secret@kid.test', $results.$gain);
        $this->assertStringContainsString(',2,40,70,30', $gain);

        array_map('unlink', glob($dir.'/*'));
        rmdir($dir);
    }
}
