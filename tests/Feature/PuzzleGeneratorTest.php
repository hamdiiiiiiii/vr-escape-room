<?php

namespace Tests\Feature;

use App\Models\GeneratedPuzzle;
use App\Models\User;
use App\Services\PuzzleGeneratorService;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PuzzleGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private const CONCEPT = 'Boundary Testing';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoomSeeder::class);
        config(['services.anthropic.key' => 'test-key', 'services.anthropic.model' => 'test-model']);
    }

    private function observation(array $over = []): array
    {
        return array_replace_recursive([
            'type' => 'observation', 'conceptTag' => self::CONCEPT,
            'prompt' => 'The bridge opens for weights of 50 or less. Which line is wrong?',
            'data' => ['codeSnippet' => [
                ['lineNumber' => 1, 'text' => 'def can_cross(weight):'],
                ['lineNumber' => 2, 'text' => '    # opens at 50 or less'],
                ['lineNumber' => 3, 'text' => '    if weight < 50:'],
                ['lineNumber' => 4, 'text' => '        return "OPEN"'],
                ['lineNumber' => 5, 'text' => '    return "CLOSED"'],
            ]],
            'hints' => ['Test the weight 50 itself.', 'Is < the right operator for "or less"?'],
            'answerKey' => ['buggyLineNumber' => 3],
            'explanation' => 'Line 3 should use <= so a weight of exactly 50 opens the bridge.',
        ], $over);
    }

    private function assembly(array $over = []): array
    {
        return array_replace_recursive([
            'type' => 'assembly', 'conceptTag' => self::CONCEPT,
            'prompt' => 'Build a boundary test for the bridge that opens at 50 or less.',
            'data' => ['options' => ['inputs' => [10, 50, 80], 'expectedOutputs' => ['OPEN', 'CLOSED']]],
            'hints' => ['The boundary is where the rule changes.', 'At exactly 50 the bridge opens.'],
            'answerKey' => ['input' => 50, 'expectedOutput' => 'OPEN'],
            'explanation' => 'Testing 50 proves the edge of the rule works.',
        ], $over);
    }

    private function fake(array $puzzle): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => json_encode($puzzle)]]])]);
    }

    private function instructor(): User
    {
        return User::factory()->create(['role' => 'instructor']);
    }

    public function test_valid_puzzles_pass_the_checks(): void
    {
        $g = app(PuzzleGeneratorService::class);

        $o = $g->validate(json_encode($this->observation()), self::CONCEPT, 'observation');
        $this->assertSame(3, $o['answerKey']['buggyLineNumber']);
        $this->assertStringStartsWith('gen-', $o['id']);

        $a = $g->validate("```json\n".json_encode($this->assembly())."\n```", self::CONCEPT, 'assembly');
        $this->assertSame(50, $a['answerKey']['input']);
    }

    public function test_bad_puzzles_are_rejected(): void
    {
        $g = app(PuzzleGeneratorService::class);
        $bad = [
            'not json' => ['not json at all', 'observation'],
            'wrong concept' => [json_encode($this->observation(['conceptTag' => 'Other'])), 'observation'],
            'wrong type' => [json_encode($this->assembly()), 'observation'],
            'bug line out of range' => [json_encode($this->observation(['answerKey' => ['buggyLineNumber' => 9]])), 'observation'],
            'bug line is a comment' => [json_encode($this->observation(['answerKey' => ['buggyLineNumber' => 2]])), 'observation'],
            'one hint only' => [json_encode(['hints' => ['x']] + $this->observation()), 'observation'],
            'answer not an option' => [json_encode($this->assembly(['answerKey' => ['input' => 7]])), 'assembly'],
            'output not an option' => [json_encode($this->assembly(['answerKey' => ['expectedOutput' => 'MAYBE']])), 'assembly'],
            'duplicate options' => [json_encode($this->assembly(['data' => ['options' => ['inputs' => [50, 50, 80]]]])), 'assembly'],
        ];

        foreach ($bad as $name => [$text, $type]) {
            $this->assertNull($g->validate($text, self::CONCEPT, $type), $name);
        }
    }

    public function test_instructor_generates_a_pending_puzzle(): void
    {
        $this->fake($this->observation());
        $instructor = $this->instructor();

        $this->actingAs($instructor)->post(route('instructor.generated-puzzles.store'), [
            'concept' => self::CONCEPT, 'type' => 'observation', 'difficulty' => 'same',
        ])->assertRedirect()->assertSessionHas('ai_ok');

        $p = GeneratedPuzzle::first();
        $this->assertSame('pending', $p->status);
        $this->assertSame($instructor->id, $p->requested_by);
    }

    public function test_only_concept_and_mistakes_are_sent_to_the_ai(): void
    {
        $this->fake($this->observation());
        $instructor = $this->instructor();
        $student = User::factory()->create(['role' => 'student', 'instructor_id' => $instructor->id, 'name' => 'Secret Kid', 'email' => 'secret@kid.test']);

        $this->actingAs($instructor)->post(route('instructor.generated-puzzles.store'), [
            'concept' => self::CONCEPT, 'type' => 'observation', 'difficulty' => 'easier', 'student_id' => $student->id,
        ])->assertSessionHas('ai_ok');

        Http::assertSent(function ($request) use ($student) {
            $body = json_encode($request->data());

            return ! str_contains($body, 'Secret Kid') && ! str_contains($body, 'secret@kid.test')
                && str_contains($body, self::CONCEPT) && $student->id !== 0;
        });
    }

    public function test_bad_ai_reply_saves_nothing(): void
    {
        $this->fake($this->observation(['answerKey' => ['buggyLineNumber' => 99]]));

        $this->actingAs($this->instructor())->post(route('instructor.generated-puzzles.store'), [
            'concept' => self::CONCEPT, 'type' => 'observation', 'difficulty' => 'same',
        ])->assertSessionHas('ai_error');

        $this->assertSame(0, GeneratedPuzzle::count());
    }

    public function test_no_api_key_falls_back_without_error(): void
    {
        config(['services.anthropic.key' => null]);
        Http::fake();

        $this->actingAs($this->instructor())->post(route('instructor.generated-puzzles.store'), [
            'concept' => self::CONCEPT, 'type' => 'observation', 'difficulty' => 'same',
        ])->assertSessionHas('ai_error');

        Http::assertNothingSent();
        $this->actingAs($this->instructor())->get(route('instructor.content-pack'))->assertOk()->assertSee('not set up yet');
    }

    public function test_api_failure_saves_nothing(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response([], 500)]);

        $this->actingAs($this->instructor())->post(route('instructor.generated-puzzles.store'), [
            'concept' => self::CONCEPT, 'type' => 'assembly', 'difficulty' => 'same',
        ])->assertSessionHas('ai_error');

        $this->assertSame(0, GeneratedPuzzle::count());
    }

    public function test_cannot_generate_for_another_instructors_student_or_unknown_concept(): void
    {
        $this->fake($this->observation());
        $other = User::factory()->create(['role' => 'student', 'instructor_id' => $this->instructor()->id]);
        $me = $this->instructor();

        $this->actingAs($me)->post(route('instructor.generated-puzzles.store'), [
            'concept' => self::CONCEPT, 'type' => 'observation', 'difficulty' => 'same', 'student_id' => $other->id,
        ])->assertForbidden();

        $this->actingAs($me)->post(route('instructor.generated-puzzles.store'), [
            'concept' => 'Made Up', 'type' => 'observation', 'difficulty' => 'same',
        ])->assertSessionHasErrors('concept');
    }

    public function test_approve_and_reject_only_by_the_owner(): void
    {
        $owner = $this->instructor();
        $p = GeneratedPuzzle::create(['requested_by' => $owner->id, 'concept_tag' => self::CONCEPT, 'type' => 'assembly',
            'difficulty' => 'same', 'content' => $this->assembly(), 'status' => 'pending']);

        $this->actingAs($this->instructor())->post(route('instructor.generated-puzzles.approve', $p->id))->assertForbidden();
        $this->assertSame('pending', $p->fresh()->status);

        $this->actingAs($owner)->post(route('instructor.generated-puzzles.approve', $p->id))->assertRedirect();
        $this->assertSame('approved', $p->fresh()->status);

        $this->actingAs($owner)->post(route('instructor.generated-puzzles.reject', $p->id));
        $this->assertSame('rejected', $p->fresh()->status);
    }

    public function test_students_cannot_use_the_generator(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->post(route('instructor.generated-puzzles.store'), [])->assertForbidden();
    }

    public function test_api_returns_only_approved_puzzles_for_the_student(): void
    {
        $inst = $this->instructor();
        $student = User::factory()->create(['role' => 'student', 'instructor_id' => $inst->id]);
        $make = fn ($status, $studentId = null) => GeneratedPuzzle::create(['requested_by' => $inst->id, 'student_id' => $studentId,
            'concept_tag' => self::CONCEPT, 'type' => 'assembly', 'difficulty' => 'same', 'content' => $this->assembly(), 'status' => $status]);
        $make('approved', $student->id);
        $make('approved');                 // whole class
        $make('pending', $student->id);
        $make('rejected', $student->id);

        $json = $this->actingAs($student)->getJson(route('api.students.puzzles', $student->id))->assertOk()->json();

        $this->assertCount(2, $json['generatedPuzzles']);
        $this->assertArrayHasKey('recommendedExisting', $json);

        $this->actingAs(User::factory()->create(['role' => 'student']))
            ->getJson(route('api.students.puzzles', $student->id))->assertForbidden();
    }
}
