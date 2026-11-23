<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\User;

class InstructorService
{
    /**
     * Get high-level overview counts for the instructor's assigned class.
     */
    public function getOverview(User $instructor): array
    {
        $students = $this->getAssignedStudents($instructor);
        $sessions = $this->getStudentSessions($instructor);

        return [
            'total_students' => count($students),
            'in_progress_count' => count(array_filter(
                $sessions,
                fn ($session) => $session['status'] === 'in_progress'
            )),
            'waiting_count' => count(array_filter(
                $sessions,
                fn ($session) => $session['status'] === 'waiting'
            )),
            'finished_count' => count(array_filter(
                $sessions,
                fn ($session) => $session['status'] === 'finished'
            )),
        ];
    }

    /**
     * Get the students assigned to this instructor.
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     session_count: int,
     *     last_active: string|null
     * }>
     */
    public function getAssignedStudents(User $instructor): array
    {
        $students = User::query()
            ->where('instructor_id', $instructor->id)
            ->withCount('gameSessions')
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        // Fetch the latest session for each student in one query.
        $latestSessions = GameSession::query()
            ->whereIn('user_id', $studentIds)
            ->orderByDesc('created_at')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        return $students->map(function (User $student) use ($latestSessions) {
            $lastSession = $latestSessions->get($student->id);
            $lastActiveDate = $lastSession
                ? ($lastSession->started_at ?? $lastSession->created_at)
                : null;

            return [
                'id' => (int) $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'session_count' => (int) $student->game_sessions_count,
                'last_active' => $lastActiveDate?->format('M d, Y'),
            ];
        })->all();
    }

    /**
     * Get the sessions belonging to students assigned to this instructor.
     *
     * @return array<int, array{
     *     id: int,
     *     student_id: int,
     *     student_name: string,
     *     student_email: string,
     *     room_name: string,
     *     concept_tag: string,
     *     date: string,
     *     status: string,
     *     score: int|float|null
     * }>
     */
    public function getStudentSessions(User $instructor): array
    {
        $studentIds = $this->getAssignedStudentIds($instructor);

        if ($studentIds->isEmpty()) {
            return [];
        }

        return GameSession::query()
            ->with(['user', 'room', 'report'])
            ->whereIn('user_id', $studentIds)
            ->orderByDesc('created_at')
            ->get()
            // Do not display waiting sessions whose codes have expired.
            ->reject(function (GameSession $session) {
                return $session->status === 'waiting'
                    && $session->expires_at !== null
                    && now()->greaterThanOrEqualTo($session->expires_at);
            })
            ->map(function (GameSession $session) {
                $config = $session->room?->config ?? [];
                $date = $session->started_at ?? $session->created_at;

                return [
                    'id' => (int) $session->id,
                    'student_id' => (int) $session->user_id,
                    'student_name' => $session->user?->name ?? 'Unknown',
                    'student_email' => $session->user?->email ?? '',
                    'room_name' => $session->room?->name ?? 'Unknown room',

                    'concept_tag' => $config['conceptTag']
                        ?? ($config['puzzles'][0]['conceptTag'] ?? ''),

                    'date' => $date?->format('M d, Y') ?? '—',
                    'status' => $session->status,

                    'score' => $session->status === 'finished'
                        ? $session->report?->session_score
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Calculate class-level analytics from real, completed database sessions.
     *
     * @return array{
     *     class_average: int|null,
     *     class_average_level: string|null,
     *     lowest_concept: string|null,
     *     lowest_concept_avg: int|null,
     *     lowest_concept_sessions: int,
     *     finished_session_count: int
     * }
     */
    public function getClassAnalytics(User $instructor): array
    {
        $studentIds = $this->getAssignedStudentIds($instructor);

        if ($studentIds->isEmpty()) {
            return $this->emptyAnalytics();
        }

        // Load reports with their sessions once. No per-session ReportService query.
        $finishedSessions = GameSession::query()
            ->with('report')
            ->whereIn('user_id', $studentIds)
            ->where('status', 'finished')
            ->whereHas('report')
            ->get();

        // Only include sessions with a numeric session score.
        $finishedSessions = $finishedSessions->filter(
            fn (GameSession $session) =>
                $session->report !== null
                && is_numeric($session->report->session_score)
        );

        $finishedCount = $finishedSessions->count();

        if ($finishedCount === 0) {
            return $this->emptyAnalytics();
        }

        $classAverage = (int) round(
            $finishedSessions->avg(
                fn (GameSession $session) =>
                    (float) $session->report->session_score
            )
        );

        $conceptTotals = [];
        $conceptCounts = [];

        foreach ($finishedSessions as $session) {
            $conceptScores = $session->report->concept_scores ?? [];

            foreach ($conceptScores as $key => $value) {
                // Supports the stored format:
                // ["testing_basics" => 80, "test_design" => 65]
                //
                // Also supports:
                // [["concept" => "testing_basics", "score" => 80]]
                if (is_array($value)) {
                    $concept = $value['concept'] ?? (string) $key;
                    $score = $value['score'] ?? null;
                } else {
                    $concept = (string) $key;
                    $score = $value;
                }

                if ($concept === '' || ! is_numeric($score)) {
                    continue;
                }

                $conceptTotals[$concept] =
                    ($conceptTotals[$concept] ?? 0) + (float) $score;

                $conceptCounts[$concept] =
                    ($conceptCounts[$concept] ?? 0) + 1;
            }
        }

        if (empty($conceptTotals)) {
            return [
                'class_average' => $classAverage,
                'class_average_level' => $this->resolveScoreLevel($classAverage),
                'lowest_concept' => null,
                'lowest_concept_avg' => null,
                'lowest_concept_sessions' => 0,
                'finished_session_count' => $finishedCount,
            ];
        }

        $conceptAverages = [];

        foreach ($conceptTotals as $concept => $total) {
            $conceptAverages[$concept] =
                $total / $conceptCounts[$concept];
        }

       $lowestConcept = collect($conceptAverages)
    ->map(fn ($avg, $concept) => ['concept' => $concept, 'avg' => $avg])
    ->sort(fn ($a, $b) => [$a['avg'], $a['concept']] <=> [$b['avg'], $b['concept']])
    ->first()['concept'];

        return [
            'class_average' => $classAverage,
            'class_average_level' => $this->resolveScoreLevel($classAverage),
            'lowest_concept' => $lowestConcept,
            'lowest_concept_avg' => (int) round(
                $conceptAverages[$lowestConcept]
            ),
            'lowest_concept_sessions' => $conceptCounts[$lowestConcept],
            'finished_session_count' => $finishedCount,
        ];
    }

    /**
     * Get IDs of students assigned to this instructor.
     */
    private function getAssignedStudentIds(User $instructor)
    {
        return User::query()
            ->where('instructor_id', $instructor->id)
            ->pluck('id');
    }

    /**
     * Return the empty analytics structure.
     */
    private function emptyAnalytics(): array
    {
        return [
            'class_average' => null,
            'class_average_level' => null,
            'lowest_concept' => null,
            'lowest_concept_avg' => null,
            'lowest_concept_sessions' => 0,
            'finished_session_count' => 0,
        ];
    }

    /**
     * Convert a numeric score into its display level.
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
