<?php

namespace App\Http\Controllers;

use App\Services\InsightsService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display the specified performance report.
     */
    public function show(Request $request, string|int $id, ReportService $reportService, InsightsService $insights): View
    {
        $report = $reportService->findReport($id);

        if (! $report) {
            abort(404, 'Performance report not found.');
        }

        if (! $reportService->canAccess($request->user(), $report)) {
            abort(403, 'Unauthorized access to this performance report.');
        }

        return view('reports.show', [
            'report' => $report,
            'reportId' => $id,
            'puzzleLog' => $insights->puzzleLog((int) $report['session_id']),
            'isInstructor' => $request->user()->isInstructor(),
        ]);
    }
}

