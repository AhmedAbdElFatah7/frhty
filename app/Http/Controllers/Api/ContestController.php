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

    public function __construct(Request $request)
    {
        $user = $request->user();
        if ($user->role != 'celebrity') {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بالدخول لهذه الصفحة',
            ], 401);
        }
    }

    public function platforms(Request $request)
    {
        $user = $request->user();

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
                        'account_url' => $platform->pivot->account_url,
                        'followers_count' => $platform->pivot->followers_count,
                    ];
                }),
                'total' => $platforms->count(),
            ],
        ], 200);
    }

    public function store(StoreContestRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();

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
     * Get all active contests.
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
     * Get a specific contest with all details.
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
}
