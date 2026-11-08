<?php

// STUB: replace with Member 5's API/database later.

namespace App\Services;

use App\Models\User;
use App\Services\ReportService;

class InstructorService
{
    /**
     * Resolve verified student ID from database or default seeded ID.
     */
    protected function resolveStudentId(string $email, int $defaultSeededId): int
    {
        return User::where('email', $email)->value('id') ?? $defaultSeededId;
    }

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
     * Get assigned students roster for the given instructor using verified seeded IDs.
     *
     * @param User $instructor
     * @return array<int, array{id: int, name: string, email: string, session_count: int, last_active: string|null}>
     */
    public function getAssignedStudents(User $instructor): array
    {
        // When Member 5 integrates the database:
        // return $instructor->students()->...

        $s1 = $this->resolveStudentId('student1@test.com', 2);
        $s2 = $this->resolveStudentId('student2@test.com', 3);
        $s3 = $this->resolveStudentId('student3@test.com', 4);
        $s4 = $this->resolveStudentId('student4@test.com', 5);
        $s5 = $this->resolveStudentId('student5@test.com', 6);

        return [
            [
                'id' => $s1,
                'name' => 'Student 1',
                'email' => 'student1@test.com',
                'session_count' => 3,
                'last_active' => 'Nov 07, 2026',
            ],
            [
                'id' => $s2,
                'name' => 'Student 2',
                'email' => 'student2@test.com',
                'session_count' => 2,
                'last_active' => 'Nov 07, 2026',
            ],
            [
                'id' => $s3,
                'name' => 'Student 3',
                'email' => 'student3@test.com',
                'session_count' => 1,
                'last_active' => 'Nov 04, 2026',
            ],
            [
                'id' => $s4,
                'name' => 'Student 4',
                'email' => 'student4@test.com',
                'session_count' => 1,
                'last_active' => 'Nov 07, 2026',
            ],
            [
                'id' => $s5,
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
        $s1 = $this->resolveStudentId('student1@test.com', 2);
        $s2 = $this->resolveStudentId('student2@test.com', 3);
        $s3 = $this->resolveStudentId('student3@test.com', 4);
        $s4 = $this->resolveStudentId('student4@test.com', 5);

        return [
            [
                'id' => 103,
                'student_id' => $s1,
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
                'student_id' => $s4,
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
                'student_id' => $s2,
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
                'student_id' => $s1,
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
                'student_id' => $s2,
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
                'student_id' => $s1,
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
                'student_id' => $s3,
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

    /**
     * Calculate class-level analytics for the authenticated instructor.
     *
     * Returns:
     *  - class_average: int|null           Rounded average score of finished sessions (null = no data).
     *  - class_average_level: string|null  Score-level label (null = no data).
     *  - lowest_concept: string|null       Concept with the lowest average concept score (null = no data).
     *  - lowest_concept_avg: int|null      That concept's rounded average score (null = no data).
     *  - lowest_concept_sessions: int      Sessions contributing to the lowest concept average.
     *  - finished_session_count: int       Finished sessions included in the class average.
     *
     * "Lowest-Scoring Concept" is defined as the concept with the lowest average concept-score
     * across all finished sessions that have a matching performance report. This is a score-based
     * ranking — NOT a count of missed attempts. See Member 5 integration note below.
     *
     * Score-level classification uses the same thresholds as ReportService::getScoreLevel()
     * (Advanced ≥ 80, Proficient ≥ 50, else Needs Support). The logic is intentionally kept
     * in a private helper rather than cross-calling ReportService, so the two stubs can be
     * replaced independently by Member 5 without tight coupling. When Member 5 delivers the
     * backend, both should be consolidated into a shared utility or model mutator.
     *
     * Member 5 integration note:
     *   Replace this method with an Eloquent query:
     *     Session::where('instructor_id', $instructor->id)
     *             ->where('status', 'finished')
     *             ->whereNotNull('score')
     *             ->with('conceptScores')
     *             ->get()
     *   The ReportService::findReport() cross-reference will no longer be needed once
     *   concept_scores are available as a relation on the Session or Report model.
     *   Preserve the returned array key names — the view and tests depend on them.
     *
     * @param User $instructor
     * @return array<string, mixed>
     */
    public function getClassAnalytics(User $instructor): array
    {
        $sessions = $this->getStudentSessions($instructor);

        // Keep only finished sessions with a valid numeric score.
        // De-duplicate by session ID to guard against future stub changes.
        $seen     = [];
        $finished = [];

        foreach ($sessions as $session) {
            if ($session['status'] !== 'finished' || $session['score'] === null) {
                continue;
            }

            $id = (int) $session['id'];

            if (isset($seen[$id])) {
                continue;
            }

            $seen[$id]  = true;
            $finished[] = $session;
        }

        $finishedCount = count($finished);

        if ($finishedCount === 0) {
            return [
                'class_average'           => null,
                'class_average_level'     => null,
                'lowest_concept'          => null,
                'lowest_concept_avg'      => null,
                'lowest_concept_sessions' => 0,
                'finished_session_count'  => 0,
            ];
        }

        // Class average score (rounded to nearest whole number).
        $totalScore   = (int) array_sum(array_column($finished, 'score'));
        $classAverage = (int) round($totalScore / $finishedCount);
        $classLevel   = $this->resolveScoreLevel($classAverage);

        // Cross-reference with ReportService for concept-level scores.
        // Only finished sessions that have a matching report contribute.
        // Sessions without a report are silently skipped — they do not count
        // as zero; they are simply excluded from the concept average.
        /** @var ReportService $reportService */
        $reportService = app(ReportService::class);

        $conceptTotals = [];
        $conceptCounts = [];

        foreach ($finished as $session) {
            $report = $reportService->findReport($session['id']);

            if ($report === null || empty($report['concept_scores'])) {
                continue;
            }

            foreach ($report['concept_scores'] as $cs) {
                $concept = $cs['concept'];
                $conceptTotals[$concept] = ($conceptTotals[$concept] ?? 0) + (int) $cs['score'];
                $conceptCounts[$concept] = ($conceptCounts[$concept] ?? 0) + 1;
            }
        }

        if (empty($conceptTotals)) {
            return [
                'class_average'           => $classAverage,
                'class_average_level'     => $classLevel,
                'lowest_concept'          => null,
                'lowest_concept_avg'      => null,
                'lowest_concept_sessions' => 0,
                'finished_session_count'  => $finishedCount,
            ];
        }

        // Compute per-concept averages.
        // Tie-breaking rule: when two concepts share the same lowest average,
        // the one that sorts first alphabetically (ascending) is selected.
        // This rule is deterministic, easy to verify in tests, and avoids
        // presenting an arbitrary winner when real data has genuine ties.
        $conceptAvgs = [];

        foreach ($conceptTotals as $concept => $total) {
            $conceptAvgs[$concept] = $total / $conceptCounts[$concept];
        }

        $minAvg     = min($conceptAvgs);
        $candidates = array_keys(array_filter($conceptAvgs, fn ($avg) => $avg == $minAvg));
        sort($candidates);

        $lowestConcept         = $candidates[0];
        $lowestConceptAvg      = (int) round($minAvg);
        $lowestConceptSessions = $conceptCounts[$lowestConcept];

        return [
            'class_average'           => $classAverage,
            'class_average_level'     => $classLevel,
            'lowest_concept'          => $lowestConcept,
            'lowest_concept_avg'      => $lowestConceptAvg,
            'lowest_concept_sessions' => $lowestConceptSessions,
            'finished_session_count'  => $finishedCount,
        ];
    }

    /**
     * Resolve a numeric score into its display level.
     *
     * Thresholds mirror ReportService::getScoreLevel().
     * See getClassAnalytics() docblock for the consolidation note.
     *
     * @param int $score
     * @return string 'Advanced' | 'Proficient' | 'Needs Support'
     */
    private function resolveScoreLevel(int $score): string
    {
        if ($score >= 80) {
            return 'Advanced';
        }

        if ($score >= 50) {
            return 'Proficient';
        }

        return 'Needs Support';
    }
}
