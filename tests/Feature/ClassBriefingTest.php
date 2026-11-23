<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\PuzzleResult;
use App\Models\User;
use App\Services\ClassBriefingService;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClassBriefingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoomSeeder::class);
        Cache::flush();
    }

    private function classWithMistakes(): User
    {
        $inst = User::factory()->create(['role' => 'instructor']);
        foreach ([1, 2, 3] as $i) {
            $s = User::factory()->create(['role' => 'student', 'instructor_id' => $inst->id, 'name' => "Kid $i"]);
            $session = GameSession::create(['user_id' => $s->id, 'room_id' => 1, 'status' => 'finished', 'expires_at' => now()->addHour(), 'started_at' => now()]);
            foreach (['puzzle-obs-01', 'puzzle-asm-02'] as $pid) {
                PuzzleResult::create(['game_session_id' => $session->id, 'puzzle_id' => $pid, 'concept_tag' => 'Boundary Testing',
                    'solved' => false, 'attempts' => 3, 'time_seconds' => 90, 'hints_used' => 2, 'score' => 0,
                    'wrong_answers' => $pid === 'puzzle-obs-01' ? [2] : [5]]);
            }
        }

        return $inst;
    }

    public function test_empty_class_has_no_briefing(): void
    {
        $b = app(ClassBriefingService::class)->briefing(User::factory()->create(['role' => 'instructor']));

        $this->assertFalse($b['has_data']);
    }

    public function test_briefing_names_the_weak_concept_and_top_mistake(): void
    {
        $b = app(ClassBriefingService::class)->briefing($this->classWithMistakes());

        $this->assertTrue($b['has_data']);
        $this->assertStringContainsString('Boundary Testing', $b['headline']);
        $this->assertStringContainsString('3 of 3 students are weak in Boundary Testing', $b['points'][0]);
        $this->assertNotEmpty($b['attention']);
    }

    public function test_dashboard_shows_the_briefing_without_an_api_key(): void
    {
        config(['services.anthropic.key' => null]);

        $this->actingAs($this->classWithMistakes())->get(route('instructor.dashboard'))
            ->assertOk()->assertSee('Class briefing')->assertSee('Focus next on Boundary Testing');
    }

    public function test_ai_summary_is_written_cached_and_has_no_names(): void
    {
        config(['services.anthropic.key' => 'k', 'services.anthropic.model' => 'm']);
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text',
            'text' => 'Most of the class struggles with boundary testing. Reading comments instead of code is the common slip, so practise tracing code next lesson.']]])]);
        $inst = $this->classWithMistakes();

        $this->actingAs($inst)->post(route('instructor.class-briefing.write'))->assertSessionHas('briefing_ok');

        Http::assertSent(fn ($r) => ! str_contains(json_encode($r->data()), 'Kid 1'));
        $this->actingAs($inst)->get(route('instructor.dashboard'))->assertSee('AI_SUMMARY')->assertSee('Most of the class struggles');
    }

    public function test_bad_ai_summary_is_not_saved(): void
    {
        config(['services.anthropic.key' => 'k', 'services.anthropic.model' => 'm']);
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => '{"oops": true}']]])]);
        $inst = $this->classWithMistakes();

        $this->actingAs($inst)->post(route('instructor.class-briefing.write'))->assertSessionHas('briefing_error');
        $this->assertNull(app(ClassBriefingService::class)->cachedNarrative($inst));
    }

    public function test_no_key_gives_a_friendly_error_and_students_are_blocked(): void
    {
        config(['services.anthropic.key' => null]);
        Http::fake();

        $this->actingAs($this->classWithMistakes())->post(route('instructor.class-briefing.write'))->assertSessionHas('briefing_error');
        Http::assertNothingSent();
        $this->actingAs(User::factory()->create(['role' => 'student']))->post(route('instructor.class-briefing.write'))->assertForbidden();
    }
}
