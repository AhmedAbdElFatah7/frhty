<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Display a listing of posts.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $userId = $request->query('user_id');
        $perPage = $request->query('per_page', 20);

        $query = Post::with(['user:id,name,user_name,image'])
            ->orderBy('created_at', 'desc');

        // Filter by specific user if provided
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $posts = $query->paginate($perPage);

        // Transform posts to include full media URL and user's like status
        $currentUserId = $request->user() ? $request->user()->id : null;

        $posts->getCollection()->transform(function ($post) use ($currentUserId) {
            $post->media = $post->media ? asset('storage/' . $post->media) : null;
            $post->is_liked = $currentUserId ? $post->isLikedByUser($currentUserId) : false;
            return $post;
        });

        return response()->json([
            'success' => true,
            'message' => 'تم جلب المنشورات بنجاح',
            'data' => $posts
        ], 200);
    }

    /**
     * Store a newly created post.
     * 
     * @param  \App\Http\Requests\StorePostRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePostRequest $request)
    {
        try {
            $user = $request->user();

            // Upload media file if provided
            $mediaPath = null;
            $mediaType = $request->input('media_type', 'image');

            if ($request->hasFile('media')) {
                $mediaFile = $request->file('media');

                // Determine storage folder based on media type
                $folder = $mediaType === 'video' ? 'posts/videos' : 'posts/images';

                // Store file
                $mediaPath = $mediaFile->store($folder, 'public');
            }

            // Create post
            $post = Post::create([
                'user_id' => $user->id,
                'content' => $request->input('content'),
                'media' => $mediaPath,
                'media_type' => $request->hasFile('media') ? $mediaType : null,
            ]);

            // Load relationships
            $post->load(['user:id,name,user_name,image']);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المنشور بنجاح',
                'data' => [
                    'post' => [
                        'id' => $post->id,
                        'content' => $post->content,
                        'media' => $post->media ? asset('storage/' . $post->media) : null,
                        'media_type' => $post->media_type,
                        'likes_count' => $post->likes_count,
                        'is_liked' => false,
                        'user' => $post->user,
                        'created_at' => $post->created_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة المنشور',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified post.
     * 
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'المنشور غير موجود'
            ], 404);
        }

        // Check if user owns the post
        if ($post->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بحذف هذا المنشور'
            ], 403);
        }

        // Delete media file if exists
        if ($post->media) {
            Storage::disk('public')->delete($post->media);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المنشور بنجاح'
        ], 200);
    }

    /**
     * Toggle like/unlike a post.
     * 
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleLike(Request $request, $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'المنشور غير موجود'
            ], 404);
        }

        $user = $request->user();

        DB::beginTransaction();
        try {
            // Check if already liked
            $like = Like::where('user_id', $user->id)
                ->where('likeable_type', Post::class)
                ->where('likeable_id', $post->id)
                ->first();

            if ($like) {
                // Unlike
                $like->delete();
                $post->decrement('likes_count');
                $isLiked = false;
                $message = 'تم إلغاء الإعجاب';
            } else {
                // Like
                Like::create([
                    'user_id' => $user->id,
                    'likeable_type' => Post::class,
                    'likeable_id' => $post->id,
                ]);
                $post->increment('likes_count');
                $isLiked = true;
                $message = 'تم الإعجاب بالمنشور';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'is_liked' => $isLiked,
                    'likes_count' => $post->fresh()->likes_count
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الإعجاب',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
