<?php

namespace App\Http\Controllers;

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
        InstructorService $instructorService
    ): View {
        $instructor = $request->user();

        return view('instructor.dashboard', [
            'instructor' => $instructor,
            'students' => $instructorService->getAssignedStudents($instructor),
            'sessions' => $instructorService->getStudentSessions($instructor),
            'overview' => $instructorService->getOverview($instructor),
            'analytics' => $instructorService->getClassAnalytics($instructor),
        ]);
    }
}
