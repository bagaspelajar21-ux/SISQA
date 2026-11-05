<?php

namespace Database\Seeders;

use App\Models\UsageGuide;
use App\Models\UsageGuideStep;
use Illuminate\Database\Seeder;

/**
 * Memperbarui konten panduan mengikuti fitur terbaru (jadwal QA terpusat,
 * multi-tester, panel eksekusi, tautan jadwal test case) dan melengkapi
 * panduan untuk menu yang belum tercakup. Idempotent: aman dijalankan ulang.
 */
class GuideContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->guides() as $data) {
            $menuName = $data['menu_name'];
            $steps = $data['steps'];
            unset($data['steps']);

            $guide = UsageGuide::withTrashed()->where('menu_name', $menuName)->first();

            if ($guide) {
                $guide->steps()->delete();
                $data['observation_status'] = 'Belum Diamati';
                $data['version'] = $data['version'] ?? '1.1';
                $guide->update($data);
            } else {
                $data['guide_code'] = sprintf('GUIDE-%04d', UsageGuide::withTrashed()->count() + 1);
                $data['created_by'] = 1;
                $data['version'] = $data['version'] ?? '1.0';
                $data['observation_status'] = 'Belum Diamati';
                $data['status'] = $data['status'] ?? 'Published';
                $guide = UsageGuide::create($data);
            }

