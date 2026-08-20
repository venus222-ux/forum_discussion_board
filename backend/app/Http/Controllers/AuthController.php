<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Auth\PasswordResetService;
use App\Services\Auth\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private ProfileService $profileService,
        private PasswordResetService $passwordResetService
    ) {}

    // ================= COOKIE HELPERS =================

    protected function refreshCookie(string $token)
    {
        return cookie(
            'refresh_token',
            $token,
            (int) config('jwt.refresh_ttl'), // minute
            '/',
            null,
            false, // secure — pune true în producție (HTTPS)
            true,  // httpOnly
            false,
            'lax'
        );
    }

    protected function clearRefreshCookie()
    {
        return cookie('refresh_token', '', -1);
    }

    // ================= REGISTER =================

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->createUser($request->dto());

        $accessToken = $this->authService->makeAccessToken($user);
        $refreshToken = $this->authService->makeRefreshToken($user);

        $this->authService->storeRefreshToken($user, $refreshToken);

        return response()->json([
            'token' => $accessToken,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
            ],
        ])->cookie($this->refreshCookie($refreshToken));
    }

    // ================= LOGIN =================

    public function login(LoginRequest $request)
    {
        $user = $this->authService->attemptLogin($request->dto());

        if (! $user) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $this->authService->markOnline($user);

        $accessToken = $this->authService->makeAccessToken($user);
        $refreshToken = $this->authService->makeRefreshToken($user);

        $this->authService->storeRefreshToken($user, $refreshToken);

        return response()->json([
            'token' => $accessToken,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
            ],
        ])->cookie($this->refreshCookie($refreshToken));
    }

    // ================= REFRESH =================

    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');

        if (! $refreshToken) {
            return response()->json(['message' => 'No refresh token'], 401);
        }

        try {
            $payload = JWTAuth::setToken($refreshToken)->getPayload();

            if ($payload->get('type') !== 'refresh') {
                return response()->json(['message' => 'Invalid token type'], 401);
            }

            $user = User::find($payload->get('sub'));

            if (! $user) {
                return response()->json(['message' => 'User not found'], 401);
            }

            $stored = DB::table('refresh_tokens')
                ->where('token_hash', hash('sha256', $refreshToken))
                ->first();

            if (! $stored || $stored->revoked) {
                $this->authService->revokeUserTokens($user);

                return response()->json(['message' => 'Token reuse detected'], 401);
            }

            if (now()->greaterThan($stored->expires_at)) {
                return response()->json(['message' => 'Token expired'], 401);
            }

            DB::table('refresh_tokens')
                ->where('id', $stored->id)
                ->update(['revoked' => true]);

            $newRefreshToken = $this->authService->makeRefreshToken($user);
            $this->authService->storeRefreshToken($user, $newRefreshToken);

            $accessToken = $this->authService->makeAccessToken($user);

            return response()->json([
                'token' => $accessToken,
                'token_type' => 'bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                ],
            ])->cookie($this->refreshCookie($newRefreshToken));

        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }
    }

    // ================= LOGOUT =================

    public function logout()
    {
        $user = Auth::guard('api')->user();

        if ($user) {
            $this->authService->revokeUserTokens($user);
            $this->authService->logout($user);
        }

        return response()
            ->json(['message' => 'Logged out'])
            ->cookie($this->clearRefreshCookie());
    }

    // ================= ME =================

    public function me()
    {
        $user = Auth::guard('api')->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->getRoleNames()->first(),
        ]);
    }

    // ================= PROFILE / PASSWORD (neschimbate) =================

    public function profile()
    {
        return response()->json($this->profileService->profile());
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        return response()->json($this->profileService->update($request->dto()));
    }

    public function destroyProfile()
    {
        return response()->json($this->profileService->destroy());
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        return response()->json($this->passwordResetService->forgotPassword($request->email));
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        return response()->json($this->passwordResetService->resetPassword($request->dto()));
    }
}