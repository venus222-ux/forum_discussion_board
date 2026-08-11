<?php

namespace App\Actions\Moderation;

use App\Models\Comment;
use App\Models\CommentFlag;
use App\Models\ModerationLog;
use Illuminate\Support\Facades\Redis;

class ApproveCommentAction
{
    public function execute(string $commentId, $user, array $data = []): array
    {
        $comment = Comment::findOrFail($commentId);

        $comment->update([
            'is_hidden' => true,
            'status' => 'hidden',
            'moderation_reason' => $data['reason'] ?? 'Hidden by moderator',
        ]);

        CommentFlag::where('comment_id', $commentId)
            ->update(['status' => 'approved']);

        ModerationLog::create([
            'moderator_id' => $user->id,
            'action' => 'hide',
            'comment_id' => $commentId,
            'reason' => $data['reason'] ?? 'Hidden by moderator',
        ]);

        Redis::del("aggregated_flags:$commentId");

        return ['message' => 'Comment hidden'];
    }
}
