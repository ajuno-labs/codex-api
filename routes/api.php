<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

// Health check routes
Route::get('/health', [HealthController::class, 'check']);
Route::get('/ping', [HealthController::class, 'ping']);

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // OAuth routes
    Route::get('/oauth/{provider}/url', [AuthController::class, 'getOAuthUrl'])
        ->whereIn('provider', ['google', 'github']);
});

// OAuth callback routes (public)
Route::get('/oauth/{provider}/callback', [AuthController::class, 'handleOAuthCallback'])
    ->whereIn('provider', ['google', 'github']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    Route::get('/user', function () {
        return response()->json(auth()->user());
    });
}); 