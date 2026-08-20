<?php

namespace App\Repositories;

use App\DTOs\Auth\RegisterData;
use App\DTOs\Auth\UpdateProfileData;
use App\Models\User;

class UserRepository
{
    public function create(RegisterData $data): User
    {
        return User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => bcrypt($data->password),
        ]);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function update(
        User $user,
        UpdateProfileData $data
    ): User {

        $user->email = $data->email;

        if ($data->password) {
            $user->password = bcrypt($data->password);
        }

        $user->save();

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
