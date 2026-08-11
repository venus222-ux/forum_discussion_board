<?php

namespace App\Actions\Moderation;

use App\Models\Comment;
use App\Models\CommentFlag;
use Illuminate\Support\Facades\Redis;

class RejectCommentAction
{
    public function execute(string $commentId): array
    {
        $comment = Comment::findOrFail($commentId);

        $comment->update([
            'is_hidden' => false,
            'status' => 'active',
            'moderation_reason' => null,
        ]);

        CommentFlag::where('comment_id', $commentId)
            ->update(['status' => 'rejected']);

        Redis::del("aggregated_flags:$commentId");

        return ['message' => 'Flag rejected'];
    }
}
