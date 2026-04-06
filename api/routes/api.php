<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\PublicApplicationController;
use App\Http\Controllers\Api\TrackingLinkController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/jobs', [JobController::class, 'index']);
    Route::post('/jobs', [JobController::class, 'store']);
    Route::get('/jobs/{job}', [JobController::class, 'show']);
    Route::put('/jobs/{job}', [JobController::class, 'update']);
    Route::delete('/jobs/{job}', [JobController::class, 'destroy']);

    Route::post('/jobs/{job}/tracking-links', [TrackingLinkController::class, 'store']);
    Route::put('/tracking-links/{trackingLink}', [TrackingLinkController::class, 'update']);
    Route::delete('/tracking-links/{trackingLink}', [TrackingLinkController::class, 'destroy']);

    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::get('/applications/{application}', [ApplicationController::class, 'show']);
    Route::patch('/applications/{application}', [ApplicationController::class, 'update']);
    Route::get('/applications/{application}/export-pdf', [ApplicationController::class, 'exportPdf']);
});

Route::middleware('throttle:30,1')->group(function (): void {
    Route::get('/public/jobs', [JobController::class, 'publicIndex']);
    Route::get('/public/jobs/{job}', [JobController::class, 'publicShow']);
    Route::get('/apply/{code}', [PublicApplicationController::class, 'show']);
    Route::post('/apply/{code}', [PublicApplicationController::class, 'store']);
});
