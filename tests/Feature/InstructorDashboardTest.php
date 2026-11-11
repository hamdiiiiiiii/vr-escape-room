<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\InstructorService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\Concerns\CreatesFinishedSessions;
use Tests\TestCase;

class InstructorDashboardTest extends TestCase
{
    use RefreshDatabase, CreatesFinishedSessions;

    /**
     * Class used by most tests: 1 instructor, 5 students,
     * 4 finished sessions (92, 85, 88, 100), 1 in progress, 2 waiting.
     *
     * @return array{0: User, 1: array<int, \App\Models\GameSession>}
     */
    private function seedClass(): array
    {
        $instructor = $this->makeInstructor();

        $students = [];
        foreach (range(1, 5) as $n) {
            $students[$n] = $this->makeStudent($instructor, "Student $n");
        }

        $finished = [
            $this->makeFinishedSession($students[1], 92, ['Boundary Testing' => 94, 'Off-by-One Trap Detection' => 90]),
            $this->makeFinishedSession($students[1], 85, ['Equivalence Partitioning' => 86, 'Invalid Partition Handling' => 84]),
            $this->makeFinishedSession($students[2], 88, ['Equivalence Partitioning' => 90, 'Boundary Input Sorting' => 85]),
            $this->makeFinishedSession($students[4], 100, ['Assertions & Test Suites' => 100]),
        ];

        $inProgress = $this->makeSession($students[1], 'in_progress');
        $waiting = [
            $this->makeSession($students[2], 'waiting'),
            $this->makeSession($students[3], 'waiting'),
        ];

        return [$instructor, compact('finished', 'inProgress', 'waiting')];
    }

    public function test_guest_is_redirected_to_login_from_instructor_dashboard(): void
    {
        $this->get(route('instructor.dashboard'))->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_instructor_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('instructor.dashboard'))->assertStatus(403);
    }

    public function test_instructor_can_access_instructor_dashboard(): void
    {
        $instructor = $this->makeInstructor();

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Instructor Dashboard');
    }

    public function test_instructor_dashboard_renders_overview_metric_counts(): void
    {
        [$instructor] = $this->seedClass();

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Assigned Students');
        $response->assertSee('5 Students');
        $response->assertSee('In Progress');
        $response->assertSee('Waiting to Start');
        $response->assertSee('Completed Sessions');
    }

    public function test_instructor_dashboard_displays_assigned_students_roster(): void
    {
        [$instructor] = $this->seedClass();

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Student 1');
        $response->assertSee('Student 5');

        // Student 5 has no sessions
        $response->assertSee('No sessions recorded');
    }

    public function test_instructor_only_sees_their_own_students(): void
    {
        [$instructor] = $this->seedClass();

        $otherInstructor = $this->makeInstructor();
        $foreign = $this->makeStudent($otherInstructor, 'Foreign Student');
        $this->makeFinishedSession($foreign);

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Foreign Student');
    }

    public function test_instructor_dashboard_displays_student_sessions_with_room_and_status(): void
    {
        [$instructor] = $this->seedClass();

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Student Sessions');
        $response->assertSee('The Boundary Maze');
        $response->assertSee('Boundary Testing');

        $response->assertSee('100 / 100');
        $response->assertSee('92 / 100');
        $response->assertSee('85 / 100');
        $response->assertSee('88 / 100');
    }

    public function test_finished_sessions_have_report_links_while_unfinished_do_not(): void
    {
        [$instructor, $sessions] = $this->seedClass();

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);

        foreach ($sessions['finished'] as $session) {
            $response->assertSee(route('reports.show', $session->id));
        }
        $response->assertSee('View report');

        $response->assertDontSee(route('reports.show', $sessions['inProgress']->id));
        foreach ($sessions['waiting'] as $session) {
            $response->assertDontSee(route('reports.show', $session->id));
        }
    }

    public function test_instructor_dashboard_renders_empty_states_when_no_data(): void
    {
        $instructor = $this->makeInstructor(); // no students, no sessions

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('No students assigned');
        $response->assertSee('No session records');
    }

    public function test_instructor_dashboard_renders_responsive_views_and_filter_controls(): void
    {
        [$instructor] = $this->seedClass();

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('hidden md:block');
        $response->assertSee('block md:hidden');
        $response->assertSee('activeFilter', false);
        $response->assertSee('All (7)');
        $response->assertSee('In Progress (1)');
        $response->assertSee('Waiting (2)');
        $response->assertSee('Finished (4)');
    }

    // ---- Class Performance analytics ----------------------------------

    public function test_instructor_dashboard_renders_class_analytics_section(): void
    {
        [$instructor] = $this->seedClass();

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Class Performance');

        $response->assertSee('Class Average Score');
        $response->assertSee('91');
        $response->assertSee('Advanced');

        $response->assertSee('Lowest-Scoring Concept');
        $response->assertSee('Invalid Partition Handling');
        $response->assertSee('84');

        $response->assertSee('provisional');
    }

    public function test_instructor_dashboard_shows_analytics_unavailable_when_no_finished_sessions(): void
    {
        $instructor = $this->makeInstructor();
        $student = $this->makeStudent($instructor);
        $this->makeSession($student, 'in_progress'); // nothing finished

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Class Performance');
        $response->assertSee('No completed sessions yet');
        $response->assertDontSee('Class Average Score');
        $response->assertDontSee('Lowest-Scoring Concept');
    }

    public function test_get_class_analytics_returns_correct_class_average(): void
    {
        [$instructor] = $this->seedClass();

        $analytics = app(InstructorService::class)->getClassAnalytics($instructor);

        // (92 + 85 + 88 + 100) / 4 = 91.25 -> 91
        $this->assertSame(91, $analytics['class_average']);
        $this->assertSame('Advanced', $analytics['class_average_level']);
        $this->assertSame(4, $analytics['finished_session_count']);
    }

    public function test_get_class_analytics_returns_correct_lowest_scoring_concept(): void
    {
        [$instructor] = $this->seedClass();

        $analytics = app(InstructorService::class)->getClassAnalytics($instructor);

        $this->assertSame('Invalid Partition Handling', $analytics['lowest_concept']);
        $this->assertSame(84, $analytics['lowest_concept_avg']);
        $this->assertSame(1, $analytics['lowest_concept_sessions']);
    }

 public function test_get_class_analytics_tie_broken_alphabetically(): void
{
    $instructor = $this->makeInstructor();
    $student = $this->makeStudent($instructor);
    $this->makeFinishedSession($student, 70, [
        'Zeta Concept' => 70,
        'Alpha Concept' => 70,
    ]);

    $analytics = app(InstructorService::class)->getClassAnalytics($instructor);

    $this->assertSame('Alpha Concept', $analytics['lowest_concept']);
    $this->assertSame(70, $analytics['lowest_concept_avg']);
}


}