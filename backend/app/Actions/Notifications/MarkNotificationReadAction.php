<?php
namespace App\Actions\Notifications;

class MarkNotificationReadAction
{
    public function execute(string $id, $user): array
    {
        $notification = $user->notifications()->findOrFail($id);

        $notification->markAsRead();

        return [
            'success' => true
        ];
    }
}
