<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Actions\Comments\CreateCommentAction;
use App\Actions\Comments\VoteCommentAction;
use App\Actions\Comments\AcceptBestAnswerAction;
use App\Actions\Comments\DeleteCommentAction;
use App\Services\Comment\CommentQueryService;

class CommentController extends Controller
{
    public function __construct(
        private CommentQueryService $queryService,
        private CreateCommentAction $createAction,
        private VoteCommentAction $voteAction,
        private AcceptBestAnswerAction $bestAction,
        private DeleteCommentAction $deleteAction
    ) {}

    // ---------------- GET COMMENTS ----------------
    public function getThreadComments(string $slug)
    {
        return response()->json(
            $this->queryService->getThreadComments($slug)
        );
    }

    // ---------------- CREATE COMMENT ----------------
    public function store(Request $request, string $slug)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'content' => 'required|string',
            'parentId' => 'nullable|string'
        ]);

        return response()->json(
            $this->createAction->execute($data, $slug, $user),
            201
        );
    }

    // ---------------- VOTE COMMENT ----------------
    public function vote(Request $request, string $commentId)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'type' => 'required|in:upvote,downvote'
        ]);

        return response()->json(
            $this->voteAction->execute($data, $commentId, $user)
        );
    }

    // ---------------- ACCEPT BEST ANSWER ----------------
    public function acceptBest(string $commentId)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json(
            $this->bestAction->execute($commentId, $user)
        );
    }

    // ---------------- DELETE COMMENT ----------------
    public function delete(string $commentId)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json(
            $this->deleteAction->execute($commentId, $user)
        );
    }
}
