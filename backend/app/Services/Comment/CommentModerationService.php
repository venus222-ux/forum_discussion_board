<?php

namespace App\Services\Comment;

use App\Jobs\ProcessAiModerationJob;

class CommentModerationService
{
    public function dispatchModeration(string $commentId): void
    {
        ProcessAiModerationJob::dispatch($commentId);
    }

    public function flagSpam(array $comment): bool
    {
        // simple rule-based example (you can replace with AI later)
        $badWords = ['spam', 'casino', 'viagra'];

        foreach ($badWords as $word) {
            if (str_contains(strtolower($comment['content'] ?? ''), $word)) {
                return true;
            }
        }

        return false;
    }
}
