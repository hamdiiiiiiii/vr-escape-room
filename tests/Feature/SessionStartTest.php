<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\Room;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionStartTest extends TestCase
{
    use RefreshDatabase;

    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room = Room::create([
            'name' => 'Test Room',
            'time_limit_seconds' => 600,
            'config' => ['puzzles' => []],
        ]);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'student']);
    }

    public function test_start_creates_session_with_six_digit_code_and_redirects(): void
    {
        $student = $this->student();

        $response = $this->actingAs($student)->post(route('student.start-room.create', $this->room->id));

        $game = GameSession::first();
        $session = (new SessionService())->getSession($game->id);

        $this->assertNotNull($session);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $session['code']);
        $this->assertSame('waiting', $session['status']);
        $this->assertSame($student->id, $session['user_id']);
        $response->assertRedirect(route('student.session.show', $game->id));
    }

    public function test_start_with_unknown_room_returns_404(): void
    {
        $response = $this->actingAs($this->student())->post(route('student.start-room.create', 999));

        $response->assertStatus(404);
    }

    public function test_code_is_zero_padded_to_six_digits(): void
    {
        $student = $this->student();
        $service = new SessionService();

        for ($i = 0; $i < 30; $i++) {
            $result = $service->startSession($student, $this->room->id);
            $this->assertSame(6, strlen($result['code']));
        }
    }

    public function test_codes_are_unique_among_active_sessions(): void
    {
        $student = $this->student();
        $service = new SessionService();

        $codes = [];
        for ($i = 0; $i < 50; $i++) {
            $codes[] = $service->startSession($student, $this->room->id)['code'];
        }

        $this->assertCount(50, array_unique($codes));
    }

    public function test_expiry_is_ten_minutes_from_now(): void
    {
        $this->travelTo(now()->startOfSecond());

        $result = (new SessionService())->startSession($this->student(), $this->room->id);

        $this->assertSame(
            now()->addMinutes(10)->toIso8601String(),
            \Illuminate\Support\Carbon::parse($result['expires_at'])->toIso8601String()
        );
    }

    public function test_status_endpoint_returns_expected_json_shape(): void
    {
        $student = $this->student();
        $created = (new SessionService())->startSession($student, $this->room->id);

        $response = $this->actingAs($student)->getJson(route('student.session.status', $created['session_id']));

        $response->assertOk();
        $response->assertExactJson([
            'session_id' => $created['session_id'],
            'status' => 'waiting',
            'expires_at' => $created['expires_at'],
        ]);
    }

    public function test_session_screen_shows_the_code(): void
    {
        $student = $this->student();
        $created = (new SessionService())->startSession($student, $this->room->id);

        $response = $this->actingAs($student)->get(route('student.session.show', $created['session_id']));

        $response->assertOk();
        $response->assertSee($created['code']);
        $response->assertSee('1. Put on the headset.', false);
        $response->assertSee('2. Type this code on the keypad.', false);
        $response->assertSee('Code Expiration Countdown');
    }

    public function test_session_screen_renders_in_progress_state(): void
    {
        $student = $this->student();
        $created = (new SessionService())->startSession($student, $this->room->id);

        GameSession::whereKey($created['session_id'])->update(['status' => 'in_progress']);

        $response = $this->actingAs($student)->get(route('student.session.show', $created['session_id']));

        $response->assertOk();
        $response->assertSee('You are in the room! Play in VR');
        $response->assertSee('In Progress');
    }

    public function test_session_screen_renders_finished_state(): void
    {
        $student = $this->student();
        $created = (new SessionService())->startSession($student, $this->room->id);

        GameSession::whereKey($created['session_id'])->update(['status' => 'finished']);

        $response = $this->actingAs($student)->get(route('student.session.show', $created['session_id']));

        $response->assertOk();
        $response->assertSee('Session Completed!');
        $response->assertSee('Open report');
        $response->assertSee(route('reports.show', $created['session_id']));
        $response->assertSee('Finished');
    }

    public function test_session_screen_renders_expired_state(): void
    {
        $student = $this->student();
        $created = (new SessionService())->startSession($student, $this->room->id);

        $this->travel(11)->minutes();

        $response = $this->actingAs($student)->get(route('student.session.show', $created['session_id']));

        $response->assertOk();
        $response->assertSee('This code has expired');
        $response->assertSee('Get new code');
        $response->assertSee(route('student.start-room.create', $this->room->id));
        $response->assertSee('Expired');
    }

    public function test_student_cannot_open_or_poll_another_students_session(): void
    {
        $owner = $this->student();
        $other = $this->student();
        $created = (new SessionService())->startSession($owner, $this->room->id);

        $this->actingAs($other)->get(route('student.session.show', $created['session_id']))->assertStatus(403);
        $this->actingAs($other)->getJson(route('student.session.status', $created['session_id']))->assertStatus(403);
    }

    public function test_unknown_session_returns_404(): void
    {
        $this->actingAs($this->student())->getJson(route('student.session.status', 424242))->assertStatus(404);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('student.start-room.create', $this->room->id))->assertRedirect(route('login'));
        $this->get(route('student.session.show', 1))->assertRedirect(route('login'));
        $this->get(route('student.session.status', 1))->assertRedirect(route('login'));
    }

    public function test_instructor_gets_403_on_session_routes(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $this->actingAs($instructor)->post(route('student.start-room.create', $this->room->id))->assertStatus(403);
        $this->actingAs($instructor)->get(route('student.session.status', 1))->assertStatus(403);
    }

    public function test_waiting_session_becomes_expired_after_ten_minutes(): void
    {
        $student = $this->student();
        $created = (new SessionService())->startSession($student, $this->room->id);

        $this->travel(9)->minutes();
        $this->assertSame('waiting', (new SessionService())->getStatus($created['session_id'])['status']);

        $this->travel(2)->minutes();
        $response = $this->actingAs($student)->getJson(route('student.session.status', $created['session_id']));

        $response->assertOk()->assertJsonPath('status', 'expired');
    }

    
    

    public function test_session_screen_configures_status_polling_and_reconnecting(): void
    {
        $student = $this->student();
        $created = (new SessionService())->startSession($student, $this->room->id);

        $response = $this->actingAs($student)->get(route('student.session.show', $created['session_id']));

        $response->assertOk();
        $response->assertSee(route('student.session.status', $created['session_id']));
        $response->assertSee('3000');
        $response->assertSee('Reconnecting...');
        $response->assertSee('pollStatus');
    }


}