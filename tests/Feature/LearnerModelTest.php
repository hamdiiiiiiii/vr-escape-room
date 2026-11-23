<?php

namespace Tests\Feature;

use App\Models\PuzzleResult;
use App\Services\LearnerModelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFinishedSessions;
use Tests\TestCase;

class LearnerModelTest extends TestCase
{
    use RefreshDatabase, CreatesFinishedSessions;

    private function model(): LearnerModelService
    {
        return app(LearnerModelService::class);
    }

    public function test_correct_answer_raises_mastery_and_wrong_answer_lowers_it(): void
    {
        $p = LearnerModelService::P_INIT;

        $this->assertGreaterThan($p, $this->model()->observe($p, true));
        $this->assertLessThan($p, $this->model()->observe($p, false));
    }

    public function test_mastery_stays_between_zero_and_one(): void
    {
        $p = 0.5;
        for ($i = 0; $i < 200; $i++) {
            $p = $this->model()->observe($p, $i % 2 === 0);
            $this->assertGreaterThan(0, $p);
            $this->assertLessThan(1, $p);
        }
    }

    public function test_many_correct_answers_reach_mastered(): void
    {
        $p = LearnerModelService::P_INIT;
        for ($i = 0; $i < 6; $i++) {
            $p = $this->model()->observe($p, true);
        }

        $this->assertSame('mastered', $this->model()->status($p));
    }

    public function test_predicted_success_is_between_guess_and_one_minus_slip(): void
    {
        $this->assertEqualsWithDelta(LearnerModelService::P_GUESS, $this->model()->predictSuccess(0.0), 1e-9);
        $this->assertEqualsWithDelta(1 - LearnerModelService::P_SLIP, $this->model()->predictSuccess(1.0), 1e-9);
    }

    public function test_student_without_history_has_no_mastery_rows(): void
    {
        $this->assertSame([], $this->model()->masteryFor($this->makeStudent()));
    }

    public function test_mastery_is_built_from_puzzle_history(): void
    {
        $student = $this->makeStudent();
        $this->makeFinishedSession($student, 92); // 3 puzzles, all score 92 (correct)

        $rows = $this->model()->masteryFor($student);

        $this->assertCount(1, $rows);
        $this->assertSame('Boundary Testing', $rows[0]['concept']);
        $this->assertSame(3, $rows[0]['puzzles_seen']);
        $this->assertGreaterThan(0.8, $rows[0]['mastery']);
    }

    public function test_failing_a_concept_makes_it_weak_and_flags_new_content(): void
    {
        $student = $this->makeStudent();
        $session = $this->makeFinishedSession($student, 92);
        PuzzleResult::where('game_session_id', $session->id)->update(['score' => 0, 'solved' => false]);

        $rows = $this->model()->masteryFor($student);
        $this->assertSame('weak', $rows[0]['status']);

        $needs = $this->model()->recommend($student)['needs_new_content'];
        $this->assertSame('Boundary Testing', $needs[0]['concept']);
        $this->assertSame('easier', $needs[0]['target_difficulty']);
    }

    public function test_recommender_picks_puzzles_closest_to_target_success(): void
    {
        $this->makeRoom(); // puzzles p1..p3, all the same concept
        $student = $this->makeStudent();

        $rec = $this->model()->recommend($student, 2);

        $this->assertCount(2, $rec['next_puzzles']);
        $this->assertSame([], $rec['needs_new_content']);
        $this->assertArrayHasKey('reason', $rec['next_puzzles'][0]);
    }

    public function test_api_payload_includes_mastery_and_recommendations(): void
    {
        $student = $this->makeStudent();
        $this->makeFinishedSession($student, 92);

        $this->actingAs($student)->getJson(route('api.students.weak-points', $student->id))
            ->assertOk()
            ->assertJsonStructure(['mastery', 'recommendations' => ['next_puzzles', 'needs_new_content']]);
    }

    public function test_dashboard_shows_mastery_and_recommendations(): void
    {
        $student = $this->makeStudent();
        $this->makeFinishedSession($student, 92);

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Skill mastery')
            ->assertSee('Recommended next');
    }

    public function test_simulation_command_runs_and_model_beats_baseline(): void
    {
        $this->artisan('learner:simulate', ['--students' => 200, '--rounds' => 8])
            ->expectsOutputToContain('model better')
            ->assertSuccessful();
    }
}
