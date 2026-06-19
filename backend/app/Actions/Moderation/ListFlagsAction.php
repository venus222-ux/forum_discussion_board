<?php
namespace App\Actions\Moderation;

use App\Models\CommentFlag;

class ListFlagsAction
{
    public function execute(): array
    {
        return CommentFlag::where('status', 'pending')
            ->with(['user', 'comment'])
            ->latest()
            ->get()
            ->toArray();
    }
}
