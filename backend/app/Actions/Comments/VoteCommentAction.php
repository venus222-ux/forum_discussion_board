<?php

namespace App\Actions\Comments;

use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\User;
use App\Notifications\CommentLiked;
use MongoDB\BSON\ObjectId;

class VoteCommentAction
{
    public function execute(array $data, string $commentId, User $user)
    {
        $userId = $user->id;

        $objectId = new ObjectId($commentId);

        $comment = Comment::raw()->findOne(['_id' => $objectId]);

        if (! $comment) {
            throw new \Exception('Comment not found');
        }

        $authorId = $comment['authorId'];

        if ($authorId === $userId) {
            throw new \Exception('You cannot vote your own comment');
        }

        $existingVote = CommentVote::raw()->findOne([
            'commentId' => $objectId,
            'userId' => $userId,
        ]);

        $action = null;

        if ($existingVote) {
            if ($existingVote['voteType'] === $data['type']) {
                CommentVote::raw()->deleteOne([
                    'commentId' => $objectId,
                    'userId' => $userId,
                ]);

                $field = $data['type'] === 'upvote' ? 'upvotes' : 'downvotes';
                Comment::raw()->updateOne(['_id' => $objectId], ['$inc' => [$field => -1]]);

                $action = 'removed';
            } else {
                $inc = $data['type'] === 'upvote' ? 'upvotes' : 'downvotes';
                $dec = $existingVote['voteType'] === 'upvote' ? 'upvotes' : 'downvotes';

                CommentVote::raw()->updateOne(
                    ['commentId' => $objectId, 'userId' => $userId],
                    ['$set' => ['voteType' => $data['type']]]
                );

                Comment::raw()->updateOne(
                    ['_id' => $objectId],
                    ['$inc' => [$inc => 1, $dec => -1]]
                );

                $action = 'changed';
            }
        } else {
            CommentVote::raw()->insertOne([
                'commentId' => $objectId,
                'userId' => $userId,
                'voteType' => $data['type'],
                'createdAt' => now(),
            ]);

            $field = $data['type'] === 'upvote' ? 'upvotes' : 'downvotes';
            Comment::raw()->updateOne(['_id' => $objectId], ['$inc' => [$field => 1]]);

            $action = 'added';
        }

        // reputation
        if ($action === 'added') {
            if ($data['type'] === 'upvote') {
                User::where('id', $authorId)->increment('reputation', 10);
            } else {
                User::where('id', $authorId)->decrement('reputation', 2);
            }
        }

        if ($action === 'removed') {
            if ($data['type'] === 'upvote') {
                User::where('id', $authorId)->decrement('reputation', 10);
            } else {
                User::where('id', $authorId)->increment('reputation', 2);
            }
        }

        if ($action === 'changed') {
            if ($data['type'] === 'upvote') {
                User::where('id', $authorId)->increment('reputation', 12);
            } else {
                User::where('id', $authorId)->decrement('reputation', 12);
            }
        }

        // notification
        if ($action === 'added' && $data['type'] === 'upvote') {
            $author = User::find($authorId);

            if ($author && $author->id !== $userId) {
                $author->notify(new CommentLiked($comment, $user));
            }
        }

        return [
            'message' => 'Vote '.$action,
            'action' => $action,
        ];
    }
}
