<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    if (request()->user()->isInstructor()) {
        return redirect()->route('instructor.dashboard');
    }

    return redirect()->route('student.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/student/dashboard', [StudentController::class, 'dashboard'])->name('student.dashboard');
    // Old GET link: only redirects to the dashboard.
    Route::get('/rooms/{id}/start', [StudentController::class, 'startRoom'])->name('student.start-room');
    Route::post('/rooms/{id}/start', [StudentController::class, 'createSession'])->name('student.start-room.create');

    Route::get('/sessions/{id}', [StudentController::class, 'showSession'])->name('student.session.show');
    Route::get('/sessions/{id}/status', [StudentController::class, 'sessionStatus'])->name('student.session.status');

    // DEV ONLY, remove when Member 5's API is connected. Returns 404 unless APP_ENV=local.
    Route::post('/dev/sessions/{id}/simulate-join', [StudentController::class, 'simulateJoin'])->name('dev.session.simulate-join');
});

Route::middleware(['auth', 'role:instructor'])->group(function () {
    Route::get('/instructor/dashboard', function () {
        return view('instructor.dashboard');
    })->name('instructor.dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/reports/{id}', function ($id) {
        return view('reports.show', ['reportId' => $id]);
    })->name('reports.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
