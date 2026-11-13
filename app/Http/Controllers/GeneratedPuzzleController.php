<?php

namespace App\Http\Controllers;

use App\Models\GeneratedPuzzle;
use App\Models\User;
use App\Services\PuzzleGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Instructor side of the AI puzzle generator: generate, approve, reject. */
class GeneratedPuzzleController extends Controller
{
    public function store(Request $request, PuzzleGeneratorService $generator): RedirectResponse
    {
        $instructor = $request->user();

        $data = $request->validate([
            'concept' => ['required', 'string', Rule::in($generator->concepts())],
            'type' => ['required', Rule::in(PuzzleGeneratorService::TYPES)],
            'difficulty' => ['required', Rule::in(PuzzleGeneratorService::DIFFICULTIES)],
            'student_id' => ['nullable', 'integer'],
        ]);

        $student = null;
        if (! empty($data['student_id'])) {
            // Only the instructor's own students.
            $student = User::where('id', $data['student_id'])
                ->where('role', 'student')
                ->where('instructor_id', $instructor->id)
                ->first();
            abort_if($student === null, 403, 'That is not one of your students.');
        }

        if (! $generator->isConfigured()) {
            return back()->with('ai_error', 'The AI is not set up yet (no API key). The normal puzzles still work.');
        }

        $puzzle = $generator->generate($instructor, $data['concept'], $data['type'], $data['difficulty'], $student);

        if ($puzzle === null) {
            return back()->with('ai_error', 'The AI did not give a usable puzzle this time, so nothing was saved. Try again.');
        }

        return back()->with('ai_ok', 'New puzzle written. Read it below, then approve or reject it.');
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        return $this->review($request, $id, GeneratedPuzzle::APPROVED, 'Puzzle approved. Students can now get it.');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        return $this->review($request, $id, GeneratedPuzzle::REJECTED, 'Puzzle rejected.');
    }

    private function review(Request $request, int $id, string $status, string $message): RedirectResponse
    {
        $puzzle = GeneratedPuzzle::find($id);
        abort_if($puzzle === null, 404);
        abort_unless($puzzle->requested_by === $request->user()->id, 403, 'This puzzle belongs to another instructor.');

        $puzzle->update(['status' => $status, 'reviewed_at' => now()]);

        return back()->with('ai_ok', $message);
    }
}
