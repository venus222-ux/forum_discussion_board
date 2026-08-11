<?php

namespace App\Http\Controllers;

use App\Actions\Moderation\ApproveCommentAction;
use App\Actions\Moderation\FlagCommentAction;
use App\Actions\Moderation\ListFlagsAction;
use App\Actions\Moderation\OfficialReplyAction;
use App\Actions\Moderation\RejectCommentAction;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function __construct(
        private FlagCommentAction $flagAction,
        private ApproveCommentAction $approveAction,
        private RejectCommentAction $rejectAction,
        private OfficialReplyAction $replyAction,
        private ListFlagsAction $listFlagsAction
    ) {}

    public function flag(Request $request, $commentId)
    {
        return response()->json(
            $this->flagAction->execute($commentId, $request->all(), auth()->user())
        );
    }

    public function approve(Request $request, $commentId)
    {
        return response()->json(
            $this->approveAction->execute($commentId, auth()->user(), $request->all())
        );
    }

    public function reject($commentId)
    {
        return response()->json(
            $this->rejectAction->execute($commentId)
        );
    }

    public function officialReply(Request $request, $commentId)
    {
        return response()->json(
            $this->replyAction->execute($commentId, $request->all(), auth()->user())
        );
    }

    // ✅ ADD THIS (fix your error)
    public function listFlags()
    {
        return response()->json(
            $this->listFlagsAction->execute()
        );
    }
}
