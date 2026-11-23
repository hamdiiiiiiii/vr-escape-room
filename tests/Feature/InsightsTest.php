<?php

namespace Tests\Feature;

use App\Models\PuzzleResult;
use App\Services\InsightsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFinishedSessions;
use Tests\TestCase;

class InsightsTest extends TestCase
{
    use RefreshDatabase, CreatesFinishedSessions;

    public function test_new_student_is_rookie_with_no_weak_points(): void
    {
        $profile = app(InsightsService::class)->studentProfile($this->makeStudent());

        $this->assertSame('Rookie', $profile['rank']['name']);
        $this->assertSame(0, $profile['xp']);
        $this->assertSame([], $profile['weak_points']);
        $this->assertFalse(collect($profile['badges'])->contains('earned', true));
    }

    public function test_xp_and_rank_come_from_finished_sessions(): void
    {
        $student = $this->makeStudent();
        $this->makeFinishedSession($student, 92); // 92 + 25 * 2 clean solves = 142 XP

        $profile = app(InsightsService::class)->studentProfile($student);

        $this->assertSame(142, $profile['xp']);
        $this->assertSame('Bug Spotter', $profile['rank']['name']);
        $this->assertSame('Tester', $profile['rank']['next_name']);
    }

    public function test_badges_are_earned_from_data(): void
    {
        $student = $this->makeStudent();
        $this->makeFinishedSession($student, 92);

        $badges = collect(app(InsightsService::class)->studentProfile($student)['badges'])->keyBy('key');

        $this->assertTrue($badges['first_escape']['earned']);
        $this->assertTrue($badges['no_hints']['earned']);
        $this->assertFalse($badges['flawless']['earned']);   // one wrong try + a hint
        $this->assertTrue($badges['speed']['earned'] === false); // 705s > half of 900s
        $this->assertFalse($badges['persistent']['earned']);
    }

    public function test_low_scoring_concept_becomes_a_weak_point(): void
    {
        $student = $this->makeStudent();
        $session = $this->makeFinishedSession($student, 92);
        PuzzleResult::where('game_session_id', $session->id)->where('puzzle_id', 'p3')
            ->update(['score' => 20, 'concept_tag' => 'Equivalence Partitioning', 'solved' => false]);

        $profile = app(InsightsService::class)->studentProfile($student);

        $weak = collect($profile['concepts'])->firstWhere('is_weak', true);
        $this->assertSame('Equivalence Partitioning', $weak['concept']);
        $this->assertSame('p3', $profile['weak_points'][0]['puzzle_id']);
    }

    public function test_student_dashboard_shows_rank_badges_and_room_status(): void
    {
        $student = $this->makeStudent();
        $this->makeFinishedSession($student, 92);

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Bug Spotter')
            ->assertSee('First Escape')
            ->assertSee('Your weak spots')
            ->assertSee('ESCAPED');
    }

    public function test_instructor_dashboard_shows_where_class_gets_stuck(): void
    {
        $instructor = $this->makeInstructor();
        $student = $this->makeStudent($instructor);
        $this->makeFinishedSession($student, 90);

        $this->actingAs($instructor)->get(route('instructor.dashboard'))
            ->assertOk()
            ->assertSee('Where the class gets stuck')
            ->assertSee('Bug Spotter');
    }

    public function test_class_stuck_ignores_unassigned_students(): void
    {
        $instructor = $this->makeInstructor();
        $this->makeFinishedSession($this->makeStudent(), 90); // not this instructor's student

        $this->assertSame([], app(InsightsService::class)->classStuck($instructor));
    }

    public function test_report_shows_puzzle_log(): void
    {
        $student = $this->makeStudent();
        $session = $this->makeFinishedSession($student, 92);

        $this->actingAs($student)->get(route('reports.show', $session->id))
            ->assertOk()
            ->assertSee('Puzzle log')
            ->assertSee('PASS');
    }

    public function test_content_pack_is_instructor_only(): void
    {
        $this->makeRoom();

        $this->get(route('instructor.content-pack'))->assertRedirect(route('login'));
        $this->actingAs($this->makeStudent())->get(route('instructor.content-pack'))->assertStatus(403);
        $this->actingAs($this->makeInstructor())->get(route('instructor.content-pack'))
            ->assertOk()
            ->assertSee('Content Pack')
            ->assertSee('The Boundary Maze')
            ->assertSee('+ Add puzzle');
    }

    public function test_weak_points_api_permissions_and_shape(): void
    {
        $instructor = $this->makeInstructor();
        $student = $this->makeStudent($instructor);
        $this->makeFinishedSession($student, 92);

        $this->getJson(route('api.students.weak-points', $student->id))->assertStatus(401);
        $this->actingAs($this->makeStudent())->getJson(route('api.students.weak-points', $student->id))->assertStatus(403);
        $this->actingAs($this->makeInstructor())->getJson(route('api.students.weak-points', $student->id))->assertStatus(403);

        $this->actingAs($student)->getJson(route('api.students.weak-points', $student->id))
            ->assertOk()->assertJsonStructure(['student_id', 'rank', 'xp', 'weak_threshold', 'concepts', 'weak_points']);
        $this->actingAs($instructor)->getJson(route('api.students.weak-points', $student->id))->assertOk();
    }
}
