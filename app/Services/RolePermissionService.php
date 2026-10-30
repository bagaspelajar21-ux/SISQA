<?php

namespace App\Services;

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
                '/activity-logs',
            ],
            'features' => [
                'execute_test',
                'create_test',
                'import_test_excel',
                'create_bug',
                'edit_bug',
                'import_bug_excel',
                'create_retest',
                'manage_flow',
                'export_report',
            ],
        ];
    }

    public static function allAvailableDefinitions(): array
    {
        return [
            'menus' => [
                ['key' => '/', 'label' => '📊 Dashboard QA', 'description' => 'Halaman ringkasan metrik dan grafik QA'],
                ['key' => '/schedules', 'label' => '📅 Jadwal QA', 'description' => 'Daftar dan kalender jadwal pengujian QA'],
                ['key' => '/test-cases', 'label' => '📝 Test Management', 'description' => 'Repositori skenario dan eksekusi test case'],
                ['key' => '/bugs', 'label' => '🐞 Bug Tracking', 'description' => 'Pelaporan dan pemantauan tiket bug'],
                ['key' => '/retests', 'label' => '🔄 Retesting', 'description' => 'Verifikasi pengujian ulang bug yang diperbaiki'],
                ['key' => '/flows', 'label' => '🔀 Flow Aplikasi', 'description' => 'Dokumentasi & preview file flow bisnis'],
                ['key' => '/guides', 'label' => '📖 Panduan SISQA', 'description' => 'Pusat panduan penggunaan aplikasi'],
                ['key' => '/reports', 'label' => '📑 QA Report', 'description' => 'Laporan resmi QA (Excel & PDF)'],
                ['key' => '/reports/wa', 'label' => '💬 Report WA', 'description' => 'Generator laporan harian versi WhatsApp'],
                ['key' => '/activity-logs', 'label' => '⏱️ Activity Logs', 'description' => 'Riwayat aktivitas pengguna'],
                ['key' => '/master/apps', 'label' => '🏢 Master Aplikasi', 'description' => 'Akses menu Master Data Aplikasi'],
                ['key' => '/master/modules', 'label' => '🗂️ Master Modul (Menu Utama)', 'description' => 'Akses menu Master Modul & Sub-Menu'],
                ['key' => '/master/menus', 'label' => '📌 Master Sub-Menu / Fitur', 'description' => 'Akses menu Master Sub-Menu'],
            ],
            'features' => [
                ['key' => 'dashboard_all_data', 'label' => '🌐 Dashboard: Akses Data Seluruh Tester', 'description' => 'Melihat statistik semua tester & filter lintas tester di Dashboard (Default User: Hanya data sendiri)'],
                ['key' => 'dashboard_exec_charts', 'label' => '📈 Dashboard: Grafik Eksekutif & Analitik Bug', 'description' => 'Menampilkan grafik kurva severity bug, siklus resolusi, dan cakupan lintas aplikasi di Dashboard'],
                ['key' => 'dashboard_quick_actions', 'label' => '⚡ Dashboard: Pintasan Cepat & Filter Lanjutan', 'description' => 'Menampilkan tombol pintasan cepat dan filter sub-menu/periode lengkap di Dashboard'],
                ['key' => 'execute_test', 'label' => '▶ Eksekusi Test Case', 'description' => 'Menjalankan pengujian (PASS/FAILED/PENDING) & unggah bukti'],
                ['key' => 'create_test', 'label' => '+ Tambah & Edit Test Case', 'description' => 'Membuat, mengubah, dan menghapus skenario Test Case'],
                ['key' => 'import_test_excel', 'label' => '📤 Template & Upload Excel Test Case', 'description' => 'Mengunduh template dan mengimpor Test Case dari Excel'],
                ['key' => 'create_bug', 'label' => '+ Laporkan Bug Baru', 'description' => 'Membuat laporan temuan tiket bug baru'],
                ['key' => 'edit_bug', 'label' => '✏️ Edit & Update Status Bug', 'description' => 'Mengubah status tiket bug dan menugaskan developer'],
                ['key' => 'import_bug_excel', 'label' => '📤 Template & Upload Excel Bug', 'description' => 'Mengunduh template dan mengimpor Bug dari Excel'],
                ['key' => 'create_retest', 'label' => '+ Input Hasil Retest', 'description' => 'Mencatat hasil verifikasi pengujian ulang (Retesting)'],
                ['key' => 'manage_flow', 'label' => '📤 Upload & Edit Flow Aplikasi', 'description' => 'Mengunggah file flow (PPT/JPG/PDF) dan mengedit flow'],
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
                return [
                    'role_user' => $decoded['role_user'] ?? self::defaultUserPermissions(),
                    'user_overrides' => $decoded['user_overrides'] ?? [],
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
        $config['role_user'] = [
            'menus' => array_values(array_unique($menus)),
            'features' => array_values(array_unique($features)),
        ];
        if ($resetOverrides) {
            $config['user_overrides'] = [];
        }

        file_put_contents(self::getConfigPath(), json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $config['role_user'];
    }

    public static function saveSingleUserPermissions(int $userId, array $menus, array $features): array
    {
        $config = self::loadConfig();
        $config['user_overrides'][(string) $userId] = [
            'menus' => array_values(array_unique($menus)),
            'features' => array_values(array_unique($features)),
        ];

        file_put_contents(self::getConfigPath(), json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $config['user_overrides'][(string) $userId];
    }

    public static function getPermissionsForUser(?User $user): array
    {
        if (!$user) {
            return ['menus' => [], 'features' => []];
        }

        if ($user->role === 'admin') {
            $defs = self::allAvailableDefinitions();
            return [
                'menus' => array_column($defs['menus'], 'key'),
                'features' => array_column($defs['features'], 'key'),
            ];
        }

        $config = self::loadConfig();
        $uid = (string) $user->id;
        if (isset($config['user_overrides'][$uid])) {
            return $config['user_overrides'][$uid];
        }

        return $config['role_user'] ?? self::defaultUserPermissions();
    }
}
