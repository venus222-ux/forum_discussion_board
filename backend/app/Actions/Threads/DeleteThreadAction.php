<?php
namespace App\Actions\Threads;

use App\Models\Thread;

class DeleteThreadAction
{
    public function execute(string $slug, int $userId): array
    {
        $thread = Thread::where('slug', $slug)->firstOrFail();

        if ($thread->user_id !== $userId) {
            throw new \Exception('Forbidden');
        }

        $thread->delete();

        return ['message' => 'Thread deleted successfully'];
    }
}
