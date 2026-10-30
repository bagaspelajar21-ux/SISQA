<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $users = $query->orderBy('name')->paginate($request->input('per_page', 15));

        $users->getCollection()->transform(function ($u) {
            $u->permissions = RolePermissionService::getPermissionsForUser($u);
            return $u;
        });

        return response()->json($users);
    }

    public function getRolePermissions(): JsonResponse
    {
        $config = RolePermissionService::loadConfig();
        return response()->json([
            'definitions' => RolePermissionService::allAvailableDefinitions(),
            'role_user' => $config['role_user'],
            'user_overrides' => $config['user_overrides'],
        ]);
    }

    public function updateRolePermissions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'menus' => 'present|array',
            'menus.*' => 'string',
            'features' => 'present|array',
            'features.*' => 'string',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        if (!empty($validated['user_id'])) {
            $saved = RolePermissionService::saveSingleUserPermissions(
                (int) $validated['user_id'],
                $validated['menus'],
                $validated['features']
            );
            $targetDesc = "pengguna ID #{$validated['user_id']}";
        } else {
            $saved = RolePermissionService::saveRoleUserPermissions(
                $validated['menus'],
                $validated['features'],
                true
            );
            $targetDesc = "seluruh akun Role User";
        }

        ActivityLog::record(
            'role_permissions_updated',
            'Pengguna',
            "Admin memperbarui ceklist hak akses menu & fitur untuk {$targetDesc}.",
            'User',
            $validated['user_id'] ?? null,
            null,
            $saved
        );

        return response()->json([
            'message' => "Hak akses menu & fitur untuk {$targetDesc} berhasil disimpan.",
            'permissions' => $saved,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:100|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'user'])],
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.menus' => 'nullable|array',
            'permissions.features' => 'nullable|array',
        ]);

        $customPerms = $validated['permissions'] ?? null;
        unset($validated['permissions']);

        $passwordRaw = $validated['password'];
        $validated['password'] = Hash::make($passwordRaw);
        $validated['is_active'] = $validated['is_active'] ?? true;

        $user = User::create($validated);

        if ($user->role === 'user' && is_array($customPerms)) {
            RolePermissionService::saveSingleUserPermissions(
                $user->id,
                $customPerms['menus'] ?? [],
                $customPerms['features'] ?? []
            );
        }

        ActivityLog::record(
            'user_created',
            'Pengguna',
            "Admin membuat akun pengguna baru: {$user->name} ({$user->role})",
            'User',
            $user->id,
            null,
            [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ]
        );

        $user->permissions = RolePermissionService::getPermissionsForUser($user);

        return response()->json([
            'message' => 'Pengguna berhasil ditambahkan.',
            'user' => $user,
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount(['schedules', 'testExecutions', 'reportedBugs', 'assignedBugs']);
        $user->permissions = RolePermissionService::getPermissionsForUser($user);
        return response()->json([
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['nullable', 'string', 'max:100', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => ['required', Rule::in(['admin', 'user'])],
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.menus' => 'nullable|array',
            'permissions.features' => 'nullable|array',
        ]);

        $customPerms = $validated['permissions'] ?? null;
        unset($validated['permissions']);

        $oldValues = [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ];

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        if ($user->role === 'user' && is_array($customPerms)) {
            RolePermissionService::saveSingleUserPermissions(
                $user->id,
                $customPerms['menus'] ?? [],
                $customPerms['features'] ?? []
            );
        }

        ActivityLog::record(
            'user_updated',
            'Pengguna',
            "Admin memperbarui data pengguna: {$user->name}",
            'User',
            $user->id,
            $oldValues,
            [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ]
        );

        $freshUser = $user->fresh();
        $freshUser->permissions = RolePermissionService::getPermissionsForUser($freshUser);

        return response()->json([
            'message' => 'Data pengguna berhasil diperbarui.',
            'user' => $freshUser,
        ]);
    }

    public function toggleActive(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.',
            ], 422);
        }

        $oldState = $user->is_active;
        $user->update(['is_active' => !$oldState]);
        $statusLabel = $user->is_active ? 'mengaktifkan' : 'menonaktifkan';

        ActivityLog::record(
            'user_status_toggled',
            'Pengguna',
            "Admin {$statusLabel} akun pengguna: {$user->name}",
            'User',
            $user->id,
            ['is_active' => $oldState],
            ['is_active' => $user->is_active]
        );

        return response()->json([
            'message' => "Akun pengguna berhasil di" . ($user->is_active ? 'aktifkan' : 'nonaktifkan') . ".",
            'user' => $user,
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ], 422);
        }

        $userName = $user->name;
        $user->delete();

        ActivityLog::record(
            'user_deleted',
            'Pengguna',
            "Admin menghapus pengguna: {$userName}",
            'User',
            $user->id,
            ['name' => $userName],
            null
        );

        return response()->json([
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }
}
