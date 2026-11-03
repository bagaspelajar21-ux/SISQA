<?php

namespace App\Services;

use App\Models\ApplicationMenu;
use App\Models\User;

class RolePermissionService
{
    private static function getConfigPath(): string
    {
        return storage_path('app/role_user_permissions.json');
    }

    public static function defaultUserPermissions(): array
    {
        return [
            'menus' => [
                '/',
                '/schedules',
                '/test-cases',
                '/bugs',
                '/retests',
                '/flows',
                '/guides',
                '/reports',
                '/reports/wa',
                '/reports/weekly',
                '/activity-logs',
            ],
            'features' => [
                'add_schedule',
                'update_schedule',
                'delete_schedule',
                'execute_test',
                'add_test',
                'update_test',
                'delete_test',
                'import_test_excel',
                'create_bug',
                'update_bug',
                'delete_bug',
                'import_bug_excel',
                'create_retest',
                'add_flow',
                'update_flow',
                'delete_flow',
                'export_report',
            ],
        ];
    }

    /**
     * @return array{menus: list<string>, features: list<string>}
     */
    public static function ownerPermissions(): array
    {
        return [
            'menus' => ['/', '/reports', '/reports/wa'],
            'features' => ['dashboard_all_data', 'dashboard_exec_charts', 'export_report'],
        ];
    }

    public static function allAvailableDefinitions(): array
    {
        $masterMenus = ApplicationMenu::query()
            ->with(['application:id,name', 'module:id,name'])
            ->where('is_active', true)
            ->orderBy('application_id')
            ->orderBy('module_id')
            ->orderBy('order_index')
            ->orderBy('name')
            ->get()
            ->map(fn (ApplicationMenu $menu): array => [
                'key' => self::masterMenuPermissionKey($menu->id),
                'name' => $menu->name,
                'application_name' => $menu->application?->name ?? 'Aplikasi',
                'module_name' => $menu->module?->name ?? 'Modul',
                'url_or_route' => $menu->url_or_route,
                'label' => implode(' / ', [
                    $menu->application?->name ?? 'Aplikasi',
                    $menu->module?->name ?? 'Modul',
                    $menu->name,
                ]),
                'description' => $menu->url_or_route
                    ? "Sub-menu Master Data · {$menu->url_or_route}"
                    : 'Sub-menu Master Data tanpa tautan.',
            ])
            ->all();

        return [
            'menus' => array_merge([
                ['key' => '/', 'label' => '📊 Dashboard QA', 'description' => 'Halaman ringkasan metrik dan grafik QA'],
                ['key' => '/schedules', 'label' => '📅 Jadwal QA', 'description' => 'Daftar dan kalender jadwal pengujian QA'],
                ['key' => '/test-cases', 'label' => '📝 Test Management', 'description' => 'Repositori skenario dan eksekusi test case'],
                ['key' => '/bugs', 'label' => '🐞 Bug Tracking', 'description' => 'Pelaporan dan pemantauan tiket bug'],
                ['key' => '/retests', 'label' => '🔄 Retesting', 'description' => 'Verifikasi pengujian ulang bug yang diperbaiki'],
                ['key' => '/flows', 'label' => '🔀 Flow Aplikasi', 'description' => 'Dokumentasi & preview file flow bisnis'],
                ['key' => '/guides', 'label' => '📖 Panduan SISQA', 'description' => 'Pusat panduan penggunaan aplikasi'],
                ['key' => '/reports', 'label' => '📑 QA Report', 'description' => 'Laporan resmi QA (Excel & PDF)'],
                ['key' => '/reports/wa', 'label' => '💬 Report WA', 'description' => 'Generator laporan harian versi WhatsApp'],
                ['key' => '/reports/weekly', 'label' => '📆 Report Mingguan', 'description' => 'Catatan pekerjaan pribadi per hari selama satu minggu, dapat diedit dan dihapus'],
                ['key' => '/activity-logs', 'label' => '⏱️ Activity Logs', 'description' => 'Riwayat aktivitas pengguna'],
            ], $masterMenus),
            'features' => [
                ['key' => 'dashboard_all_data', 'label' => '🌐 Dashboard: Akses Data Seluruh Tester', 'description' => 'Melihat statistik semua tester & filter lintas tester di Dashboard (Default User: Hanya data sendiri)'],
                ['key' => 'dashboard_exec_charts', 'label' => '📈 Dashboard: Grafik Eksekutif & Analitik Bug', 'description' => 'Menampilkan grafik kurva severity bug, siklus resolusi, dan cakupan lintas aplikasi di Dashboard'],
                ['key' => 'dashboard_quick_actions', 'label' => '⚡ Dashboard: Pintasan Cepat & Filter Lanjutan', 'description' => 'Menampilkan tombol pintasan cepat dan filter sub-menu/periode lengkap di Dashboard'],
                ['key' => 'add_schedule', 'label' => 'Jadwal QA: Tambah', 'description' => 'Membuat jadwal pengujian QA baru'],
                ['key' => 'update_schedule', 'label' => 'Jadwal QA: Edit', 'description' => 'Mengubah jadwal pengujian QA yang tersedia'],
                ['key' => 'delete_schedule', 'label' => 'Jadwal QA: Hapus', 'description' => 'Menghapus jadwal pengujian QA'],
                ['key' => 'execute_test', 'label' => '▶ Eksekusi Test Case', 'description' => 'Menjalankan pengujian (PASS/FAILED/PENDING) & unggah bukti'],
                ['key' => 'add_test', 'label' => 'Test Management: Tambah', 'description' => 'Membuat skenario Test Case baru'],
                ['key' => 'update_test', 'label' => 'Test Management: Edit', 'description' => 'Mengubah skenario Test Case'],
                ['key' => 'delete_test', 'label' => 'Test Management: Hapus', 'description' => 'Menghapus skenario Test Case'],
                ['key' => 'import_test_excel', 'label' => '📤 Template & Upload Excel Test Case', 'description' => 'Mengunduh template dan mengimpor Test Case dari Excel'],
                ['key' => 'create_bug', 'label' => 'Bug Tracking: Tambah', 'description' => 'Membuat tiket bug baru'],
                ['key' => 'update_bug', 'label' => 'Bug Tracking: Edit', 'description' => 'Mengubah status tiket bug dan menugaskan developer'],
                ['key' => 'delete_bug', 'label' => 'Bug Tracking: Hapus', 'description' => 'Menghapus tiket bug'],
                ['key' => 'import_bug_excel', 'label' => '📤 Template & Upload Excel Bug', 'description' => 'Mengunduh template dan mengimpor Bug dari Excel'],
                ['key' => 'create_retest', 'label' => '+ Input Hasil Retest', 'description' => 'Mencatat hasil verifikasi pengujian ulang (Retesting)'],
                ['key' => 'add_flow', 'label' => 'Flow Aplikasi: Tambah', 'description' => 'Membuat flow aplikasi dan mengunggah dokumen awal'],
                ['key' => 'update_flow', 'label' => 'Flow Aplikasi: Edit', 'description' => 'Mengubah flow atau menambahkan dokumen'],
                ['key' => 'delete_flow', 'label' => 'Flow Aplikasi: Hapus', 'description' => 'Menghapus flow aplikasi atau file terkait'],
                ['key' => 'export_report', 'label' => '📊 Export Excel & PDF Laporan QA', 'description' => 'Mengunduh laporan QA dalam bentuk file Excel dan PDF'],
            ],
        ];
    }

