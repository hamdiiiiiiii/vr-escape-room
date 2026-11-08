<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\InstructorService;
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
}

