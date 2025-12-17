<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Contest;
use App\Models\ContestAttempt;
use App\Models\Conversation;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Story;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * Get all statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'users' => $this->getUsersStats(),
                'content' => $this->getContentStats(),
                'engagement' => $this->getEngagementStats(),
                'contests' => $this->getContestsStats(),
            ],
        ]);
    }

    /**
     * Get users statistics.
     *
     * @return array
     */
    public function getUsersStats()
    {
        $totalUsers = User::count();
        $followers = User::where('role', 'follower')->count();
        $celebrities = User::where('role', 'celebrity')->count();
        $admins = User::where('is_admin', true)->count();
        $verifiedUsers = User::where('verified', true)->count();

        // New users today
        $newUsersToday = User::whereDate('created_at', Carbon::today())->count();

        // New users this week
        $newUsersThisWeek = User::whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek(),
        ])->count();

        // New users this month
        $newUsersThisMonth = User::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        // Gender distribution
        $genderDistribution = [
            'male' => User::where('gender', 'male')->count(),
            'female' => User::where('gender', 'female')->count(),
            'other' => User::where('gender', 'other')->count(),
        ];

        return [
            'total' => $totalUsers,
            'followers' => $followers,
            'celebrities' => $celebrities,
            'admins' => $admins,
            'verified' => $verifiedUsers,
            'new_today' => $newUsersToday,
            'new_this_week' => $newUsersThisWeek,
            'new_this_month' => $newUsersThisMonth,
            'gender_distribution' => $genderDistribution,
        ];
    }

    /**
     * Get content statistics.
     *
     * @return array
     */
    public function getContentStats()
    {
        $totalPosts = Post::count();
        $totalStories = Story::count();
        $activeStories = Story::where('expires_at', '>', Carbon::now())->count();

        // Posts today
        $postsToday = Post::whereDate('created_at', Carbon::today())->count();

        // Stories today
        $storiesToday = Story::whereDate('created_at', Carbon::today())->count();

        return [
            'total_posts' => $totalPosts,
            'total_stories' => $totalStories,
            'active_stories' => $activeStories,
            'posts_today' => $postsToday,
            'stories_today' => $storiesToday,
        ];
    }

    /**
     * Get engagement statistics.
     *
     * @return array
     */
    public function getEngagementStats()
    {
        $totalLikes = Like::count();
        $totalFollows = Follow::count();
        $totalMessages = Message::count();
        $totalConversations = Conversation::count();
        $totalNotifications = Notification::count();

        // Likes today
        $likesToday = Like::whereDate('created_at', Carbon::today())->count();

        // Messages today
        $messagesToday = Message::whereDate('created_at', Carbon::today())->count();

        return [
            'total_likes' => $totalLikes,
            'total_follows' => $totalFollows,
            'total_messages' => $totalMessages,
            'total_conversations' => $totalConversations,
            'total_notifications' => $totalNotifications,
            'likes_today' => $likesToday,
            'messages_today' => $messagesToday,
        ];
    }

    /**
     * Get contests statistics.
     *
     * @return array
     */
    public function getContestsStats()
    {
        $totalContests = Contest::count();
        $activeContests = Contest::where('status', 'active')->count();
        $totalAttempts = ContestAttempt::count();

        // Contests today
        $contestsToday = Contest::whereDate('created_at', Carbon::today())->count();

        // Attempts today
        $attemptsToday = ContestAttempt::whereDate('created_at', Carbon::today())->count();

        return [
            'total_contests' => $totalContests,
            'active_contests' => $activeContests,
            'total_attempts' => $totalAttempts,
            'contests_today' => $contestsToday,
            'attempts_today' => $attemptsToday,
        ];
    }

    /**
     * Get users statistics only.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function users()
    {
        return response()->json([
            'success' => true,
            'data' => $this->getUsersStats(),
        ]);
    }

    /**
     * Get content statistics only.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function content()
    {
        return response()->json([
            'success' => true,
            'data' => $this->getContentStats(),
        ]);
    }

    /**
     * Get engagement statistics only.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function engagement()
    {
        return response()->json([
            'success' => true,
            'data' => $this->getEngagementStats(),
        ]);
    }

    /**
     * Get contests statistics only.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function contests()
    {
        return response()->json([
            'success' => true,
            'data' => $this->getContestsStats(),
        ]);
    }
}
