<?php

namespace App\Actions\Comments;

use App\Models\Comment;
use App\Models\Thread;
use App\Models\User;
use App\Notifications\BestAnswerSelectedNotification;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;

class AcceptBestAnswerAction
{
    public function execute(string $commentId, User $user)
    {
        $objectId = new ObjectId($commentId);

        $comment = Comment::raw()->findOne(['_id' => $objectId]);

        if (! $comment) {
            throw new \Exception('Comment not found');
        }

        $thread = Thread::where('uuid', $comment['threadId'])->first();

        if (! $thread) {
            throw new \Exception('Thread not found');
        }

        if ($thread->user_id !== $user->id) {
            throw new \Exception('Forbidden');
        }

        if ($thread->best_comment_id === $commentId) {
            throw new \Exception('Already accepted');
        }

        DB::beginTransaction();

        try {

            if ($thread->best_comment_id) {
                $old = Comment::raw()->findOne([
                    '_id' => new ObjectId($thread->best_comment_id),
                ]);

                if ($old) {
                    $oldAuthor = User::find($old['authorId']);

                    if ($oldAuthor) {
                        $oldAuthor->decrement('reputation', 15);
                        $oldAuthor->decrement('accepted_answers', 1);
                    }
                }
            }

            $thread->best_comment_id = $commentId;
            $thread->save();

            Thread::where('uuid', $thread->uuid)
                ->lockForUpdate()
                ->update(['best_comment_id' => $commentId]);

            $author = User::find($comment['authorId']);

            if ($author) {
                $author->increment('reputation', 15);
                $author->increment('accepted_answers');
                $author->notify(new BestAnswerSelectedNotification($thread, $comment, $user));
            }

            $user->increment('reputation', 2);
            $user->increment('given_best_answers');

            DB::commit();

            return [
                'message' => 'Best answer selected',
                'best_comment_id' => $commentId,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
