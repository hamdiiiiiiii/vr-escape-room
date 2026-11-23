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
}
