<?php

namespace App\Actions\Moderation;

use App\Models\Comment;
use App\Models\CommentFlag;
use App\Services\Moderation\CommentModerationService;
use Illuminate\Support\Facades\Redis;

class FlagCommentAction
{
    public function __construct(
        private CommentModerationService $moderationService
    ) {}

    public function execute(string $commentId, array $data, $user): array
    {
        $key = "user_flag_rate:{$user->id}:$commentId";

        if (Redis::exists($key) && Redis::get($key) >= 3) {
            throw new \Exception('Rate limit exceeded');
        }

        $count = Redis::incr($key);
        if ($count == 1) {
            Redis::expire($key, 3600);
        }

        if (CommentFlag::where('comment_id', $commentId)->where('user_id', $user->id)->exists()) {
            throw new \Exception('Already flagged');
        }

        CommentFlag::create([
            'comment_id' => $commentId,
            'user_id' => $user->id,
            'reason' => $data['reason'],
            'status' => 'pending'
        ]);

        $this->moderationService->autoHide($commentId);

        return [
            'message' => 'Flag submitted',
            'total_flags' => CommentFlag::where('comment_id', $commentId)->count()
        ];
    }
}
