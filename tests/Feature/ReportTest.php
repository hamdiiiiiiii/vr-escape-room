<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_report(): void
    {
        $response = $this->get(route('reports.show', 101));

        $response->assertRedirect(route('login'));
    }

    public function test_student_can_view_their_own_finished_report(): void
    {
        $student1 = User::factory()->create([
            'email' => 'student1@test.com',
            'role' => 'student',
        ]);

        $response = $this->actingAs($student1)->get(route('reports.show', 101));

        $response->assertStatus(200);
        $response->assertSee('Session Performance Report');
        $response->assertSee('Session #101');
        $response->assertSee('The Boundary Maze');
        $response->assertSee('92 / 100');
        $response->assertSee('Advanced (80–100)');
        $response->assertSee('Back to Student Dashboard');
    }

    public function test_student_cannot_view_another_students_report(): void
    {
        // Student 1 owns report 101
        $student1 = User::factory()->create([
            'email' => 'student1@test.com',
            'role' => 'student',
        ]);

        // Student 2 is a different student
        $student2 = User::factory()->create([
            'email' => 'student2@test.com',
            'role' => 'student',
        ]);

        $response = $this->actingAs($student2)->get(route('reports.show', 101));

        $response->assertStatus(403);
    }

    public function test_instructor_can_view_assigned_students_report(): void
    {
        $student1 = User::factory()->create([
            'email' => 'student1@test.com',
            'role' => 'student',
        ]);

        $instructor = User::factory()->create([
            'email' => 'instructor@test.com',
            'role' => 'instructor',
        ]);

        $response = $this->actingAs($instructor)->get(route('reports.show', 101));

        $response->assertStatus(200);
        $response->assertSee('Session Performance Report');
        $response->assertSee('Session #101');
        $response->assertSee('Back to Instructor Dashboard');
    }

    public function test_instructor_cannot_view_unassigned_students_report(): void
    {
        $instructor = User::factory()->create([
            'email' => 'instructor@test.com',
            'role' => 'instructor',
        ]);

        // Mock ReportService to return a report belonging to an unassigned student ID
        $this->mock(ReportService::class, function ($mock) {
            $mock->shouldReceive('findReport')->with('888')->andReturn([
                'id' => 888,
                'session_id' => 888,
                'student_id' => 999, // Unassigned student
                'student_name' => 'Foreign Student',
                'student_email' => 'foreign@test.com',
                'room_name' => 'The Boundary Maze',
                'concept_tag' => 'Boundary Testing',
                'date' => 'Nov 05, 2026',
                'status' => 'finished',
                'score' => 90,
                'score_level' => 'Advanced',
                'time_spent' => '10m 00s',
                'time_limit_minutes' => 15,
                'total_attempts' => 3,
                'puzzles_solved' => 3,
                'total_puzzles' => 3,
                'puzzles_without_hints' => 2,
                'hints_used' => 1,
                'concept_scores' => [],
            ]);

            $mock->shouldReceive('canAccess')->andReturn(false);
        });

        $response = $this->actingAs($instructor)->get(route('reports.show', 888));

        $response->assertStatus(403);
    }

    public function test_accessing_non_existent_report_returns_404(): void
    {
        $student1 = User::factory()->create([
            'email' => 'student1@test.com',
            'role' => 'student',
        ]);

        $response = $this->actingAs($student1)->get(route('reports.show', 999));

        $response->assertStatus(404);
    }

    public function test_accessing_unfinished_session_report_returns_404(): void
    {
        $student1 = User::factory()->create([
            'email' => 'student1@test.com',
            'role' => 'student',
        ]);

        // Session 103 is in_progress (unfinished)
        $response = $this->actingAs($student1)->get(route('reports.show', 103));

        $response->assertStatus(404);
    }

    public function test_report_displays_metrics_time_limits_and_attempts(): void
    {
        $student1 = User::factory()->create([
            'email' => 'student1@test.com',
            'role' => 'student',
        ]);

        $response = $this->actingAs($student1)->get(route('reports.show', 101));

        $response->assertStatus(200);
        $response->assertSee('11m 45s');
        $response->assertSee('Time limit: 15 minutes');
        $response->assertSee('3 / 3');
        $response->assertSee('Total attempts: 4');
        $response->assertSee('Without Hints');
        $response->assertSee('2 / 3');
        $response->assertSee('Hints used: 1');
    }

    public function test_report_displays_provisional_concept_scores(): void
    {
        $student1 = User::factory()->create([
            'email' => 'student1@test.com',
            'role' => 'student',
        ]);

        $response = $this->actingAs($student1)->get(route('reports.show', 101));

        $response->assertStatus(200);
        $response->assertSee('Testing Concepts Breakdown');
        $response->assertSee('Provisional Topics:');
        $response->assertSee('Boundary Testing');
        $response->assertSee('94%');
        $response->assertSee('Off-by-One Trap Detection');
        $response->assertSee('90%');
    }

    public function test_report_displays_score_level_classifications(): void
    {
        $student1 = User::factory()->create([
            'email' => 'student1@test.com',
            'role' => 'student',
        ]);

        // Advanced (80-100) on report 101
        $responseAdv = $this->actingAs($student1)->get(route('reports.show', 101));
        $responseAdv->assertSee('Advanced (80–100)');

        // Proficient (50-79) on report 501
        $responseProf = $this->actingAs($student1)->get(route('reports.show', 501));
        $responseProf->assertSee('Proficient (50–79)');

        // Needs Support (0-49) on report 502
        $responseNeed = $this->actingAs($student1)->get(route('reports.show', 502));
        $responseNeed->assertSee('Needs Support (0–49)');
    }

    public function test_report_displays_empty_ai_analysis_section_with_friendly_message(): void
    {
        $student1 = User::factory()->create([
            'email' => 'student1@test.com',
            'role' => 'student',
        ]);

        $response = $this->actingAs($student1)->get(route('reports.show', 101));

        $response->assertStatus(200);
        $response->assertSee('AI Tutor & Bug Hunting Analysis');
        $response->assertSee('AI Analysis Not Available Yet');
        $response->assertSee('Automated code-level bug hunting recommendations and AI tutor analysis will be available in an upcoming update');
    }
}

