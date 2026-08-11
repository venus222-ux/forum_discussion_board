<?php

namespace App\Services\Comment;

use App\Models\Comment;
use App\Models\Thread;

class CommentQueryService
{
    public function __construct(
        private CommentTreeService $treeService,
        private CommentModerationService $moderationService
    ) {}

    public function getThreadComments(string $slug): array
    {
        $thread = Thread::where('slug', $slug)->first();

        if (! $thread) {
            return [];
        }

        $comments = Comment::where('threadId', $thread->uuid)
            ->where('status', 'active')
            ->orderBy('path')
            ->get();

        $tree = $this->treeService->build($comments);
        $tree = $this->treeService->markBest($tree, $thread->best_comment_id);
        $tree = $this->treeService->attachUsers($tree);

        return $this->treeService->sort($tree);
    }
}
