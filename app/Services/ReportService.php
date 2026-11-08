<?php

// STUB: replace with Member 5's API/database later.

namespace App\Services;

use App\Models\User;

class ReportService
{
    /**
     * Determine the official score classification.
     *
     * @param int $score
     * @return string 'Needs Support' | 'Proficient' | 'Advanced'
     */
    public function getScoreLevel(int $score): string
    {
        if ($score >= 80) {
            return 'Advanced';
        }

        if ($score >= 50) {
            return 'Proficient';
        }

        return 'Needs Support';
    }

    /**
     * Resolve verified student ID from database or default seeded ID.
     */
    protected function resolveStudentId(string $email, int $defaultSeededId): int
    {
        return User::where('email', $email)->value('id') ?? $defaultSeededId;
    }

    /**
     * Get all available finished performance reports.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getReports(): array
    {
        $student1Id = $this->resolveStudentId('student1@test.com', 2);
        $student2Id = $this->resolveStudentId('student2@test.com', 3);
        $student4Id = $this->resolveStudentId('student4@test.com', 5);

        return [
            // Session 101: Student 1 - The Boundary Maze
            [
                'id' => 101,
                'session_id' => 101,
                'student_id' => $student1Id,
                'student_name' => 'Student 1',
                'student_email' => 'student1@test.com',
                'room_id' => 1,
                'room_name' => 'The Boundary Maze',
                'concept_tag' => 'Boundary Testing',
                'date' => 'Nov 05, 2026',
                'status' => 'finished',
                'score' => 92,
                'score_level' => $this->getScoreLevel(92),
                'time_spent' => '11m 45s',
                'time_limit_minutes' => 15,
                'total_attempts' => 4,
                'puzzles_solved' => 3,
                'total_puzzles' => 3,
                'puzzles_without_hints' => 2,
                'hints_used' => 1,
                'concept_scores' => [
                    [
                        'concept' => 'Boundary Testing',
                        'score' => 94,
                        'note' => 'Provisional concept evaluation',
                    ],
                    [
                        'concept' => 'Off-by-One Trap Detection',
                        'score' => 90,
                        'note' => 'Provisional concept evaluation',
                    ],
                ],
            ],

            // Session 102: Student 1 - Equivalence Chamber
            [
                'id' => 102,
                'session_id' => 102,
                'student_id' => $student1Id,
                'student_name' => 'Student 1',
                'student_email' => 'student1@test.com',
                'room_id' => 2,
                'room_name' => 'Equivalence Chamber',
                'concept_tag' => 'Equivalence Partitioning',
                'date' => 'Nov 06, 2026',
                'status' => 'finished',
                'score' => 85,
                'score_level' => $this->getScoreLevel(85),
                'time_spent' => '16m 10s',
                'time_limit_minutes' => 20,
                'total_attempts' => 5,
                'puzzles_solved' => 3,
                'total_puzzles' => 3,
                'puzzles_without_hints' => 2,
                'hints_used' => 1,
                'concept_scores' => [
                    [
                        'concept' => 'Equivalence Partitioning',
                        'score' => 86,
                        'note' => 'Provisional concept evaluation',
                    ],
                    [
                        'concept' => 'Invalid Partition Handling',
                        'score' => 84,
                        'note' => 'Provisional concept evaluation',
                    ],
                ],
            ],

            // Session 201: Student 2 - Equivalence Chamber
            [
                'id' => 201,
                'session_id' => 201,
                'student_id' => $student2Id,
                'student_name' => 'Student 2',
                'student_email' => 'student2@test.com',
                'room_id' => 2,
                'room_name' => 'Equivalence Chamber',
                'concept_tag' => 'Equivalence Partitioning',
                'date' => 'Nov 06, 2026',
                'status' => 'finished',
                'score' => 88,
                'score_level' => $this->getScoreLevel(88),
                'time_spent' => '18m 30s',
                'time_limit_minutes' => 20,
                'total_attempts' => 6,
                'puzzles_solved' => 3,
                'total_puzzles' => 3,
                'puzzles_without_hints' => 1,
                'hints_used' => 2,
                'concept_scores' => [
                    [
                        'concept' => 'Equivalence Partitioning',
                        'score' => 90,
                        'note' => 'Provisional concept evaluation',
                    ],
                    [
                        'concept' => 'Boundary Input Sorting',
                        'score' => 85,
                        'note' => 'Provisional concept evaluation',
                    ],
                ],
            ],

            // Session 401: Student 4 - The Assertion Vault
            [
                'id' => 401,
                'session_id' => 401,
                'student_id' => $student4Id,
                'student_name' => 'Student 4',
                'student_email' => 'student4@test.com',
                'room_id' => 3,
                'room_name' => 'The Assertion Vault',
                'concept_tag' => 'Assertions & Test Suites',
                'date' => 'Nov 07, 2026',
                'status' => 'finished',
                'score' => 100,
                'score_level' => $this->getScoreLevel(100),
                'time_spent' => '19m 20s',
                'time_limit_minutes' => 25,
                'total_attempts' => 4,
                'puzzles_solved' => 4,
                'total_puzzles' => 4,
                'puzzles_without_hints' => 4,
                'hints_used' => 0,
                'concept_scores' => [
                    [
                        'concept' => 'Assertions & Test Suites',
                        'score' => 100,
                        'note' => 'Provisional concept evaluation',
                    ],
                    [
                        'concept' => 'System State Verification',
                        'score' => 100,
                        'note' => 'Provisional concept evaluation',
                    ],
                ],
            ],

            // Demo Report 501: Student 1 - Proficient Score Range (50-79)
            [
                'id' => 501,
                'session_id' => 501,
                'student_id' => $student1Id,
                'student_name' => 'Student 1',
                'student_email' => 'student1@test.com',
                'room_id' => 1,
                'room_name' => 'The Boundary Maze',
                'concept_tag' => 'Boundary Testing',
                'date' => 'Nov 03, 2026',
                'status' => 'finished',
                'score' => 72,
                'score_level' => $this->getScoreLevel(72),
                'time_spent' => '14m 10s',
                'time_limit_minutes' => 15,
                'total_attempts' => 7,
                'puzzles_solved' => 2,
                'total_puzzles' => 3,
                'puzzles_without_hints' => 1,
                'hints_used' => 2,
                'concept_scores' => [
                    [
                        'concept' => 'Boundary Testing',
                        'score' => 75,
                        'note' => 'Provisional concept evaluation',
                    ],
                    [
                        'concept' => 'Off-by-One Trap Detection',
                        'score' => 68,
                        'note' => 'Provisional concept evaluation',
                    ],
                ],
            ],

            // Demo Report 502: Student 1 - Needs Support Score Range (0-49)
            [
                'id' => 502,
                'session_id' => 502,
                'student_id' => $student1Id,
                'student_name' => 'Student 1',
                'student_email' => 'student1@test.com',
                'room_id' => 1,
                'room_name' => 'The Boundary Maze',
                'concept_tag' => 'Boundary Testing',
                'date' => 'Nov 01, 2026',
                'status' => 'finished',
                'score' => 45,
                'score_level' => $this->getScoreLevel(45),
                'time_spent' => '14m 55s',
                'time_limit_minutes' => 15,
                'total_attempts' => 9,
                'puzzles_solved' => 1,
                'total_puzzles' => 3,
                'puzzles_without_hints' => 0,
                'hints_used' => 3,
                'concept_scores' => [
                    [
                        'concept' => 'Boundary Testing',
                        'score' => 45,
                        'note' => 'Provisional concept evaluation',
                    ],
                    [
                        'concept' => 'Off-by-One Trap Detection',
                        'score' => 45,
                        'note' => 'Provisional concept evaluation',
                    ],
                ],
            ],
        ];
    }

    /**
     * Find a specific finished performance report by its ID.
     *
     * @param int|string $id
     * @return array<string, mixed>|null
     */
    public function findReport(int|string $id): ?array
    {
        foreach ($this->getReports() as $report) {
            if ((int) $report['id'] === (int) $id) {
                return $report;
            }
        }

        return null;
    }

    /**
     * Get verified student IDs assigned to the given instructor.
     *
     * @param User $instructor
     * @return array<int, int>
     */
    public function getAssignedStudentIds(User $instructor): array
    {
        $instructorService = app(InstructorService::class);
        $assigned = $instructorService->getAssignedStudents($instructor);

        return array_map(fn ($s) => (int) $s['id'], $assigned);
    }

    /**
     * Check whether the authenticated user has authorization to view this report.
     *
     * Rules:
     * - Students can access only their own report.
     * - Instructors can access only their assigned students' reports.
     * - Strict ID verification with NO email-based fallbacks.
     *
     * @param User $user
     * @param array<string, mixed> $report
     * @return bool
     */
    public function canAccess(User $user, array $report): bool
    {
        if ($user->isStudent()) {
            return (int) $user->id === (int) $report['student_id'];
        }

        if ($user->isInstructor()) {
            $assignedIds = $this->getAssignedStudentIds($user);

            return in_array((int) $report['student_id'], $assignedIds, true);
        }

        return false;
    }
}

