<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\User;

class ReportService
{
    /**
     * Resolve a numeric score into its display level.
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
     * Get all completed session reports.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getReports(): array
    {
        return GameSession::with([
            'room',
            'report',
            'puzzleResults',
            'user',
        ])
            ->where('status', 'finished')
            ->whereHas('report')
            ->orderByDesc('id')
            ->get()
            ->map(fn (GameSession $session) => $this->build($session))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Find a report by its game session ID.
     */
    public function findReport(int|string $id): ?array
    {
        $session = GameSession::with([
            'room',
            'report',
            'puzzleResults',
            'user',
        ])->find((int) $id);

        return $session
            ? $this->build($session)
            : null;
    }

    /**
     * Get the IDs of students assigned to an instructor.
     *
     * @return array<int, int>
     */
    public function getAssignedStudentIds(User $instructor): array
    {
        return User::query()
            ->where('instructor_id', $instructor->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Check whether a user can access a report.
     */
    public function canAccess(User $user, array $report): bool
    {
        if (! isset($report['student_id'])) {
            return false;
        }

        if ($user->isStudent()) {
            return (int) $user->id === (int) $report['student_id'];
        }

        if ($user->isInstructor()) {
            return in_array(
                (int) $report['student_id'],
                $this->getAssignedStudentIds($user),
                true
            );
        }

        return false;
    }

    /**
     * Build the report data from a real database session.
     */
    private function build(GameSession $session): ?array
    {
        $report = $session->report;

        if ($session->status !== 'finished' || $report === null) {
            return null;
        }

        $results = $session->puzzleResults;
        $config = $session->room?->config ?? [];

        $seconds = (int) ($report->total_time_seconds ?? 0);

        $date = $session->ended_at
            ?? $session->started_at
            ?? $session->created_at;

        $conceptScores = collect($report->concept_scores ?? [])
            ->map(fn ($score, $tag) => [
                'concept' => (string) $tag,
                'score' => (int) $score,
                'note' => '',
            ])
            ->values()
            ->all();

        $solvedResults = $results->filter(
            fn ($result) => (bool) $result->solved
        );

        $timeLimitSeconds = (int) (
            $session->room?->time_limit_seconds ?? 0
        );

        return [
            'id' => (int) $session->id,
            'session_id' => (int) $session->id,

            'student_id' => (int) $session->user_id,
            'student_name' => $session->user?->name ?? 'Unknown',
            'student_email' => $session->user?->email ?? '',

            'room_id' => $session->room_id,
            'room_name' => $session->room?->name ?? 'Unknown room',

            'concept_tag' => $config['conceptTag']
                ?? ($config['puzzles'][0]['conceptTag'] ?? ''),

            'date' => $date?->format('M d, Y') ?? '—',
            'status' => 'finished',

            'score' => (int) $report->session_score,
            'score_level' => $this->getScoreLevel(
                (int) $report->session_score
            ),

            'time_spent' => intdiv($seconds, 60)
                . 'm '
                . ($seconds % 60)
                . 's',

            'time_limit_minutes' => (int) ceil(
                $timeLimitSeconds / 60
            ),

            'total_attempts' => (int) $results->sum('attempts'),
            'puzzles_solved' => $solvedResults->count(),

            'total_puzzles' => count($config['puzzles'] ?? [])
                ?: $results->count(),

            'puzzles_without_hints' => $solvedResults
                ->filter(fn ($result) => (int) $result->hints_used === 0)
                ->count(),

            'hints_used' => (int) $results->sum('hints_used'),

            'concept_scores' => $conceptScores,
            'ai_analysis' => $report->ai_analysis,
        ];
    }
}
