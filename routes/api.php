<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SocialAccountController;
use App\Http\Controllers\Api\CelebrityController;
use App\Http\Controllers\Api\ContestController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\StoryController;

// Public routes (Authentication only)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-registration', [AuthController::class, 'verifyRegistrationOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

// Protected routes (Mobile App)
Route::middleware('auth:sanctum')->group(function () {

    // Auth Routes
    Route::post('/complete-profile', [AuthController::class, 'completeProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Home Routes
    Route::get('/home/platforms', [HomeController::class, 'getPlatforms']);
    Route::get('/home/latest-contests', [HomeController::class, 'getLatestContestsPerPlatform']);
    Route::get('/contests-by-platform', [HomeController::class, 'getByPlatform']);

    // Contest Routes (All Users)
    Route::get('/contests', [ContestController::class, 'index']);
    Route::get('/contests/my-contests', [ContestController::class, 'myContests']);
    Route::get('/contests/{id}', [ContestController::class, 'show']);

    // Story Routes (3 endpoints only)
    Route::get('/stories', [StoryController::class, 'index']); // Get all stories
    Route::post('/stories', [StoryController::class, 'store']); // Create story


    // Celebrity Routes
    Route::get('/platforms', [ContestController::class, 'platforms']); // Celebrity only
    Route::post('/contests', [ContestController::class, 'store']); // Celebrity only
});
