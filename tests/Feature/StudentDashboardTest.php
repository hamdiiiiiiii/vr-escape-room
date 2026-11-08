<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RoomService;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_student_dashboard(): void
    {
        $response = $this->get(route('student.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_instructor_cannot_access_student_dashboard(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor)->get(route('student.dashboard'));

        $response->assertStatus(403);
    }

    public function test_student_dashboard_displays_rooms_from_service(): void
    {
        $student = User::factory()->create([
            'name' => 'Alice Student',
            'role' => 'student',
            'email' => 'student1@test.com',
        ]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Welcome, Alice Student!');
        $response->assertSee('Available Escape Rooms');

        $roomService = new RoomService();
        foreach ($roomService->getRooms() as $room) {
            $response->assertSee($room['name']);
            $response->assertSee($room['concept_tag']);
            $response->assertSee(route('student.start-room', $room['id']));
        }
    }

    public function test_student_sees_only_their_own_sessions(): void
    {
        // Student 1 has sessions 101, 102, 103
        $student1 = User::factory()->create([
            'id' => 1,
            'email' => 'student1@test.com',
            'role' => 'student',
        ]);

        // Student 2 has sessions 201, 202
        $student2 = User::factory()->create([
            'id' => 2,
            'email' => 'student2@test.com',
            'role' => 'student',
        ]);

        $response1 = $this->actingAs($student1)->get(route('student.dashboard'));
        $response1->assertStatus(200);
        $response1->assertSee(route('reports.show', 101));
        $response1->assertDontSee(route('reports.show', 201));

        $response2 = $this->actingAs($student2)->get(route('student.dashboard'));
        $response2->assertStatus(200);
        $response2->assertSee(route('reports.show', 201));
        $response2->assertDontSee(route('reports.show', 101));
    }

    public function test_student_with_empty_history_sees_friendly_empty_state(): void
    {
        $student5 = User::factory()->create([
            'id' => 5,
            'email' => 'student5@test.com',
            'role' => 'student',
        ]);

        $response = $this->actingAs($student5)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('No past missions yet!');
        $response->assertSee('You have not started any VR escape room challenges yet');
    }

    public function test_finished_session_links_to_report_while_unfinished_does_not(): void
    {
        $student1 = User::factory()->create([
            'id' => 1,
            'email' => 'student1@test.com',
            'role' => 'student',
        ]);

        $response = $this->actingAs($student1)->get(route('student.dashboard'));

        // Session 101 is finished -> has link to /reports/101
        $response->assertSee(route('reports.show', 101));
        $response->assertSee('Open report');

        // Session 103 is in_progress -> should NOT have link to /reports/103
        $response->assertDontSee(route('reports.show', 103));
    }

    public function test_old_start_room_get_link_redirects_to_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get(route('student.start-room', 1));

        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_dashboard_start_room_button_is_a_post_form(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertSee('method="POST" action="'.route('student.start-room.create', 1).'"', false);
    }
}
