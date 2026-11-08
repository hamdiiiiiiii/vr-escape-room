<?php

use App\Http\Controllers\Api\SessionApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/sessions/verify-code', [SessionApiController::class, 'verifyCode'])
    ->middleware('throttle:10,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/sessions/{id}/results', [SessionApiController::class, 'storeResult'])->whereNumber('id');
    Route::post('/sessions/{id}/end', [SessionApiController::class, 'endSession'])->whereNumber('id');
});