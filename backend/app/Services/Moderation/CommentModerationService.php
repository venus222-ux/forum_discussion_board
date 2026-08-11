<?php

namespace App\Services\Moderation;

use App\Models\Comment;
use App\Models\CommentFlag;
use App\Models\Thread;

class CommentModerationService
{
    public function autoHide(string $commentId): void
    {
        $count = CommentFlag::where('comment_id', $commentId)
            ->where('status', 'pending')
            ->count();

        $comment = Comment::find($commentId);
        if (! $comment) {
            return;
        }

        $wasHidden = $comment->is_hidden;

        $updateData = null;

        if ($comment->ai_hate_label === 'offensive' && $count >= 3) {
            $updateData = [
                'is_hidden' => true,
                'status' => 'hidden',
                'moderation_reason' => 'AI hate detection + community flags',
            ];
        } elseif ($comment->ai_label === 'toxic' && $count >= 3) {
            $updateData = [
                'is_hidden' => true,
                'status' => 'hidden',
                'moderation_reason' => 'AI + community flags',
            ];
        } elseif ($count >= 5) {
            $updateData = [
                'is_hidden' => true,
                'status' => 'hidden',
                'moderation_reason' => 'Auto-hidden due to multiple flags',
            ];
        }

        if ($updateData) {
            $comment->update($updateData);

            if (! $wasHidden) {
                $thread = Thread::where('uuid', $comment->threadId)->first();

                broadcast(new \App\Events\CommentModerated(
                    $commentId,
                    true,
                    $updateData['moderation_reason'],
                    $thread->slug
                ))->toOthers();
            }
        }
    }
}
