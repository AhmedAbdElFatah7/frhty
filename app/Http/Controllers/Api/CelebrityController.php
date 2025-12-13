<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CelebrityController extends Controller
{
    /**
     * Search for celebrities by name or username.
     * 
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->query('q');

        if (!$query || strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى إدخال كلمة بحث (حرفين على الأقل)'
            ], 400);
        }

        $celebrities = User::where('role', 'celebrity')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('user_name', 'LIKE', "%{$query}%");
            })
            ->select('id', 'name', 'user_name', 'image', 'cover')
            ->withCount(['followers', 'posts'])
            ->paginate(20);

        // Transform images to full URLs
        $celebrities->getCollection()->transform(function ($celebrity) {
            $celebrity->image = $celebrity->image ? asset('storage/' . $celebrity->image) : null;
            $celebrity->cover = $celebrity->cover ? asset('storage/' . $celebrity->cover) : null;
            return $celebrity;
        });

        return response()->json([
            'success' => true,
            'message' => 'تم البحث بنجاح',
            'data' => $celebrities
        ], 200);
    }

    /**
     * Get celebrity profile with all details.
     * 
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request, $id)
    {
        $celebrity = User::where('id', $id)
            ->where('role', 'celebrity')
            ->with([
                'platforms:id,name,name_ar,icon',
                'posts' => function ($query) {
                    $query->orderBy('created_at', 'desc')->limit(10);
                },
                'stories' => function ($query) {
                    $query->where('expires_at', '>', now())
                        ->where('is_active', true)
                        ->orderBy('created_at', 'desc');
                },
            ])
            ->withCount(['followers', 'following', 'posts'])
            ->first();

        if (!$celebrity) {
            return response()->json([
                'success' => false,
                'message' => 'الشخص المشهور غير موجود'
            ], 404);
        }

        // Get active contests created by this celebrity
        $contests = DB::table('contests')
            ->where('user_id', $id)
            ->where('end_date', '>=', now())
            ->select('id', 'title', 'description', 'image', 'platform_id', 'start_date', 'end_date', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform images/media to full URLs
        $celebrity->image = $celebrity->image ? asset('storage/' . $celebrity->image) : null;
        $celebrity->cover = $celebrity->cover ? asset('storage/' . $celebrity->cover) : null;

        // Transform platforms
        $celebrity->platforms->transform(function ($platform) use ($celebrity) {
            $platformData = $platform->toArray();
            $pivot = DB::table('user_platforms')
                ->where('user_id', $celebrity->id)
                ->where('platform_id', $platform->id)
                ->first();

            $platformData['account_url'] = $pivot->account_url ?? null;
            $platformData['followers_count'] = $pivot->followers_count ?? 0;
            $platformData['icon'] = $platform->icon ? asset('storage/' . $platform->icon) : null;

            return $platformData;
        });

        // Transform posts
        $currentUserId = $request->user() ? $request->user()->id : null;
        $celebrity->posts->transform(function ($post) use ($currentUserId) {
            $post->media = $post->media ? asset('storage/' . $post->media) : null;
            $post->is_liked = $currentUserId ? $post->isLikedByUser($currentUserId) : false;
            return $post;
        });

        // Transform stories
        $celebrity->stories->transform(function ($story) use ($currentUserId) {
            $story->media_path = $story->media_path ? asset('storage/' . $story->media_path) : null;
            return $story;
        });

        // Transform contests
        $contests = $contests->map(function ($contest) {
            $contest->image = $contest->image ? asset('storage/' . $contest->image) : null;
            return $contest;
        });

        // Check if current user is following this celebrity
        $isFollowing = false;
        if ($currentUserId) {
            $isFollowing = DB::table('follows')
                ->where('follower_id', $currentUserId)
                ->where('following_id', $id)
                ->exists();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم جلب البروفايل بنجاح',
            'data' => [
                'user' => [
                    'id' => $celebrity->id,
                    'name' => $celebrity->name,
                    'user_name' => $celebrity->user_name,
                    'image' => $celebrity->image,
                    'cover' => $celebrity->cover,
                    'gender' => $celebrity->gender,
                    'followers_count' => $celebrity->followers_count,
                    'following_count' => $celebrity->following_count,
                    'posts_count' => $celebrity->posts_count,
                    'is_following' => $isFollowing,
                ],
                'platforms' => $celebrity->platforms,
                'posts' => $celebrity->posts,
                'stories' => $celebrity->stories,
                'active_contests' => $contests,
            ]
        ], 200);
    }

    /**
     * Toggle follow/unfollow a celebrity.
     * 
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleFollow(Request $request, $id)
    {
        $currentUser = $request->user();

        $celebrity = User::where('id', $id)
            ->where('role', 'celebrity')
            ->first();

        if (!$celebrity) {
            return response()->json([
                'success' => false,
                'message' => 'الشخص المشهور غير موجود'
            ], 404);
        }

        if ($currentUser->id === $celebrity->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك متابعة نفسك'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $isFollowing = DB::table('follows')
                ->where('follower_id', $currentUser->id)
                ->where('following_id', $celebrity->id)
                ->exists();

            if ($isFollowing) {
                // Unfollow
                DB::table('follows')
                    ->where('follower_id', $currentUser->id)
                    ->where('following_id', $celebrity->id)
                    ->delete();

                $message = 'تم إلغاء المتابعة بنجاح';
                $isFollowingNow = false;
            } else {
                // Follow
                DB::table('follows')->insert([
                    'follower_id' => $currentUser->id,
                    'following_id' => $celebrity->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $message = 'تم المتابعة بنجاح';
                $isFollowingNow = true;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'is_following' => $isFollowingNow,
                    'followers_count' => $celebrity->followersCount()
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المتابعة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
