<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = $request->input('login');
        $user = User::where('email', $loginInput)
            ->orWhere('username', $loginInput)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            ActivityLog::record(
                'login_failed',
                'Auth',
                "Percobaan login gagal untuk akun: {$loginInput}",
                'User',
                $user ? $user->id : null,
                null,
                ['login_input' => $loginInput],
                'Failed',
                $user ? $user->id : null
            );

            throw ValidationException::withMessages([
                'login' => ['Kombinasi kredensial tidak cocok dengan data kami.'],
            ]);
        }

        if (!$user->is_active) {
            ActivityLog::record(
                'login_blocked',
                'Auth',
                "Percobaan login oleh akun yang dinonaktifkan: {$user->email}",
                'User',
                $user->id,
                null,
                ['user_id' => $user->id],
                'Failed',
                $user->id
            );

            return response()->json([
                'message' => 'Akun Anda berstatus nonaktif. Silakan hubungi Administrator.',
            ], 403);
        }

        // Generate Sanctum token
        $token = $user->createToken('sisqa-auth-token')->plainTextToken;

        ActivityLog::record(
            'login_success',
            'Auth',
            "Pengguna {$user->name} ({$user->role}) berhasil login.",
            'User',
            $user->id,
            null,
            ['role' => $user->role],
            'Success',
            $user->id
        );

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'permissions' => RolePermissionService::getPermissionsForUser($user),
                'created_at' => $user->created_at,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            ActivityLog::record(
                'logout',
                'Auth',
                "Pengguna {$user->name} melakukan logout.",
                'User',
                $user->id,
                null,
                null,
                'Success',
                $user->id
            );

            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'permissions' => RolePermissionService::getPermissionsForUser($user),
                'created_at' => $user->created_at,
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:100|unique:users,username,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $old = [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
        ];

        $user->update($validated);

        ActivityLog::record(
            'profile_updated',
            'Pengguna',
            "Pengguna {$user->name} memperbarui profil.",
            'User',
            $user->id,
            $old,
            $validated,
            'Success',
            $user->id
        );

        $freshUser = $user->fresh();
        $freshUser->permissions = RolePermissionService::getPermissionsForUser($freshUser);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $freshUser,
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini tidak sesuai.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        ActivityLog::record(
            'password_updated',
            'Pengguna',
            "Pengguna {$user->name} memperbarui password akun.",
            'User',
            $user->id,
            null,
            null,
            'Success',
            $user->id
        );

        return response()->json([
            'message' => 'Password berhasil diperbarui.',
        ]);
    }
}
