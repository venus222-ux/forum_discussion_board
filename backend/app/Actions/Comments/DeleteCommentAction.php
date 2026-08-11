<?php

namespace App\Actions\Comments;

use App\Models\Comment;

class DeleteCommentAction
{
    public function execute(string $commentId)
    {
        Comment::where('_id', $commentId)->update([
            'status' => 'deleted',
            'content' => '[deleted]',
            'updatedAt' => now(),
        ]);

        return [
            'message' => 'Deleted',
        ];
    }
}
