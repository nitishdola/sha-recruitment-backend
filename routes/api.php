<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ApplicationController;

Route::post('/send-otp', [AuthController::class, 'sendOtp'])
    ->middleware('throttle:5,1');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/submit-application', [ApplicationController::class, 'store']);
    Route::get('/application', [ApplicationController::class, 'show']);
    Route::get('/application/document/{type}', [ApplicationController::class, 'documentUrl']);
    Route::get('/application/document/{type}/stream', [ApplicationController::class, 'documentStream']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::patch('/applications/{application}/status', [ApplicationController::class, 'updateStatus']);
});