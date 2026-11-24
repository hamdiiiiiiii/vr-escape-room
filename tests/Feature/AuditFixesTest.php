<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\PuzzleResult;
use App\Models\Report;
use App\Models\Room;
use App\Models\SessionEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFinishedSessions;
use Tests\TestCase;

class AuditFixesTest extends TestCase
{
    use RefreshDatabase, CreatesFinishedSessions;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->room = Room::create([
            'name' => 'Key Room', 'time_limit_seconds' => 600,
            'config' => ['puzzles' => [
                ['id' => 'obs', 'type' => 'observation', 'conceptTag' => 'Boundary Testing', 'answerKey' => ['buggyLineNumber' => 3]],
                ['id' => 'asm', 'type' => 'assembly', 'conceptTag' => 'Boundary Testing', 'answerKey' => ['input' => 10, 'expectedOutput' => 'UNLOCK']],
                ['id' => 'nokey', 'conceptTag' => 'Other'],
            ]],
        ]);
    }

    /** @return array{0: GameSession, 1: string, 2: User} */
    private function running(): array
    {
        $user = User::factory()->create(['role' => 'student']);
        $session = GameSession::create(['user_id' => $user->id, 'room_id' => $this->room->id, 'status' => 'in_progress', 'expires_at' => now()->addMinutes(10), 'started_at' => now()]);

        return [$session, $user->createToken('session-'.$session->id)->plainTextToken, $user];
    }

    private function payload(string $puzzle, array $extra = []): array
    {
        return array_merge(['puzzleId' => $puzzle, 'conceptTag' => 'Boundary Testing', 'solved' => true, 'attempts' => 1, 'timeSeconds' => 30, 'hintsUsed' => 0], $extra);
    }

    // ---------- 1. the server checks answers

    public function test_wrong_answer_overrides_a_claimed_win(): void
    {
        [$s, $token] = $this->running();
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/results", $this->payload('obs', ['answer' => 4]))
            ->assertCreated()->assertJsonPath('solved', false)->assertJsonPath('verified', true)->assertJsonPath('score', 0);
        $this->assertFalse(PuzzleResult::first()->solved);
    }

    public function test_correct_answers_are_accepted_for_both_puzzle_types(): void
    {
        [$s, $token] = $this->running();
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/results", $this->payload('obs', ['answer' => 3]))
            ->assertCreated()->assertJsonPath('solved', true)->assertJsonPath('score', 100);
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/results", $this->payload('asm', ['answer' => ['input' => '10', 'expectedOutput' => 'unlock']]))
            ->assertCreated()->assertJsonPath('solved', true)->assertJsonPath('verified', true);
    }

    public function test_without_an_answer_old_clients_still_work_but_are_not_verified(): void
    {
        [$s, $token] = $this->running();
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/results", $this->payload('obs'))
            ->assertCreated()->assertJsonPath('solved', true)->assertJsonPath('verified', false);
    }

    public function test_strict_mode_requires_an_answer_when_the_puzzle_has_a_key(): void
    {
        config(['bughunt.require_answer' => true]);
        [$s, $token] = $this->running();
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/results", $this->payload('obs'))->assertStatus(422);
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/results", $this->payload('nokey'))->assertCreated();
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/results", $this->payload('obs', ['answer' => 3]))->assertCreated();
    }

    public function test_oversized_answer_is_rejected(): void
    {
        [$s, $token] = $this->running();
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/results", $this->payload('obs', ['answer' => str_repeat('a', 300)]))->assertStatus(422);
    }

    // ---------- 3. events

    public function test_game_can_send_events_and_they_are_stored(): void
    {
        [$s, $token] = $this->running();
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/events", ['events' => [
            ['type' => 'room_entered'],
            ['type' => 'puzzle_started', 'puzzleId' => 'obs', 'payload' => ['pedestal' => 2]],
        ]])->assertCreated()->assertJsonPath('stored', 2);
        $this->assertSame(2, SessionEvent::where('game_session_id', $s->id)->count());
    }

    public function test_events_reject_unknown_types_future_clock_and_big_batches(): void
    {
        [$s, $token] = $this->running();
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/events", ['events' => [['type' => 'hack']]])->assertStatus(422);
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/events", ['events' => array_fill(0, 51, ['type' => 'room_entered'])])->assertStatus(422);
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/events", ['events' => [['type' => 'door_opened', 'occurredAt' => now()->addYear()->toIso8601String()]]])->assertCreated();
        $this->assertTrue(SessionEvent::first()->occurred_at->lessThanOrEqualTo(now()->addSecond()));
    }

    public function test_events_need_the_right_token_and_a_running_session(): void
    {
        [$s, $token] = $this->running();
        [$other, $otherToken] = $this->running();
        $this->app['auth']->forgetGuards();
        $this->withToken($otherToken)->postJson("/api/sessions/{$s->id}/events", ['events' => [['type' => 'room_entered']]])->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->postJson("/api/sessions/{$s->id}/events", ['events' => [['type' => 'room_entered']]])->assertUnauthorized();
        $s->update(['status' => 'finished']);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/events", ['events' => [['type' => 'room_entered']]])->assertStatus(409);
    }

    public function test_results_and_end_are_logged_automatically(): void
    {
        [$s, $token] = $this->running();
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/results", $this->payload('obs', ['answer' => 3]))->assertCreated();
        $this->withToken($token)->postJson("/api/sessions/{$s->id}/end", ['reason' => 'escaped'])->assertOk();
        $types = SessionEvent::where('game_session_id', $s->id)->pluck('type')->all();
        $this->assertContains('answer_checked', $types);
        $this->assertContains('session_ended', $types);
    }

    // ---------- 2. AI on the report page + timeline

    public function test_report_shows_the_saved_ai_analysis_and_timeline(): void
    {
        $instructor = $this->makeInstructor();
        $student = $this->makeStudent($instructor);
        $session = $this->makeFinishedSession($student);
        Report::where('game_session_id', $session->id)->update(['ai_analysis' => json_encode([
            'overallLevel' => 'Proficient', 'weakConcepts' => ['Boundary Testing'],
            'studentRecommendation' => 'Try the edge numbers first.', 'instructorNote' => 'Needs boundary practice.',
        ])]);
        SessionEvent::record($session, 'hint_requested', 'p1', ['level' => 0]);

        $this->actingAs($student)->get(route('reports.show', $session->id))->assertOk()
            ->assertSee('Try the edge numbers first.')->assertDontSee('Needs boundary practice.')
            ->assertSee('Boundary Testing')->assertSee('hint requested')->assertDontSee('AI Analysis Not Available Yet');
        $this->actingAs($instructor)->get(route('reports.show', $session->id))->assertOk()
            ->assertSee('Needs boundary practice.')->assertSee('Print / Save as PDF');
    }

    public function test_report_without_ai_shows_a_friendly_message(): void
    {
        $student = $this->makeStudent();
        $session = $this->makeFinishedSession($student);
        $this->actingAs($student)->get(route('reports.show', $session->id))->assertOk()->assertSee('No AI analysis for this run');
    }

    // ---------- 4. gradebook export

    public function test_instructor_downloads_a_csv_of_only_their_own_students(): void
    {
        $instructor = $this->makeInstructor();
        $mine = $this->makeStudent($instructor, 'Mine Student');
        $evil = $this->makeStudent($instructor, '=HYPERLINK("http://x")');
        $foreign = $this->makeStudent(null, 'Foreign Student');
        $this->makeFinishedSession($mine, 88);
        $this->makeFinishedSession($evil, 40);
        $this->makeFinishedSession($foreign, 99);

        $response = $this->actingAs($instructor)->get(route('instructor.export.gradebook'));
        $response->assertOk()->assertHeader('content-disposition');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Mine Student', $csv);
        $this->assertStringNotContainsString('Foreign Student', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);   // formula neutralised
        $this->assertStringContainsString('Score', $csv);
    }

    public function test_students_and_guests_cannot_export(): void
    {
        $this->get(route('instructor.export.gradebook'))->assertRedirect(route('login'));
        $this->actingAs($this->makeStudent())->get(route('instructor.export.gradebook'))->assertForbidden();
    }

    public function test_dashboard_has_the_export_button(): void
    {
        $this->actingAs($this->makeInstructor())->get(route('instructor.dashboard'))->assertOk()->assertSee('Export gradebook');
    }
}
