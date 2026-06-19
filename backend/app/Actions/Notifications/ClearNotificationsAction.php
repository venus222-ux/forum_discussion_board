<?php
namespace App\Actions\Notifications;

class ClearNotificationsAction
{
    public function execute($user): array
    {
        $notifications = $user->notifications()
            ->latest()
            ->take(10)
            ->get();

        $user->unreadNotifications->each(function ($n) {
            $n->markAsRead();
        });

        $user->notifications()->delete();

        return [
            'status' => 'ok',
            'data' => $notifications,
            'unread_count' => 0,
        ];
    }
}
