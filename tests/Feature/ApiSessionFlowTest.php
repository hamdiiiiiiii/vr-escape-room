<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\PuzzleResult;
use App\Models\Report;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSessionFlowTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room = Room::create([
            'name' => 'Test Room',
            'time_limit_seconds' => 600,
            'config' => ['puzzles' => [
                ['id' => 'p1', 'conceptTag' => 'bug-spotting'],
                ['id' => 'p2', 'conceptTag' => 'test-case'],
            ]],
        ]);
    }

    /** @return array{0: GameSession, 1: string, 2: User} */
    private function inProgress(): array
    {
        $user = User::factory()->create(['role' => 'student']);
        $session = GameSession::create([
            'user_id' => $user->id,
            'room_id' => $this->room->id,
            'status' => 'in_progress',
            'expires_at' => now()->addMinutes(10),
            'started_at' => now(),
        ]);
        $token = $user->createToken('session-'.$session->id)->plainTextToken;

        return [$session, $token, $user];
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'puzzleId' => 'p1',
            'conceptTag' => 'bug-spotting',
            'solved' => true,
            'attempts' => 1,
            'timeSeconds' => 60,
            'hintsUsed' => 0,
        ], $override);
    }

    public function test_verify_code_moves_session_to_in_progress_and_is_single_use(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $session = GameSession::create([
            'user_id' => $user->id,
            'room_id' => $this->room->id,
            'code' => '004211',
            'status' => 'waiting',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/sessions/verify-code', ['code' => '004211'])
            ->assertOk()
            ->assertJsonStructure(['sessionId', 'token', 'room' => ['roomId', 'name', 'timeLimitSeconds']]);

        $this->assertSame('in_progress', $session->fresh()->status);

        $this->postJson('/api/sessions/verify-code', ['code' => '004211'])->assertStatus(404);
    }

    public function test_verify_code_rejects_bad_format_and_expired_codes(): void
    {
        $this->postJson('/api/sessions/verify-code', ['code' => '12'])->assertStatus(422);

        $user = User::factory()->create(['role' => 'student']);
        GameSession::create([
            'user_id' => $user->id,
            'room_id' => $this->room->id,
            'code' => '111111',
            'status' => 'waiting',
            'expires_at' => now()->subMinute(),
        ]);

        $this->postJson('/api/sessions/verify-code', ['code' => '111111'])->assertStatus(410);
    }

    public function test_results_require_a_token(): void
    {
        [$session] = $this->inProgress();

        $this->postJson("/api/sessions/{$session->id}/results", $this->payload())->assertStatus(401);
    }

    public function test_result_is_saved_and_scored(): void
    {
        [$session, $token] = $this->inProgress();

        $this->withToken($token)
            ->postJson("/api/sessions/{$session->id}/results", $this->payload(['attempts' => 2]))
            ->assertStatus(201)
            ->assertJsonPath('score', 70);

        $this->assertDatabaseHas('puzzle_results', [
            'game_session_id' => $session->id,
            'puzzle_id' => 'p1',
            'score' => 70,
        ]);
    }

    public function test_hint_halves_the_score(): void
    {
        [$session, $token] = $this->inProgress();

        $this->withToken($token)
            ->postJson("/api/sessions/{$session->id}/results", $this->payload(['hintsUsed' => 1]))
            ->assertStatus(201)
            ->assertJsonPath('score', 50);
    }

    public function test_result_validation_errors(): void
    {
        [$session, $token] = $this->inProgress();

        $this->withToken($token)
            ->postJson("/api/sessions/{$session->id}/results", $this->payload(['attempts' => 9]))
            ->assertStatus(422);

        $this->withToken($token)
            ->postJson("/api/sessions/{$session->id}/results", ['puzzleId' => 'p1'])
            ->assertStatus(422);
    }

    public function test_same_puzzle_sent_twice_is_stored_once(): void
    {
        [$session, $token] = $this->inProgress();

        $this->withToken($token)->postJson("/api/sessions/{$session->id}/results", $this->payload())->assertStatus(201);
        $this->withToken($token)->postJson("/api/sessions/{$session->id}/results", $this->payload())->assertStatus(201);

        $this->assertSame(1, PuzzleResult::where('game_session_id', $session->id)->count());
    }

    public function test_token_of_another_session_is_rejected(): void
    {
        [, $tokenA] = $this->inProgress();
        [$sessionB] = $this->inProgress();

        $this->withToken($tokenA)
            ->postJson("/api/sessions/{$sessionB->id}/results", $this->payload())
            ->assertStatus(403);
    }

    public function test_results_rejected_when_session_not_in_progress(): void
    {
        [$session, $token] = $this->inProgress();
        $session->update(['status' => 'finished']);

        $this->withToken($token)
            ->postJson("/api/sessions/{$session->id}/results", $this->payload())
            ->assertStatus(409);
    }

    public function test_end_creates_report_and_finishes_session(): void
    {
        [$session, $token, $user] = $this->inProgress();

        $this->withToken($token)->postJson("/api/sessions/{$session->id}/results", $this->payload())->assertStatus(201);
        $this->withToken($token)->postJson("/api/sessions/{$session->id}/results", $this->payload([
            'puzzleId' => 'p2',
            'conceptTag' => 'test-case',
            'attempts' => 2,
        ]))->assertStatus(201);

        $this->withToken($token)
            ->postJson("/api/sessions/{$session->id}/end", ['reason' => 'escaped'])
            ->assertOk()
            ->assertJsonPath('status', 'finished')
            ->assertJsonPath('sessionScore', 85)
            ->assertJsonPath('level', 'Advanced');

        $session->refresh();
        $this->assertSame('finished', $session->status);
        $this->assertSame('escaped', $session->end_reason);
        $this->assertNotNull($session->ended_at);

        $report = Report::where('game_session_id', $session->id)->first();
        $this->assertNotNull($report);
        $this->assertSame(85, $report->session_score);
        $this->assertSame(['bug-spotting' => 100, 'test-case' => 70], $report->concept_scores);

        // the session token is revoked after the end
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_unreached_puzzles_count_as_zero_when_time_runs_out(): void
    {
        [$session, $token] = $this->inProgress();

        $this->withToken($token)->postJson("/api/sessions/{$session->id}/results", $this->payload())->assertStatus(201);

        $this->withToken($token)
            ->postJson("/api/sessions/{$session->id}/end", ['reason' => 'timed_out'])
            ->assertOk()
            ->assertJsonPath('sessionScore', 50)
            ->assertJsonPath('level', 'Proficient');

        $this->assertSame('timed_out', $session->fresh()->end_reason);
    }

    public function test_end_rejects_unknown_reason(): void
    {
        [$session, $token] = $this->inProgress();

        $this->withToken($token)
            ->postJson("/api/sessions/{$session->id}/end", ['reason' => 'quit'])
            ->assertStatus(422);
    }

        public function test_verify_code_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/sessions/verify-code', ['code' => '000000'])->assertStatus(404);
        }

        $this->postJson('/api/sessions/verify-code', ['code' => '000000'])->assertStatus(429);
    }
}