<?php

namespace App\Services\Auth;

use App\DTOs\Auth\LoginData;
use App\DTOs\Auth\RegisterData;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        private UserRepository $users
    ) {}

    // ================= USER =================

    public function createUser(RegisterData $data): User
    {
        $user = $this->users->create($data);

        $user->assignRole($data->role);

        return $user;
    }

    // ================= LOGIN =================

    public function attemptLogin(LoginData $data): ?User
    {
        if (! Auth::guard('api')->attempt([
            'email' => $data->email,
            'password' => $data->password,
        ])) {
            return null;
        }

        return Auth::guard('api')->user();
    }

    public function markOnline(User $user): void
    {
        Cache::put("user-is-online-{$user->id}", true, now()->addMinutes(5));
    }

    // ================= JWT TOKENS =================

    public function makeAccessToken(User $user): string
    {
        return JWTAuth::claims([
            'type' => 'access',
            'role' => $user->getRoleNames()->first(),
        ])->fromUser($user);
    }

    public function makeRefreshToken(User $user): string
    {
        return JWTAuth::claims([
            'type' => 'refresh',
        ])->fromUser($user);
    }

    // ================= REFRESH TOKENS (DB) =================

    public function storeRefreshToken(User $user, string $token): void
    {
        DB::table('refresh_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes((int) config('jwt.refresh_ttl')),
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function revokeUserTokens(User $user): void
    {
        DB::table('refresh_tokens')
            ->where('user_id', $user->id)
            ->update(['revoked' => true]);
    }

    // ================= LOGOUT =================

    public function logout(User $user): void
    {
        Cache::forget("user-is-online-{$user->id}");
        Auth::guard('api')->logout();
    }
}