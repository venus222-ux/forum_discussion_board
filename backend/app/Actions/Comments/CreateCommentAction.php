<?php

namespace App\Actions\Comments;

use App\Models\Thread;
use App\Models\Comment;
use App\Models\User;
use App\Jobs\ProcessAiModerationJob;
use App\Notifications\ThreadCommented;
use App\Notifications\CommentReplied;

class CreateCommentAction
{
    public function execute(array $data, string $slug, User $actor)
    {
        $thread = Thread::where('slug', $slug)->first();

        if (!$thread) {
            throw new \Exception('Thread not found');
        }

        $parent = null;
        $path = '';
        $depth = 0;

        if (!empty($data['parentId'])) {
            $parent = Comment::where('_id', $data['parentId'])->first();

            if (!$parent) {
                throw new \Exception('Parent comment not found');
            }

            $depth = $parent->depth + 1;

            $lastChild = Comment::where('parentId', (string)$parent->_id)
                ->orderBy('path', 'desc')
                ->first();

            $nextSegment = $lastChild ? intval(substr($lastChild->path, -3)) + 1 : 1;

            $path = $parent->path . '.' . str_pad($nextSegment, 3, '0', STR_PAD_LEFT);

            Comment::where('_id', $parent->_id)->increment('replyCount');
        } else {
            $lastRoot = Comment::where('threadId', $thread->uuid)
                ->whereNull('parentId')
                ->orderBy('path', 'desc')
                ->first();

            $nextSegment = $lastRoot ? intval($lastRoot->path) + 1 : 1;

            $path = str_pad($nextSegment, 3, '0', STR_PAD_LEFT);
        }

        $comment = Comment::create([
            'threadId' => $thread->uuid,
            'authorId' => $actor->id,
            'content' => $data['content'],
            'parentId' => $parent ? (string)$parent->_id : null,
            'path' => $path,
            'depth' => $depth,
        ]);

        ProcessAiModerationJob::dispatch((string)$comment->_id);

        $thread->increment('comment_count');

        // notifications
        if ($thread->user_id !== $actor->id) {
            $threadAuthor = User::find($thread->user_id);
            if ($threadAuthor) {
                $threadAuthor->notify(new ThreadCommented($thread, $comment, $actor));
            }
        }

        if ($parent && $parent->authorId !== $actor->id) {
            $parentAuthor = User::find($parent->authorId);
            if ($parentAuthor) {
                $parentAuthor->notify(new CommentReplied($thread, $comment, $actor));
            }
        }

        return $comment;
    }
}
