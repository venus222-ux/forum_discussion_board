<?php
namespace App\Actions\Notifications;

class ListNotificationsAction
{
    public function execute($user): array
    {
        $notifications = $user->notifications()
            ->latest()
            ->cursorPaginate(10);

        return [
            'data' => $notifications->items(),
            'next_cursor' => optional($notifications->nextCursor())->encode(),
            'unread_count' => $user->unreadNotifications()->count(),
        ];
    }
}
