<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\ApplicationFlow;
use App\Models\ApplicationFlowStep;
use App\Models\ApplicationMenu;
use App\Models\ApplicationModule;
use App\Models\Bug;
use App\Models\BugHistory;
use App\Models\QaSchedule;
use App\Models\Retest;
use App\Models\TestCase;
use App\Models\TestExecution;
use App\Models\UsageGuide;
use App\Models\UsageGuideStep;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@sisqa.com'],
            [
                'name' => 'Admin QA SISQA',
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $tester1 = User::firstOrCreate(
            ['email' => 'qa1@sisqa.com'],
            [
                'name' => 'Budi Santoso (Senior QA)',
                'username' => 'budi_qa',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'is_active' => true,
            ]
        );

        $tester2 = User::firstOrCreate(
            ['email' => 'qa2@sisqa.com'],
            [
                'name' => 'Siti Rahma (QA Engineer)',
                'username' => 'siti_qa',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'is_active' => true,
            ]
        );

        $dev = User::firstOrCreate(
            ['email' => 'dev1@sisqa.com'],
            [
                'name' => 'Rian Hidayat (Lead Dev)',
                'username' => 'rian_dev',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'is_active' => true,
            ]
        );

        // 2. Master Applications
        $appsData = [
            [
                'name' => 'ASIS ERP',
                'code' => 'ASIS-ERP',
                'description' => 'Enterprise Resource Planning komprehensif untuk operasional korporat.',
                'version' => '3.4.2',
                'environment' => 'Production',
                'modules' => [
                    [
                        'name' => 'Master Data',
                        'code' => 'MD',
                        'description' => 'Kelola data master barang, rekanan, gudang, dan satuan.',
                        'menus' => ['Data Barang & Jasa', 'Data Rekanan & Vendor', 'Master Gudang', 'Satuan Ukuran'],
                    ],
                    [
                        'name' => 'Pembelian',
                        'code' => 'PUR',
                        'description' => 'Manajemen order pembelian, PO, dan invoice supplier.',
                        'menus' => ['Permintaan Pembelian (PR)', 'Pesanan Pembelian (PO)', 'Penerimaan Barang (GRN)', 'Faktur Tagihan'],
                    ],
                    [
                        'name' => 'Penjualan',
                        'code' => 'SAL',
                        'description' => 'Manajemen sales order, invoice, dan surat jalan pelanggan.',
                        'menus' => ['Penawaran Harga', 'Sales Order (SO)', 'Surat Jalan Pengiriman', 'Faktur Penjualan'],
                    ],
                    [
                        'name' => 'Gudang atau Stok',
                        'code' => 'WHS',
                        'description' => 'Mutasi stok, opname gudang, dan kartu stok.',
                        'menus' => ['Mutasi Antar Gudang', 'Stock Opname', 'Kartu Stok', 'Penyesuaian Stok'],
                    ],
                    [
                        'name' => 'Produksi',
                        'code' => 'PRD',
                        'description' => 'Work order, jadwal pabrikasi, dan monitoring batch.',
                        'menus' => ['Bill of Materials (BOM)', 'Work Order (WO)', 'Pencatatan Hasil Produksi'],
                    ],
                    [
                        'name' => 'Keuangan',
                        'code' => 'FIN',
                        'description' => 'Jurnal umum, kas bank, buku besar, dan neraca.',
                        'menus' => ['Kas & Bank Masuk', 'Kas & Bank Keluar', 'Jurnal Umum', 'Laporan Laba Rugi'],
                    ],
                    [
                        'name' => 'SDM',
                        'code' => 'HR',
                        'description' => 'Data karyawan, absensi, cuti, dan penggajian.',
                        'menus' => ['Data Karyawan', 'Pengajuan Cuti & Izin', 'Proses Payroll Bulanan'],
                    ],
                    [
                        'name' => 'Service',
                        'code' => 'SVC',
                        'description' => 'Tiket garansi pelanggan dan perbaikan unit.',
                        'menus' => ['Registrasi Tiket Servis', 'Pemeriksaan Teknisi', 'Pengembalian Unit'],
                    ],
                    [
                        'name' => 'Laporan',
                        'code' => 'RPT',
                        'description' => 'Dashboard eksekutif dan analitik bisnis terpusat.',
                        'menus' => ['Laporan Penjualan Konsolidasi', 'Laporan Keuangan Audit', 'Laporan KPI Operasional'],
                    ],
                    [
                        'name' => 'MRP',
                        'code' => 'MRP',
                        'description' => 'Material Requirements Planning untuk rantai pasok.',
                        'menus' => ['Perencanaan Kebutuhan Bahan', 'Simulasi Kapasitas Produksi'],
                    ],
                ],
            ],
            [
                'name' => 'ASIS Golang',
                'code' => 'ASIS-GO',
                'description' => 'Microservices backend berbasis Go untuk transaksi throughput tinggi.',
                'version' => '2.1.0',
                'environment' => 'Staging',
                'modules' => [
                    [
                        'name' => 'Payment Gateway Service',
                        'code' => 'PG-SVC',
                        'description' => 'Integrasi QRIS, Virtual Account, dan Settlement.',
                        'menus' => ['QRIS Generator', 'VA Settlement Callback', 'Refund Processor'],
                    ],
                    [
                        'name' => 'Realtime Inventory Sync',
                        'code' => 'INV-SYNC',
                        'description' => 'Sync engine stok multi-channel via Kafka/Redis.',
                        'menus' => ['Stream Monitor', 'Sync Health Check', 'Conflict Resolver'],
                    ],
                ],
            ],
            [
                'name' => 'Amazink POS',
                'code' => 'AMAZINK-POS',
                'description' => 'Point of Sales modern untuk kasir ritel offline & online.',
                'version' => '1.9.4',
                'environment' => 'Production',
                'modules' => [
                    [
                        'name' => 'Kasir & Transaksi',
                        'code' => 'POS-TRX',
                        'description' => 'Layar kasir cepat, barcode scanner, dan cetak struk thermal.',
                        'menus' => ['Layar Kasir Utama', 'Hold & Resume Order', 'Retur Barang Belanja'],
                    ],
                    [
                        'name' => 'Manajemen Shift & Laci Kas',
                        'code' => 'POS-SHIFT',
                        'description' => 'Buka tutup shift kasir dan rekonsiliasi kas tunai.',
                        'menus' => ['Buka Shift', 'Tutup Shift & Hitung Kas', 'Laporan Kas Kasir'],
                    ],
                ],
            ],
            [
                'name' => 'NEXIA',
                'code' => 'NEXIA',
                'description' => 'Aplikasi mobile customer loyalty dan katalog digital perusahaan.',
                'version' => '4.0.1',
                'environment' => 'Development',
                'modules' => [
                    [
                        'name' => 'Customer Portal',
                        'code' => 'NEX-CUST',
                        'description' => 'Katalog reward poin dan voucher belanja digital.',
                        'menus' => ['Katalog Reward', 'Redeem Voucher', 'Riwayat Poin Loyalitas'],
                    ],
                ],
            ],
            [
                'name' => 'Aplikasi Lainnya',
                'code' => 'OTHER-APP',
                'description' => 'Sistem pendukung dan utilitas internal lainnya.',
                'version' => '1.0.0',
                'environment' => 'Production',
                'modules' => [
                    [
                        'name' => 'Single Sign-On (SSO)',
                        'code' => 'SSO',
                        'description' => 'Identity Provider korporat OAuth2 & LDAP.',
                        'menus' => ['Portal Akun Karyawan', 'Audit Akses Login'],
                    ],
                ],
            ],
        ];

        $createdApps = [];
        $createdModules = [];
        $createdMenus = [];

        foreach ($appsData as $appInfo) {
            $modules = $appInfo['modules'];
            unset($appInfo['modules']);

            $app = Application::firstOrCreate(['code' => $appInfo['code']], $appInfo);
            $createdApps[$app->code] = $app;

            foreach ($modules as $modIdx => $modInfo) {
                $menus = $modInfo['menus'];
                unset($modInfo['menus']);

                $modInfo['application_id'] = $app->id;
                $modInfo['order_index'] = $modIdx + 1;
                $mod = ApplicationModule::firstOrCreate(
                    ['application_id' => $app->id, 'code' => $modInfo['code']],
                    $modInfo
                );
                $createdModules[$app->code . ':' . $mod->code] = $mod;

                foreach ($menus as $menuIdx => $menuName) {
                    $menu = ApplicationMenu::firstOrCreate(
                        ['module_id' => $mod->id, 'code' => $mod->code . '-M' . ($menuIdx + 1)],
                        [
                            'application_id' => $app->id,
                            'name' => $menuName,
                            'order_index' => $menuIdx + 1,
                            'url_or_route' => '/' . strtolower($app->code) . '/' . strtolower($mod->code) . '/' . ($menuIdx + 1),
                            'description' => 'Menu ' . $menuName . ' pada modul ' . $mod->name,
                            'is_active' => true,
                        ]
                    );
                    $createdMenus[] = $menu;
                }
            }
        }

        // 3. Flow Aplikasi (Contoh: Alur Pengadaan Barang ASIS ERP)
        $asisApp = $createdApps['ASIS-ERP'];
        $purModule = $createdModules['ASIS-ERP:PUR'];
        $prMenu = ApplicationMenu::where('module_id', $purModule->id)->first();

        $flow = ApplicationFlow::firstOrCreate(
            ['flow_code' => 'FLOW-ASIS-PUR-01'],
            [
                'title' => 'Alur Standar Pengadaan Barang & Penerimaan PO',
                'application_id' => $asisApp->id,
                'module_id' => $purModule->id,
                'menu_id' => $prMenu ? $prMenu->id : null,
                'description' => 'Alur lengkap dari pembuatan Permintaan Pembelian (PR), persetujuan manajer, penerbitan PO ke vendor, hingga penerimaan barang di gudang.',
                'process_goal' => 'Memastikan seluruh pembelian barang terdokumentasi, disetujui bertingkat, dan kuantitas sesuai saat penerimaan gudang.',
                'actors' => 'Staff Gudang, Manajer Pembelian, Vendor, Staff Keuangan',
                'prerequisites' => 'Data vendor aktif dan master barang telah terdaftar di modul Master Data.',
                'expected_outcome' => 'Barang masuk stok gudang dan faktur tagihan siap dibayar oleh bagian keuangan.',
                'version' => '1.0',
                'status' => 'Published',
                'created_by' => $admin->id,
            ]
        );

        $flowStepsData = [
            [
                'step_number' => 1,
                'title' => 'Input Permintaan Pembelian (PR)',
                'step_type' => 'Start',
                'activity_description' => 'Staff gudang mengajukan PR ketika stok barang di bawah safety stock.',
                'actor' => 'Staff Gudang',
                'input_data' => 'Kode barang, jumlah kebutuhan, tanggal dibutuhkan',
                'expected_result' => 'Nomor PR terbit dengan status Draft/Submitted.',
            ],
            [
                'step_number' => 2,
                'title' => 'Persetujuan Manajer Pembelian',
                'step_type' => 'Decision',
                'activity_description' => 'Manajer memeriksa anggaran dan urgensi kebutuhan barang.',
                'actor' => 'Manajer Pembelian',
                'input_data' => 'Nomor PR & riwayat konsumsi stok',
                'expected_result' => 'PR disetujui (Approved) atau ditolak (Rejected) dengan catatan perbaikan.',
                'decision_branches' => [
                    ['condition' => 'Approved', 'target_step_number' => 3, 'label' => 'Disetujui'],
                    ['condition' => 'Rejected', 'target_step_number' => 1, 'label' => 'Ditolak / Revisi'],
                ],
            ],
            [
                'step_number' => 3,
                'title' => 'Penerbitan Pesanan Pembelian (PO)',
                'step_type' => 'Process',
                'activity_description' => 'Sistem mengonversi PR approved menjadi Purchase Order resmi dan mengirimkannya ke vendor.',
                'actor' => 'Staff Purchasing',
                'input_data' => 'Pilihan vendor rekanan, harga satuan, termin pembayaran',
                'expected_result' => 'Dokumen PO terbit dan terkirim otomatis via email vendor.',
            ],
            [
                'step_number' => 4,
                'title' => 'Penerimaan Barang & Cek Fisik (GRN)',
                'step_type' => 'End',
                'activity_description' => 'Staff gudang menerima fisik kiriman vendor, melakukan quality check, dan mencocokkan kuantitas dengan PO.',
                'actor' => 'Staff Gudang',
                'input_data' => 'Surat jalan vendor, nomor PO, hasil inspeksi fisik',
                'expected_result' => 'Good Receipt Note (GRN) diterbitkan dan stok sistem otomatis bertambah.',
            ],
        ];

        foreach ($flowStepsData as $sData) {
            ApplicationFlowStep::firstOrCreate(
                ['flow_id' => $flow->id, 'step_number' => $sData['step_number']],
                $sData
            );
        }

        // 4. Test Cases
        $testCasesData = [
            [
                'test_case_code' => 'TC-PUR-001',
                'title' => 'Validasi Pengajuan PR dengan Kuantitas Positif',
                'application_id' => $asisApp->id,
                'module_id' => $purModule->id,
                'menu_id' => $prMenu ? $prMenu->id : null,
                'application_flow_id' => $flow->id,
                'description' => 'Memastikan sistem menolak input kuantitas PR bernilai 0 atau negatif.',
                'preconditions' => 'User sudah login sebagai Staff Gudang dengan hak akses menu PR.',
                'test_steps' => "1. Buka menu Permintaan Pembelian\n2. Klik Tambah PR\n3. Pilih barang 'Kertas A4'\n4. Isi kuantitas '-5'\n5. Klik Simpan",
                'test_data' => "Item: Kertas A4, Qty: -5",
                'expected_result' => "Sistem menampilkan pesan error validasi 'Kuantitas harus lebih besar dari 0' dan form tidak tersimpan.",
                'assigned_tester_id' => $tester1->id,
                'priority' => 'High',
                'is_active' => true,
            ],
            [
                'test_case_code' => 'TC-PUR-002',
                'title' => 'Otorisasi Manajer pada Approval PR',
                'application_id' => $asisApp->id,
                'module_id' => $purModule->id,
                'menu_id' => $prMenu ? $prMenu->id : null,
                'application_flow_id' => $flow->id,
                'description' => 'User biasa tanpa role manajer tidak boleh memiliki akses tombol Approval PR.',
                'preconditions' => 'Login menggunakan akun tester/staff biasa.',
                'test_steps' => "1. Buka daftar PR status Submitted\n2. Buka detail salah satu PR\n3. Periksa keberadaan tombol 'Approve PR'",
                'test_data' => "PR-2026-0091",
                'expected_result' => "Tombol Approve tidak muncul dan endpoint API /api/pr/{id}/approve mengembalikan HTTP 403 Forbidden.",
                'assigned_tester_id' => $tester2->id,
                'priority' => 'High',
                'is_active' => true,
            ],
            [
                'test_case_code' => 'TC-POS-001',
                'title' => 'Perhitungan Diskon Bertingkat pada Kasir POS',
                'application_id' => $createdApps['AMAZINK-POS']->id,
                'module_id' => $createdModules['AMAZINK-POS:POS-TRX']->id,
                'menu_id' => ApplicationMenu::where('module_id', $createdModules['AMAZINK-POS:POS-TRX']->id)->first()->id ?? null,
                'application_flow_id' => null,
                'description' => 'Memverifikasi kalkulasi subtotal saat terdapat diskon member 10% ditambah promo voucher Rp 20.000.',
                'preconditions' => 'Aplikasi POS aktif dan terhubung dengan server lokal.',
                'test_steps' => "1. Scan 2 item @ Rp 100.000 (Subtotal Rp 200.000)\n2. Masukkan nomor member (Diskon 10% = Rp 20.000)\n3. Input voucher 'HEMAT20' (-Rp 20.000)\n4. Periksa Grand Total",
                'test_data' => "Item A (100k) x2, Member VIP, Voucher HEMAT20",
                'expected_result' => "Grand Total akhir tepat Rp 160.000 (Rp 200.000 - Rp 20.000 - Rp 20.000).",
                'assigned_tester_id' => $tester1->id,
                'priority' => 'Urgent',
                'is_active' => true,
            ],
            [
                'test_case_code' => 'TC-POS-002',
                'title' => 'Pencetakan Struk Thermal saat Offline',
                'application_id' => $createdApps['AMAZINK-POS']->id,
                'module_id' => $createdModules['AMAZINK-POS:POS-TRX']->id,
                'menu_id' => ApplicationMenu::where('module_id', $createdModules['AMAZINK-POS:POS-TRX']->id)->first()->id ?? null,
                'application_flow_id' => null,
                'description' => 'Transaksi offline harus tetap dapat mencetak struk kasir dan disimpan di antrean sync.',
                'preconditions' => 'Koneksi internet kasir dimatikan, printer thermal USB terhubung.',
                'test_steps' => "1. Putus koneksi LAN/Wifi\n2. Selesaikan transaksi tunai Rp 50.000\n3. Klik Selesai Bayar",
                'test_data' => "Transaksi Tunai Rp 50.000",
                'expected_result' => "Struk tercetak dengan indikator '[MODE OFFLINE]' dan data tersimpan di LocalStorage / SQLite kasir.",
                'assigned_tester_id' => $tester2->id,
                'priority' => 'Medium',
                'is_active' => true,
            ],
            [
                'test_case_code' => 'TC-GO-001',
                'title' => 'Callback Idempotency QRIS Settlement',
                'application_id' => $createdApps['ASIS-GO']->id,
                'module_id' => $createdModules['ASIS-GO:PG-SVC']->id,
                'menu_id' => ApplicationMenu::where('module_id', $createdModules['ASIS-GO:PG-SVC']->id)->first()->id ?? null,
                'application_flow_id' => null,
                'description' => 'Memastikan webhook callback yang dikirim berulang (duplicate event) tidak menyebabkan double credit.',
                'preconditions' => 'Mock webhook server aktif.',
                'test_steps' => "1. Kirim payload callback QRIS pertama (status: PAID)\n2. Verifikasi status transaksi menjadi SUCCESS\n3. Kirim kembali payload callback persis sama untuk kedua kalinya",
                'test_data' => "Transaction ID: TRX-QRIS-99812, Amount: 150000",
                'expected_result' => "Sistem merespons HTTP 200 OK pada request kedua tanpa melakukan update saldo ganda.",
                'assigned_tester_id' => $tester1->id,
                'priority' => 'Urgent',
                'is_active' => true,
            ],
        ];

        $createdTestCases = [];
        foreach ($testCasesData as $tcData) {
            $tc = TestCase::firstOrCreate(['test_case_code' => $tcData['test_case_code']], $tcData);
            $createdTestCases[$tc->test_case_code] = $tc;
        }

        // 5. QA Schedules
        $schedule1 = QaSchedule::firstOrCreate(
            ['schedule_code' => 'SCH-202610-01'],
            [
                'title' => 'Pengujian Regresi Siklus Bulanan ASIS ERP',
                'application_id' => $asisApp->id,
                'module_id' => $purModule->id,
                'menu_id' => $prMenu ? $prMenu->id : null,
                'application_flow_id' => $flow->id,
                'testing_type' => 'Regression',
                'tester_id' => $tester1->id,
                'start_time' => now()->startOfWeek(),
                'end_time' => now()->endOfWeek(),
                'target_test_cases_count' => 2,
                'priority' => 'High',
                'notes' => 'Fokus pengujian pada integritas transaksi modul Pembelian pasca update patch 3.4.2.',
                'status' => 'In Progress',
            ]
        );
        $schedule1->testCases()->syncWithoutDetaching([
            $createdTestCases['TC-PUR-001']->id,
            $createdTestCases['TC-PUR-002']->id,
        ]);

        $schedule2 = QaSchedule::firstOrCreate(
            ['schedule_code' => 'SCH-202610-02'],
            [
                'title' => 'Daily Smoke Testing Amazink POS Ritel',
                'application_id' => $createdApps['AMAZINK-POS']->id,
                'module_id' => $createdModules['AMAZINK-POS:POS-TRX']->id,
                'menu_id' => ApplicationMenu::where('module_id', $createdModules['AMAZINK-POS:POS-TRX']->id)->first()->id ?? null,
                'testing_type' => 'Daily',
                'tester_id' => $tester2->id,
                'start_time' => now()->subDay(),
                'end_time' => now()->addDays(2),
                'target_test_cases_count' => 2,
                'priority' => 'Urgent',
                'notes' => 'Pengecekan rutin fitur kasir dan pencetakan struk sebelum pembukaan toko.',
                'status' => 'Scheduled',
            ]
        );
        $schedule2->testCases()->syncWithoutDetaching([
            $createdTestCases['TC-POS-001']->id,
            $createdTestCases['TC-POS-002']->id,
        ]);

        // 6. Test Executions
        // TC-PUR-001 -> PASS
        TestExecution::firstOrCreate(
            [
                'test_case_id' => $createdTestCases['TC-PUR-001']->id,
                'tester_id' => $tester1->id,
                'executed_at' => now()->subHours(5),
            ],
            [
                'qa_schedule_id' => $schedule1->id,
                'status' => 'PASS',
                'actual_result' => 'Sistem menampilkan popup validasi merah dengan pesan sesuai: Kuantitas harus lebih besar dari 0.',
                'notes' => 'Validasi sisi client dan server bekerja dengan tepat.',
            ]
        );

        // TC-PUR-002 -> FAILED
        $execFailed = TestExecution::firstOrCreate(
            [
                'test_case_id' => $createdTestCases['TC-PUR-002']->id,
                'tester_id' => $tester2->id,
                'executed_at' => now()->subHours(3),
            ],
            [
                'qa_schedule_id' => $schedule1->id,
                'status' => 'FAILED',
                'actual_result' => 'Tombol Approve PR masih tampak aktif untuk akun staff biasa dan tombol dapat diklik.',
                'notes' => 'Security vulnerability: API authorization gate belum dipasang pada controller PR.',
            ]
        );

        // TC-POS-001 -> PASS
        TestExecution::firstOrCreate(
            [
                'test_case_id' => $createdTestCases['TC-POS-001']->id,
                'tester_id' => $tester1->id,
                'executed_at' => now()->subHours(2),
            ],
            [
                'qa_schedule_id' => $schedule2->id,
                'status' => 'PASS',
                'actual_result' => 'Kalkulasi total belanja tepat Rp 160.000 dengan rincian diskon member dan voucher tertera pada layar kasir.',
                'notes' => 'Algoritma diskon bertingkat telah sesuai spesifikasi promo.',
            ]
        );

        // 7. Bug Tracking (Berelasi dengan TC-PUR-002 yang FAILED)
        $bug = Bug::firstOrCreate(
            ['bug_code' => 'BUG-202610-001'],
            [
                'title' => 'Otorisasi Approve PR Terbuka untuk Non-Manager di ASIS ERP',
                'application_id' => $asisApp->id,
                'module_id' => $purModule->id,
                'menu_id' => $prMenu ? $prMenu->id : null,
                'test_case_id' => $createdTestCases['TC-PUR-002']->id,
                'qa_schedule_id' => $schedule1->id,
                'application_flow_id' => $flow->id,
                'application_flow_step_id' => ApplicationFlowStep::where('flow_id', $flow->id)->where('step_number', 2)->first()->id ?? null,
                'description' => 'Staff gudang dengan peran non-manajer dapat menyetujui dokumen PR miliknya sendiri karena ketiadaan middleware otorisasi pada endpoint approve.',
                'steps_to_reproduce' => "1. Login dengan user 'budi_qa' (staff biasa)\n2. Buka URL /asis-erp/pur/pr/detail/102\n3. Klik tombol 'Setujui Permintaan'\n4. Refresh halaman, status PR berubah menjadi 'APPROVED'",
                'expected_result' => 'Akses ditolak dengan pesan error 403 Forbidden.',
                'actual_result' => 'Status PR berubah menjadi Approved dan langsung dapat diteruskan menjadi PO.',
                'reporter_id' => $tester2->id,
                'assignee_id' => $dev->id,
                'report_date' => now()->subDays(1),
                'severity' => 'Critical',
                'priority' => 'Urgent',
                'status' => 'Ready for Retest',
                'resolution_notes' => 'Telah ditambahkan middleware check_role:manager dan validasi policy pada PurchaseRequestController::approve().',
            ]
        );

        BugHistory::firstOrCreate(
            ['bug_id' => $bug->id, 'old_value' => 'Open', 'new_value' => 'Assigned'],
            [
                'user_id' => $admin->id,
                'field_changed' => 'status',
                'notes' => 'Ditugaskan ke Rian Hidayat (Lead Dev) untuk perbaikan segera.',
                'created_at' => now()->subHours(20),
            ]
        );

        BugHistory::firstOrCreate(
            ['bug_id' => $bug->id, 'old_value' => 'Assigned', 'new_value' => 'In Progress'],
            [
                'user_id' => $dev->id,
                'field_changed' => 'status',
                'notes' => 'Sedang mengimplementasikan authorization policy.',
                'created_at' => now()->subHours(10),
            ]
        );

        BugHistory::firstOrCreate(
            ['bug_id' => $bug->id, 'old_value' => 'In Progress', 'new_value' => 'Ready for Retest'],
            [
                'user_id' => $dev->id,
                'field_changed' => 'status',
                'notes' => 'Perbaikan selesai di branch fix/pr-auth-gate. Siap diuji ulang oleh tim QA.',
                'created_at' => now()->subHours(2),
            ]
        );

        // 8. Retesting (Untuk bug BUG-202610-001)
        Retest::firstOrCreate(
            [
                'bug_id' => $bug->id,
                'tester_id' => $tester1->id,
            ],
            [
                'test_case_id' => $createdTestCases['TC-PUR-002']->id,
                'qa_schedule_id' => $schedule1->id,
                'application_id' => $asisApp->id,
                'module_id' => $purModule->id,
                'menu_id' => $prMenu ? $prMenu->id : null,
                'retest_date' => now()->subHour(),
                'tested_version' => '3.4.2-patch1',
                'expected_result' => 'Akses ditolak HTTP 403 saat staff biasa mencoba approve PR.',
                'actual_result' => 'Tombol Approve kini tersembunyi untuk staff biasa dan API mengembalikan HTTP 403 Forbidden jika dipanggil langsung.',
                'status' => 'Passed',
                'notes' => 'Verifikasi retesting berhasil. Kelemahan otorisasi telah tertutup dengan sempurna.',
            ]
        );

        // 9. Panduan Penggunaan SISQA (Lengkap untuk Seluruh Menu Aplikasi)
        $allMenuGuides = [
            [
                'guide_code' => 'GUIDE-DASH-01',
                'title' => 'Panduan Membaca & Menganalisis Dashboard QA',
                'menu_name' => 'Dashboard QA',
                'category' => 'Monitoring & Analitik',
                'menu_purpose' => 'Menampilkan ringkasan kondisi kualitas perangkat lunak secara real-time dari database aktual.',
                'menu_function' => 'Memantau kartu statistik (PASS, FAILED, PENDING, NOT TESTED, Bug Aktif, Jadwal Terlambat), melihat grafik distribusi status, tren bug per severity, dan menyaring data per aplikasi, modul, menu, tester, serta rentang tanggal.',
                'prerequisites' => 'Sudah login ke aplikasi SISQA sebagai Admin atau User.',
                'tips' => 'Gunakan filter Aplikasi -> Modul -> Menu secara berurutan untuk mempersempit analisis kualitas pada satu fitur spesifik.',
                'troubleshooting' => 'Jika grafik kosong atau persentase PASS 0%, periksa apakah filter rentang tanggal atau aplikasi yang dipilih sudah memiliki riwayat eksekusi test case.',
                'observation_status' => 'Sudah Diamati',
                'steps' => [
                    ['title' => 'Gunakan Filter Cakupan Data di Bagian Atas', 'description' => 'Pilih Aplikasi (atau Semua Aplikasi), Modul, Menu, Tester, serta Tanggal Mulai dan Tanggal Akhir. Seluruh kartu angka dan grafik akan otomatis dihitung ulang.', 'notes' => 'Mengubah pilihan Aplikasi akan mereset pilihan Modul dan Menu secara otomatis.'],
                    ['title' => 'Perhatikan Kartu Statistik Utama', 'description' => 'Lihat kartu Total Test Case, jumlah PASS (hijau), FAILED (merah), PENDING (kuning), NOT TESTED (abu-abu), dan Persentase Keberhasilan (Pass Rate).', 'notes' => 'PENDING dan NOT TESTED tidak dihitung sebagai PASS.'],
                    ['title' => 'Analisis Grafik Tren & Distribusi Bug', 'description' => 'Amati grafik distribusi status testing, grafik bug berdasarkan tingkat keparahan (Critical, Major, Minor, Trivial), serta grafik progres QA per aplikasi.', 'notes' => 'Hover kursor pada grafik untuk melihat angka detail.'],
                ],
            ],
            [
                'guide_code' => 'GUIDE-SCH-01',
                'title' => 'Panduan Manajemen Jadwal QA & Kalender Pengujian',
                'menu_name' => 'Jadwal QA',
                'category' => 'Perencanaan QA',
                'menu_purpose' => 'Mengatur jadwal pelaksanaan pengujian perangkat lunak, penugasan tester, target test case, dan pemantauan progres aktual.',
                'menu_function' => 'Menampilkan jadwal dalam mode Daftar maupun Kalender, mendeteksi bentrok jadwal pada tester yang sama, menghubungkan test case ke jadwal, dan memantau persentase penyelesaian.',
                'prerequisites' => 'Data aplikasi dan skenario test case telah tersedia di sistem.',
                'tips' => 'Beralihlah antara tampilan "📋 Daftar" dan "📅 Kalender" di pojok kanan atas untuk memantau beban kerja mingguan/bulanan tim QA.',
                'troubleshooting' => 'Jika muncul peringatan bentrok jadwal saat menyimpan, artinya tester tersebut sudah memiliki jadwal aktif lain di rentang jam yang beririsan.',
                'observation_status' => 'Sudah Diamati',
                'steps' => [
                    ['title' => 'Buka Menu Jadwal QA & Pilih Mode Tampilan', 'description' => 'Klik menu Jadwal QA di sidebar. Gunakan tombol toggle Daftar atau Kalender untuk melihat agenda pengujian.', 'notes' => 'Gunakan filter Status (Scheduled, In Progress, Completed, Overdue) untuk menyaring agenda.'],
                    ['title' => 'Tambah Jadwal Baru (Khusus Admin)', 'description' => 'Klik "+ Tambah Jadwal", isi judul, pilih Aplikasi & Modul, jenis testing (Daily, Weekly, Regression, Retesting), Tester penanggung jawab, waktu mulai & selesai, serta pilih daftar Test Case target.', 'notes' => 'Sistem akan otomatis mendeteksi apabila jadwal tester bentrok.'],
                    ['title' => 'Pantau Progres & Detail Jadwal', 'description' => 'Klik tombol "Detail" pada baris jadwal untuk melihat daftar test case yang terhubung dan progres eksekusi aktualnya.', 'notes' => 'Jadwal yang Completed tetap memisahkan status jadwal dengan status kelulusan test case (PASS/FAILED).'],
                ],
            ],
            [
                'guide_code' => 'GUIDE-TC-01',
                'title' => 'Panduan Manajemen dan Eksekusi Test Case',
                'menu_name' => 'Test Management',
                'category' => 'Pengujian',
                'menu_purpose' => 'Menyediakan repositori terpusat skenario pengujian, langkah pengujian, ekspektasi hasil, dan pencatatan hasil aktual testing beserta bukti (evidence).',
                'menu_function' => 'Membuat test case baru, menyaring berdasarkan aplikasi/modul, menjalankan eksekusi (PASS/FAILED/PENDING/NOT TESTED), mengunggah bukti testing, dan melihat riwayat eksekusi.',
                'prerequisites' => 'Telah terdaftar sebagai user atau admin aktif di sistem SISQA.',
                'tips' => 'Selalu sertakan langkah reproduksi yang terperinci dan lampirkan screenshot/video jika hasil pengujian berstatus FAILED.',
                'troubleshooting' => 'Jika unggah file evidence gagal, pastikan format file termasuk PNG, JPG, WEBP, PDF, DOC, XLS, atau MP4 dengan ukuran maksimal 50MB.',
                'observation_status' => 'Sudah Diamati',
                'steps' => [
                    ['title' => 'Cari atau Buat Skenario Test Case', 'description' => 'Gunakan filter Aplikasi, Modul, atau Prioritas untuk mencari skenario pengujian. Admin dapat mengklik "+ Tambah Test Case" untuk mendokumentasikan skenario baru.', 'notes' => 'Setiap Test Case dapat dihubungkan ke Flow Aplikasi terkait.'],
                    ['title' => 'Jalankan Pengujian dengan Tombol "▶ Eksekusi"', 'description' => 'Klik tombol hijau "▶ Eksekusi" pada baris test case yang diuji. Baca Expected Result, lalu pilih status hasil: PASS, FAILED, PENDING, atau NOT TESTED.', 'notes' => 'Isi kolom Hasil Aktual (Actual Result) dan unggah file bukti testing bila ada.'],
                    ['title' => 'Periksa Riwayat Eksekusi di Tombol "Detail"', 'description' => 'Klik tombol "Detail" untuk melihat seluruh riwayat pengujian sebelumnya beserta siapa tester yang mengeksekusi dan lampiran buktinya.', 'notes' => 'Dashboard menggunakan satu hasil eksekusi terbaru per test case untuk ringkasan kondisi saat ini.'],
                ],
            ],
            [
                'guide_code' => 'GUIDE-BUG-01',
                'title' => 'Panduan Pelaporan Bug & Defect Tracking',
                'menu_name' => 'Bug Tracking',
                'category' => 'Defect Tracking',
                'menu_purpose' => 'Mendokumentasikan temuan bug/error perangkat lunak, menentukan tingkat keparahan (Severity) & prioritas, menugaskan developer, dan melacak riwayat perbaikan.',
                'menu_function' => 'Membuat tiket bug baru, menghubungkan bug dengan test case/flow langkah terkait, mengunggah screenshot error, mengubah status penyelesaian, dan memantau log perubahan status.',
                'prerequisites' => 'Ditemukan anomali sistem atau ketidaksesuaian antara expected result dan actual result saat pengujian.',
                'tips' => 'Bedakan antara Severity (dampak teknis bug terhadap sistem: Critical, Major, Minor, Trivial) dan Priority (urgensi urutan pengerjaan: Urgent, High, Medium, Low).',
                'troubleshooting' => 'Jika bug sudah diperbaiki oleh developer, ubah status tiket menjadi "Ready for Retest" agar muncul di daftar antrean menu Retesting.',
                'observation_status' => 'Sudah Diamati',
                'steps' => [
                    ['title' => 'Laporkan Temuan Bug Baru', 'description' => 'Klik tombol merah "+ Laporkan Bug", isi judul bug, pilih Aplikasi & Modul, pilih Test Case terkait, tulis langkah reproduksi (Steps to Reproduce), Hasil Diharapkan vs Hasil Aktual, dan unggah bukti screenshot/video.', 'notes' => 'Tentukan Severity (Critical/Major/Minor/Trivial) dan Priority dengan tepat.'],
                    ['title' => 'Kelola Status & Penugasan Bug (Tombol Edit)', 'description' => 'Klik tombol "Edit" pada baris bug untuk menugaskan Developer (Assignee), memperbarui status (Open -> Assigned -> In Progress -> Fixed -> Ready for Retest), dan mengisi catatan resolusi.', 'notes' => 'Setiap perubahan status akan tercatat otomatis di riwayat histori bug.'],
                    ['title' => 'Lihat Detail, Bukti & Audit Histori Bug', 'description' => 'Klik tombol "Detail" untuk melihat deskripsi lengkap, mengunduh file bukti (evidence), dan meninjau jejak waktu perubahan status tiket.', 'notes' => 'Tiket hanya boleh berstatus Closed setelah terverifikasi lulus Retesting.'],
                ],
            ],
            [
                'guide_code' => 'GUIDE-RET-01',
                'title' => 'Panduan Verifikasi Pengujian Ulang (Retesting)',
                'menu_name' => 'Retesting',
                'category' => 'Verifikasi Perbaikan',
                'menu_purpose' => 'Memverifikasi apakah tiket bug yang telah diperbaiki oleh tim developer benar-benar sudah teratasi tanpa menimbulkan error baru.',
                'menu_function' => 'Mencatat hasil pengujian ulang terhadap tiket bug berstatus Ready for Retest, mengunggah bukti retesting, serta otomatis menutup (Closed) atau membuka kembali (Reopened) tiket bug.',
                'prerequisites' => 'Terdapat tiket bug dengan status "Ready for Retest", "Fixed", atau "Open".',
                'tips' => 'Selalu cantumkan nomor versi patch aplikasi yang diuji (misal: v3.4.2-patch1) pada saat mencatat hasil retesting.',
                'troubleshooting' => 'Jika tiket bug tidak muncul di dropdown pilihan saat klik Input Hasil Retest, pastikan status tiket di menu Bug Tracking sudah diubah ke "Ready for Retest" atau "Fixed".',
                'observation_status' => 'Sudah Diamati',
                'steps' => [
                    ['title' => 'Klik "+ Input Hasil Retest"', 'description' => 'Buka menu Retesting, lalu klik tombol "+ Input Hasil Retest" di kanan atas halaman.', 'notes' => 'Anda juga dapat menyaring riwayat retesting berdasarkan status Passed, Failed, atau Reopened.'],
                    ['title' => 'Pilih Tiket Bug & Tentukan Status Hasil', 'description' => 'Pilih tiket bug yang diuji ulang, isi versi aplikasi yang diuji, dan pilih hasil: Passed (otomatis mengubah status bug menjadi Closed) atau Failed/Reopened (otomatis mengubah status bug menjadi Reopened).', 'notes' => 'Lampirkan screenshot bukti bahwa perbaikan telah berjalan normal.'],
                    ['title' => 'Tinjau Riwayat & Bukti Retesting', 'description' => 'Lihat tabel riwayat retesting untuk memantau seluruh verifikasi yang pernah dilakukan lengkap dengan tautan unduh "📎 Bukti".', 'notes' => 'Satu bug dapat memiliki beberapa riwayat retesting jika sempat gagal dan diperbaiki ulang.'],
                ],
            ],
            [
                'guide_code' => 'GUIDE-FLOW-01',
                'title' => 'Panduan Dokumentasi & Diagram Flow Aplikasi',
                'menu_name' => 'Flow Aplikasi',
                'category' => 'Dokumentasi Sistem',
                'menu_purpose' => 'Mendokumentasikan alur proses bisnis aplikasi dalam bentuk visualisasi diagram node (Start, Process, Decision, End) beserta berkas pendukung.',
                'menu_function' => 'Menyusun langkah-langkah alur kerja bisnis, menentukan aktor di setiap tahapan, memvisualisasikan diagram alur interaktif, dan mengunggah file dokumen/diagram.',
                'prerequisites' => 'Aplikasi dan modul terkait sudah terdaftar di Master Data.',
                'tips' => 'Klik pada kotak node di dalam modal "📊 Lihat Diagram" untuk membaca detail aktivitas, input data, dan hasil yang diharapkan pada langkah tersebut.',
                'troubleshooting' => 'User non-admin hanya dapat melihat Flow Aplikasi yang berstatus "Published". Jika flow belum tampil bagi tester, pastikan statusnya bukan "Draft".',
                'observation_status' => 'Sudah Diamati',
                'steps' => [
                    ['title' => 'Lihat Visualisasi Diagram Alur Proses', 'description' => 'Pilih salah satu kartu Flow Aplikasi, lalu klik tombol "📊 Lihat Diagram" untuk membuka tampilan visualisasi flowchart dan daftar berkas dokumen pendukung.', 'notes' => 'Admin juga dapat mengunggah berkas PDF/Gambar/Draw.io langsung di dalam modal diagram.'],
                    ['title' => 'Buat atau Edit Flow Aplikasi (Khusus Admin)', 'description' => 'Klik "+ Tambah Flow Baru" atau tombol "Edit" pada kartu flow. Isi informasi tujuan proses, aktor, prasyarat, dan tambahkan node langkah menggunakan tombol "+ Tambah Langkah (Node)".', 'notes' => 'Pilih tipe node: Start (Mulai), Process (Proses), Decision (Percabangan), atau End (Selesai).'],
                ],
            ],
            [
                'guide_code' => 'GUIDE-MAN-01',
                'title' => 'Panduan Mengelola & Mengamati Panduan SISQA',
                'menu_name' => 'Panduan SISQA',
                'category' => 'Dokumentasi Operasional',
                'menu_purpose' => 'Menyediakan pusat petunjuk penggunaan seluruh menu SISQA sekaligus mencatat verifikasi pengamatan oleh Administrator.',
                'menu_function' => 'Membaca petunjuk teknis per menu, membuat/mengedit panduan penggunaan, serta menandai panduan sebagai "Sudah Diamati oleh Admin" beserta log audit pengamatannya.',
                'prerequisites' => 'Semua pengguna dapat membaca panduan Published; hanya Admin yang dapat mengubah panduan dan menandai Sudah Diamati.',
                'tips' => 'Anda juga dapat membuka panduan menu yang sedang aktif kapan saja melalui tombol "📖 Panduan Menu Ini" di bilah header atas layar.',
                'troubleshooting' => 'Apabila panduan yang tadinya berstatus "Sudah Diamati" diedit isinya atau versinya oleh Admin, sistem otomatis mereset statusnya menjadi "Belum Diamati" agar ditinjau ulang.',
                'observation_status' => 'Sudah Diamati',
                'steps' => [
                    ['title' => 'Baca Detail Panduan Penggunaan', 'description' => 'Cari panduan berdasarkan nama menu atau kata kunci, kemudian klik tombol "📖 Baca" untuk melihat tujuan menu, langkah-langkah berurutan, tips, dan solusi masalah.', 'notes' => 'Tersedia juga filter Status Pengamatan Admin.'],
                    ['title' => 'Tandai Panduan Sudah Diamati (Khusus Admin)', 'description' => 'Setelah memeriksa kesesuaian isi panduan, Admin dapat mengklik tombol hijau "✓ Tandai Diamati". Sistem akan mencatat nama Admin, waktu pengamatan, dan versi panduan.', 'notes' => 'Riwayat pengamatan terekam permanen di bagian bawah modal detail panduan.'],
                ],
            ],
            [
                'guide_code' => 'GUIDE-RPT-01',
                'title' => 'Panduan Penyusunan & Ekspor Laporan QA (Excel & PDF)',
                'menu_name' => 'QA Report',
                'category' => 'Pelaporan & Ekspor',
                'menu_purpose' => 'Menghasilkan laporan resmi kualitas pengujian perangkat lunak yang siap diekspor ke format Microsoft Excel (.xlsx) maupun dokumen PDF siap cetak.',
                'menu_function' => 'Menyaring cakupan laporan lintas aplikasi/modul/periode, memilih metode perhitungan (kondisi terbaru vs seluruh riwayat eksekusi), dan mengunduh laporan Excel multi-sheet atau PDF.',
                'prerequisites' => 'Terdapat data test case dan riwayat eksekusi pada cakupan filter yang dipilih.',
                'tips' => 'Gunakan mode perhitungan "Status Terbaru (1 Hasil Terakhir per Test Case)" untuk laporan status rilis saat ini, atau "Historis (Seluruh Eksekusi)" untuk audit aktivitas periodik.',
                'troubleshooting' => 'Pastikan pop-up/download di browser tidak diblokir saat mengklik tombol Ekspor Excel atau Ekspor PDF.',
                'observation_status' => 'Sudah Diamati',
                'steps' => [
                    ['title' => 'Atur Filter Cakupan & Metode Perhitungan', 'description' => 'Pilih Aplikasi, Modul, Tester, Rentang Tanggal, dan Mode Perhitungan, lalu klik tombol "Terapkan Filter".', 'notes' => 'Metode perhitungan dijelaskan secara transparan pada kotak informasi di halaman laporan.'],
                    ['title' => 'Tinjau Ringkasan KPI & Rincian Tabel', 'description' => 'Periksa angka Pass Rate, rincian status eksekusi, serta daftar temuan bug yang tampil di layar pratinjau laporan.', 'notes' => 'Seluruh angka dihitung langsung dari data aktual di database.'],
                    ['title' => 'Ekspor ke Excel (.xlsx) atau PDF (.pdf)', 'description' => 'Klik tombol hijau "📊 Ekspor Excel" untuk mengunduh spreadsheet multi-sheet (Ringkasan, Detail Test Case, Daftar Bug) atau tombol merah "📄 Ekspor PDF" untuk dokumen laporan formal.', 'notes' => 'Setiap aktivitas ekspor dicatat ke dalam Activity Logs.'],
                ],
            ],
            [
                'guide_code' => 'GUIDE-LOG-01',
                'title' => 'Panduan Audit Trail & Activity Logs',
                'menu_name' => 'Activity Logs',
                'category' => 'Keamanan & Audit',
                'menu_purpose' => 'Merekam seluruh jejak aktivitas pengguna di dalam sistem SISQA untuk kebutuhan transparansi, keamanan, dan audit internal.',
                'menu_function' => 'Menampilkan daftar log aktivitas (login, eksekusi test, lapor bug, retesting, perubahan status, pengamatan panduan, ekspor laporan) beserta waktu, alamat IP, dan perubahan nilai.',
                'prerequisites' => 'Admin dapat melihat aktivitas seluruh pengguna; User biasa hanya melihat riwayat aktivitas miliknya sendiri.',
                'tips' => 'Gunakan filter Modul atau Kata Kunci untuk melacak kapan suatu tiket bug atau panduan terakhir kali diubah.',
                'troubleshooting' => 'Demi keamanan, password dan token autentikasi tidak pernah disimpan di dalam Activity Logs.',
                'observation_status' => 'Sudah Diamati',
                'steps' => [
                    ['title' => 'Saring Riwayat Aktivitas', 'description' => 'Gunakan kolom pencarian atau filter modul untuk menemukan aktivitas spesifik berdasarkan nama user, jenis aksi, atau kode dokumen.', 'notes' => 'Log diurutkan dari aktivitas paling baru di urutan teratas.'],
                    ['title' => 'Periksa Detail Waktu, Aktor & Alamat IP', 'description' => 'Lihat informasi pengguna pelaku aksi, modul terkait, deskripsi aktivitas, status keberhasilan, dan alamat IP perangkat.', 'notes' => 'Data log bersifat read-only dan tidak dapat dihapus sembarangan demi integritas audit.'],
                ],
            ],
            [
                'guide_code' => 'GUIDE-MAPP-01',
                'title' => 'Panduan Pengelolaan Master Data Aplikasi',
                'menu_name' => 'Master Aplikasi',
                'category' => 'Administrasi Master',
                'menu_purpose' => 'Mengelola daftar aplikasi/sistem perusahaan yang menjadi objek pengujian tim Quality Assurance.',
                'menu_function' => 'Menambah, mengubah, menonaktifkan, dan menghapus master aplikasi beserta informasi kode unik, versi rilis, dan environment (Production/Staging/Development).',
                'prerequisites' => 'Hanya dapat diakses oleh pengguna dengan role Admin.',
                'tips' => 'Perbarui kolom Versi Aplikasi setiap kali ada rilis baru agar pencatatan pengujian selalu mengacu pada versi terkini.',
                'troubleshooting' => 'Menghapus aplikasi akan menghapus data modul dan menu di bawahnya. Gunakan status Nonaktif jika hanya ingin menghentikan sementara pengujian aplikasi tersebut.',
                'observation_status' => 'Sudah Diamati',
                'steps' => [
                    ['title' => 'Tambah Master Aplikasi Baru', 'description' => 'Klik tombol "+ Tambah Aplikasi", masukkan Nama Aplikasi, Kode Unik (contoh: ASIS-ERP), Versi, Environment, dan Deskripsi, lalu klik Simpan.', 'notes' => 'Kode aplikasi harus bersifat unik.'],
                    ['title' => 'Edit atau Hapus Aplikasi', 'description' => 'Gunakan tombol "Edit" berwarna kuning untuk memperbarui versi/informasi aplikasi, atau tombol "Hapus" berwarna merah untuk menghapus data.', 'notes' => 'Tabel juga menampilkan jumlah modul dan jumlah test case pada setiap aplikasi.'],
                ],
            ],
            [
                'guide_code' => 'GUIDE-MMOD-01',
                'title' => 'Panduan Pengelolaan Master Data Modul',
                'menu_name' => 'Master Modul',
                'category' => 'Administrasi Master',
                'menu_purpose' => 'Mengelompokkan fitur-fitur di dalam suatu aplikasi ke dalam modul-modul fungsional (seperti Pembelian, Penjualan, Keuangan, Gudang).',
                'menu_function' => 'Menambah, mengedit, dan menghapus modul yang terhubung langsung ke aplikasi induknya.',
                'prerequisites' => 'Aplikasi induk harus sudah terdaftar di menu Master Aplikasi. Khusus Admin.',
                'tips' => 'Gunakan kode modul singkat (2-5 huruf, misal: PUR, SAL, FIN) agar kode menu dan test case mudah dikenali.',
                'troubleshooting' => 'Jika aplikasi induk tidak muncul di dropdown, pastikan aplikasi tersebut sudah dibuat di Master Aplikasi.',
                'observation_status' => 'Sudah Diamati',
                'steps' => [
                    ['title' => 'Tambah Modul pada Aplikasi', 'description' => 'Klik "+ Tambah Modul", pilih Aplikasi Induk dari dropdown, isi Nama Modul dan Kode Modul, lalu klik Simpan.', 'notes' => 'Modul ini akan otomatis muncul pada dropdown bertingkat saat memilih aplikasi terkait.'],
                    ['title' => 'Kelola Daftar Modul', 'description' => 'Klik tombol "Edit" untuk mengubah nama/kode modul atau "Hapus" untuk menghapus modul yang belum digunakan.', 'notes' => 'Setiap modul menampilkan jumlah menu yang terdaftar di dalamnya.'],
                ],
            ],
            [
                'guide_code' => 'GUIDE-MMEN-01',
                'title' => 'Panduan Pengelolaan Master Data Menu / Fitur',
                'menu_name' => 'Master Menu',
                'category' => 'Administrasi Master',
                'menu_purpose' => 'Mendata halaman menu spesifik atau endpoint fitur di dalam setiap modul aplikasi yang akan diuji.',
                'menu_function' => 'Menambah, mengubah, dan menghapus data menu dengan relasi berjenjang (Aplikasi -> Modul -> Menu) beserta informasi URL/Route.',
                'prerequisites' => 'Aplikasi dan Modul terkait sudah terdaftar. Khusus Admin.',
                'tips' => 'Isi kolom Route / Path URL (misal: /asis-erp/pur/po) untuk memudahkan tester mengetahui alamat halaman yang diuji.',
                'troubleshooting' => 'Ketika Anda mengganti pilihan Aplikasi pada form, daftar pilihan Modul akan otomatis dimuat ulang menyesuaikan aplikasi yang dipilih.',
                'observation_status' => 'Sudah Diamati',
                'steps' => [
                    ['title' => 'Tambah Menu Fitur Baru', 'description' => 'Klik "+ Tambah Menu", pilih Aplikasi terlebih dahulu agar dropdown Modul terisi, lalu pilih Modul, masukkan Nama Menu, Kode Menu, dan Route URL.', 'notes' => 'Relasi Aplikasi dan Modul divalidasi di backend agar selalu konsisten.'],
                    ['title' => 'Edit atau Hapus Menu', 'description' => 'Klik tombol "Edit" pada baris menu untuk memperbarui detailnya, atau klik "Hapus" untuk menghapus menu.', 'notes' => 'Data menu ini digunakan sebagai filter spesifik di Dashboard, Jadwal, Test Case, dan Bug Tracking.'],
                ],
            ],
            [
                'guide_code' => 'GUIDE-USR-01',
                'title' => 'Panduan Manajemen Pengguna & Hak Akses Role',
                'menu_name' => 'Manajemen Pengguna',
                'category' => 'Administrasi Sistem',
                'menu_purpose' => 'Mengelola akun anggota tim QA, Developer, dan Administrator beserta pengaturan role dan status aktif login.',
                'menu_function' => 'Membuat akun pengguna baru, mengatur role (Admin atau User), mereset password, menonaktifkan/mengaktifkan akun dengan satu klik, dan menghapus pengguna.',
                'prerequisites' => 'Hanya dapat diakses oleh Administrator.',
                'tips' => 'Jika seorang anggota tim sudah tidak bertugas namun riwayat pengujiannya harus tetap terjaga, cukup klik lencana status "● Aktif" menjadi "○ Nonaktif" tanpa menghapus akunnya.',
                'troubleshooting' => 'Pengguna dengan status Nonaktif otomatis ditolak saat mencoba login dan token sesinya langsung dicabut.',
                'observation_status' => 'Sudah Diamati',
                'steps' => [
                    ['title' => 'Tambah Akun Pengguna Baru', 'description' => 'Klik "+ Tambah Pengguna", isi Nama Lengkap, Username unik, pilih Role (User / QA Tester atau Admin QA), Email unik, dan Password minimal 8 karakter.', 'notes' => 'Password dienkripsi menggunakan algoritma hashing aman di database.'],
                    ['title' => 'Ubah Status Aktif atau Edit Akun', 'description' => 'Klik lencana status "● Aktif / ○ Nonaktif" pada tabel untuk mengaktifkan/menonaktifkan akses login pengguna secara instan, atau klik "Edit" untuk memperbarui data profil dan password.', 'notes' => 'Kosongkan kolom password saat mengedit jika tidak ingin mengubah password pengguna.'],
                ],
            ],
            [
                'guide_code' => 'GUIDE-PRF-01',
                'title' => 'Panduan Pengaturan Profil Saya & Keamanan Password',
                'menu_name' => 'Profil Saya',
                'category' => 'Akun Pengguna',
                'menu_purpose' => 'Memungkinkan setiap pengguna memperbarui identitas profil pribadinya dan mengganti password akun secara mandiri.',
                'menu_function' => 'Mengubah nama lengkap, username, alamat email, serta memperbarui password dengan validasi password lama.',
                'prerequisites' => 'Sudah login ke dalam aplikasi SISQA.',
                'tips' => 'Gunakan kombinasi huruf besar, huruf kecil, dan angka minimal 8 karakter saat memperbarui password Anda.',
                'troubleshooting' => 'Jika gagal mengubah password, pastikan Password Saat Ini yang Anda ketik sudah benar dan Konfirmasi Password Baru sama persis.',
                'observation_status' => 'Sudah Diamati',
                'steps' => [
                    ['title' => 'Perbarui Informasi Profil', 'description' => 'Buka halaman Profil Saya dari tombol header atas atau footer sidebar. Ubah Nama Lengkap, Username, atau Email, lalu klik "Simpan Perubahan Profil".', 'notes' => 'Perubahan nama akan langsung tercermin pada tampilan sesi Anda.'],
                    ['title' => 'Ganti Password Keamanan', 'description' => 'Pada kartu Ubah Password, masukkan Password Saat Ini, Password Baru (minimal 8 karakter), dan Konfirmasi Password Baru, kemudian klik "Perbarui Password".', 'notes' => 'Aktivitas perubahan password dicatat tanpa menyimpan teks password ke log.'],
                ],
            ],
        ];

        foreach ($allMenuGuides as $gData) {
            $steps = $gData['steps'];
            unset($gData['steps']);

            $guide = UsageGuide::updateOrCreate(
                ['guide_code' => $gData['guide_code']],
                array_merge($gData, [
                    'version' => '1.0',
                    'status' => 'Published',
                    'last_observed_by' => $gData['observation_status'] === 'Sudah Diamati' ? $admin->id : null,
                    'last_observed_at' => $gData['observation_status'] === 'Sudah Diamati' ? now()->subHours(6) : null,
                    'last_observed_version' => $gData['observation_status'] === 'Sudah Diamati' ? '1.0' : null,
                    'created_by' => $admin->id,
                ])
            );

            foreach ($steps as $idx => $st) {
                UsageGuideStep::updateOrCreate(
                    ['usage_guide_id' => $guide->id, 'step_number' => $idx + 1],
                    [
                        'title' => $st['title'],
                        'description' => $st['description'],
                        'notes' => $st['notes'] ?? null,
                    ]
                );
            }
        }

        // 10. Initial Activity Logs
        ActivityLog::record(
            'login_success',
            'Auth',
            'Admin login ke sistem SISQA dari alamat IP lokal.',
            'User',
            $admin->id,
            null,
            null,
            'Success',
            $admin->id
        );

        ActivityLog::record(
            'test_executed',
            'Test Management',
            'Tester Budi Santoso mengeksekusi test case TC-PUR-001 dengan hasil PASS.',
            'TestExecution',
            1,
            null,
            ['status' => 'PASS', 'test_case_code' => 'TC-PUR-001'],
            'Success',
            $tester1->id
        );

        ActivityLog::record(
            'bug_created',
            'Bug Tracking',
            'Tester Siti Rahma melaporkan tiket bug baru BUG-202610-001 (Severity: Critical).',
            'Bug',
            $bug->id,
            null,
            ['bug_code' => 'BUG-202610-001', 'severity' => 'Critical'],
            'Success',
            $tester2->id
        );
    }
}
