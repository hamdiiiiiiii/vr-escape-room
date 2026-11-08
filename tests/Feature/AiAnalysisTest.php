<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\Report;
use App\Models\Room;
use App\Models\User;
use App\Services\AiAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private array $summary = [
        'sessionScore' => 68,
        'level' => 'Proficient',
        'endReason' => 'escaped',
        'concepts' => [
            ['conceptTag' => 'bug-spotting', 'score' => 100],
            ['conceptTag' => 'test-case', 'score' => 35],
        ],
        'puzzles' => [],
    ];

    private function withKey(): void
    {
        config(['services.anthropic.key' => 'test-key', 'services.anthropic.model' => 'test-model']);
    }

    private function reply(array|string $content): array
    {
        $text = is_array($content) ? json_encode($content) : $content;

        return ['content' => [['type' => 'text', 'text' => $text]]];
    }

    private function good(): array
    {
        return [
            'overallLevel' => 'Proficient',
            'weakConcepts' => ['test-case'],
            'studentRecommendation' => 'Great job! Practice test cases next.',
            'instructorNote' => 'Needed a hint on test-case.',
        ];
    }

    public function test_valid_reply_is_returned(): void
    {
        $this->withKey();
        Http::fake(['*' => Http::response($this->reply($this->good()))]);

        $result = (new AiAnalysisService())->analyze($this->summary);

        $this->assertSame(['test-case'], $result['weakConcepts']);
        $this->assertSame('Proficient', $result['overallLevel']);
    }

    public function test_unknown_concept_makes_reply_invalid(): void
    {
        $this->withKey();
        $bad = $this->good();
        $bad['weakConcepts'] = ['made-up'];
        Http::fake(['*' => Http::response($this->reply($bad))]);

        $this->assertNull((new AiAnalysisService())->analyze($this->summary));
    }

    public function test_non_json_reply_is_discarded(): void
    {
        $this->withKey();
        Http::fake(['*' => Http::response($this->reply('Sorry, I cannot help.'))]);

        $this->assertNull((new AiAnalysisService())->analyze($this->summary));
    }

    public function test_server_error_returns_null(): void
    {
        $this->withKey();
        Http::fake(['*' => Http::response('error', 500)]);

        $this->assertNull((new AiAnalysisService())->analyze($this->summary));
    }

    public function test_missing_key_skips_the_call(): void
    {
        config(['services.anthropic.key' => null, 'services.anthropic.model' => null]);
        Http::fake();

        $this->assertNull((new AiAnalysisService())->analyze($this->summary));
        Http::assertNothingSent();
    }

    public function test_ending_a_session_stores_the_analysis(): void
    {
        $this->withKey();
        Http::fake(['*' => Http::response($this->reply($this->good()))]);

        [$session, $token] = $this->inProgress();

        $this->withToken($token)
            ->postJson("/api/sessions/{$session->id}/results", [
                'puzzleId' => 'p1', 'conceptTag' => 'test-case', 'solved' => true,
                'attempts' => 2, 'timeSeconds' => 60, 'hintsUsed' => 1,
            ])->assertStatus(201);

        $this->withToken($token)
            ->postJson("/api/sessions/{$session->id}/end", ['reason' => 'escaped'])
            ->assertOk();

        $report = Report::where('game_session_id', $session->id)->first();
        $this->assertSame('Great job! Practice test cases next.', $report->ai_analysis['studentRecommendation']);
    }

    public function test_session_still_ends_when_ai_fails(): void
    {
        $this->withKey();
        Http::fake(['*' => Http::response('boom', 500)]);

        [$session, $token] = $this->inProgress();

        $this->withToken($token)
            ->postJson("/api/sessions/{$session->id}/end", ['reason' => 'timed_out'])
            ->assertOk()
            ->assertJsonPath('status', 'finished');

        $this->assertNull(Report::where('game_session_id', $session->id)->first()->ai_analysis);
    }

    /** @return array{0: GameSession, 1: string} */
    private function inProgress(): array
    {
        $room = Room::create([
            'name' => 'Test Room',
            'time_limit_seconds' => 600,
            'config' => ['puzzles' => [['id' => 'p1', 'conceptTag' => 'test-case']]],
        ]);
        $user = User::factory()->create(['role' => 'student']);
        $session = GameSession::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'in_progress',
            'expires_at' => now()->addMinutes(10),
            'started_at' => now(),
        ]);

        return [$session, $user->createToken('session-'.$session->id)->plainTextToken];
    }
}