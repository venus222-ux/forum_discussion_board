<?php

namespace App\Actions\Threads;

use App\Models\Thread;
use Illuminate\Support\Str;

class UpdateThreadAction
{
    public function execute(string $slug, array $data, int $userId)
    {
        $thread = Thread::where('slug', $slug)->firstOrFail();

        if ($thread->user_id !== $userId) {
            throw new \Exception('Forbidden');
        }

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $thread->update($data);

        return $thread;
    }
}
