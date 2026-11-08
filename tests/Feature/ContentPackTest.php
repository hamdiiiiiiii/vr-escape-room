<?php

namespace Tests\Feature;

use App\Services\RoomService;
use Tests\TestCase;

class ContentPackTest extends TestCase
{
    /**
     * Helper to load and decode the demo-room.json file.
     *
     * @return array<string, mixed>
     */
    protected function loadDemoRoom(): array
    {
        $path = base_path('content/demo-room.json');
        $this->assertFileExists($path, 'The content/demo-room.json file must exist.');

        $content = file_get_contents($path);
        $this->assertNotFalse($content, 'Unable to read content/demo-room.json.');

        $data = json_decode($content, true);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), 'content/demo-room.json must be valid JSON: ' . json_last_error_msg());
        $this->assertIsArray($data, 'Decoded JSON must be an array.');

        return $data;
    }

    public function test_demo_room_json_file_exists_and_is_valid_json(): void
    {
        $data = $this->loadDemoRoom();
        $this->assertNotEmpty($data);
    }

    public function test_demo_room_has_all_required_top_level_fields(): void
    {
        $data = $this->loadDemoRoom();

        $requiredFields = [
            'roomId',
            'name',
            'description',
            'timeLimitSeconds',
            'puzzleOrder',
            'puzzles',
        ];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $data, "Top-level JSON is missing required field: '{$field}'.");
            $this->assertNotEmpty($data[$field], "Top-level field '{$field}' must not be empty.");
        }
    }

    public function test_demo_room_has_positive_time_limit_consistent_with_room_service(): void
    {
        $data = $this->loadDemoRoom();

        $this->assertIsInt($data['timeLimitSeconds']);
        $this->assertGreaterThan(0, $data['timeLimitSeconds']);

        // Room 1 (The Boundary Maze) has time_limit_minutes: 15 (900 seconds)
        $roomService = new RoomService();
        $room = $roomService->findRoom($data['roomId']);

        $this->assertNotNull($room, "Room with ID {$data['roomId']} must exist in RoomService.");
        $this->assertEquals($room['time_limit_minutes'] * 60, $data['timeLimitSeconds']);
    }

    public function test_demo_room_contains_exactly_two_puzzles(): void
    {
        $data = $this->loadDemoRoom();

        $this->assertIsArray($data['puzzles']);
        $this->assertCount(2, $data['puzzles'], 'Demo room must contain exactly two puzzles.');
    }

    public function test_puzzle_order_matches_puzzle_ids_uniquely(): void
    {
        $data = $this->loadDemoRoom();

        $this->assertIsArray($data['puzzleOrder']);
        $this->assertCount(2, $data['puzzleOrder']);

        $puzzleIds = array_column($data['puzzles'], 'id');

        // IDs must be unique
        $this->assertEquals(count($puzzleIds), count(array_unique($puzzleIds)), 'Puzzle IDs must be unique.');

        // Order array must match the puzzle IDs in order
        $this->assertEquals($puzzleIds, $data['puzzleOrder']);
    }

    public function test_demo_room_has_one_observation_and_one_assembly_puzzle(): void
    {
        $data = $this->loadDemoRoom();

        $types = array_column($data['puzzles'], 'type');

        $this->assertContains('observation', $types, 'Room must contain an observation puzzle.');
        $this->assertContains('assembly', $types, 'Room must contain an assembly puzzle.');
        $this->assertEquals(['observation', 'assembly'], $types, 'First puzzle must be observation, second must be assembly.');
    }

    public function test_each_puzzle_has_exactly_one_nonempty_concept_tag(): void
    {
        $data = $this->loadDemoRoom();

        foreach ($data['puzzles'] as $index => $puzzle) {
            $this->assertArrayHasKey('conceptTag', $puzzle, "Puzzle at index {$index} must have conceptTag.");
            $this->assertIsString($puzzle['conceptTag']);
            $this->assertNotEmpty(trim($puzzle['conceptTag']), "Puzzle at index {$index} conceptTag must not be blank.");
            $this->assertEquals('Boundary Testing', $puzzle['conceptTag']);
        }
    }

    public function test_each_puzzle_has_exactly_two_nonempty_hints_encouraging_boundary_testing(): void
    {
        $data = $this->loadDemoRoom();

        foreach ($data['puzzles'] as $index => $puzzle) {
            $this->assertArrayHasKey('hints', $puzzle, "Puzzle at index {$index} must have hints array.");
            $this->assertIsArray($puzzle['hints']);
            $this->assertCount(2, $puzzle['hints'], "Puzzle at index {$index} must have exactly two hints.");

            foreach ($puzzle['hints'] as $hintIndex => $hint) {
                $this->assertIsString($hint);
                $this->assertNotEmpty(trim($hint), "Hint {$hintIndex} on puzzle index {$index} must not be blank.");
            }
        }
    }

    public function test_each_puzzle_has_nonempty_prompt_and_explanation(): void
    {
        $data = $this->loadDemoRoom();

        foreach ($data['puzzles'] as $index => $puzzle) {
            $this->assertArrayHasKey('prompt', $puzzle);
            $this->assertIsString($puzzle['prompt']);
            $this->assertNotEmpty(trim($puzzle['prompt']));

            $this->assertArrayHasKey('explanation', $puzzle);
            $this->assertIsString($puzzle['explanation']);
            $this->assertNotEmpty(trim($puzzle['explanation']));
        }
    }

    public function test_observation_puzzle_has_valid_type_specific_data_and_answer_key(): void
    {
        $data = $this->loadDemoRoom();

        $observation = $data['puzzles'][0];
        $this->assertEquals('observation', $observation['type']);

        // Must have type-specific data object
        $this->assertArrayHasKey('data', $observation);
        $this->assertIsArray($observation['data']);
        $this->assertArrayHasKey('codeSnippet', $observation['data']);
        $this->assertIsArray($observation['data']['codeSnippet']);
        $this->assertNotEmpty($observation['data']['codeSnippet']);

        $lineNumbers = array_column($observation['data']['codeSnippet'], 'lineNumber');
        $this->assertNotEmpty($lineNumbers);

        // Must have answerKey referencing a valid line
        $this->assertArrayHasKey('answerKey', $observation);
        $this->assertArrayHasKey('buggyLineNumber', $observation['answerKey']);
        $this->assertContains(
            $observation['answerKey']['buggyLineNumber'],
            $lineNumbers,
            'Observation answerKey buggyLineNumber must exist in the codeSnippet line numbers.'
        );

        $this->assertEquals(3, $observation['answerKey']['buggyLineNumber']);
    }

    public function test_assembly_puzzle_has_valid_type_specific_data_and_answer_key(): void
    {
        $data = $this->loadDemoRoom();

        $assembly = $data['puzzles'][1];
        $this->assertEquals('assembly', $assembly['type']);

        // Must have type-specific data object
        $this->assertArrayHasKey('data', $assembly);
        $this->assertIsArray($assembly['data']);
        $this->assertArrayHasKey('options', $assembly['data']);
        $this->assertArrayHasKey('inputs', $assembly['data']['options']);
        $this->assertArrayHasKey('expectedOutputs', $assembly['data']['options']);

        $inputs = $assembly['data']['options']['inputs'];
        $outputs = $assembly['data']['options']['expectedOutputs'];

        $this->assertIsArray($inputs);
        $this->assertIsArray($outputs);
        $this->assertNotEmpty($inputs);
        $this->assertNotEmpty($outputs);

        // Must have answerKey referencing selectable options
        $this->assertArrayHasKey('answerKey', $assembly);
        $this->assertArrayHasKey('input', $assembly['answerKey']);
        $this->assertArrayHasKey('expectedOutput', $assembly['answerKey']);

        $this->assertContains(
            $assembly['answerKey']['input'],
            $inputs,
            'Assembly answerKey input must exist in the candidate inputs.'
        );
        $this->assertContains(
            $assembly['answerKey']['expectedOutput'],
            $outputs,
            'Assembly answerKey expectedOutput must exist in the candidate outputs.'
        );

        $this->assertEquals(10, $assembly['answerKey']['input']);
        $this->assertEquals('UNLOCK', $assembly['answerKey']['expectedOutput']);
    }
}

