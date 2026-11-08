<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\InstructorService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class InstructorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_instructor_dashboard(): void
    {
        $response = $this->get(route('instructor.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_instructor_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get(route('instructor.dashboard'));

        $response->assertStatus(403);
    }

    public function test_instructor_can_access_instructor_dashboard(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Instructor Dashboard');
        $response->assertSee('Review assigned students, track escape room challenge sessions, and inspect student test reports.');
    }

    public function test_instructor_dashboard_renders_overview_metric_counts(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

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
        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Assigned Students');
        $response->assertSee('Student 1');
        $response->assertSee('student1@test.com');
        $response->assertSee('Student 5');
        $response->assertSee('student5@test.com');

        // Student 5 has 0 sessions
        $response->assertSee('No sessions recorded');
    }

    public function test_instructor_dashboard_displays_student_sessions_with_room_and_status(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Student Sessions');
        $response->assertSee('The Assertion Vault');
        $response->assertSee('The Boundary Maze');
        $response->assertSee('Equivalence Chamber');
        $response->assertSee('Boundary Testing');
        $response->assertSee('Equivalence Partitioning');
        $response->assertSee('Assertions & Test Suites');

        // Check scores
        $response->assertSee('100 / 100');
        $response->assertSee('92 / 100');
        $response->assertSee('85 / 100');
        $response->assertSee('88 / 100');
    }

    public function test_finished_sessions_have_report_links_while_unfinished_do_not(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);

        // Finished sessions: 101, 102, 201, 401 have links to reports
        $response->assertSee(route('reports.show', 101));
        $response->assertSee(route('reports.show', 102));
        $response->assertSee(route('reports.show', 201));
        $response->assertSee(route('reports.show', 401));
        $response->assertSee('View report');

        // Unfinished sessions: 103 (in_progress), 202 (waiting), 301 (waiting) must NOT have links to reports
        $response->assertDontSee(route('reports.show', 103));
        $response->assertDontSee(route('reports.show', 202));
        $response->assertDontSee(route('reports.show', 301));
    }

    public function test_instructor_dashboard_renders_empty_states_when_no_data(): void
    {
        $this->mock(InstructorService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAssignedStudents')->once()->andReturn([]);
            $mock->shouldReceive('getStudentSessions')->once()->andReturn([]);
            $mock->shouldReceive('getOverview')->once()->andReturn([
                'total_students' => 0,
                'in_progress_count' => 0,
                'waiting_count' => 0,
                'finished_count' => 0,
            ]);
            $mock->shouldReceive('getClassAnalytics')->once()->andReturn([
    'class_average' => null,
    'class_average_level' => null,
    'lowest_concept' => null,
    'lowest_concept_avg' => null,
    'lowest_concept_sessions' => 0,
    'finished_session_count' => 0,
]);
        });

        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('No students assigned');
        $response->assertSee('No session records');
    }

    public function test_instructor_dashboard_renders_responsive_views_and_filter_controls(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);

        // Verify responsive dual-layout elements
        $response->assertSee('hidden md:block');
        $response->assertSee('block md:hidden');

        // Verify Alpine.js filter buttons
        $response->assertSee('activeFilter', false);
        $response->assertSee('All (7)');
        $response->assertSee('In Progress (1)');
        $response->assertSee('Waiting (2)');
        $response->assertSee('Finished (4)');
    }

    // -------------------------------------------------------------------------
    // Phase 5 — Class Performance Analytics tests
    // -------------------------------------------------------------------------

    /**
     * The Class Performance section must be visible on the instructor dashboard
     * and display the correct demo values derived from stub data.
     *
     * Expected demo values (from InstructorService finished sessions 101, 102, 201, 401):
     *   Class average  : 91  (rounded from 91.25 = (92+85+88+100)/4)
     *   Score level    : Advanced
     *   Lowest concept : Invalid Partition Handling (avg 84, from session 102 only)
     */
    public function test_instructor_dashboard_renders_class_analytics_section(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);

        // Section heading and "Demo data" label
        $response->assertSee('Class Performance');
        $response->assertSee('Demo data');

        // Class Average Score card
        $response->assertSee('Class Average Score');
        $response->assertSee('91');       // rounded class average
        $response->assertSee('Advanced'); // score level

        // Lowest-Scoring Concept card
        $response->assertSee('Lowest-Scoring Concept');
        $response->assertSee('Invalid Partition Handling');
        $response->assertSee('84');       // lowest concept avg

        // Provisional disclaimer must be visible
        $response->assertSee('provisional');
    }

    /**
     * When no finished sessions exist the Class Performance section must display
     * the "No completed sessions yet" empty state and no metric values.
     */
    public function test_instructor_dashboard_shows_analytics_unavailable_when_no_finished_sessions(): void
    {
        $this->mock(InstructorService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAssignedStudents')->once()->andReturn([]);
            $mock->shouldReceive('getStudentSessions')->once()->andReturn([]);
            $mock->shouldReceive('getOverview')->once()->andReturn([
                'total_students'   => 0,
                'in_progress_count' => 0,
                'waiting_count'    => 0,
                'finished_count'   => 0,
            ]);
            $mock->shouldReceive('getClassAnalytics')->once()->andReturn([
                'class_average'           => null,
                'class_average_level'     => null,
                'lowest_concept'          => null,
                'lowest_concept_avg'      => null,
                'lowest_concept_sessions' => 0,
                'finished_session_count'  => 0,
            ]);
        });

        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Class Performance');
        $response->assertSee('No completed sessions yet');
        $response->assertDontSee('Class Average Score');
        $response->assertDontSee('Lowest-Scoring Concept');
    }

    /**
     * getClassAnalytics() must return the correct class average and score level
     * based strictly on finished sessions with valid scores from getStudentSessions().
     *
     * Finished sessions in stub: 101 (92), 102 (85), 201 (88), 401 (100).
     * Sessions 501 and 502 are absent from getStudentSessions() and must NOT contribute.
     * Waiting/in-progress sessions (103, 202, 301) must be excluded.
     *
     * Expected: average = round((92+85+88+100)/4) = round(91.25) = 91, level = Advanced.
     */
    public function test_get_class_analytics_returns_correct_class_average(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $service    = app(InstructorService::class);
        $analytics  = $service->getClassAnalytics($instructor);

        $this->assertSame(91, $analytics['class_average']);
        $this->assertSame('Advanced', $analytics['class_average_level']);
        $this->assertSame(4, $analytics['finished_session_count']);
    }

    /**
     * getClassAnalytics() must identify the concept with the lowest average concept score
     * across finished sessions that have a matching report, excluding sessions 501 and 502
     * which are not present in getStudentSessions().
     *
     * Concepts from reports 101, 102, 201, 401:
     *   Boundary Testing          : 94      (avg 94.0)
     *   Off-by-One Trap Detection : 90      (avg 90.0)
     *   Equivalence Partitioning  : 86, 90  (avg 88.0)
     *   Invalid Partition Handling: 84      (avg 84.0) ← lowest
     *   Boundary Input Sorting    : 85      (avg 85.0)
     *   Assertions & Test Suites  : 100     (avg 100.0)
     *   System State Verification : 100     (avg 100.0)
     *
     * Expected lowest: "Invalid Partition Handling", avg = 84, contributing sessions = 1.
     */
    public function test_get_class_analytics_returns_correct_lowest_scoring_concept(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $service    = app(InstructorService::class);
        $analytics  = $service->getClassAnalytics($instructor);

        $this->assertSame('Invalid Partition Handling', $analytics['lowest_concept']);
        $this->assertSame(84, $analytics['lowest_concept_avg']);
        $this->assertSame(1, $analytics['lowest_concept_sessions']);
    }

    /**
     * When two or more concepts share the same lowest average score, the tie must be
     * broken by selecting the first concept alphabetically (ascending).
     *
     * This test mocks ReportService::findReport() to return two concepts with identical
     * scores ("Zeta Concept" and "Alpha Concept", both 70) for every finished session.
     * The service must resolve the tie in favour of "Alpha Concept".
     */
    public function test_get_class_analytics_tie_broken_alphabetically(): void
    {
        $this->mock(ReportService::class, function (MockInterface $mock) {
            $mock->shouldReceive('findReport')
                 ->andReturn([
                     'concept_scores' => [
                         ['concept' => 'Zeta Concept',  'score' => 70],
                         ['concept' => 'Alpha Concept', 'score' => 70],
                     ],
                 ]);
        });

        $instructor = User::factory()->create(['role' => 'instructor']);
        $service    = app(InstructorService::class);
        $analytics  = $service->getClassAnalytics($instructor);

        // Both concepts have avg 70; "Alpha Concept" wins alphabetically.
        $this->assertSame('Alpha Concept', $analytics['lowest_concept']);
        $this->assertSame(70, $analytics['lowest_concept_avg']);
    }
}

