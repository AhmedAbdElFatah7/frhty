<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Chat
 */
class ChatController extends Controller
{
    /**
     * Get all conversations for the authenticated user
     * 
     * Returns a list of all conversations with the last message and unread count
     * 
     * @response 200 scenario=success {
     *   "success": true,
     *   "message": "تم جلب المحادثات بنجاح",
     *   "data": {
     *     "conversations": [
     *       {
     *         "id": 1,
     *         "other_user": {
     *           "id": 2,
     *           "name": "أحمد علي",
     *           "user_name": "ahmed_ali",
     *           "image": "https://example.com/image.jpg"
     *         },
     *         "last_message": {
     *           "message": "مرحبا",
     *           "sender_id": 2,
     *           "created_at": "2025-12-15 12:30:00",
     *           "is_mine": false
     *         },
     *         "unread_count": 3,
     *         "last_message_at": "2025-12-15 12:30:00"
     *       }
     *     ]
     *   }
     * }
     */
    public function index()
    {
        $user = Auth::user();

        // Get all conversations for current user
        $conversations = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->with(['userOne', 'userTwo', 'latestMessage'])
            ->orderBy('last_message_at', 'desc')
            ->get();

        $result = $conversations->map(function ($conversation) use ($user) {
            $otherUser = $conversation->getOtherUser($user->id);
            $lastMessage = $conversation->latestMessage;

            return [
                'id' => $conversation->id,
                'other_user' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'user_name' => $otherUser->user_name,
                    'image' => $otherUser->image ? asset('storage/' . $otherUser->image) : null,
                ],
                'last_message' => $lastMessage ? [
                    'message' => $lastMessage->message,
                    'sender_id' => $lastMessage->sender_id,
                    'created_at' => $lastMessage->created_at->format('Y-m-d H:i:s'),
                    'is_mine' => $lastMessage->sender_id == $user->id,
                ] : null,
                'unread_count' => $conversation->unreadCount($user->id),
                'last_message_at' => $conversation->last_message_at?->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'تم جلب المحادثات بنجاح',
            'data' => [
                'conversations' => $result,
            ],
        ]);
    }

