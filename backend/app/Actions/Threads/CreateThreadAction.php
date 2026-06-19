<?php
namespace App\Actions\Threads;

use App\Models\Thread;

class CreateThreadAction
{
    public function execute(array $data, int $userId)
    {
        return Thread::create([
            'title' => $data['title'],
            'content' => $data['content'],
            'category_id' => $data['category_id'],
            'user_id' => $userId,
        ]);
    }
}
