<?php

namespace App\Actions\Threads;

use App\Models\Thread;
use App\Models\User;

class MyThreadsAction
{
    public function execute(User $user): array
    {
        return Thread::where('user_id', $user->id)
            ->with('category')
            ->latest()
            ->get()
            ->toArray();
    }
}
