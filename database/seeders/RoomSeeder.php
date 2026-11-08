<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        Room::updateOrCreate(
            ['name' => 'Testing Basics'],
            [
                'time_limit_seconds' => 600,
                'config' => [
                    'roomId' => 1,
                    'name' => 'Testing Basics',
                     'description' => 'Learn to spot bugs and build test cases.',
                 'conceptTag' => 'Bug spotting & test cases',
                    'timeLimitSeconds' => 600,
                    'puzzles' => [
                        [
                            'id' => 'p1',
                            'type' => 'observation',
                            'conceptTag' => 'bug-spotting',
                            'prompt' => 'Find the line with the bug.',
                            'hints' => ['Look at the loop.', 'Check the last index.'],
                            'explanation' => 'The loop runs one step too far.',
                            'data' => ['buggyLine' => 3],
                        ],
                        [
                            'id' => 'p2',
                            'type' => 'assembly',
                            'conceptTag' => 'test-case',
                            'prompt' => 'Build a test case.',
                            'hints' => ['Pick an input first.', 'Match the expected result.'],
                            'explanation' => 'A test case needs an input and an expected result.',
                            'data' => ['input' => '5', 'expected' => '25'],
                        ],
                    ],
                ],
            ]
        );
    }
}