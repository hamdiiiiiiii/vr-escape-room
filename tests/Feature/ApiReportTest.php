<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\PuzzleResult;
use App\Models\Report;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiReportTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;
    private User $student;
    private GameSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $room = Room::create([
            'name' => 'Test Room',
            'time_limit_seconds' => 600,
            'config' => ['puzzles' => [['id' => 'p1'], ['id' => 'p2']]],
        ]);

        $this->instructor = User::factory()->create(['role' => 'instructor']);

        $this->student = User::factory()->create(['role' => 'student']);
        $this->student->forceFill(['instructor_id' => $this->instructor->id])->save();

        $this->session = GameSession::create([
            'user_id' => $this->student->id,
            'room_id' => $room->id,
            'status' => 'finished',
            'end_reason' => 'escaped',
            'expires_at' => now(),
            'started_at' => now()->subMinutes(5),
            'ended_at' => now(),
        ]);

        PuzzleResult::create([
            'game_session_id' => $this->session->id, 'puzzle_id' => 'p1', 'concept_tag' => 'bug-spotting',
            'solved' => true, 'attempts' => 1, 'time_seconds' => 60, 'hints_used' => 0, 'score' => 100,
        ]);
        PuzzleResult::create([
            'game_session_id' => $this->session->id, 'puzzle_id' => 'p2', 'concept_tag' => 'test-case',
            'solved' => true, 'attempts' => 2, 'time_seconds' => 90, 'hints_used' => 1, 'score' => 35,
        ]);

        Report::create([
            'game_session_id' => $this->session->id,
            'total_time_seconds' => 150,
            'session_score' => 68,
            'level' => 'Proficient',
            'concept_scores' => ['bug-spotting' => 100, 'test-case' => 35],
            'ai_analysis' => null,
        ]);
    }

    private function url(): string
    {
        return "/api/sessions/{$this->session->id}/report";
    }

    public function test_student_can_read_own_report(): void
    {
        $this->actingAs($this->student)->getJson($this->url())
            ->assertOk()
            ->assertJsonPath('sessionScore', 68)
            ->assertJsonPath('puzzlesSolved', 2)
            ->assertJsonPath('puzzlesTotal', 2)
            ->assertJsonPath('solvedWithoutHelp', 1)
            ->assertJsonPath('level', 'Proficient')
            ->assertJsonPath('ai', null)
            ->assertJsonCount(2, 'puzzles')
            ->assertJsonCount(2, 'concepts');
    }

     public function test_their_instructor_can_read_it(): void
    {
        $this->actingAs($this->instructor)->getJson($this->url())->assertOk();
    }
    public function test_other_student_and_other_instructor_are_forbidden(): void
    {
        $otherStudent = User::factory()->create(['role' => 'student']);
        $otherInstructor = User::factory()->create(['role' => 'instructor']);

        $this->actingAs($otherStudent)->getJson($this->url())->assertStatus(403);
        $this->actingAs($otherInstructor)->getJson($this->url())->assertStatus(403);
    }

    public function test_guest_gets_401_and_unknown_session_404(): void
    {
        $this->getJson($this->url())->assertStatus(401);
        $this->actingAs($this->student)->getJson('/api/sessions/99999/report')->assertStatus(404);
    }

    public function test_unfinished_session_has_no_report(): void
    {
        $this->session->update(['status' => 'in_progress']);

        $this->actingAs($this->student)->getJson($this->url())->assertStatus(404);
    }
}