<?php

// STUB: replace with Member 5's API/database later.

namespace App\Services;

use App\Models\User;

class InstructorService
{
    /**
     * Get high-level overview counts for the authenticated instructor's class.
     *
     * @param User $instructor
     * @return array{total_students: int, in_progress_count: int, waiting_count: int, finished_count: int}
     */
    public function getOverview(User $instructor): array
    {
        $students = $this->getAssignedStudents($instructor);
        $sessions = $this->getStudentSessions($instructor);

        $inProgress = count(array_filter($sessions, fn ($s) => $s['status'] === 'in_progress'));
        $waiting = count(array_filter($sessions, fn ($s) => $s['status'] === 'waiting'));
        $finished = count(array_filter($sessions, fn ($s) => $s['status'] === 'finished'));

        return [
            'total_students' => count($students),
            'in_progress_count' => $inProgress,
            'waiting_count' => $waiting,
            'finished_count' => $finished,
        ];
    }

    /**
     * Get assigned students roster for the given instructor.
     *
     * @param User $instructor
     * @return array<int, array{id: int, name: string, email: string, session_count: int, last_active: string|null}>
     */
    public function getAssignedStudents(User $instructor): array
    {
        // When Member 5 integrates the database:
        // return $instructor->students()->...

        return [
            [
                'id' => 1,
                'name' => 'Student 1',
                'email' => 'student1@test.com',
                'session_count' => 3,
                'last_active' => 'Nov 07, 2026',
            ],
            [
                'id' => 2,
                'name' => 'Student 2',
                'email' => 'student2@test.com',
                'session_count' => 2,
                'last_active' => 'Nov 07, 2026',
            ],
            [
                'id' => 3,
                'name' => 'Student 3',
                'email' => 'student3@test.com',
                'session_count' => 1,
                'last_active' => 'Nov 04, 2026',
            ],
            [
                'id' => 4,
                'name' => 'Student 4',
                'email' => 'student4@test.com',
                'session_count' => 1,
                'last_active' => 'Nov 07, 2026',
            ],
            [
                'id' => 5,
                'name' => 'Student 5',
                'email' => 'student5@test.com',
                'session_count' => 0,
                'last_active' => null,
            ],
        ];
    }

    /**
     * Get student sessions associated with the given instructor.
     *
     * @param User $instructor
     * @return array<int, array{id: int, student_id: int, student_name: string, student_email: string, room_name: string, concept_tag: string, date: string, status: string, score: int|null}>
     */
    public function getStudentSessions(User $instructor): array
    {
        // Aligned with SessionService and RoomService data conventions
        return [
            [
                'id' => 103,
                'student_id' => 1,
                'student_name' => 'Student 1',
                'student_email' => 'student1@test.com',
                'room_name' => 'The Assertion Vault',
                'concept_tag' => 'Assertions & Test Suites',
                'date' => 'Nov 07, 2026',
                'status' => 'in_progress',
                'score' => null,
            ],
            [
                'id' => 401,
                'student_id' => 4,
                'student_name' => 'Student 4',
                'student_email' => 'student4@test.com',
                'room_name' => 'The Assertion Vault',
                'concept_tag' => 'Assertions & Test Suites',
                'date' => 'Nov 07, 2026',
                'status' => 'finished',
                'score' => 100,
            ],
            [
                'id' => 202,
                'student_id' => 2,
                'student_name' => 'Student 2',
                'student_email' => 'student2@test.com',
                'room_name' => 'The Boundary Maze',
                'concept_tag' => 'Boundary Testing',
                'date' => 'Nov 07, 2026',
                'status' => 'waiting',
                'score' => null,
            ],
            [
                'id' => 102,
                'student_id' => 1,
                'student_name' => 'Student 1',
                'student_email' => 'student1@test.com',
                'room_name' => 'Equivalence Chamber',
                'concept_tag' => 'Equivalence Partitioning',
                'date' => 'Nov 06, 2026',
                'status' => 'finished',
                'score' => 85,
            ],
            [
                'id' => 201,
                'student_id' => 2,
                'student_name' => 'Student 2',
                'student_email' => 'student2@test.com',
                'room_name' => 'Equivalence Chamber',
                'concept_tag' => 'Equivalence Partitioning',
                'date' => 'Nov 06, 2026',
                'status' => 'finished',
                'score' => 88,
            ],
            [
                'id' => 101,
                'student_id' => 1,
                'student_name' => 'Student 1',
                'student_email' => 'student1@test.com',
                'room_name' => 'The Boundary Maze',
                'concept_tag' => 'Boundary Testing',
                'date' => 'Nov 05, 2026',
                'status' => 'finished',
                'score' => 92,
            ],
            [
                'id' => 301,
                'student_id' => 3,
                'student_name' => 'Student 3',
                'student_email' => 'student3@test.com',
                'room_name' => 'The Boundary Maze',
                'concept_tag' => 'Boundary Testing',
                'date' => 'Nov 04, 2026',
                'status' => 'waiting',
                'score' => null,
            ],
        ];
    }
}