            foreach ($steps as $index => $step) {
                UsageGuideStep::create([
                    'usage_guide_id' => $guide->id,
                    'step_number' => $index + 1,
                    'title' => $step['title'],
                    'description' => $step['description'],
                    'notes' => $step['notes'] ?? null,
                ]);
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function guides(): array
    {
        return [
            $this->jadwalQaGuide(),
            $this->testManagementGuide(),
            $this->reportWaGuide(),
            $this->reportMingguanGuide(),
            $this->analisisPekerjaanGuide(),
            $this->pengingatNotifikasiGuide(),
            $this->pengaturanQaGuide(),
        ];
    }

    /**
     * @return array<int, array{title: string, description: string, notes?: string}>
     */
    private function steps(array $items): array
    {
        return $items;
    }

    private function jadwalQaGuide(): array
    {
        return [
            'title' => 'Panduan Manajemen Jadwal QA Terpusat & Kalender Pengujian',
            'menu_name' => 'Jadwal QA',
            'menu_path' => '/schedules',
            'category' => 'Operasional QA',
            'version' => '2.0',
            'menu_purpose' => 'Mengatur satu jadwal QA per modul yang mencakup banyak submenu, menugaskan beberapa tester sekaligus, dan memantau progres eksekusi aktual per submenu.',
            'menu_function' => 'Mendukung cakupan submenu "Semua" atau "Tertentu", multi-tester, test case yang diturunkan otomatis dari cakupan submenu (dapat dikurangi admin), deteksi bentrok jadwal tester, progres per submenu, riwayat aktivitas, dan panel eksekusi 2 langkah untuk tester.',
            'tips' => 'Gunakan satu jadwal per modul agar tester tidak perlu menunggu jadwal terpisah untuk tiap submenu. Admin dapat mengurangi cakupan test case dari detail jadwal tanpa mengubah cakupan submenu.',
            'troubleshooting' => 'Jika tester tidak melihat jadwal, pastikan tester sudah ditambahkan pada jadwal (checkbox tester) atau jadwal lama hanya memiliki tester_id tunggal. Jika eksekusi ditolak, periksa apakah jadwal sudah terkunci (lewat end_time sesuai mode lock) atau test case/submenu tidak tercakup jadwal.',
            'steps' => $this->steps([
                ['title' => 'Buka Menu Jadwal QA & Pilih Mode Tampilan', 'description' => 'Klik menu Jadwal QA di sidebar. Gunakan tombol toggle "Daftar" atau "Kalender" untuk melihat agenda pengujian. Filter aplikasi, status, tester, dan rentang tanggal tersedia di bagian atas.'],
                ['title' => 'Buat Jadwal Terpusat per Modul (Admin)', 'description' => 'Klik "+ Tambah Jadwal", isi judul, pilih Aplikasi lalu Modul (wajib). Pada bagian "Cakupan Submenu" pilih "Semua Submenu" (snapshot seluruh submenu aktif modul) atau "Submenu Tertentu" lalu centang submenu yang dicakup. Test case dihitung otomatis dari cakupan submenu.', 'notes' => 'Minimal satu submenu harus dicakup saat memilih mode tertentu.'],
                ['title' => 'Tugaskan Banyak Tester Sekaligus', 'description' => 'Pada bagian tester, centang satu atau lebih tester dari daftar pengguna. Sistem otomatis mendeteksi bentrok jadwal pada rentang waktu yang sama dan menampilkan peringatan sebelum simpan.'],
                ['title' => 'Kurangi Cakupan Test Case (Opsional, Admin)', 'description' => 'Buka "Detail" jadwal → bagian "Test Case dalam Jadwal" → hapus centang test case yang tidak perlu → klik "Simpan Cakupan TC". Cakupan submenu tidak berubah; hanya daftar test case yang dieksekusi yang berkurang.'],
                ['title' => 'Pantau Progres per Submenu & Riwayat Aktivitas', 'description' => 'Di modal Detail jadwal terdapat tabel cakupan submenu dengan statistik PASS/FAIL/PENDING/NOT TESTED per submenu, daftar test case beserta status eksekusinya, dan riwayat aktivitas gabungan (log + eksekusi semua tester).'],
                ['title' => 'Mulai Eksekusi QA sebagai Tester', 'description' => 'Pada jadwal yang ditugaskan dan belum terkunci, klik "▶ Mulai Eksekusi QA". Panel eksekusi menampilkan langkah 1 (pilih submenu dari cakupan jadwal) lalu langkah 2 (daftar test case submenu tersebut). Pilih test case, isi hasil (PASS/FAILED/PENDING/NOT TESTED), catatan, dan unggah bukti bila perlu.', 'notes' => 'Hanya test case dalam cakupan jadwal & submenu terpilih yang bisa dieksekusi.'],
                ['title' => 'Deteksi Bentrok & Status Jadwal', 'description' => 'Mode Kalender menampilkan seluruh agenda aktif. Jadwal yang melewati end_time otomatis berubah status "Overdue" dan eksekusi terkunci sesuai mode lock yang diatur admin (Pengaturan QA).'],
            ]),
        ];
    }

    private function testManagementGuide(): array
    {
        return [
            'title' => 'Panduan Manajemen Test Case, Tautan Jadwal & Eksekusi',
            'menu_name' => 'Test Management',
            'menu_path' => '/test-cases',
            'category' => 'Operasional QA',
            'version' => '2.0',
            'menu_purpose' => 'Mengelola skenario test case, menautkannya ke jadwal QA, dan menjalankan eksekusi test dengan bukti pendukung.',
            'menu_function' => 'Mendukung filter berdasarkan jadwal QA, field tautan jadwal opsional pada form test case, impor Excel, serta eksekusi dengan status dan unggah bukti file.',
            'tips' => 'Gunakan filter jadwal untuk melihat test case yang tercakup suatu jadwal. Test case yang ditautkan ke jadwal tetap bisa dieksekusi lewat panel jadwal.',
            'troubleshooting' => 'Jika test case tidak muncul saat eksekusi jadwal, pastikan test case termasuk dalam cakupan jadwal (otomatis dari submenu atau dipilih admin) dan submenu-nya termasuk cakupan jadwal.',
            'steps' => $this->steps([
                ['title' => 'Buka Menu Test Management & Filter', 'description' => 'Klik menu Test Management di sidebar. Gunakan filter aplikasi, modul, menu, dan jadwal QA untuk menyaring daftar test case.'],
                ['title' => 'Tambah / Edit Test Case', 'description' => 'Klik tombol tambah untuk membuat skenario baru: isi kode, judul, aplikasi, modul, submenu, prioritas, dan status aktif. Field "Jadwal QA" bersifat opsional untuk menautkan test case ke jadwal tertentu.', 'notes' => 'Tautan jadwal opsional — test case tetap bisa masuk cakupan jadwal lewat cakupan submenu.'],
                ['title' => 'Impor Test Case dari Excel', 'description' => 'Unduh template Excel, isi data, lalu unggah untuk impor massal. Sistem dapat membuat modul/menu baru secara otomatis bila belum ada.'],
                ['title' => 'Eksekusi Test Case', 'description' => 'Eksekusi dapat dilakukan dari panel "Mulai Eksekusi QA" pada jadwal (pilih submenu → test case) atau langsung dari menu ini. Isi hasil PASS/FAILED/PENDING/NOT TESTED beserta catatan dan bukti (gambar/PDF/video).', 'notes' => 'Bila dieksekusi lewat jadwal, sistem memvalidasi bahwa test case dan submenu termasuk cakupan jadwal yang aktif.'],
            ]),
        ];
    }

    private function reportWaGuide(): array
    {
        return [
            'title' => 'Panduan Report WA — Laporan Harian Format WhatsApp',
            'menu_name' => 'Report WA',
            'menu_path' => '/reports/wa',
            'category' => 'Pelaporan',
            'menu_purpose' => 'Membuat laporan harian pengujian dalam format ringkas yang mudah dibagikan melalui WhatsApp.',
            'menu_function' => 'Menghasilkan teks laporan harian berisi ringkasan eksekusi, bug, dan progres QA untuk periode terpilih.',
            'tips' => 'Salin teks laporan lalu tempel langsung ke WhatsApp untuk update cepat ke tim.',
            'troubleshooting' => 'Jika laporan kosong, periksa filter tanggal dan pastikan ada eksekusi/bug pada periode tersebut.',
            'steps' => $this->steps([
                ['title' => 'Buka Menu Report WA', 'description' => 'Klik menu Report WA di sidebar laporan.'],
                ['title' => 'Pilih Periode Laporan', 'description' => 'Atur tanggal/rentang yang ingin dilaporkan lalu muat datanya.'],
                ['title' => 'Salin & Bagikan', 'description' => 'Salin teks laporan yang dihasilkan dan tempel ke WhatsApp atau media lain.'],
            ]),
        ];
    }

    private function reportMingguanGuide(): array
    {
        return [
            'title' => 'Panduan Report Mingguan — Catatan Pekerjaan Pribadi',
            'menu_name' => 'Report Mingguan',
            'menu_path' => '/reports/weekly',
            'category' => 'Pelaporan',
            'menu_purpose' => 'Mencatat dan memantau pekerjaan pribadi per hari selama satu minggu.',
            'menu_function' => 'Menampilkan catatan pekerjaan mingguan yang dapat diedit dan dihapus oleh pemiliknya.',
            'tips' => 'Isi catatan setiap hari agar rekap mingguan akurat saat akhir minggu.',
            'troubleshooting' => 'Jika catatan tidak tersimpan, periksa koneksi dan coba lagi.',
            'steps' => $this->steps([
                ['title' => 'Buka Menu Report Mingguan', 'description' => 'Klik menu Report Mingguan di sidebar laporan.'],
                ['title' => 'Isi Catatan Per Hari', 'description' => 'Tambahkan atau edit catatan pekerjaan untuk tiap hari dalam minggu berjalan.'],
                ['title' => 'Tinjau & Hapus Bila Perlu', 'description' => 'Catatan dapat dihapus bila salah input; data tersimpan per pengguna.'],
            ]),
        ];
    }

    private function analisisPekerjaanGuide(): array
    {
        return [
            'title' => 'Panduan Analisis Pekerjaan (Admin)',
            'menu_name' => 'Analisis Pekerjaan',
            'menu_path' => '/admin/work-analysis',
            'category' => 'Administrasi',
            'menu_purpose' => 'Memberikan analisis aktivitas dan catatan pekerjaan pengguna untuk keperluan monitoring admin.',
            'menu_function' => 'Menampilkan rekap dan analisis log pekerjaan pengguna, khusus akses admin.',
            'tips' => 'Gunakan filter periode untuk melihat tren aktivitas tim per minggu/bulan.',
            'troubleshooting' => 'Menu ini hanya tampil untuk role admin.',
            'steps' => $this->steps([
                ['title' => 'Buka Menu Analisis Pekerjaan', 'description' => 'Klik menu Analisis Pekerjaan pada navigasi admin.'],
                ['title' => 'Atur Filter Periode & Pengguna', 'description' => 'Pilih rentang tanggal atau pengguna yang ingin dianalisis.'],
                ['title' => 'Tinjau Hasil Analisis', 'description' => 'Pelajari rekap aktivitas dan catatan pekerjaan yang ditampilkan.'],
            ]),
        ];
    }

    private function pengingatNotifikasiGuide(): array
    {
        return [
            'title' => 'Panduan Pengingat & Notifikasi Otomatis (Admin)',
            'menu_name' => 'Pengingat & Notifikasi',
            'menu_path' => '/admin/reminders',
            'category' => 'Administrasi',
            'menu_purpose' => 'Mengatur pengiriman notifikasi dan reminder otomatis kepada tester terkait jadwal QA.',
            'menu_function' => 'Mengaktifkan/mematikan reminder harian, mingguan, bulanan, notifikasi mulai/akhir jadwal, dan melihat riwayat pengiriman otomatis. Notifikasi mulai/akhir jadwal dikirim ke seluruh tester yang ditugaskan (multi-tester).',
            'tips' => 'Pastikan cron server menjalankan `php artisan schedule:run` tiap menit agar reminder otomatis terkirim. Sistem memiliki catch-up harian sehingga reminder tidak hilang bila cron terlambat.',
            'troubleshooting' => 'Jika notifikasi tidak masuk, cek status cron di server dan lihat tabel riwayat pengiriman otomatis untuk memastikan event terkirim.',
            'steps' => $this->steps([
                ['title' => 'Buka Menu Pengingat & Notifikasi', 'description' => 'Klik menu Pengingat & Notifikasi pada navigasi admin.'],
                ['title' => 'Atur Jadwal Reminder', 'description' => 'Aktifkan reminder harian/mingguan/bulanan dan atur jam pengirimannya.'],
                ['title' => 'Cek Riwayat Pengiriman Otomatis', 'description' => 'Pada bagian riwayat dispatch, verifikasi event notifikasi jadwal dan reminder sudah terkirim beserta waktu dan pesannya.'],
            ]),
        ];
    }

    private function pengaturanQaGuide(): array
    {
        return [
            'title' => 'Panduan Pengaturan QA, Motivasi & Mode Lock (Admin)',
            'menu_name' => 'Pengaturan QA & Motivasi',
            'menu_path' => '/admin/qa-settings',
            'category' => 'Administrasi',
            'menu_purpose' => 'Mengatur mode penguncian jadwal QA dan konten motivasi harian yang tampil setelah login.',
            'menu_function' => 'Memilih mode lock (grace_then_lock, lock_for_duration, follow_schedule), mengatur daftar kutipan motivasi harian dengan format "kutipan | Penulis", dan mengatur gambar motivasi.',
            'tips' => 'Motivasi otomatis bertema pada hari libur Indonesia (nasional & keagamaan) — tidak perlu diatur manual. Format kutipan: "teks motivasi | Nama Penulis".',
            'troubleshooting' => 'Jika popup motivasi tidak muncul, pastikan motivasi harian aktif dan user belum melihatnya pada sesi login tersebut.',
            'steps' => $this->steps([
                ['title' => 'Buka Menu Pengaturan QA & Motivasi', 'description' => 'Klik menu Pengaturan QA & Motivasi pada navigasi admin.'],
                ['title' => 'Atur Mode Lock Jadwal', 'description' => 'Pilih mode penguncian jadwal dan toleransi menitnya sesuai kebijakan tim.'],
                ['title' => 'Kelola Kutipan Motivasi Harian', 'description' => 'Tambah/hapus kutipan dengan format "kutipan | Penulis". Sistem merotasi satu kutipan per hari dan menampilkan nama penulis pada popup motivasi.'],
            ]),
        ];
    }
}
