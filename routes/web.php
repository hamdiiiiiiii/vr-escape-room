<?php

use App\Http\Controllers\InstructorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReportApiController;

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
   Route::get('/api/sessions/{id}/report', [ReportApiController::class, 'show'])
        ->whereNumber('id')->name('api.sessions.report');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
