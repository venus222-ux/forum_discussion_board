<?php

namespace App\Services\Admin;

use App\Http\Repositories\Admin\UserRepository;
use App\Http\Repositories\Admin\CategoryRepository;
use App\Http\Repositories\Admin\ThreadRepository;
use App\Http\Repositories\Admin\CommentRepository;
use App\Http\Repositories\Admin\CommentFlagRepository;
use MongoDB\BSON\UTCDateTime;

class AdminDashboardService
{
    public function __construct(
        private UserRepository $users,
        private CategoryRepository $categories,
        private ThreadRepository $threads,
        private CommentRepository $comments,
        private CommentFlagRepository $flags,
    ) {}

    public function getDashboardData(string $range = '30d'): array
    {
        $startDate = $this->resolveDateRange($range);

        return [
            'totalUsers' => $this->users->count(),
            'totalCategories' => $this->categories->count(),
            'totalThreads' => $this->threads->count(),
            'totalComments' => $this->comments->count(),
            'flaggedComments' => $this->flags->countPending(),

            'recentUsers' => $this->users->recent(5),
            'recentThreads' => $this->threads->recentWithRelations(5),

            'topComments' => $this->comments->topComments($startDate),

            'threadsPerCategory' => $this->categories->threadsPerCategory(),

            'userRoleDistribution' => $this->users->roleDistribution(),
        ];
    }

    private function resolveDateRange(string $range)
    {
        return match ($range) {
            '7d' => now()->subDays(7),
            '90d' => now()->subDays(90),
            default => now()->subDays(30),
        };
    }
}
