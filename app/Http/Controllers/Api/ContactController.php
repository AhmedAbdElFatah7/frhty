<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\PrivacyPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Store Contact Message
     * 
     * Receives and stores a contact message from authenticated users.
     * 
     * @authenticated
     * 
     * @bodyParam name string required The sender's name. Example: أحمد محمد
     * @bodyParam email string optional The sender's email. Example: ahmed@example.com
     * @bodyParam phone string required The sender's phone number. Example: 01012345678
     * @bodyParam message string required The message content. Example: أريد الاستفسار عن المسابقات
     * 
     * @response 201 scenario="success" {
     *   "success": true,
     *   "message": "تم إرسال رسالتك بنجاح، سنتواصل معك قريباً"
     * }
     * 
     * @response 422 scenario="validation error" {
     *   "success": false,
     *   "message": "بيانات غير صحيحة",
     *   "errors": {...}
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'required|string|max:20',
                'message' => 'required|string|max:2000',
            ], [
                'name.required' => 'الاسم مطلوب',
                'name.max' => 'الاسم طويل جداً',
                'email.email' => 'البريد الإلكتروني غير صحيح',
                'phone.required' => 'رقم الهاتف مطلوب',
                'message.required' => 'الرسالة مطلوبة',
                'message.max' => 'الرسالة طويلة جداً',
            ]);

            // Add user_id to validated data
            $validated['user_id'] = $user->id;

            Contact::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال رسالتك بنجاح، سنتواصل معك قريباً',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الرسالة',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get All Contact Messages
     * 
     * Returns all contact messages (for admin use).
     * 
     * @authenticated
     * 
     * @queryParam unread_only boolean optional Filter to show only unread messages. Example: true
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "messages": [
     *       {
     *         "id": 1,
     *         "name": "أحمد محمد",
     *         "email": "ahmed@example.com",
     *         "phone": "01012345678",
     *         "message": "أريد الاستفسار عن المسابقات",
     *         "is_read": false,
     *         "created_at": "2025-12-18 11:30:00"
     *       }
     *     ],
     *     "total": 10,
     *     "unread_count": 5
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Contact::orderBy('created_at', 'desc');

            // Filter unread only if requested
            if ($request->boolean('unread_only')) {
                $query->where('is_read', false);
            }

            $messages = $query->get();
            $unreadCount = Contact::where('is_read', false)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'messages' => $messages->map(function ($contact) {
                        return [
                            'id' => $contact->id,
                            'name' => $contact->name,
                            'email' => $contact->email,
                            'phone' => $contact->phone,
                            'message' => $contact->message,
                            'is_read' => $contact->is_read,
                            'created_at' => $contact->created_at->format('Y-m-d H:i:s'),
                            'created_at_formatted' => $contact->created_at->diffForHumans(),
                        ];
                    }),
                    'total' => $messages->count(),
                    'unread_count' => $unreadCount,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الرسائل',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark Message as Read
     * 
     * Marks a contact message as read.
     * 
     * @authenticated
     * 
     * @urlParam id integer required The ID of the message. Example: 1
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "تم تحديث حالة الرسالة"
     * }
     */
    public function markAsRead($id): JsonResponse
    {
        try {
            $contact = Contact::findOrFail($id);
            $contact->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الرسالة',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'الرسالة غير موجودة',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Privacy Policy
     * 
     * Returns the privacy policy content (title and text).
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "title": "سياسة الخصوصية",
     *     "text": "نص سياسة الخصوصية..."
     *   }
     * }
     */
    public function getPrivacyPolicy(): JsonResponse
    {
        try {
            $policy = PrivacyPolicy::first();

            if (!$policy) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا توجد بيانات',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'title' => $policy->title,
                    'text' => $policy->text,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
