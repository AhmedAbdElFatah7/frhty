<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CelebrityController;
use App\Http\Controllers\Api\ContestController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Dashboard\AuthController as AdminAuthController;
use App\Http\Controllers\Dashboard\UserController as AdminUserController;
use App\Http\Controllers\Dashboard\StatisticsController;

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

    // Contact Route (requires auth)
    Route::post('/contact', [ContactController::class, 'store']);

    // Privacy Policy Route
    Route::get('/privacy-policy', [ContactController::class, 'getPrivacyPolicy']);

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'getProfile']); // Get my profile
    Route::post('/profile/update', [ProfileController::class, 'updateProfile']); // Update profile
    Route::get('/profile/following', [ProfileController::class, 'getFollowing']); // Get following list

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
    Route::get('/contests/{id}/results', [ContestController::class, 'getContestResults']); // Get contest results and rankings
    Route::get('/contests-active', [ContestController::class, 'activeContestsWithParticipation']); // Get active contests with participation status


    // Story Routes
    Route::get('/stories', [StoryController::class, 'index']); // Get all stories
    Route::get('/stories/{id}', [StoryController::class, 'show']); // Get single story
    Route::post('/stories', [StoryController::class, 'store']); // Create story
    Route::post('/stories/{id}/view', [StoryController::class, 'markAsViewed']); // Mark story as viewed

    // Post Routes
    Route::get('/posts', [PostController::class, 'index']); // Get all posts
    Route::post('/posts', [PostController::class, 'store']); // Create post (celebrity only)
    Route::delete('/posts/{id}', [PostController::class, 'destroy']); // Delete post
    Route::post('/posts/{id}/toggle-like', [PostController::class, 'toggleLike']); // Like/Unlike post

    // Celebrity Routes (Profile & Follow)
    Route::get('/celebrities/search', [CelebrityController::class, 'search']); // Search celebrities
    Route::get('/celebrities/{id}/profile', [CelebrityController::class, 'profile']); // Get celebrity profile
    Route::post('/celebrities/{id}/toggle-follow', [CelebrityController::class, 'toggleFollow']); // Follow/Unfollow

    // Notification Routes
    Route::get('/notifications', [NotificationController::class, 'index']); // Get notifications
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']); // Get unread count
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead']); // Mark as read
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']); // Mark all as read

    // Chat Routes
    Route::get('/conversations', [ChatController::class, 'index']); // Get all conversations
    Route::get('/conversations/{userId}', [ChatController::class, 'show']); // Get or create conversation with user
    Route::post('/messages/send', [ChatController::class, 'sendMessage']); // Send message
    Route::delete('/conversations/{conversationId}', [ChatController::class, 'deleteConversation']); // Delete conversation
    Route::get('/messages/unread-count', [ChatController::class, 'unreadCount']); // Get unread messages count

    // Celebrity Routes
    Route::get('/platforms', [ContestController::class, 'platforms']); // Celebrity only
    Route::post('/contests', [ContestController::class, 'store']); // Celebrity only
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/



Route::prefix('admin')->group(function () {

    // Public admin routes (Login)
    Route::post('/login', [AdminAuthController::class, 'login']);

    // Protected admin routes
    Route::middleware(['auth:sanctum', 'is_admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);

        // User Management Routes
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{id}', [AdminUserController::class, 'show']);
        Route::put('/users/{id}', [AdminUserController::class, 'update']);
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

        // Statistics Routes
        Route::get('/statistics', [StatisticsController::class, 'index']);
        Route::get('/statistics/users', [StatisticsController::class, 'users']);
        Route::get('/statistics/content', [StatisticsController::class, 'content']);
        Route::get('/statistics/engagement', [StatisticsController::class, 'engagement']);
        Route::get('/statistics/contests', [StatisticsController::class, 'contests']);

        // Contact Messages Routes
        Route::get('/contacts', [ContactController::class, 'index']);
        Route::post('/contacts/{id}/read', [ContactController::class, 'markAsRead']);
    });
});