    public static function loadConfig(): array
    {
        $path = self::getConfigPath();
        if (file_exists($path)) {
            $decoded = json_decode(file_get_contents($path), true);
            if (is_array($decoded)) {
                $roleUser = is_array($decoded['role_user'] ?? null)
                    ? $decoded['role_user']
                    : self::defaultUserPermissions();
                $userOverrides = is_array($decoded['user_overrides'] ?? null)
                    ? $decoded['user_overrides']
                    : [];

                return [
                    'role_user' => self::normalizePermissions($roleUser),
                    'user_overrides' => collect($userOverrides)
                        ->filter(fn (mixed $permissions): bool => is_array($permissions))
                        ->map(fn (array $permissions): array => self::normalizePermissions($permissions))
                        ->all(),
                ];
            }
        }

        return [
            'role_user' => self::defaultUserPermissions(),
            'user_overrides' => [],
        ];
    }

    public static function saveRoleUserPermissions(array $menus, array $features, bool $resetOverrides = true): array
    {
        $config = self::loadConfig();
        $config['role_user'] = self::normalizePermissions([
            'menus' => array_values(array_unique($menus)),
            'features' => array_values(array_unique($features)),
        ]);
        if ($resetOverrides) {
            $config['user_overrides'] = [];
        }

        file_put_contents(self::getConfigPath(), json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $config['role_user'];
    }

    public static function saveSingleUserPermissions(int $userId, array $menus, array $features): array
    {
        $config = self::loadConfig();
        $config['user_overrides'][(string) $userId] = self::normalizePermissions([
            'menus' => array_values(array_unique($menus)),
            'features' => array_values(array_unique($features)),
        ]);

        file_put_contents(self::getConfigPath(), json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $config['user_overrides'][(string) $userId];
    }

    public static function getPermissionsForUser(?User $user): array
    {
        if (! $user) {
            return ['menus' => [], 'features' => [], 'master_menus' => []];
        }

        if ($user->role === 'admin') {
            $defs = self::allAvailableDefinitions();
            $menus = array_column($defs['menus'], 'key');

            return [
                'menus' => $menus,
                'features' => array_column($defs['features'], 'key'),
                'master_menus' => self::masterMenusForPermissions($menus),
            ];
        }

        if ($user->isOwner()) {
            return [
                ...self::ownerPermissions(),
                'master_menus' => [],
            ];
        }

        $config = self::loadConfig();
        $uid = (string) $user->id;
        $permissions = is_array($config['user_overrides'][$uid] ?? null)
            ? self::normalizePermissions($config['user_overrides'][$uid])
            : self::normalizePermissions($config['role_user'] ?? self::defaultUserPermissions());

        return [
            ...$permissions,
            'master_menus' => self::masterMenusForPermissions($permissions['menus']),
        ];
    }

    public static function hasMenuAccess(?User $user, string $menuPath): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->role === 'admin' || $menuPath === '/profile') {
            return true;
        }

        $permissions = self::getPermissionsForUser($user);

        return in_array($menuPath, $permissions['menus'], true)
            || collect($permissions['master_menus'])
                ->contains(fn (array $menu): bool => $menu['url_or_route'] === $menuPath);
    }

