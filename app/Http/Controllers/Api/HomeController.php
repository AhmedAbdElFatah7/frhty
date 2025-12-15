<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Platform;
use App\Models\Contest;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Get time ago in Arabic format
     * Returns "منذ X أيام" or "منذ X ساعات"
     */
    private function getTimeAgo($createdAt)
    {
        $diffInHours = (int) $createdAt->diffInHours();
        $diffInDays = (int) $createdAt->diffInDays();

        if ($diffInDays > 0) {
            return 'منذ ' . $diffInDays . ' ' . ($diffInDays == 1 ? 'يوم' : 'أيام');
        } else {
            return 'منذ ' . $diffInHours . ' ' . ($diffInHours == 1 ? 'ساعة' : 'ساعات');
        }
    }

    /**
     * Get All Platforms
     * 
     * Returns a list of all available social media platforms in the system.
     * Platforms are ordered alphabetically by name.
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "platforms": [
     *       {
     *         "id": 1,
     *         "name": "tiktok",
     *         "display_name": "TikTok",
     *         "created_at": "2025-12-09 12:00:00"
     *       }
     *     ],
     *     "total": 5
     *   }
     * }
     * 
     * @response 500 scenario="error" {
     *   "success": false,
     *   "message": "حدث خطأ أثناء جلب المنصات",
     *   "error": "Error details"
     * }
     */
    public function getPlatforms(): JsonResponse
    {
        try {
            $platforms = Platform::orderBy('id')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'platforms' => $platforms->map(function ($platform) {
                        return [
                            'id' => $platform->id,
                            'name' => $platform->name,
                            'display_name' => $platform->display_name,
                            'name_ar' => $platform->name_ar,
                            'created_at' => $platform->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'total' => $platforms->count(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المنصات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Latest Contests Per Platform
     * 
     * Returns the most recent active contest for each platform.
     * Only returns contests where is_active=true and end_date >= now.
     * Includes contest title, image, and owner information.
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "contests": [
     *       {
     *         "id": 5,
     *         "title": "مسابقة TikTok الكبرى",
     *         "description": "اختبر معلوماتك",
     *         "image": "http://localhost:8000/storage/contests/image.jpg",
     *         "owner": {
     *           "id": 1,
     *           "name": "أحمد محمد",
     *           "image": "http://localhost:8000/storage/users/ahmed.jpg"
     *         },
     *         "days_ago": "منذ 2 أيام",
     *         "start_date": "2025-12-10 00:00:00",
     *         "end_date": "2025-12-20 23:59:59",
     *         "max_attempts": 3
     *       }
     *     ],
     *     "stories": [
     *       {
     *         "user": {
     *           "id": 1,
     *           "name": "أحمد محمد",
     *           "user_name": "ahmed_m",
     *           "image": "http://localhost:8000/storage/users/ahmed.jpg"
     *         },
     *         "stories": [
     *           {
     *             "id": 1,
     *             "media_path": "http://localhost:8000/storage/stories/video.mp4",
     *             "media_type": "video",
     *             "caption": "شاركت في المسابقة",
     *             "contest_id": 1,
     *             "contest_title": "مسابقة TikTok الكبرى",
     *             "views_count": 150,
     *             "is_viewed": true,
     *             "created_at": "2025-12-15 12:00:00",
     *             "expires_at": "2025-12-16 12:00:00"
     *           }
     *         ]
     *       }
     *     ],
     *     "total_contests": 2,
     *     "total_stories_groups": 1
     *   }
     * }
     * 
     * @response 200 scenario="no contests" {
     *   "success": true,
     *   "data": {
     *     "contests": [],
     *     "total": 0
     *   }
     * }
     * 
     * @response 500 scenario="error" {
     *   "success": false,
     *   "message": "حدث خطأ أثناء جلب المسابقات",
     *   "error": "Error details"
     * }
     */
    public function getLatestContestsPerPlatform(Request $request): JsonResponse
    {
        try {
            $platforms = Platform::with(['contests' => function ($query) {
                $query->where('is_active', true)
                    ->where('end_date', '>=', now())
                    ->orderBy('created_at', 'desc')
                    ->limit(1);
            }, 'contests.user'])
                ->get();

            $result = [];

            foreach ($platforms as $platform) {
                $latestContest = $platform->contests->first();

                if ($latestContest) {
                    $result[] = [
                        'id' => $latestContest->id,
                        'title' => $latestContest->title,
                        'description' => $latestContest->description,
                        'image' => $latestContest->image ? asset('storage/' . $latestContest->image) : null,
                        'owner' => [
                            'id' => $latestContest->user->id,
                            'name' => $latestContest->user->name,
                            'image' => $latestContest->user->image ? asset('storage/' . $latestContest->user->image) : null,
                        ],
                        'days_ago' => $this->getTimeAgo($latestContest->created_at),
                        'start_date' => $latestContest->start_date->format('Y-m-d H:i:s'),
                        'end_date' => $latestContest->end_date->format('Y-m-d H:i:s'),
                        'max_attempts' => $latestContest->max_attempts,
                    ];
                }
            }

            // Get all viewable stories (not filtered by contests)
            $stories = Story::with(['user', 'contest'])
                ->withCount('views') // Add views count
                ->viewable() // Active and not expired (assuming this is a scope on Story model)
                ->orderBy('created_at', 'desc')
                ->get();

            // Current user ID for checking if they viewed the story
            $currentUserId = $request->user() ? $request->user()->id : null; // Handle unauthenticated users

            // Group stories by user
            $storiesGrouped = $stories->groupBy('user_id')->map(function ($userStories) use ($currentUserId) {
                $user = $userStories->first()->user;
                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'user_name' => $user->user_name,
                        'image' => $user->image ? asset('storage/' . $user->image) : null,
                    ],
                    'stories' => $userStories->map(function ($story) use ($currentUserId) {
                        return [
                            'id' => $story->id,
                            'media_path' => $story->media_path ? asset('storage/' . $story->media_path) : null,
                            'media_type' => $story->media_type,
                            'caption' => $story->caption,
                            'contest_id' => $story->contest_id,
                            'contest_title' => $story->contest ? $story->contest->title : null,
                            'views_count' => $story->views_count, // Total views
                            'is_viewed' => $currentUserId ? $story->isViewedBy($currentUserId) : false, // Did current user view it?
                            'created_at' => $story->created_at->format('Y-m-d H:i:s'),
                            'expires_at' => $story->expires_at ? $story->expires_at->format('Y-m-d H:i:s') : null,
                        ];
                    })->values(),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'contests' => $result,
                    'stories' => $storiesGrouped, // Add stories to the response
                    'total_contests' => count($result),
                    'total_stories_groups' => count($storiesGrouped),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المسابقات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Get Contests By Platform
     * 
     * Returns all active contests for a specific platform.
     * Only returns contests where is_active=true and end_date >= now.
     * Results are ordered by creation date (newest first).
     * 
     * @queryParam platform_id integer required The ID of the platform. Example: 1
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "contests": [
     *       {
     *         "id": 1,
     *         "title": "مسابقة TikTok الكبرى",
     *         "description": "اختبر معلوماتك",
     *         "image": "http://localhost:8000/storage/contests/image.jpg",
     *         "owner": {
     *           "id": 1,
     *           "name": "أحمد محمد",
     *           "image": "http://localhost:8000/storage/users/ahmed.jpg"
     *         },
     *         "days_ago": "منذ 5 ساعات"
     *       }
     *     ],
     *     "stories": [
     *       {
     *         "user": {
     *           "id": 2,
     *           "name": "محمد علي",
     *           "user_name": "mohamed_ali",
     *           "image": "http://localhost:8000/storage/users/user2.jpg"
     *         },
     *         "stories": [
     *           {
     *             "id": 1,
     *             "media_path": "http://localhost:8000/storage/stories/video.mp4",
     *             "media_type": "video",
     *             "caption": "شاركت في المسابقة",
     *             "contest_id": 1,
     *             "contest_title": "مسابقة TikTok الكبرى",
     *             "views_count": 150,
     *             "is_viewed": true,
     *             "created_at": "2025-12-15 12:00:00",
     *             "expires_at": "2025-12-16 12:00:00"
     *           }
     *         ]
     *       }
     *     ]
     *   }
     * }
     * 
     * @response 422 scenario="validation error" {
     *   "message": "The platform id field is required.",
     *   "errors": {
     *     "platform_id": ["The platform id field is required."]
     *   }
     * }
     * 
     * @response 500 scenario="error" {
     *   "success": false,
     *   "message": "حدث خطأ أثناء جلب المسابقات",
     *   "error": "Error details"
     * }
     */
    public function getByPlatform(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'platform_id' => 'required|exists:platforms,id',
            ]);

            $platform = Platform::find($request->platform_id)->select('id', 'name', 'display_name', 'name_ar')->first();

            $contests = Contest::with(['platform', 'user'])
                ->where('platform_id', $request->platform_id)
                ->where('is_active', true)
                ->where('end_date', '>=', now())
                ->orderBy('created_at', 'desc')
                ->get();

            // Get all viewable stories (not filtered by platform)
            $stories = Story::with(['user', 'contest'])
                ->withCount('views') // Add views count
                ->viewable() // Active and not expired
                ->orderBy('created_at', 'desc')
                ->get();

            // Current user ID for checking if they viewed the story
            $currentUserId = $request->user()->id;

            // Group stories by user
            $storiesGrouped = $stories->groupBy('user_id')->map(function ($userStories) use ($currentUserId) {
                $user = $userStories->first()->user;
                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'user_name' => $user->user_name,
                        'image' => $user->image ? asset('storage/' . $user->image) : null,
                    ],
                    'stories' => $userStories->map(function ($story) use ($currentUserId) {
                        return [
                            'id' => $story->id,
                            'media_path' => $story->media_path ? asset('storage/' . $story->media_path) : null,
                            'media_type' => $story->media_type,
                            'caption' => $story->caption,
                            'contest_id' => $story->contest_id,
                            'contest_title' => $story->contest ? $story->contest->title : null,
                            'views_count' => $story->views_count, // Total views
                            'is_viewed' => $story->isViewedBy($currentUserId), // Did current user view it?
                            'created_at' => $story->created_at->format('Y-m-d H:i:s'),
                            'expires_at' => $story->expires_at ? $story->expires_at->format('Y-m-d H:i:s') : null,
                        ];
                    })->values(),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'platform' => $platform,
                    'contests' => $contests->map(function ($contest) {
                        return [
                            'id' => $contest->id,
                            'title' => $contest->title,
                            'description' => $contest->description,
                            'image' => $contest->image ? asset('storage/' . $contest->image) : null,
                            'owner' => [
                                'id' => $contest->user->id,
                                'name' => $contest->user->name,
                                'image' => $contest->user->image ? asset('storage/' . $contest->user->image) : null,
                            ],
                            'days_ago' => $this->getTimeAgo($contest->created_at),
                        ];
                    }),
                    'stories' => $storiesGrouped,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المسابقات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
