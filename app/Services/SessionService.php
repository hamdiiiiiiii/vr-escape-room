<?php

// STUB: replace with Member 5's API/database later.

namespace App\Services;

use App\Models\User;

class SessionService
{
    /**
     * Get past escape room sessions for a specific student.
     *
     * @param User $user
     * @return array<int, array{id: int, room_id: int, room_name: string, date: string, status: string, score: int|null}>
     */
    public function getPastSessions(User $user): array
    {
        // One student (student5@test.com or modulo 0) has an empty session history.
        if ($user->email === 'student5@test.com' || ($user->id > 0 && $user->id % 5 === 0)) {
            return [];
        }

        // Student 2 has 1 finished and 1 waiting session
        if ($user->email === 'student2@test.com' || $user->id % 5 === 2) {
            return [
                [
                    'id' => 201,
                    'room_id' => 2,
                    'room_name' => 'Equivalence Chamber',
                    'date' => 'Nov 06, 2026',
                    'status' => 'finished',
                    'score' => 88,
                ],
                [
                    'id' => 202,
                    'room_id' => 1,
                    'room_name' => 'The Boundary Maze',
                    'date' => 'Nov 07, 2026',
                    'status' => 'waiting',
                    'score' => null,
                ],
            ];
        }

        // Student 3 has only a waiting session
        if ($user->email === 'student3@test.com' || $user->id % 5 === 3) {
            return [
                [
                    'id' => 301,
                    'room_id' => 1,
                    'room_name' => 'The Boundary Maze',
                    'date' => 'Nov 04, 2026',
                    'status' => 'waiting',
                    'score' => null,
                ],
            ];
        }

        // Student 4 has a perfect score on Assertion Vault
        if ($user->email === 'student4@test.com' || $user->id % 5 === 4) {
            return [
                [
                    'id' => 401,
                    'room_id' => 3,
                    'room_name' => 'The Assertion Vault',
                    'date' => 'Nov 07, 2026',
                    'status' => 'finished',
                    'score' => 100,
                ],
            ];
        }

        // Default student (e.g. Student 1 / modulo 1) has 2 finished and 1 in_progress session
        return [
            [
                'id' => 101,
                'room_id' => 1,
                'room_name' => 'The Boundary Maze',
                'date' => 'Nov 05, 2026',
                'status' => 'finished',
                'score' => 92,
            ],
            [
                'id' => 102,
                'room_id' => 2,
                'room_name' => 'Equivalence Chamber',
                'date' => 'Nov 06, 2026',
                'status' => 'finished',
                'score' => 85,
            ],
            [
                'id' => 103,
                'room_id' => 3,
                'room_name' => 'The Assertion Vault',
                'date' => 'Nov 07, 2026',
                'status' => 'in_progress',
                'score' => null,
            ],
        ];
    }
}
