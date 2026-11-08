<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room = Room::create([
            'name' => 'Test Room',
            'time_limit_seconds' => 600,
            'config' => [
                'description' => 'A test room.',
                'conceptTag' => 'Bug spotting',
                'puzzles' => [],
            ],
        ]);
    }

    private function makeSession(User $user, string $status): GameSession
    {
        return GameSession::create([
            'user_id' => $user->id,
            'room_id' => $this->room->id,
            'code' => null,
            'status' => $status,
            'expires_at' => now()->addMinutes(10),
            'started_at' => now(),
        ]);
    }

    public function test_guest_is_redirected_to_login_from_student_dashboard(): void
    {
        $this->get(route('student.dashboard'))->assertRedirect(route('login'));
    }

    public function test_instructor_cannot_access_student_dashboard(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $this->actingAs($instructor)->get(route('student.dashboard'))->assertStatus(403);
    }

    public function test_student_dashboard_displays_rooms_from_service(): void
    {
        $student = User::factory()->create(['name' => 'Alice Student', 'role' => 'student']);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Welcome, Alice Student!');
        $response->assertSee('Available Escape Rooms');
        $response->assertSee('Test Room');
        $response->assertSee('Bug spotting');
        $response->assertSee(route('student.start-room', $this->room->id));
    }

    public function test_student_sees_only_their_own_sessions(): void
    {
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);

        $s1 = $this->makeSession($student1, 'finished');
        $s2 = $this->makeSession($student2, 'finished');

        $response1 = $this->actingAs($student1)->get(route('student.dashboard'));
        $response1->assertStatus(200);
        $response1->assertSee(route('reports.show', $s1->id));
        $response1->assertDontSee(route('reports.show', $s2->id));

        $response2 = $this->actingAs($student2)->get(route('student.dashboard'));
        $response2->assertStatus(200);
        $response2->assertSee(route('reports.show', $s2->id));
        $response2->assertDontSee(route('reports.show', $s1->id));
    }

    public function test_student_with_empty_history_sees_friendly_empty_state(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('No past missions yet!');
        $response->assertSee('You have not started any VR escape room challenges yet');
    }

    public function test_finished_session_links_to_report_while_unfinished_does_not(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $finished = $this->makeSession($student, 'finished');
        $inProgress = $this->makeSession($student, 'in_progress');

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertSee(route('reports.show', $finished->id));
        $response->assertSee('Open report');
        $response->assertDontSee(route('reports.show', $inProgress->id));
    }

    public function test_old_start_room_get_link_redirects_to_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('student.start-room', $this->room->id))
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_dashboard_start_room_button_is_a_post_form(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertSee('method="POST" action="'.route('student.start-room.create', $this->room->id).'"', false);
    }

    public function test_navigation_contains_my_sessions_anchor_and_profile_link(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertSee(route('student.dashboard').'#past-sessions');
        $response->assertSee('My Sessions');
        $response->assertSee('id="past-sessions"', false);
        $response->assertSee(route('profile.edit'));
    }
}