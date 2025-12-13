<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStoryRequest;
use App\Models\Story;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoryController extends Controller
{

    /**
     * Display a listing of all active stories.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $userId = $request->query('user_id');
        $contestId = $request->query('contest_id');

        $query = Story::with(['user:id,name,user_name,image', 'contest:id,title'])
            ->viewable()
            ->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($contestId) {
            $query->where('contest_id', $contestId);
        }

        $stories = $query->paginate(20);

        // Transform media_path to full URL
        $stories->getCollection()->transform(function ($story) {
            $story->media_path = $story->media_path ? asset('storage/' . $story->media_path) : null;
            return $story;
        });

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الستوريز بنجاح',
            'data' => $stories
        ], 200);
    }

    /**
     * Store a newly created story.
     * 
     * @param  \App\Http\Requests\StoreStoryRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreStoryRequest $request)
    {
        try {
            $user = $request->user();

            // Upload media file if provided
            $mediaPath = null;
            $mediaType = $request->input('media_type', 'image');

            if ($request->hasFile('media')) {
                $mediaFile = $request->file('media');

                // Determine storage folder based on media type
                $folder = $mediaType === 'video' ? 'stories/videos' : 'stories/images';

                // Store file
                $mediaPath = $mediaFile->store($folder, 'public');
            }

            // Create story with 24 hours expiration
            $story = Story::create([
                'user_id' => $user->id,
                'contest_id' => $request->input('contest_id'),
                'media_path' => $mediaPath,
                'media_type' => $mediaType,
                'caption' => $request->input('caption'),
                'expires_at' => Carbon::now()->addHours(24),
                'is_active' => true,
            ]);

            // Load relationships
            $story->load(['user:id,name,user_name,image', 'contest:id,title']);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الستوري بنجاح',
                'data' => [
                    'story' => [
                        'id' => $story->id,
                        'media_path' => $story->media_path ? asset('storage/' . $story->media_path) : null,
                        'media_type' => $story->media_type,
                        'caption' => $story->caption,
                        'expires_at' => $story->expires_at->format('Y-m-d H:i:s'),
                        'user' => $story->user,
                        'contest' => $story->contest,
                        'created_at' => $story->created_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الستوري',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
