<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\VerifyRegistrationOtpRequest;
use App\Http\Requests\CompleteProfileRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Register New User
     * 
     * Creates a new user account and sends an OTP for verification.
     * User account will be created with verified=false status.
     * 
     * @bodyParam name string required User's full name. Example: أحمد محمد
     * @bodyParam phone string required Saudi phone number (05xxxxxxxx). Example: 0512345678
     * @bodyParam role string optional User role (follower or celebrity). Example: follower
     * 
     * @response 201 scenario="success" {
     *   "success": true,
     *   "message": "تم التسجيل بنجاح، تم إرسال رمز التحقق",
     *   "data": {
     *     "phone": "0512345678",
     *     "otp": "1234",
     *     "expires_in": "5 minutes",
     *     "user_id": 1
     *   }
     * }
     * 
     * @response 500 scenario="error" {
     *   "success": false,
     *   "message": "حدث خطأ أثناء التسجيل",
     *   "error": "Error details"
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // Create user with verified = false
            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'role' => $request->role ?? 'follower',
                'verified' => false,
            ]);

            // Generate and send OTP
            $otp = $this->otpService->createOtp($request->phone);

            return response()->json([
                'success' => true,
                'message' => 'تم التسجيل بنجاح، تم إرسال رمز التحقق',
                'data' => [
                    'phone' => $request->phone,
                    'otp' => $otp,
                    'expires_in' => '5 minutes',
                    'user_id' => $user->id,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التسجيل',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify Registration OTP
     * 
     * Verifies the OTP sent after registration and activates the user account.
     * Returns an authentication token upon successful verification.
     * 
     * @bodyParam phone string required The phone number used during registration. Example: 0512345678
     * @bodyParam otp string required The 4-digit OTP code. Example: 1234
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "تم التحقق من الحساب بنجاح",
     *   "data": {
     *     "user": {...},
     *     "token": "1|token...",
     *     "token_type": "Bearer"
     *   }
     * }
     * 
     * @response 401 scenario="invalid OTP" {
     *   "success": false,
     *   "message": "رمز التحقق غير صحيح أو منتهي الصلاحية"
     * }
     */
    public function verifyRegistrationOtp(VerifyRegistrationOtpRequest $request): JsonResponse
    {
        try {
            // Verify OTP
            if (!$this->otpService->verifyOtp($request->phone, $request->otp)) {
                return response()->json([
                    'success' => false,
                    'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية',
                ], 401);
            }

            // Get user by phone
            $user = User::where('phone', $request->phone)->firstOrFail();

            // Update verified status
            $user->update(['verified' => true]);

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'تم التحقق من الحساب بنجاح',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'role' => $user->role,
                        'verified' => $user->verified,
                        'completed' => $user->completed,
                        'image' => $user->image ? asset('storage/' . $user->image) : null,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحقق من الحساب',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login (Send OTP)
     * 
     * Initiates the login process by sending an OTP to the user's phone number.
     * User must exist in the database.
     * 
     * @bodyParam phone string required Registered phone number. Example: 0512345678
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "تم إرسال رمز التحقق",
     *   "data": {
     *     "phone": "0512345678",
     *     "otp": "1234",
     *     "expires_in": "5 minutes"
     *   }
     * }
     * 
     * @response 404 scenario="user not found" {
     *   "success": false,
     *   "message": "رقم الهاتف غير مسجل"
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Check if user exists
            $user = User::where('phone', $request->phone)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'رقم الهاتف غير مسجل',
                ], 404);
            }

            // Generate and send OTP
            $otp = $this->otpService->createOtp($request->phone);

            // في الإنتاج، يجب إرسال OTP عبر SMS
            // هنا نرجعه في الـ response للتطوير فقط
            return response()->json([
                'success' => true,
                'message' => 'تم إرسال رمز التحقق',
                'data' => [
                    'phone' => $request->phone,
                    'otp' => $otp, // فقط للتطوير - احذفه في الإنتاج
                    'expires_in' => '5 minutes',
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال رمز التحقق',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify OTP and Login
     * 
     * Verifies the OTP and logs the user in by generating an authentication token.
     * 
     * @bodyParam phone string required Phone number. Example: 0512345678
     * @bodyParam otp string required The 4-digit OTP code. Example: 1234
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "تم تسجيل الدخول بنجاح",
     *   "data": {
     *     "user": {...},
     *     "token": "1|token...",
     *     "token_type": "Bearer"
     *   }
     * }
     * 
     * @response 401 scenario="invalid OTP" {
     *   "success": false,
     *   "message": "رمز التحقق غير صحيح أو منتهي الصلاحية"
     * }
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            // Verify OTP
            if (!$this->otpService->verifyOtp($request->phone, $request->otp)) {
                return response()->json([
                    'success' => false,
                    'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية',
                ], 401);
            }

            // Get user
            $user = User::where('phone', $request->phone)->firstOrFail();

            // Ensure user is verified
            if (!$user->verified) {
                $user->update(['verified' => true]);
            }

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'user_name' => $user->user_name,
                        'phone' => $user->phone,
                        'role' => $user->role,
                        'gender' => $user->gender,
                        'verified' => $user->verified,
                        'completed' => $user->completed,
                        'image' => $user->image ? asset('storage/' . $user->image) : null,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحقق من رمز التحقق',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete Profile
     * 
     * Completes user profile with username, gender, image, and social platforms (for celebrities).
     * Marks profile as completed.
     * 
     * @authenticated
     * 
     * @bodyParam user_name string required Unique username. Example: ahmed_celebrity
     * @bodyParam gender string required User gender (male, female, other). Example: male
     * @bodyParam image file optional Profile image (max 6MB).
     * @bodyParam tiktok_url string optional (Celebrity only) TikTok account URL.
     * @bodyParam tiktok_followers integer optional (Celebrity only) TikTok followers count.
     * @bodyParam instagram_url string optional (Celebrity only) Instagram account URL.
     * @bodyParam instagram_followers integer optional (Celebrity only) Instagram followers count.
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "تم تحديث البيانات بنجاح",
     *   "data": {
     *     "user": {...},
     *     "platforms": [...]
     *   }
     * }
     */
    public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $data = [
                'user_name' => $request->user_name,
                'gender' => $request->gender,
                'completed' => true, // Mark profile as completed
            ];

            if ($request->hasFile('image')) {
                // Store image in public/users folder
                $path = $request->file('image')->store('users', 'public');
                $data['image'] = $path;
            }

            $user->update($data);

            // Handle platforms for celebrity users
            if ($user->role === 'celebrity') {
                // Detach all existing platforms
                $user->platforms()->detach();

                // Define platforms mapping with URLs and followers
                $platformsData = [
                    'tiktok' => ['url' => $request->tiktok_url, 'followers' => $request->tiktok_followers],
                    'snapchat' => ['url' => $request->snapchat_url, 'followers' => $request->snapchat_followers],
                    'youtube' => ['url' => $request->youtube_url, 'followers' => $request->youtube_followers],
                    'x' => ['url' => $request->x_url, 'followers' => $request->x_followers],
                    'instagram' => ['url' => $request->instagram_url, 'followers' => $request->instagram_followers],
                    'store' => ['url' => $request->store_url, 'followers' => $request->store_followers],
                ];

                // Get all platforms from database
                $platforms = \App\Models\Platform::whereIn('name', array_keys($platformsData))->get();

                // Attach platforms that have URLs
                foreach ($platforms as $platform) {
                    $data = $platformsData[$platform->name];

                    if (!empty($data['url'])) {
                        $user->platforms()->attach($platform->id, [
                            'account_url' => $data['url'],
                            'followers_count' => $data['followers'] ?? null,
                        ]);
                    }
                }
            }

            // Load platforms for response
            $user->load('platforms');

            // Format platforms for response
            $formattedPlatforms = $user->platforms->map(function ($platform) {
                return [
                    'id' => $platform->id,
                    'name' => $platform->name,
                    'display_name' => $platform->display_name,
                    'account_url' => $platform->pivot->account_url,
                    'followers_count' => $platform->pivot->followers_count,
                    'created_at' => $platform->pivot->created_at,
                    'updated_at' => $platform->pivot->updated_at,
                ];
            });

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
                        'verified' => $user->verified,
                        'completed' => $user->completed,
                        'image' => $user->image ? asset('storage/' . $user->image) : null,
                    ],
                    'platforms' => $formattedPlatforms,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث البيانات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout
     * 
     * Logs out the user by revoking the current access token.
     * 
     * @authenticated
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "تم تسجيل الخروج بنجاح"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الخروج',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Current User
     * 
     * Returns the authenticated user's information.
     * 
     * @authenticated
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "أحمد محمد",
     *       "phone": "0512345678",
     *       "role": "follower",
     *       "gender": "male",
     *       "image": "http://localhost:8000/storage/users/profile.jpg",
     *       "created_at": "2025-12-09 12:00:00"
     *     }
     *   }
     * }
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'role' => $user->role,
                        'gender' => $user->gender,
                        'image' => $user->image ? asset('storage/' . $user->image) : null,
                        'created_at' => $user->created_at,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات المستخدم',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
