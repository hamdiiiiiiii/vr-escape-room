<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\User;
use App\Services\HintTutorService;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HintTutorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoomSeeder::class);
    }

    private function play(string $status = 'in_progress'): array
    {
        $user = User::factory()->create(['role' => 'student']);
        $s = GameSession::create(['user_id' => $user->id, 'room_id' => 1, 'status' => $status, 'expires_at' => now()->addHour(), 'started_at' => now()]);

        return [$s, $user->createToken('session-'.$s->id)->plainTextToken];
    }

    private function ask(GameSession $s, string $token, array $body)
    {
        app('auth')->forgetGuards();

        return $this->withToken($token)->postJson("/api/sessions/{$s->id}/hint", $body);
    }

    public function test_without_ai_a_wrong_answer_gets_the_misconception_advice(): void
    {
        config(['services.anthropic.key' => null]);
        [$s, $t] = $this->play();

        $json = $this->ask($s, $t, ['puzzleId' => 'puzzle-obs-01', 'wrongAnswers' => [2]])->assertOk()->json();

        $this->assertSame('rules', $json['source']);
        $this->assertStringContainsString('comment', $json['hint']);
    }

    public function test_without_a_mistake_the_puzzle_hint_is_used_by_level(): void
    {
        [$s, $t] = $this->play();

        $this->assertStringContainsString('airlock rule', $this->ask($s, $t, ['puzzleId' => 'puzzle-obs-01', 'level' => 0])->json('hint'));
        $this->assertStringContainsString('9, 10, and 11', $this->ask($s, $t, ['puzzleId' => 'puzzle-obs-01', 'level' => 1])->json('hint'));
        $this->assertSame('puzzle', $this->ask($s, $t, ['puzzleId' => 'puzzle-obs-01'])->json('source'));
    }

    public function test_ai_hint_is_used_and_never_receives_the_answer_key(): void
    {
        config(['services.anthropic.key' => 'k', 'services.anthropic.model' => 'm']);
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'Look at what the comment promises, then ask which line really does that.']]])]);
        [$s, $t] = $this->play();

        $json = $this->ask($s, $t, ['puzzleId' => 'puzzle-obs-01', 'wrongAnswers' => [2]])->assertOk()->json();

        $this->assertSame('ai', $json['source']);
        Http::assertSent(fn ($r) => ! str_contains(json_encode($r->data()), 'buggyLineNumber') && ! str_contains(json_encode($r->data()), 'explanation'));
    }

    public function test_ai_hint_that_gives_the_answer_away_falls_back(): void
    {
        config(['services.anthropic.key' => 'k', 'services.anthropic.model' => 'm']);
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'The bug is on line 3, change the operator.']]])]);
        [$s, $t] = $this->play();

        $json = $this->ask($s, $t, ['puzzleId' => 'puzzle-obs-01', 'wrongAnswers' => [2]])->json();

        $this->assertSame('rules', $json['source']);
        $this->assertStringNotContainsString('line 3', $json['hint']);
    }

    public function test_safe_check_blocks_assembly_answers_and_bad_text(): void
    {
        $tutor = app(HintTutorService::class);
        $asm = ['answerKey' => ['input' => 10, 'expectedOutput' => 'UNLOCK']];

        $this->assertNull($tutor->safe('Pick 10 and expect UNLOCK to win.', $asm));
        $this->assertNotNull($tutor->safe('Think about the number where the rule changes.', $asm));
        $this->assertNull($tutor->safe('short', $asm));
        $this->assertNull($tutor->safe('{"hint": "this is json and long enough to pass length"}', $asm));
        $this->assertNull($tutor->safe(null, $asm));
    }

    public function test_api_failure_falls_back_to_rules(): void
    {
        config(['services.anthropic.key' => 'k', 'services.anthropic.model' => 'm']);
        Http::fake(['api.anthropic.com/*' => Http::response([], 500)]);
        [$s, $t] = $this->play();

        $this->assertSame('rules', $this->ask($s, $t, ['puzzleId' => 'puzzle-asm-02', 'wrongAnswers' => [5]])->assertOk()->json('source'));
    }

    public function test_access_rules(): void
    {
        [$s, $t] = $this->play();
        [$other] = $this->play();
        [$done, $doneToken] = $this->play('finished');

        $this->postJson("/api/sessions/{$s->id}/hint", ['puzzleId' => 'puzzle-obs-01'])->assertUnauthorized();
        $this->ask($other, $t, ['puzzleId' => 'puzzle-obs-01'])->assertForbidden();
        $this->ask($done, $doneToken, ['puzzleId' => 'puzzle-obs-01'])->assertStatus(409);
        $this->ask($s, $t, ['puzzleId' => 'nope'])->assertNotFound();
        $this->ask($s, $t, ['puzzleId' => 'puzzle-obs-01', 'wrongAnswers' => [1, 2, 3, 4]])->assertStatus(422);
    }
}
