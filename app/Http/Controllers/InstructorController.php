<?php

namespace App\Http\Controllers;

use App\Services\InstructorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorController extends Controller
{
    /**
     * Display the instructor dashboard with assigned students and session records.
     */
    public function dashboard(Request $request, InstructorService $instructorService): View
    {
        $instructor = $request->user();
        $students = $instructorService->getAssignedStudents($instructor);
        $sessions = $instructorService->getStudentSessions($instructor);
        $overview = $instructorService->getOverview($instructor);

        return view('instructor.dashboard', [
            'instructor' => $instructor,
            'students' => $students,
            'sessions' => $sessions,
            'overview' => $overview,
        ]);
    }
}

