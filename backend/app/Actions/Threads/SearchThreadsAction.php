<?php
namespace App\Actions\Threads;

use App\Models\Thread;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchThreadsAction
{
    public function execute(string $query, int $page, string $url, array $params): array
    {
        if (!$query) {
            return [
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0,
            ];
        }

        $allIds = Thread::search($query)->keys()->toArray();

        $perPage = 10;
        $total = count($allIds);

        $pagedIds = array_slice($allIds, ($page - 1) * $perPage, $perPage);

        $threads = Thread::with(['user:id,name', 'category:id,name,slug'])
            ->whereIn('id', $pagedIds)
            ->get();

        return [
            'data' => $threads->toArray(),
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'total' => $total,
        ];
    }
}
