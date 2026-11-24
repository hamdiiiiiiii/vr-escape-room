<?php

use App\Http\Controllers\Api\AvatarApiController;
use App\Http\Controllers\Api\EventApiController;
use App\Http\Controllers\Api\HintApiController;
use App\Http\Controllers\Api\SessionApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/sessions/verify-code', [SessionApiController::class, 'verifyCode'])
    ->middleware('throttle:10,1');
Route::get('/avatar/options', [AvatarApiController::class, 'options'])->middleware('throttle:60,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/sessions/{id}/avatar', [AvatarApiController::class, 'mine'])->whereNumber('id');
    Route::get('/sessions/{id}/squad', [AvatarApiController::class, 'squad'])->whereNumber('id');
    Route::post('/sessions/{id}/results', [SessionApiController::class, 'storeResult'])->whereNumber('id');
    Route::post('/sessions/{id}/hint', [HintApiController::class, 'show'])->whereNumber('id')->middleware('throttle:30,1');
    Route::post('/sessions/{id}/events', [EventApiController::class, 'store'])->whereNumber('id')->middleware('throttle:60,1');
    Route::post('/sessions/{id}/end', [SessionApiController::class, 'endSession'])->whereNumber('id');
});