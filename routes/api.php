<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CelebrityController;
use App\Http\Controllers\Api\ContestController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\PostController;

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

    // Contest Participation Routes
    Route::get('/contests/{id}/attempt', [ContestController::class, 'getContestForAttempt']); // Get contest with attempt status
    Route::get('/contests/{id}/questions', [ContestController::class, 'getContestQuestions']); // Get contest questions
    Route::post('/contests/{id}/submit', [ContestController::class, 'submitContestAnswers']); // Submit answers

    // Story Routes (3 endpoints only)
    Route::get('/stories', [StoryController::class, 'index']); // Get all stories
    Route::post('/stories', [StoryController::class, 'store']); // Create story

    // Post Routes
    Route::get('/posts', [PostController::class, 'index']); // Get all posts
    Route::post('/posts', [PostController::class, 'store']); // Create post (celebrity only)
    Route::delete('/posts/{id}', [PostController::class, 'destroy']); // Delete post
    Route::post('/posts/{id}/toggle-like', [PostController::class, 'toggleLike']); // Like/Unlike post

    // Celebrity Routes (Profile & Follow)
    Route::get('/celebrities/search', [CelebrityController::class, 'search']); // Search celebrities
    Route::get('/celebrities/{id}/profile', [CelebrityController::class, 'profile']); // Get celebrity profile
    Route::post('/celebrities/{id}/toggle-follow', [CelebrityController::class, 'toggleFollow']); // Follow/Unfollow


    // Celebrity Routes
    Route::get('/platforms', [ContestController::class, 'platforms']); // Celebrity only
    Route::post('/contests', [ContestController::class, 'store']); // Celebrity only
});
