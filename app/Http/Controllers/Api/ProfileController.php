<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Get Profile
     * 
     * Returns the authenticated user's complete profile information.
     * 
     * @authenticated
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "أحمد محمد",
     *       "user_name": "@ahmed",
     *       "phone": "0512345678",
     *       "role": "follower",
     *       "gender": "male",
     *       "image": "http://localhost:8000/storage/users/profile.jpg",
     *       "cover": "http://localhost:8000/storage/covers/cover.jpg",
     *       "verified": true,
     *       "completed": true,
     *       "followers_count": 100,
     *       "following_count": 50,
     *       "created_at": "2025-12-09 12:00:00"
     *     }
     *   }
     * }
     */
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Load counts
            $user->loadCount(['followers', 'following']);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'user_name' => $user->user_name,
                        'phone' => $user->phone,
                        'role' => $user->role,
                        'gender' => $user->gender,
                        'image' => $user->image ? asset('storage/' . $user->image) : null,
                        'cover' => $user->cover ? asset('storage/' . $user->cover) : null,
                        'verified' => $user->verified,
                        'completed' => $user->completed,
                        'followers_count' => $user->followers_count,
                        'following_count' => $user->following_count,
                        'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update Profile
     * 
     * Updates the authenticated user's profile information.
     * 
     * @authenticated
     * 
     * @bodyParam name string optional User's full name. Example: أحمد محمد علي
     * @bodyParam user_name string optional Username (unique, alphanumeric + underscore). Example: ahmed_2025
     * @bodyParam gender string optional Gender (male, female, other). Example: male
     * @bodyParam image file optional Profile image (max 6MB).
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "تم تحديث البيانات بنجاح",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "أحمد محمد علي",
     *       "user_name": "@ahmed_2025",
     *       "gender": "male",
     *       "image": "http://localhost:8000/storage/users/new_profile.jpg"
     *     }
     *   }
     * }
     * 
     * @response 422 scenario="validation error" {
     *   "success": false,
     *   "message": "بيانات غير صحيحة",
     *   "errors": {
     *     "user_name": ["اسم المستخدم مستخدم من قبل"]
     *   }
     * }
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $updateData = [];

            // Update name if provided
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }

            // Update user_name if provided
            if ($request->has('user_name')) {
                $updateData['user_name'] = $request->user_name;
            }

            // Update gender if provided
            if ($request->has('gender')) {
                $updateData['gender'] = $request->gender;
            }

            // Update image if provided
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($user->image) {
                    Storage::disk('public')->delete($user->image);
                }

                // Store new image
                $imagePath = $request->file('image')->store('users', 'public');
                $updateData['image'] = $imagePath;
            }

            // Update user
            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث البيانات بنجاح',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'user_name' => $user->user_name,
                        'phone' => $user->phone,
                        'role' => $user->role,
                        'gender' => $user->gender,
                        'image' => $user->image ? asset('storage/' . $user->image) : null,
                        'cover' => $user->cover ? asset('storage/' . $user->cover) : null,
                        'verified' => $user->verified,
                        'completed' => $user->completed,
                    ],
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث البيانات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Following List
     * 
     * Returns list of celebrities that the user is following.
     * 
     * @authenticated
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "following": [
     *       {
     *         "id": 5,
     *         "name": "محمد السعيد",
     *         "user_name": "@mohammed",
     *         "image": "http://localhost:8000/storage/users/profile.jpg",
     *         "followers_count": 5000,
     *         "followed_at": "2025-12-10 14:30:00"
     *       }
     *     ],
     *     "total": 10
     *   }
     * }
     */
    public function getFollowing(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get users that this user is following
            $following = $user->following()
                ->withCount('followers')
                ->get()
                ->map(function ($celebrity) {
                    return [
                        'id' => $celebrity->id,
                        'name' => $celebrity->name,
                        'user_name' => $celebrity->user_name,
                        'image' => $celebrity->image ? asset('storage/' . $celebrity->image) : null,
                        'cover' => $celebrity->cover ? asset('storage/' . $celebrity->cover) : null,
                        'followers_count' => $celebrity->followers_count,
                        'followed_at' => $celebrity->pivot->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'following' => $following,
                    'total' => $following->count(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
