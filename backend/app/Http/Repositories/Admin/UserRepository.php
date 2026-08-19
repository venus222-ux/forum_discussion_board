<?php

namespace App\Http\Repositories\Admin;

use App\Models\User;
use Spatie\Permission\Models\Role;

class UserRepository
{
    public function count(): int
    {
        return User::count();
    }

    public function recent(int $limit = 5): array
    {
        return User::with('roles')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get([
                'id',
                'name',
                'email',
                'created_at',
            ])
            ->toArray();
    }

    public function roleDistribution(): array
    {
        return Role::withCount('users')
            ->get()
            ->map(fn ($role) => [
                'role' => $role->name,
                'count' => $role->users_count,
            ])
            ->toArray();
    }
}