<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Send a contest winner notification.
     */
    public function sendContestWinnerNotification(User $user, $contestTitle, $score, $totalQuestions, $contestId)
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => 'contest_winner',
            'title' => '🎉 مبروك! لقد فزت في المسابقة',
            'message' => "تهانينا! لقد أجبت على جميع الأسئلة بشكل صحيح في مسابقة \"{$contestTitle}\" وحصلت على {$score}/{$totalQuestions}",
            'data' => [
                'contest_id' => $contestId,
                'contest_title' => $contestTitle,
                'score' => $score,
                'total_questions' => $totalQuestions,
            ],
        ]);
    }

    /**
     * Send a new follower notification.
     */
    public function sendNewFollowerNotification(User $user, User $follower)
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => 'new_follower',
            'title' => 'متابع جديد',
            'message' => "{$follower->name} بدأ في متابعتك",
            'data' => [
                'follower_id' => $follower->id,
                'follower_name' => $follower->name,
                'follower_user_name' => $follower->user_name,
                'follower_image' => $follower->image,
            ],
        ]);
    }

    /**
     * Send a new post notification.
     */
    public function sendNewPostNotification(User $user, $postId, $celebrityName)
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => 'new_post',
            'title' => 'منشور جديد',
            'message' => "{$celebrityName} نشر منشوراً جديداً",
            'data' => [
                'post_id' => $postId,
                'celebrity_name' => $celebrityName,
            ],
        ]);
    }

    /**
     * Send a new contest notification.
     */
    public function sendNewContestNotification(User $user, $contestTitle, $contestId, $celebrityName)
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => 'new_contest',
            'title' => 'مسابقة جديدة',
            'message' => "{$celebrityName} أطلق مسابقة جديدة: {$contestTitle}",
            'data' => [
                'contest_id' => $contestId,
                'contest_title' => $contestTitle,
                'celebrity_name' => $celebrityName,
            ],
        ]);
    }

    /**
     * Get user notifications.
     */
    public function getUserNotifications(User $user, $limit = 50, $unreadOnly = false)
    {
        $query = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->unread();
        }

        return $query->limit($limit)->get();
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead($notificationId, User $user)
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Mark all user notifications as read.
     */
    public function markAllAsRead(User $user)
    {
        return Notification::where('user_id', $user->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get unread count.
     */
    public function getUnreadCount(User $user)
    {
        return Notification::where('user_id', $user->id)
            ->unread()
            ->count();
    }
}
