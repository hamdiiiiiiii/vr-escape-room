<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\PuzzleResult;
use App\Models\Room;
use App\Models\User;
use App\Services\InsightsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WrongAnswersTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->room = Room::create([
            'name' => 'Test Room',
            'time_limit_seconds' => 600,
            'config' => ['puzzles' => [['id' => 'p1', 'conceptTag' => 'Boundary Testing']]],
        ]);
    }

    private function inProgress(?User $instructorOf = null): array
    {
        $user = User::factory()->create(['role' => 'student']);
        if ($instructorOf) {
            $user->forceFill(['instructor_id' => $instructorOf->id])->save();
        }
        $session = GameSession::create([
            'user_id' => $user->id, 'room_id' => $this->room->id, 'status' => 'in_progress',
            'expires_at' => now()->addMinutes(10), 'started_at' => now(),
        ]);

        return [$session, $user->createToken('session-'.$session->id)->plainTextToken];
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'puzzleId' => 'p1', 'conceptTag' => 'Boundary Testing', 'solved' => true,
            'attempts' => 2, 'timeSeconds' => 60, 'hintsUsed' => 0,
        ], $extra);
    }

    public function test_result_without_wrong_answers_still_works(): void
    {
        [$session, $token] = $this->inProgress();

        $this->withToken($token)->postJson("/api/sessions/{$session->id}/results", $this->payload())->assertStatus(201);

        $this->assertNull(PuzzleResult::first()->wrong_answers);
    }

    public function test_wrong_answers_are_stored(): void
    {
        [$session, $token] = $this->inProgress();

        $this->withToken($token)->postJson("/api/sessions/{$session->id}/results", $this->payload([
            'wrongAnswers' => [5, 'line 4', ['input' => 20, 'expected' => 'LOCKED']],
        ]))->assertStatus(201);

        $this->assertSame([5, 'line 4', ['input' => 20, 'expected' => 'LOCKED']], PuzzleResult::first()->wrong_answers);
    }

    public function test_more_than_three_or_too_long_wrong_answers_are_rejected(): void
    {
        [$session, $token] = $this->inProgress();
        $url = "/api/sessions/{$session->id}/results";

        $this->withToken($token)->postJson($url, $this->payload(['wrongAnswers' => [1, 2, 3, 4]]))->assertStatus(422);
        $this->withToken($token)->postJson($url, $this->payload(['wrongAnswers' => [str_repeat('x', 300)]]))->assertStatus(422);
        $this->assertSame(0, PuzzleResult::count());
    }

    public function test_retry_replaces_wrong_answers_without_duplicating_the_result(): void
    {
        [$session, $token] = $this->inProgress();
        $url = "/api/sessions/{$session->id}/results";

        $this->withToken($token)->postJson($url, $this->payload(['wrongAnswers' => [1]]))->assertStatus(201);
        $this->withToken($token)->postJson($url, $this->payload(['wrongAnswers' => [1, 2]]))->assertStatus(201);

        $this->assertSame(1, PuzzleResult::count());
        $this->assertSame([1, 2], PuzzleResult::first()->wrong_answers);
    }

    public function test_common_wrong_answers_are_counted_and_shown_to_the_instructor(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        foreach ([[5, 5], [5], [20]] as $wrong) {
            app("auth")->forgetGuards();
            [$session, $token] = $this->inProgress($instructor);
            $this->withToken($token)->postJson("/api/sessions/{$session->id}/results", $this->payload(['wrongAnswers' => $wrong]))->assertStatus(201);
        }

        $stuck = app(InsightsService::class)->classStuck($instructor);
        $this->assertSame(['answer' => '5', 'count' => 3], $stuck[0]['common_wrong'][0]);

        $this->actingAs($instructor)->get(route('instructor.dashboard'))
            ->assertOk()->assertSee('Common wrong answers');
    }
}
