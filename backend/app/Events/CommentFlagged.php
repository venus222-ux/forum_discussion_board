<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentFlagged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $commentId;

    public $totalFlags;

    public $flagIncrement;

    public $userId;

    public $threadSlug;

    /**
     * Create a new event instance.
     */
    public function __construct($commentId, $totalFlags, $flagIncrement, $userId, $threadSlug)
    {
        $this->commentId = $commentId;
        $this->totalFlags = $totalFlags;
        $this->flagIncrement = $flagIncrement;
        $this->userId = $userId;
        $this->threadSlug = $threadSlug;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('thread.comments.'.$this->threadSlug),
        ];
    }
}
