<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContestRequest;
use App\Models\Contest;
use App\Models\ContestTerm;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContestController extends Controller
{

    /**
     * Get My Platforms
     * 
     * Returns all social media platforms associated with the authenticated celebrity user.
     * Includes platform details and follower count.
     * 
     * @authenticated
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "تم الحصول على منصاتك بنجاح",
     *   "data": {
     *     "platforms": [
     *       {
     *         "id": 1,
     *         "name": "tiktok",
     *         "display_name": "TikTok",
     *         "account_url": "https://tiktok.com/@username",
     *         "followers_count": 50000
     *       }
     *     ],
     *     "total": 3
     *   }
     * }
     * 
     * @response 401 scenario="unauthorized" {
     *   "success": false,
     *   "message": "غير مسموح لك بالدخول لهذه الصفحة"
     * }
     */
    public function platforms(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role != 'celebrity') {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بالدخول لهذه الصفحة',
            ], 401);
        }


        $platforms = $user->platforms()->get();

        return response()->json([
            'success' => true,
            'message' => 'تم الحصول على منصاتك بنجاح',
            'data' => [
                'platforms' => $platforms->map(function ($platform) {
                    return [
                        'id' => $platform->id,
                        'name' => $platform->name,
                        'display_name' => $platform->display_name,
                        'name_ar' => $platform->name_ar,
                        'account_url' => $platform->pivot->account_url,
                        'followers_count' => $platform->pivot->followers_count,
                    ];
                }),
                'total' => $platforms->count(),
            ],
        ], 200);
    }

    /**
     * Create Contest
     * 
     * Creates a new contest for a specific platform.
     * Only celebrities can create contests.
     * Includes contest details, terms, and questions.
     * 
     * @authenticated
     * 
     * @bodyParam platform_id integer required The ID of the platform. Example: 1
     * @bodyParam title string required The contest title. Example: مسابقة TikTok الكبرى
     * @bodyParam description string optional Contest description. Example: اختبر معلوماتك
     * @bodyParam image file optional Contest image (max 6MB).
     * @bodyParam start_date datetime required Contest start date. Example: 2025-12-10 00:00:00
     * @bodyParam end_date datetime required Contest end date. Example: 2025-12-20 23:59:59
     * @bodyParam max_attempts integer required Maximum attempts allowed (1-10). Example: 3
     * @bodyParam terms array optional Array of contest terms.
     * @bodyParam terms.* string optional Contest term text. Example: يجب أن تكون متابعاً للحساب
     * @bodyParam questions array required Array of questions (min 1).
     * @bodyParam questions.*.question_text string required Question text. Example: ما هي عاصمة السعودية؟
     * @bodyParam questions.*.option_1 string required First option. Example: الرياض
     * @bodyParam questions.*.option_2 string required Second option. Example: جدة
     * @bodyParam questions.*.option_3 string required Third option. Example: مكة
     * @bodyParam questions.*.correct_answer string required Correct answer (1, 2, or 3). Example: 1
     * 
     * @response 201 scenario="success" {
     *   "success": true,
     *   "message": "تم إنشاء المسابقة بنجاح",
     *   "data": {
     *     "contest": {
     *       "id": 1,
     *       "title": "مسابقة TikTok الكبرى",
     *       "platform": {...},
     *       "celebrity": {...},
     *       "questions": [...],
     *       "terms": [...]
     *     }
     *   }
     * }
     * 
     * @response 401 scenario="unauthorized" {
     *   "success": false,
     *   "message": "غير مسموح لك بالدخول لهذه الصفحة"
     * }
     * 
     * @response 500 scenario="error" {
     *   "success": false,
     *   "message": "حدث خطأ أثناء إنشاء المسابقة",
     *   "error": "Error details"
     * }
     */
    public function store(StoreContestRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();

            if (!$user || $user->role != 'celebrity') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مسموح لك بالدخول لهذه الصفحة',
                ], 401);
            }


            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('contests', 'public');
            }

            $contest = Contest::create([
                'user_id' => $user->id,
                'platform_id' => $request->platform_id,
                'title' => $request->title,
                'description' => $request->description,
                'image' => $imagePath,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => true,
                'max_attempts' => $request->max_attempts,
            ]);

            // Add terms if provided
            if ($request->has('terms') && is_array($request->terms)) {
                foreach ($request->terms as $index => $termText) {
                    ContestTerm::create([
                        'contest_id' => $contest->id,
                        'term' => $termText,
                        'order' => $index + 1,
                    ]);
                }
            }

            // Add questions
            foreach ($request->questions as $index => $questionData) {
                Question::create([
                    'contest_id' => $contest->id,
                    'question_text' => $questionData['question_text'],
                    'option_1' => $questionData['option_1'],
                    'option_2' => $questionData['option_2'],
                    'option_3' => $questionData['option_3'],
                    'correct_answer' => $questionData['correct_answer'],
                    'order' => $index + 1,
                ]);
            }

            DB::commit();

            // Load relationships for response
            $contest->load(['platform', 'questions', 'terms', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المسابقة بنجاح',
                'data' => [
                    'contest' => [
                        'id' => $contest->id,
                        'title' => $contest->title,
                        'description' => $contest->description,
                        'image' => $contest->image ? asset('storage/' . $contest->image) : null,
                        'start_date' => $contest->start_date->format('Y-m-d H:i:s'),
                        'end_date' => $contest->end_date->format('Y-m-d H:i:s'),
                        'max_attempts' => $contest->max_attempts,
                        'is_active' => $contest->is_active,
                        'platform' => [
                            'id' => $contest->platform->id,
                            'name' => $contest->platform->name,
                            'display_name' => $contest->platform->display_name,
                        ],
                        'celebrity' => [
                            'id' => $contest->user->id,
                            'name' => $contest->user->name,
                            'user_name' => $contest->user->user_name,
                        ],
                        'terms' => $contest->terms->map(function ($term) {
                            return [
                                'id' => $term->id,
                                'term' => $term->term,
                                'order' => $term->order,
                            ];
                        }),
                        'questions' => $contest->questions->map(function ($question) {
                            return [
                                'id' => $question->id,
                                'question_text' => $question->question_text,
                                'options' => [
                                    '1' => $question->option_1,
                                    '2' => $question->option_2,
                                    '3' => $question->option_3,
                                ],
                                'order' => $question->order,
                                // Don't send correct_answer in response
                            ];
                        }),
                        'questions_count' => $contest->questions->count(),
                        'terms_count' => $contest->terms->count(),
                        'created_at' => $contest->created_at->format('Y-m-d H:i:s'),
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء المسابقة',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get All Active Contests
     * 
     * Returns a list of all active contests.
     * Only returns contests where is_active=true and end_date >= now.
     * Results are ordered by creation date (newest first).
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
     *         "start_date": "2025-12-10 00:00:00",
     *         "end_date": "2025-12-20 23:59:59",
     *         "max_attempts": 3,
     *         "platform": {...},
     *         "celebrity": {...},
     *         "questions_count": 3,
     *         "terms_count": 2,
     *         "is_active": true
     *       }
     *     ],
     *     "total": 5
     *   }
     * }
     * 
     * @response 500 scenario="error" {
     *   "success": false,
     *   "message": "حدث خطأ أثناء جلب المسابقات",
     *   "error": "Error details"
     * }
     */
    public function index(): JsonResponse
    {
        try {
            $contests = Contest::with(['platform', 'user', 'questions', 'terms'])
                ->where('is_active', true)
                ->where('end_date', '>=', now())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'contests' => $contests->map(function ($contest) {
                        return [
                            'id' => $contest->id,
                            'title' => $contest->title,
                            'description' => $contest->description,
                            'image' => $contest->image ? asset('storage/' . $contest->image) : null,
                            'start_date' => $contest->start_date->format('Y-m-d H:i:s'),
                            'end_date' => $contest->end_date->format('Y-m-d H:i:s'),
                            'max_attempts' => $contest->max_attempts,
                            'platform' => [
                                'id' => $contest->platform->id,
                                'name' => $contest->platform->name,
                                'display_name' => $contest->platform->display_name,
                                'name_ar' => $contest->platform->name_ar,
                            ],
                            'celebrity' => [
                                'id' => $contest->user->id,
                                'name' => $contest->user->name,
                                'user_name' => $contest->user->user_name,
                            ],
                            'questions_count' => $contest->questions->count(),
                            'terms_count' => $contest->terms->count(),
                            'is_active' => $contest->isActive(),
                        ];
                    }),
                    'total' => $contests->count(),
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
     * Get Contest Details
     * 
     * Returns detailed information about a specific contest.
     * Includes platform, celebrity, questions, and terms.
     * Questions are returned without correct answers for security.
     * 
     * @urlParam id integer required The ID of the contest. Example: 1
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "contest": {
     *       "id": 1,
     *       "title": "مسابقة TikTok الكبرى",
     *       "description": "اختبر معلوماتك",
     *       "image": "http://localhost:8000/storage/contests/image.jpg",
     *       "start_date": "2025-12-10 00:00:00",
     *       "end_date": "2025-12-20 23:59:59",
     *       "max_attempts": 3,
     *       "is_active": true,
     *       "platform": {
     *         "id": 1,
     *         "name": "tiktok",
     *         "display_name": "TikTok"
     *       },
     *       "celebrity": {
     *         "id": 1,
     *         "name": "أحمد محمد",
     *         "user_name": "ahmed_celebrity",
     *         "image": "http://localhost:8000/storage/users/profile.jpg"
     *       },
     *       "terms": [...],
     *       "questions": [...],
     *       "questions_count": 3,
     *       "terms_count": 2
     *     }
     *   }
     * }
     * 
     * @response 404 scenario="not found" {
     *   "success": false,
     *   "message": "المسابقة غير موجودة"
     * }
     * 
     * @response 500 scenario="error" {
     *   "success": false,
     *   "message": "حدث خطأ أثناء جلب المسابقة",
     *   "error": "Error details"
     * }
     */
    public function show($id): JsonResponse
    {
        try {
            $contest = Contest::with(['platform', 'user', 'questions', 'terms'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'contest' => [
                        'id' => $contest->id,
                        'title' => $contest->title,
                        'description' => $contest->description,
                        'image' => $contest->image ? asset('storage/' . $contest->image) : null,
                        'start_date' => $contest->start_date->format('Y-m-d H:i:s'),
                        'end_date' => $contest->end_date->format('Y-m-d H:i:s'),
                        'max_attempts' => $contest->max_attempts,
                        'is_active' => $contest->isActive(),
                        'platform' => [
                            'id' => $contest->platform->id,
                            'name' => $contest->platform->name,
                            'display_name' => $contest->platform->display_name,
                            'name_ar' => $contest->platform->name_ar,
                        ],
                        'celebrity' => [
                            'id' => $contest->user->id,
                            'name' => $contest->user->name,
                            'user_name' => $contest->user->user_name,
                            'image' => $contest->user->image ? asset('storage/' . $contest->user->image) : null,
                        ],
                        'terms' => $contest->terms->map(function ($term) {
                            return [
                                'id' => $term->id,
                                'term' => $term->term,
                                'order' => $term->order,
                            ];
                        }),
                        'questions' => $contest->questions->map(function ($question) {
                            return [
                                'id' => $question->id,
                                'question_text' => $question->question_text,
                                'options' => [
                                    '1' => $question->option_1,
                                    '2' => $question->option_2,
                                    '3' => $question->option_3,
                                ],
                                'order' => $question->order,
                            ];
                        }),
                        'questions_count' => $contest->questions->count(),
                        'terms_count' => $contest->terms->count(),
                    ],
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'المسابقة غير موجودة',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المسابقة',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get My Contests
     * 
     * Returns all contests created by the authenticated user.
     * Only available for celebrity users.
     * 
     * @authenticated
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "تم جلب مسابقاتك بنجاح",
     *   "data": {
     *     "contests": [...],
     *     "total": 5
     *   }
     * }
     * 
     * @response 500 scenario="error" {
     *   "success": false,
     *   "message": "حدث خطأ أثناء جلب المسابقات",
     *   "error": "Error details"
     * }
     */
    public function myContests(Request $request)
    {
        try {
            $user = $request->user();

            $contests = Contest::where('user_id', $user->id)
                ->where('end_date', '>=', now())
                ->select('id', 'title')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'تم جلب مسابقاتك بنجاح',
                'data' => $contests,
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
     * Get Contest for Attempt
     * 
     * Returns contest details with user's attempt status.
     * Checks if user can still attempt the contest.
     * 
     * @authenticated
     * 
     * @urlParam id integer required The ID of the contest. Example: 1
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "contest": {
     *       "id": 1,
     *       "title": "مسابقة TikTok الكبرى",
     *       "description": "اختبر معلوماتك",
     *       "image": "http://localhost:8000/storage/contests/image.jpg",
     *       "max_attempts": 3,
     *       "questions_count": 5,
     *       "platform": {...},
     *       "celebrity": {...},
     *       "terms": [...]
     *     },
     *     "user_status": {
     *       "attempts_used": 1,
     *       "attempts_remaining": 2,
     *       "can_attempt": true,
     *       "last_score": 3,
     *       "last_percentage": 60.00
     *     }
     *   }
     * }
     */
    public function getContestForAttempt(Request $request, $id): JsonResponse
    {
        try {
            $contest = Contest::with(['platform', 'user', 'terms'])
                ->withCount('questions')
                ->findOrFail($id);

            $user = $request->user();

            // Get user's attempts for this contest
            $attempts = \App\Models\ContestAttempt::where('contest_id', $id)
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $attemptsUsed = $attempts->count();
            $attemptsRemaining = $contest->max_attempts - $attemptsUsed;
            $canAttempt = $contest->canUserAttempt($user) && $contest->isActive();

            $lastAttempt = $attempts->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'contest' => [
                        'id' => $contest->id,
                        'title' => $contest->title,
                        'description' => $contest->description,
                        'image' => $contest->image ? asset('storage/' . $contest->image) : null,
                        'start_date' => $contest->start_date->format('Y-m-d H:i:s'),
                        'end_date' => $contest->end_date->format('Y-m-d H:i:s'),
                        'max_attempts' => $contest->max_attempts,
                        'questions_count' => $contest->questions_count,
                        'is_active' => $contest->isActive(),
                        'platform' => [
                            'id' => $contest->platform->id,
                            'name' => $contest->platform->name,
                            'display_name' => $contest->platform->display_name,
                            'name_ar' => $contest->platform->name_ar,
                        ],
                        'celebrity' => [
                            'id' => $contest->user->id,
                            'name' => $contest->user->name,
                            'user_name' => $contest->user->user_name,
                            'image' => $contest->user->image ? asset('storage/' . $contest->user->image) : null,
                        ],
                        'terms' => $contest->terms->map(function ($term) {
                            return [
                                'id' => $term->id,
                                'term' => $term->term,
                                'order' => $term->order,
                            ];
                        }),
                    ],
                    'user_status' => [
                        'attempts_used' => $attemptsUsed,
                        'attempts_remaining' => $attemptsRemaining,
                        'can_attempt' => $canAttempt,
                        'last_score' => $lastAttempt ? $lastAttempt->score : null,
                        'last_total' => $lastAttempt ? $lastAttempt->total_questions : null,
                        'last_percentage' => $lastAttempt ? $lastAttempt->percentage : null,
                    ],
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'المسابقة غير موجودة',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المسابقة',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Contest Questions
     * 
     * Returns all questions for a contest (without correct answers).
     * User must be able to attempt the contest.
     * 
     * @authenticated
     * 
     * @urlParam id integer required The ID of the contest. Example: 1
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "questions": [
     *       {
     *         "id": 1,
     *         "question_text": "ما هي عاصمة السعودية؟",
     *         "options": {
     *           "1": "الرياض",
     *           "2": "جدة",
     *           "3": "مكة"
     *         },
     *         "order": 1
     *       }
     *     ],
     *     "total_questions": 5
     *   }
     * }
     */
    public function getContestQuestions(Request $request, $id): JsonResponse
    {
        try {
            $contest = Contest::with('questions')->findOrFail($id);
            $user = $request->user();

            // Check if user can attempt this contest
            if (!$contest->canUserAttempt($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لقد استنفدت عدد محاولاتك المسموح بها',
                ], 403);
            }

            if (!$contest->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'المسابقة غير نشطة حالياً',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'questions' => $contest->questions->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'question_text' => $question->question_text,
                            'options' => [
                                '1' => $question->option_1,
                                '2' => $question->option_2,
                                '3' => $question->option_3,
                            ],
                            'order' => $question->order,
                        ];
                    }),
                    'total_questions' => $contest->questions->count(),
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'المسابقة غير موجودة',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الأسئلة',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit Contest Answers
     * 
     * Submits user's answers for a contest, evaluates them, and stores the results.
     * Returns scored results with correct/incorrect answers.
     * 
     * @authenticated
     * 
     * @urlParam id integer required The ID of the contest. Example: 1
     * 
     * @bodyParam answers array required Array of answers. Example: [{"question_id": 1, "selected_answer": "1"}]
     * @bodyParam answers.*.question_id integer required The question ID. Example: 1
     * @bodyParam answers.*.selected_answer string required Selected answer (1, 2, or 3). Example: "1"
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "تم إرسال إجاباتك بنجاح",
     *   "data": {
     *     "attempt": {
     *       "id": 1,
     *       "score": 3,
     *       "total_questions": 5,
     *       "percentage": 60.00,
     *       "completed_at": "2025-12-13 14:30:00"
     *     },
     *     "results": [
     *       {
     *         "question_id": 1,
     *         "question_text": "ما هي عاصمة السعودية؟",
     *         "selected_answer": "1",
     *         "correct_answer": "1",
     *         "is_correct": true
     *       }
     *     ]
     *   }
     * }
     */
    public function submitContestAnswers(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'answers' => 'required|array|min:1',
                'answers.*.question_id' => 'required|exists:questions,id',
                'answers.*.selected_answer' => 'required|in:1,2,3',
            ]);

            DB::beginTransaction();

            $contest = Contest::with('questions')->findOrFail($id);
            $user = $request->user();

            // Check if user can attempt
            if (!$contest->canUserAttempt($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لقد استنفدت عدد محاولاتك المسموح بها',
                ], 403);
            }

            if (!$contest->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'المسابقة غير نشطة حالياً',
                ], 403);
            }

            // Create attempt
            $attempt = \App\Models\ContestAttempt::create([
                'user_id' => $user->id,
                'contest_id' => $contest->id,
                'score' => 0,
                'total_questions' => $contest->questions->count(),
                'completed_at' => now(),
            ]);

            $score = 0;
            $results = [];

            // Process each answer
            foreach ($request->answers as $answerData) {
                $question = Question::findOrFail($answerData['question_id']);

                // Check if question belongs to this contest
                if ($question->contest_id != $contest->id) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'السؤال لا ينتمي لهذه المسابقة',
                    ], 400);
                }

                $isCorrect = $question->isCorrectAnswer($answerData['selected_answer']);

                if ($isCorrect) {
                    $score++;
                }

                // Store user answer
                \App\Models\UserAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_answer' => $answerData['selected_answer'],
                    'is_correct' => $isCorrect,
                ]);

                // Add to results
                $results[] = [
                    'question_id' => $question->id,
                    'question_text' => $question->question_text,
                    'selected_answer' => $answerData['selected_answer'],
                    'correct_answer' => $question->correct_answer,
                    'is_correct' => $isCorrect,
                ];
            }

            // Update attempt score
            $attempt->update(['score' => $score]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال إجاباتك بنجاح',
                'data' => [
                    'attempt' => [
                        'id' => $attempt->id,
                        'score' => $attempt->score,
                        'total_questions' => $attempt->total_questions,
                        'percentage' => $attempt->percentage,
                        'completed_at' => $attempt->completed_at->format('Y-m-d H:i:s'),
                    ],
                    'results' => $results,
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'المسابقة أو السؤال غير موجود',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الإجابات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
