<?php

namespace App\Http\Repositories\Admin;

use App\Models\CommentFlag;

class CommentFlagRepository
{
    public function countPending(): int
    {
        return CommentFlag::where('status', 'pending')->count();
    }
}
