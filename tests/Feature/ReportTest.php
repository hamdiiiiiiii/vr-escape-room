<?php
 
namespace Tests\Feature;
 
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFinishedSessions;
use Tests\TestCase;
 
class ReportTest extends TestCase
{
    use RefreshDatabase, CreatesFinishedSessions;
 
    public function test_guest_is_redirected_to_login_when_accessing_report(): void
    {
        $this->get(route('reports.show', 1))->assertRedirect(route('login'));
    }
 
    public function test_student_can_view_their_own_finished_report(): void
    {
        $student = $this->makeStudent();
        $session = $this->makeFinishedSession($student, 92);
 
        $response = $this->actingAs($student)->get(route('reports.show', $session->id));
 
        $response->assertStatus(200);
        $response->assertSee('Session Performance Report');
        $response->assertSee('Session #'.$session->id);
        $response->assertSee('The Boundary Maze');
        $response->assertSee('92 / 100');
        $response->assertSee('Advanced (80–100)');
        $response->assertSee('Back to Student Dashboard');
    }
 
    public function test_student_cannot_view_another_students_report(): void
    {
        $owner = $this->makeStudent();
        $other = $this->makeStudent();
        $session = $this->makeFinishedSession($owner);
 
        $this->actingAs($other)->get(route('reports.show', $session->id))->assertStatus(403);
    }
 
    public function test_instructor_can_view_assigned_students_report(): void
    {
        $instructor = $this->makeInstructor();
        $student = $this->makeStudent($instructor);
        $session = $this->makeFinishedSession($student);
 
        $response = $this->actingAs($instructor)->get(route('reports.show', $session->id));
 
        $response->assertStatus(200);
        $response->assertSee('Session Performance Report');
        $response->assertSee('Session #'.$session->id);
        $response->assertSee('Back to Instructor Dashboard');
    }
 
    public function test_instructor_cannot_view_unassigned_students_report(): void
    {
        $instructor = $this->makeInstructor();
        $foreignStudent = $this->makeStudent(); // not linked to this instructor
        $session = $this->makeFinishedSession($foreignStudent);
 
        $this->actingAs($instructor)->get(route('reports.show', $session->id))->assertStatus(403);
    }
 
    public function test_accessing_non_existent_report_returns_404(): void
    {
        $student = $this->makeStudent();
 
        $this->actingAs($student)->get(route('reports.show', 999999))->assertStatus(404);
    }
 
    public function test_accessing_unfinished_session_report_returns_404(): void
    {
        $student = $this->makeStudent();
        $session = $this->makeSession($student, 'in_progress');
 
        $this->actingAs($student)->get(route('reports.show', $session->id))->assertStatus(404);
    }
 
    public function test_report_displays_metrics_time_limits_and_attempts(): void
    {
        $student = $this->makeStudent();
        $session = $this->makeFinishedSession($student);
 
        $response = $this->actingAs($student)->get(route('reports.show', $session->id));
 
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
        $student = $this->makeStudent();
        $session = $this->makeFinishedSession($student);
 
        $response = $this->actingAs($student)->get(route('reports.show', $session->id));
 
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
        $student = $this->makeStudent();
 
        $advanced = $this->makeFinishedSession($student, 92);
        $proficient = $this->makeFinishedSession($student, 72);
        $needs = $this->makeFinishedSession($student, 45);
 
        $this->actingAs($student)->get(route('reports.show', $advanced->id))->assertSee('Advanced (80–100)');
        $this->actingAs($student)->get(route('reports.show', $proficient->id))->assertSee('Proficient (50–79)');
        $this->actingAs($student)->get(route('reports.show', $needs->id))->assertSee('Needs Support (0–49)');
    }
 
    public function test_report_displays_empty_ai_analysis_section_with_friendly_message(): void
    {
        $student = $this->makeStudent();
        $session = $this->makeFinishedSession($student);
 
        $response = $this->actingAs($student)->get(route('reports.show', $session->id));
 
        $response->assertStatus(200);
        $response->assertSee('AI Tutor & Bug Hunting Analysis');
    }
 
    public function test_report_renders_responsive_layout_and_adaptive_back_links(): void
    {
        $instructor = $this->makeInstructor();
        $student = $this->makeStudent($instructor);
        $session = $this->makeFinishedSession($student);
 
        $studentResponse = $this->actingAs($student)->get(route('reports.show', $session->id));
        $studentResponse->assertStatus(200);
        $studentResponse->assertSee(route('student.dashboard'));
        $studentResponse->assertSee('Back to Student Dashboard');
 
        $instructorResponse = $this->actingAs($instructor)->get(route('reports.show', $session->id));
        $instructorResponse->assertStatus(200);
        $instructorResponse->assertSee(route('instructor.dashboard'));
        $instructorResponse->assertSee('Back to Instructor Dashboard');
    }
}