<?php

namespace App\Http\Controllers;

use App\Models\GeneratedPuzzle;
use App\Models\User;
use App\Services\ClassBriefingService;
use App\Services\InsightsService;
use App\Services\PuzzleGeneratorService;
use App\Services\InstructorService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InstructorController extends Controller
{
    /**
     * Display the instructor dashboard.
     */
    public function dashboard(
        Request $request,
        InstructorService $instructorService,
        InsightsService $insights,
        ClassBriefingService $briefing
    ): View {
        $instructor = $request->user();

        return view('instructor.dashboard', [
            'instructor' => $instructor,
            'students' => $instructorService->getAssignedStudents($instructor),
            'sessions' => $instructorService->getStudentSessions($instructor),
            'overview' => $instructorService->getOverview($instructor),
            'analytics' => $instructorService->getClassAnalytics($instructor),
            'stuck' => $insights->classStuck($instructor),
            'misconceptions' => $insights->classMisconceptions($instructor),
            'briefing' => $briefing->briefing($instructor),
            'briefingNarrative' => $briefing->cachedNarrative($instructor),
            'briefingAiReady' => $briefing->isConfigured(),
            'studentMeta' => $insights->classStudentBadges($instructor),
        ]);
    }

    /**
     * Read-only view of the rooms and puzzles in the content pack.
     */
    public function contentPack(Request $request, InsightsService $insights, PuzzleGeneratorService $generator): View
    {
        return view('instructor.content-pack', [
            'rooms' => $insights->contentPack(),
            'generated' => GeneratedPuzzle::where('requested_by', $request->user()->id)->with('student:id,name')->latest('id')->get(),
            'concepts' => $generator->concepts(),
            'students' => User::where('role', 'student')->where('instructor_id', $request->user()->id)->orderBy('name')->get(['id', 'name']),
            'aiReady' => $generator->isConfigured(),
        ]);
    }

    /**
     * Gradebook CSV: one row per finished session of the instructor's own students.
     */
    public function exportGradebook(Request $request): StreamedResponse
    {
        $studentIds = \App\Models\User::where('instructor_id', $request->user()->id)->pluck('id');

        $sessions = \App\Models\GameSession::query()
            ->with(['user:id,name,email', 'room:id,name', 'report', 'puzzleResults'])
            ->whereIn('user_id', $studentIds)
            ->where('status', 'finished')
            ->orderBy('user_id')->orderBy('ended_at')
            ->get();

        // A cell that starts with = + - @ could run as a formula in Excel, so it gets a leading apostrophe.
        $safe = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;

        return response()->streamDownload(function () use ($sessions, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads UTF-8 names correctly
            fputcsv($out, ['Student', 'Email', 'Room', 'Date', 'Score', 'Level', 'Puzzles solved', 'Hints used', 'Time (seconds)']);
            foreach ($sessions as $s) {
                fputcsv($out, [
                    $safe($s->user?->name), $safe($s->user?->email), $safe($s->room?->name),
                    ($s->ended_at ?? $s->created_at)?->format('Y-m-d'),
                    $s->report?->session_score, $s->report?->level,
                    $s->puzzleResults->where('solved', true)->count(),
                    $s->puzzleResults->sum('hints_used'),
                    $s->report?->total_time_seconds,
                ]);
            }
            fclose($out);
        }, 'gradebook-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
