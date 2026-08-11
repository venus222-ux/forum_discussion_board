<?php

namespace App\Actions\Moderation;

use App\Models\Comment;

class OfficialReplyAction
{
    public function execute(string $commentId, array $data, $user): Comment
    {
        $parent = Comment::findOrFail($commentId);

        $lastChild = Comment::where('parentId', $parent->id)
            ->orderBy('path', 'desc')
            ->first();

        $next = $lastChild ? intval(substr($lastChild->path, -3)) + 1 : 1;

        $path = $parent->path.'.'.str_pad($next, 3, '0', STR_PAD_LEFT);

        return Comment::create([
            'threadId' => $parent->threadId,
            'authorId' => $user->id,
            'content' => $data['content'],
            'parentId' => $parent->id,
            'path' => $path,
            'depth' => $parent->depth + 1,
            'official_reply' => true,
            'status' => 'active',
            'is_hidden' => false,
        ]);
    }
}
