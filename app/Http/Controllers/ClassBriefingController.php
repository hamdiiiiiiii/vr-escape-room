<?php

namespace App\Http\Controllers;

use App\Services\ClassBriefingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClassBriefingController extends Controller
{
    /** "Write with AI" button on the instructor dashboard. */
    public function write(Request $request, ClassBriefingService $briefing): RedirectResponse
    {
        if (! $briefing->isConfigured()) {
            return back()->with('briefing_error', 'The AI is not set up yet (no API key). The briefing above still works.');
        }

        if ($briefing->narrative($request->user()) === null) {
            return back()->with('briefing_error', 'The AI did not give a usable summary this time. Try again.');
        }

        return back()->with('briefing_ok', 'Summary written.');
    }
}
