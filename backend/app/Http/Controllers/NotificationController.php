<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Actions\Notifications\ListNotificationsAction;
use App\Actions\Notifications\MarkNotificationReadAction;
use App\Actions\Notifications\ClearNotificationsAction;

class NotificationController extends Controller
{
    public function __construct(
        private ListNotificationsAction $listAction,
        private MarkNotificationReadAction $markReadAction,
        private ClearNotificationsAction $clearAction
    ) {}

    public function index(Request $request)
    {
        return response()->json(
            $this->listAction->execute(auth('api')->user())
        );
    }

    public function markAsRead($id)
    {
        return response()->json(
            $this->markReadAction->execute($id, auth('api')->user())
        );
    }

    public function clear(Request $request)
    {
        return response()->json(
            $this->clearAction->execute(auth('api')->user())
        );
    }
}
