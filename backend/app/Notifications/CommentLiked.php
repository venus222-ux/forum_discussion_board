<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class CommentLiked extends Notification
{
    use Queueable;

    public function __construct(
        public $comment,
        public $actor
    ) {}

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'comment_liked',
            'comment_id' => (string) $this->comment['_id'],
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'message' => "{$this->actor->name} liked your comment",
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage(
            $this->toArray($notifiable)
        );
    }
}
