<?php

namespace App\Http\Repositories\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    public function count(): int
    {
        return User::count();
    }

    public function recent(int $limit = 5): array
    {
        return User::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'role', 'created_at'])
            ->toArray();
    }

    public function roleDistribution(): array
    {
        return User::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->get()
            ->toArray();
    }
}
