<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SocialAccountController;
use App\Http\Controllers\Api\CelebrityController;
use App\Http\Controllers\Api\ContestController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-registration', [AuthController::class, 'verifyRegistrationOtp']); // Verify registration OTP and activate user
Route::post('/login', [AuthController::class, 'login']); // Send OTP
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']); // Verify OTP and login

// Public Contest Routes
Route::get('/contests', [ContestController::class, 'index']); // List all active contests
Route::get('/contests/{id}', [ContestController::class, 'show']); // View contest details

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/complete-profile', [AuthController::class, 'completeProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Contest Routes (Celebrity only)
    Route::get('/platforms', [ContestController::class, 'platforms']); // Get all platforms
    Route::post('/contests', [ContestController::class, 'store']); // Create contest
});