    /**
     * Get or create a conversation with a specific user and get all messages
     * 
     * If conversation doesn't exist, it will be created automatically
     * 
     * @urlParam userId integer required The ID of the user to chat with. Example: 5
     * 
     * @response 200 scenario=success {
     *   "success": true,
     *   "message": "تم جلب المحادثة بنجاح",
     *   "data": {
     *     "conversation": {
     *       "id": 1,
     *       "other_user": {
     *         "id": 5,
     *         "name": "أحمد علي",
     *         "user_name": "ahmed_ali",
     *         "image": "https://example.com/image.jpg"
     *       },
     *       "messages": [
     *         {
     *           "id": 1,
     *           "message": "مرحبا",
     *           "sender_id": 5,
     *           "is_mine": false,
     *           "is_read": true,
     *           "created_at": "2025-12-15 12:00:00"
     *         }
     *       ]
     *     }
     *   }
     * }
     * 
     * @response 404 scenario="user not found" {
     *   "success": false,
     *   "message": "المستخدم غير موجود"
     * }
     */
    public function show($userId)
    {
        $currentUser = Auth::user();

        // Check if other user exists
        $otherUser = User::find($userId);
        if (!$otherUser) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير موجود',
            ], 404);
        }

        // Can't chat with yourself
        if ($currentUser->id == $userId) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك المحادثة مع نفسك',
            ], 400);
        }

        // Find or create conversation
        $conversation = Conversation::findOrCreateConversation($currentUser->id, $userId);

        // Get all messages
        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark all unread messages as read
        $conversation->messages()
            ->where('sender_id', '!=', $currentUser->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب المحادثة بنجاح',
            'data' => [
                'conversation' => [
                    'id' => $conversation->id,
                    'other_user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'user_name' => $otherUser->user_name,
                        'image' => $otherUser->image ? asset('storage/' . $otherUser->image) : null,
                    ],
                    'messages' => $messages->map(function ($message) use ($currentUser) {
                        return [
                            'id' => $message->id,
                            'message' => $message->message,
                            'sender_id' => $message->sender_id,
                            'is_mine' => $message->sender_id == $currentUser->id,
                            'is_read' => $message->is_read,
                            'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                        ];
                    })->values(),
                ],
            ],
        ]);
    }

    /**
     * Send a message to a user
     * 
     * Creates a new message in an existing or new conversation
     * 
     * @bodyParam receiver_id integer required The ID of the user to send message to. Example: 5
     * @bodyParam message string required The message content (max 2000 chars). Example: مرحبا، كيف حالك؟
     * 
     * @response 200 scenario=success {
     *   "success": true,
     *   "message": "تم إرسال الرسالة بنجاح",
     *   "data": {
     *     "message": {
     *       "id": 1,
     *       "conversation_id": 1,
     *       "message": "مرحبا، كيف حالك؟",
     *       "sender_id": 1,
     *       "is_mine": true,
     *       "is_read": false,
     *       "created_at": "2025-12-15 12:30:00"
     *     }
     *   }
     * }
     * 
     * @response 400 scenario="self message" {
     *   "success": false,
     *   "message": "لا يمكنك إرسال رسالة لنفسك"
     * }
     */
    public function sendMessage(SendMessageRequest $request)
    {
        $currentUser = Auth::user();
        $receiverId = $request->receiver_id;

        // Can't send message to yourself
        if ($currentUser->id == $receiverId) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إرسال رسالة لنفسك',
            ], 400);
        }

        // Find or create conversation
        $conversation = Conversation::findOrCreateConversation($currentUser->id, $receiverId);

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $currentUser->id,
            'message' => $request->message,
        ]);

        // Update last message time
        $conversation->update([
            'last_message_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الرسالة بنجاح',
            'data' => [
                'message' => [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'message' => $message->message,
                    'sender_id' => $message->sender_id,
                    'is_mine' => true,
                    'is_read' => $message->is_read,
                    'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                ],
            ],
        ], 201);
    }

    /**
     * Delete a conversation
     * 
     * Deletes a conversation and all its messages
     * 
     * @urlParam conversationId integer required The ID of the conversation to delete. Example: 1
     * 
     * @response 200 scenario=success {
     *   "success": true,
     *   "message": "تم حذف المحادثة بنجاح"
     * }
     * 
     * @response 404 scenario="not found" {
     *   "success": false,
     *   "message": "المحادثة غير موجودة"
     * }
     * 
     * @response 403 scenario="not authorized" {
     *   "success": false,
     *   "message": "غير مصرح لك بحذف هذه المحادثة"
     * }
     */
    public function deleteConversation($conversationId)
    {
        $currentUser = Auth::user();

        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'المحادثة غير موجودة',
            ], 404);
        }

        // Check if user is part of this conversation
        if ($conversation->user_one_id != $currentUser->id && $conversation->user_two_id != $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بحذف هذه المحادثة',
            ], 403);
        }

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المحادثة بنجاح',
        ]);
    }

    /**
     * Get unread messages count
     * 
     * Returns the total number of unread messages across all conversations
     * 
     * @response 200 scenario=success {
     *   "success": true,
     *   "message": "تم جلب عدد الرسائل غير المقروءة بنجاح",
     *   "data": {
     *     "unread_count": 5
     *   }
     * }
     */
    public function unreadCount()
    {
        $user = Auth::user();

        // Get all conversation IDs for this user
        $conversationIds = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->pluck('id');

        // Count unread messages in these conversations
        $unreadCount = Message::whereIn('conversation_id', $conversationIds)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب عدد الرسائل غير المقروءة بنجاح',
            'data' => [
                'unread_count' => $unreadCount,
            ],
        ]);
    }
}