    public static function hasFeatureAccess(?User $user, string $featureKey): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return in_array($featureKey, self::getPermissionsForUser($user)['features'], true);
    }

    /**
     * @param  array<string, mixed>  $permissions
     * @return array{menus: list<string>, features: list<string>}
     */
    private static function normalizePermissions(array $permissions): array
    {
        $definitions = self::allAvailableDefinitions();
        $menuKeys = array_column($definitions['menus'], 'key');
        $featureKeys = array_column($definitions['features'], 'key');
        $menus = is_array($permissions['menus'] ?? null) ? $permissions['menus'] : [];
        $features = is_array($permissions['features'] ?? null) ? $permissions['features'] : [];
        $legacyFeatureAliases = [
            'create_schedule' => ['add_schedule', 'update_schedule', 'delete_schedule'],
            'create_test' => ['add_test', 'update_test', 'delete_test'],
            'edit_bug' => ['update_bug', 'delete_bug'],
            'manage_flow' => ['add_flow', 'update_flow', 'delete_flow'],
        ];
        foreach ($features as $feature) {
            if (is_string($feature) && isset($legacyFeatureAliases[$feature])) {
                $features = array_merge($features, $legacyFeatureAliases[$feature]);
            }
        }

        return [
            'menus' => array_values(array_filter(
                $menus,
                fn (mixed $menu): bool => is_string($menu) && in_array($menu, $menuKeys, true)
            )),
            'features' => array_values(array_filter(
                $features,
                fn (mixed $feature): bool => is_string($feature) && in_array($feature, $featureKeys, true)
            )),
        ];
    }

    private static function masterMenuPermissionKey(int $menuId): string
    {
        return 'master-menu:'.$menuId;
    }

    /**
     * @param  list<string>  $menuKeys
     * @return list<array{key: string, name: string, application_name: string, module_name: string, url_or_route: ?string}>
     */
    private static function masterMenusForPermissions(array $menuKeys): array
    {
        $menuIds = collect($menuKeys)
            ->filter(fn (string $key): bool => str_starts_with($key, 'master-menu:'))
            ->map(fn (string $key): int => (int) substr($key, strlen('master-menu:')))
            ->all();

        if ($menuIds === []) {
            return [];
        }

        return collect(self::allAvailableDefinitions()['menus'])
            ->filter(fn (array $menu): bool => in_array($menu['key'], $menuKeys, true)
                && str_starts_with($menu['key'], 'master-menu:'))
            ->values()
            ->map(fn (array $menu): array => [
                'key' => $menu['key'],
                'name' => $menu['name'],
                'application_name' => $menu['application_name'],
                'module_name' => $menu['module_name'],
                'url_or_route' => $menu['url_or_route'],
            ])
            ->all();
    }
}
