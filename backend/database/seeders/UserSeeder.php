<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run()
{
    // Create/ensure privileged accounts exist first
    $admin = User::firstOrCreate(
        ['email' => 'admin@example.com'],
        ['name' => 'Admin', 'password' => bcrypt('password123')]
    );
    $admin->syncRoles(['admin']);

    $moderator = User::firstOrCreate(
        ['email' => 'moderator@example.com'],
        ['name' => 'Moderator', 'password' => bcrypt('password123')]
    );
    $moderator->syncRoles(['moderator']);

    // Reset all normal users, but keep admin and moderator
    DB::table('users')
        ->whereNotIn('email', ['admin@example.com', 'moderator@example.com'])
        ->delete();

        
        // Reset all normal users, but keep admin and moderator
        DB::table('users')
            ->whereNotIn('email', ['admin@example.com', 'moderator@example.com'])
            ->delete();

        // Create normal users
        $alice = User::factory()->create([
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'password' => bcrypt('password123'),
        ]);
        $alice->assignRole('user');

        $bob = User::factory()->create([
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com',
            'password' => bcrypt('password123'),
        ]);
        $bob->assignRole('user');

        $charlie = User::factory()->create([
            'name' => 'Charlie Davis',
            'email' => 'charlie@example.com',
            'password' => bcrypt('password123'),
        ]);
        $charlie->assignRole('user');

        // More normal users via factory
        User::factory(7)->create()->each(function (User $user) {
            $user->assignRole('user');
        });
    }
}