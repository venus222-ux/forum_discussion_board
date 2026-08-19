<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
{
    $data = $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6|confirmed',
        'role' => 'required|in:user,moderator',
    ]);

    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => bcrypt($data['password']),
    ]);

    $user->assignRole($data['role']);

    $token = auth('api')->login($user);

    return response()->json(['token' => $token]);
}

public function login(Request $request)
{
    $credentials = $request->only('email', 'password');

    if (! $token = Auth::guard('api')->attempt($credentials)) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $user = Auth::guard('api')->user();

    Cache::put("user-is-online-{$user->id}", true, now()->addMinutes(5));

    return response()->json([
        'token' => $token,
        'token_type' => 'bearer',
        'expires_in' => auth('api')->factory()->getTTL() * 60,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->getRoleNames()->first(),
        ],
    ]);
}

public function refresh()
{
    $newToken = auth('api')->refresh();
    $user = auth('api')->user();

    return response()->json([
        'token' => $newToken,
        'token_type' => 'bearer',
        'expires_in' => auth('api')->factory()->getTTL() * 60,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->getRoleNames()->first(),
        ],
    ]);
}

public function me()
{
    return response()->json([
        'id' => auth()->id(),
        'name' => auth()->user()->name,
        'email' => auth()->user()->email,
        'role' => auth()->user()->getRoleNames()->first(),
    ]);
}
    // --- LOGOUT ---
    public function logout()
    {
        $user = Auth::guard('api')->user();
        Cache::forget("user-is-online-{$user->id}");
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    // --- PROFILE ---
    public function profile()
    {
        $user = auth()->user();

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|min:6|confirmed',
        ]);

        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }
        $user->save();

        return response()->json(['message' => 'Profile updated successfully']);
    }

    public function destroyProfile()
    {
        $user = auth()->user();
        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }

    // --- FORGOT PASSWORD ---
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $token = Str::random(64);
        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => now()]
        );

        $user = User::where('email', $request->email)->first();
        $user->notify(new ResetPasswordNotification($token));

        return response()->json(['message' => 'Password reset link sent to your email']);
    }

    // --- RESET PASSWORD ---
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (! $reset || now()->diffInMinutes($reset->created_at) > 60) {
            return response()->json(['message' => 'Invalid or expired token'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = bcrypt($request->password);
        $user->save();

        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password has been reset successfully']);
    }



  
}
