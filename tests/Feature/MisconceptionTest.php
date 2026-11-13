<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\PuzzleResult;
use App\Models\Room;
use App\Models\User;
use App\Services\InsightsService;
use App\Services\MisconceptionService;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MisconceptionTest extends TestCase
{
    use RefreshDatabase;

    private function makeResult(string $puzzle, array $wrong, ?User $student = null): PuzzleResult
    {
        $student ??= User::factory()->create(['role' => 'student']);
        $session = GameSession::create([
            'user_id' => $student->id, 'room_id' => 1, 'status' => 'finished',
            'expires_at' => now()->addMinutes(10), 'started_at' => now(),
        ]);

        return PuzzleResult::create([
            'game_session_id' => $session->id, 'puzzle_id' => $puzzle, 'concept_tag' => 'Boundary Testing',
            'solved' => true, 'attempts' => 2, 'time_seconds' => 40, 'hints_used' => 0, 'score' => 50,
            'wrong_answers' => $wrong,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoomSeeder::class);
    }

    private function codes(PuzzleResult $r): array
    {
        return app(MisconceptionService::class)->forResult($r);
    }

    public function test_observation_wrong_lines_are_named(): void
    {
        // Demo puzzle: line 1 def, 2 comment, 3 buggy if, 4 return UNLOCK, 5 return LOCKED
        $this->assertSame(['looks_at_name'], $this->codes($this->makeResult('puzzle-obs-01', [1])));
        $this->assertSame(['reads_comment'], $this->codes($this->makeResult('puzzle-obs-01', ['line 2'])));
        $this->assertSame(['looks_at_result'], $this->codes($this->makeResult('puzzle-obs-01', [5])));
        $this->assertSame(['looks_at_result'], $this->codes($this->makeResult('puzzle-obs-01', [['line' => 4]])));
        $this->assertSame([], $this->codes($this->makeResult('puzzle-obs-01', [3])));   // that is the right line
        $this->assertSame(['unsure_where_decision'], $this->codes($this->makeResult('puzzle-obs-01', [99])));
    }

    public function test_assembly_wrong_picks_are_named(): void
    {
        $this->assertSame(['tests_typical_value'], $this->codes($this->makeResult('puzzle-asm-02', [5])));
        $this->assertSame(['tests_typical_value'], $this->codes($this->makeResult('puzzle-asm-02', [['input' => 20, 'expectedOutput' => 'UNLOCK']])));
        $this->assertSame(['inclusive_exclusive'], $this->codes($this->makeResult('puzzle-asm-02', ['LOCKED'])));
        $this->assertSame(['inclusive_exclusive'], $this->codes($this->makeResult('puzzle-asm-02', [['input' => 10, 'expectedOutput' => 'LOCKED']])));
        $this->assertSame([], $this->codes($this->makeResult('puzzle-asm-02', [['input' => 10, 'expectedOutput' => 'UNLOCK']])));
    }

    public function test_unknown_puzzles_and_missing_answers_give_nothing(): void
    {
        $this->assertSame([], $this->codes($this->makeResult('no-such-puzzle', [1])));
        $this->assertSame([], $this->codes($this->makeResult('puzzle-obs-01', [])));
        $this->assertSame([], $this->codes($this->makeResult('puzzle-obs-01', ['banana'])));
    }

    public function test_detect_counts_and_sorts(): void
    {
        $results = collect([
            $this->makeResult('puzzle-obs-01', [5]),
            $this->makeResult('puzzle-obs-01', [4]),
            $this->makeResult('puzzle-asm-02', [5]),
        ]);

        $found = app(MisconceptionService::class)->detect($results);

        $this->assertSame('looks_at_result', $found[0]['code']);
        $this->assertSame(2, $found[0]['count']);
        $this->assertSame(1, $found[1]['count']);
        $this->assertNotEmpty($found[0]['advice']);
    }

    public function test_student_profile_and_api_payload_include_misconceptions(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->makeResult('puzzle-obs-01', [2], $student);
        // The profile only counts finished sessions that have a report.
        \App\Models\Report::create(['game_session_id' => GameSession::first()->id, 'session_score' => 50, 'level' => 'Needs support',
            'total_time_seconds' => 40, 'concept_scores' => []]);

        $payload = app(InsightsService::class)->weakPointsPayload($student);

        $this->assertSame('reads_comment', $payload['misconceptions'][0]['code']);
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk()->assertSee('Mistakes to fix')->assertSee('Reads the comment instead of the code');
    }

    public function test_instructor_sees_class_misconceptions(): void
    {
        $inst = User::factory()->create(['role' => 'instructor']);
        foreach ([[2], [2], [5]] as $w) {
            $this->makeResult('puzzle-obs-01', $w, User::factory()->create(['role' => 'student', 'instructor_id' => $inst->id]));
        }

        $top = app(InsightsService::class)->classMisconceptions($inst);
        $this->assertSame(['reads_comment', 2], [$top[0]['code'], $top[0]['count']]);

        $this->actingAs($inst)->get(route('instructor.dashboard'))->assertOk()->assertSee('Common misconceptions')->assertSee('Reads the comment instead of the code');
    }
}
