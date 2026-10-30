<template>
  <div class="space-y-6">
    <!-- Modern Hero Banner with Export Buttons -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950 to-emerald-950 p-6 text-white shadow-lg border border-slate-800">
      <OfficeSkylineBg />
      <div class="absolute -right-12 -top-12 w-56 h-56 rounded-full bg-emerald-500/20 blur-2xl pointer-events-none"></div>
      <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-[10px] font-extrabold uppercase tracking-wider mb-2">
            <span>📑 Official Export &amp; Reporting Hub</span>
          </div>
          <h2 class="text-lg font-black tracking-tight text-white">Pusat Laporan &amp; Ekspor QA (Excel • PDF • WhatsApp)</h2>
          <p class="text-xs text-slate-300 mt-0.5">
            Unduh laporan QA resmi dalam format Excel (.xlsx), PDF (.pdf), atau susun pesan laporan instan untuk grup WhatsApp (WA).
          </p>
        </div>

        <!-- EXPORT & WA BUTTONS -->
        <div class="flex flex-wrap items-center gap-2 shrink-0">
          <button
            v-if="hasMenuAccess('/reports/wa')"
            @click="goToWaReportMenu"
            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-xs font-extrabold text-white shadow-lg shadow-green-600/30 transition-all flex items-center space-x-1.5 cursor-pointer"
          >
            <span>💬 Report WA</span>
          </button>
          <button
            v-if="hasFeatureAccess('export_report')"
            @click="exportExcel"
            :disabled="exporting"
            class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs font-bold text-white shadow-md shadow-emerald-600/30 transition-all flex items-center space-x-1.5 cursor-pointer disabled:opacity-50"
          >
            <span>📊 Export Excel (.xlsx)</span>
          </button>
          <button
            v-if="hasFeatureAccess('export_report')"
            @click="exportPdf"
            :disabled="exporting"
            class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-xs font-bold text-white shadow-md shadow-rose-600/30 transition-all flex items-center space-x-1.5 cursor-pointer disabled:opacity-50"
          >
            <span>📑 Download PDF (.pdf)</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Cascading Filters Card -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Aplikasi</label>
          <select
            v-model="filters.application_id"
            @change="handleAppChange"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Aplikasi</option>
            <option v-for="app in apps" :key="app.id" :value="app.id">{{ app.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Menu Utama / Modul</label>
          <select
            v-model="filters.module_id"
            @change="handleModuleChange"
            :disabled="!filters.application_id"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50"
          >
            <option value="">Semua Modul</option>
            <option v-for="m in moduleOptions" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Sub-Menu / Fitur</label>
          <select
            v-model="filters.menu_id"
            @change="fetchReportData"
            :disabled="!filters.module_id"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50"
          >
            <option value="">Semua Sub-Menu</option>
            <option v-for="mn in menuOptions" :key="mn.id" :value="mn.id">{{ mn.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Status Pengujian</label>
          <select
            v-model="filters.test_status"
            @change="fetchReportData"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Status</option>
            <option value="PASS">PASS</option>
            <option value="FAILED">FAILED</option>
            <option value="PENDING">PENDING</option>
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal Mulai</label>
          <input
            type="date"
            v-model="filters.start_date"
            @change="fetchReportData"
            class="w-full text-xs py-1.5 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </div>

        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal Selesai</label>
          <input
            type="date"
            v-model="filters.end_date"
            @change="fetchReportData"
            class="w-full text-xs py-1.5 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <LoadingSpinner v-if="loading && !reportData" text="Menyusun data laporan ekspor..." />

    <!-- Report Preview -->
    <div v-else-if="reportData" class="space-y-6">
      <!-- Summary Box -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
          <div class="text-[10px] font-bold text-slate-400 uppercase">Total Skenario</div>
          <div class="text-xl font-black text-slate-900 mt-1">{{ reportData.summary.total_test_cases }}</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-emerald-200 shadow-xs bg-emerald-50/20">
          <div class="text-[10px] font-bold text-emerald-700 uppercase">PASS</div>
          <div class="text-xl font-black text-emerald-700 mt-1">{{ reportData.summary.pass }}</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-red-200 shadow-xs bg-red-50/20">
          <div class="text-[10px] font-bold text-red-700 uppercase">FAILED</div>
          <div class="text-xl font-black text-red-700 mt-1">{{ reportData.summary.failed }}</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-amber-200 shadow-xs bg-amber-50/20">
          <div class="text-[10px] font-bold text-amber-700 uppercase">PENDING</div>
          <div class="text-xl font-black text-amber-700 mt-1">{{ reportData.summary.pending }}</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-blue-200 shadow-xs bg-blue-50/30">
          <div class="text-[10px] font-bold text-blue-700 uppercase">Success Rate</div>
          <div class="text-xl font-black text-blue-700 mt-1">{{ reportData.summary.success_rate }}%</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
          <div class="text-[10px] font-bold text-slate-400 uppercase">Total Bug Aktif</div>
          <div class="text-xl font-black text-red-600 mt-1">{{ reportData.summary.active_bugs }}</div>
        </div>
      </div>

      <!-- Preview Table: Test Executions -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Preview Hasil Pengujian ({{ reportData.executions?.length || 0 }} Baris)</h3>
          <span class="text-[11px] text-slate-400">Dicetak: {{ reportData.generated_at }}</span>
        </div>
        <div class="overflow-x-auto max-h-96">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50 text-[11px] font-bold text-slate-600 uppercase border-b border-slate-200">
                <th class="py-2.5 px-3">Kode TC</th>
                <th class="py-2.5 px-3">Aplikasi / Modul</th>
                <th class="py-2.5 px-3">Skenario</th>
                <th class="py-2.5 px-3">Status</th>
                <th class="py-2.5 px-3">Tester</th>
                <th class="py-2.5 px-3">Hasil Aktual</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="ex in reportData.executions" :key="ex.id" class="hover:bg-slate-50">
                <td class="py-2.5 px-3 font-mono font-bold text-blue-600">{{ ex.test_case?.test_case_code }}</td>
                <td class="py-2.5 px-3 text-slate-700">
                  <div class="font-medium">{{ ex.test_case?.application?.name }}</div>
                  <div class="text-[10px] text-slate-400">{{ ex.test_case?.module?.name || '-' }}</div>
                </td>
                <td class="py-2.5 px-3 font-medium text-slate-800">{{ ex.test_case?.title }}</td>
                <td class="py-2.5 px-3">
                  <Badge :value="ex.status" />
                </td>
                <td class="py-2.5 px-3 text-slate-600">{{ ex.tester?.name }}</td>
                <td class="py-2.5 px-3 text-slate-500 truncate max-w-xs">{{ ex.actual_result || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Preview Table: Bugs -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100">
          <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Preview Daftar Bug Terkait ({{ reportData.bugs?.length || 0 }} Tiket)</h3>
        </div>
        <div class="overflow-x-auto max-h-96">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50 text-[11px] font-bold text-slate-600 uppercase border-b border-slate-200">
                <th class="py-2.5 px-3">Kode Bug</th>
                <th class="py-2.5 px-3">Aplikasi</th>
                <th class="py-2.5 px-3">Judul Bug</th>
                <th class="py-2.5 px-3">Severity</th>
                <th class="py-2.5 px-3">Status</th>
                <th class="py-2.5 px-3">Pelapor</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="b in reportData.bugs" :key="b.id" class="hover:bg-slate-50">
                <td class="py-2.5 px-3 font-mono font-bold text-red-600">{{ b.bug_code }}</td>
                <td class="py-2.5 px-3 text-slate-700">{{ b.application?.name }}</td>
                <td class="py-2.5 px-3 font-medium text-slate-800">{{ b.title }}</td>
                <td class="py-2.5 px-3"><Badge :value="b.severity" type="severity" /></td>
                <td class="py-2.5 px-3"><Badge :value="b.status" /></td>
                <td class="py-2.5 px-3 text-slate-600">{{ b.reporter?.name }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Pratinjau WhatsApp Lengkap -->
    <Modal
      :show="showWaModal"
      title="💬 Generator Report QA Versi WhatsApp (WA)"
      subtitle="Salin format laporan siap kirim ke grup WhatsApp Tim QA / Developer / Manajemen"
      size="lg"
      @close="showWaModal = false"
    >
      <div class="space-y-4 text-xs">
        <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
          <span class="font-bold text-slate-700">Pilih Gaya Template WA:</span>
          <div class="flex items-center gap-1.5">
            <button
              type="button"
              @click="setWaTemplate('lengkap')"
              :class="['px-3 py-1.5 rounded-lg font-bold cursor-pointer transition-colors', waTemplate === 'lengkap' ? 'bg-green-600 text-white' : 'bg-white border border-slate-200 text-slate-700']"
            >
              📋 Lengkap
            </button>
            <button
              type="button"
              @click="setWaTemplate('ringkas')"
              :class="['px-3 py-1.5 rounded-lg font-bold cursor-pointer transition-colors', waTemplate === 'ringkas' ? 'bg-green-600 text-white' : 'bg-white border border-slate-200 text-slate-700']"
            >
              ⚡ Ringkas Eksekutif
            </button>
            <button
              type="button"
              @click="setWaTemplate('bug')"
              :class="['px-3 py-1.5 rounded-lg font-bold cursor-pointer transition-colors', waTemplate === 'bug' ? 'bg-green-600 text-white' : 'bg-white border border-slate-200 text-slate-700']"
            >
              🐞 Fokus Bug &amp; Retest
            </button>
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Pratinjau Pesan WhatsApp (Dapat Diedit):</label>
          <textarea
            v-model="waReportText"
            rows="14"
            class="w-full p-3.5 rounded-xl bg-slate-900 text-emerald-300 font-mono text-xs leading-relaxed border border-slate-700 focus:outline-none focus:ring-2 focus:ring-green-500"
          ></textarea>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
          <button
            type="button"
            @click="showWaModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
          >
            Tutup
          </button>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="copyWaText"
              class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold cursor-pointer"
            >
              {{ copied ? '✅ Teks Berhasil Disalin!' : '📋 Copy Teks WA' }}
            </button>
            <button
              type="button"
              @click="sendToWhatsApp"
              class="px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold cursor-pointer"
            >
              📲 Buka &amp; Kirim via WhatsApp
            </button>
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';
import { useAuth } from '../auth';
import Badge from '../components/Badge.vue';
import Modal from '../components/Modal.vue';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const router = useRouter();
const { currentUser, hasMenuAccess, hasFeatureAccess } = useAuth();

const goToWaReportMenu = () => {
  const query = {};
  if (filters.application_id) query.application_id = filters.application_id;
  if (filters.module_id) query.module_id = filters.module_id;
  if (filters.start_date) query.start_date = filters.start_date;
  if (filters.end_date) query.end_date = filters.end_date;
  router.push({ name: 'wa-report', query });
};

const loading = ref(false);
const exporting = ref(false);
const reportData = ref(null);
const apps = ref([]);
const moduleOptions = ref([]);
const menuOptions = ref([]);

const showWaModal = ref(false);
const waTemplate = ref('lengkap');
const waReportText = ref('');
const copied = ref(false);

const filters = reactive({
  application_id: '',
  module_id: '',
  menu_id: '',
  test_status: '',
  start_date: '',
  end_date: '',
});

const fetchLookups = async () => {
  try {
    const res = await api.get('/cascade/apps');
    apps.value = res.data;
  } catch (e) {}
};

const handleAppChange = async () => {
  filters.module_id = '';
  filters.menu_id = '';
  moduleOptions.value = [];
  menuOptions.value = [];
  if (filters.application_id) {
    try {
      const res = await api.get(`/cascade/modules?application_id=${filters.application_id}`);
      moduleOptions.value = res.data;
    } catch (e) {}
  }
  fetchReportData();
};

const handleModuleChange = async () => {
  filters.menu_id = '';
  menuOptions.value = [];
  if (filters.module_id) {
    try {
      const res = await api.get(`/cascade/menus?module_id=${filters.module_id}`);
      menuOptions.value = res.data;
    } catch (e) {}
  }
  fetchReportData();
};

const fetchReportData = async () => {
  loading.value = true;
  try {
    const res = await api.get('/reports/summary', { params: filters });
    reportData.value = res.data;
    generateWaText();
  } catch (e) {
    console.error('Fetch report summary error:', e);
  } finally {
    loading.value = false;
  }
};

const setWaTemplate = (tpl) => {
  waTemplate.value = tpl;
  generateWaText();
};

const statusIcon = (st) => {
  if (st === 'PASS' || st === 'Passed' || st === 'Closed') return '✅';
  if (st === 'FAILED' || st === 'Failed' || st === 'Reopened') return '❌';
  if (st === 'PENDING') return '⏳';
  return '⚪';
};

const generateWaText = () => {
  if (!reportData.value) return;
  const d = reportData.value;
  const s = d.summary || {};
  const f = d.filters || {};
  const sender = currentUser.value?.name || 'Tim QA';
  const tanggalIndo = new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });

  const modProg = d.module_progress || [];
  const completedMods = modProg.filter((m) => m.progress === 100).length;
  const inProgMods = modProg.filter((m) => m.progress > 0 && m.progress < 100).length;

  const standardModules = [
    'Master Data',
    'Pembelian',
    'Penjualan',
    'Stock',
    'Service',
    'MRP',
    'Keuangan',
    'Laporan',
    'Perbaikan',
  ];

  const lines = [];
  lines.push(`*QA DAILY TESTING & PROGRESS REPORT*`);
  lines.push(``);
  lines.push(`📅 *Tanggal:* ${tanggalIndo}`);
  lines.push(`👤 *QA:* ${sender}`);
  lines.push(`🖥️ *Server/Environment:* ${f.application && f.application !== 'Semua Aplikasi' ? f.application : 'Server QA / Server Baru / ASIS 5173'}`);
  lines.push(``);
  lines.push(`*📌 Progress Testing*`);
  lines.push(`• *Complete:* ${s.pass || 0} TC (${completedMods} Modul)`);
  lines.push(`• *On Progress:* ${(s.pending || 0) + (s.not_tested || 0)} TC (${inProgMods} Modul)`);
  lines.push(`• *Temuan Bug:* ${s.active_bugs ?? s.total_bugs ?? 0}`);
  lines.push(``);
  lines.push(`*📋 Modul yang Diuji*`);
  standardModules.forEach((modName) => {
    const lowerName = modName.toLowerCase();
    const matched = modProg.find((dbm) => {
      const dbLower = (dbm.module_name || '').toLowerCase();
      if (dbLower.includes(lowerName) || lowerName.includes(dbLower)) return true;
      if (lowerName === 'stock' && (dbLower.includes('inventori') || dbLower.includes('gudang') || dbLower.includes('stok'))) return true;
      return false;
    });
    lines.push(`• ${modName}: ${matched ? matched.progress : 0}%`);
  });
  lines.push(``);
  lines.push(`*🐞 Temuan Bug*`);
  if (!d.bugs || d.bugs.length === 0) {
    lines.push(`• Tidak ada temuan bug baru hari ini.`);
  } else {
    d.bugs.slice(0, 10).forEach((b, i) => {
      const menuName = b.menu?.name || b.module?.name || b.application?.name || 'Menu Umum';
      const st = ['Fixed', 'Ready for Retest', 'Closed'].includes(b.status)
        ? 'Fixed'
        : b.status === 'In Progress'
        ? 'On Progress'
        : 'Belum Fix';
      lines.push(`${i + 1}. ${menuName} – ${b.title}`);
      lines.push(`   Status: ${st}`);
      if (i < Math.min(d.bugs.length, 10) - 1) lines.push(``);
    });
  }
  lines.push(``);
  lines.push(`*🔄 Retest*`);
  if (!d.retests || d.retests.length === 0) {
    lines.push(`• Belum ada antrean retest hari ini.`);
  } else {
    d.retests.slice(0, 10).forEach((rt) => {
      const menuName = rt.bug?.menu?.name || rt.bug?.module?.name || rt.bug?.title || 'Fitur';
      lines.push(`• ${menuName}: ${rt.status}${rt.actual_result ? ' (' + rt.actual_result + ')' : ''}`);
    });
  }
  lines.push(``);
  lines.push(`*📝 Catatan*`);
  lines.push(`Pengujian berjalan lancar, beberapa temuan bug sedang dikoordinasikan bersama tim developer.`);
  lines.push(``);
  lines.push(`*🎯 Rencana Testing Selanjutnya*`);
  lines.push(`Melanjutkan pengujian modul yang masih On Progress dan melakukan retest pada perbaikan bug.`);
  lines.push(``);
  const statusHariIni =
    (s.active_bugs || 0) > 0 || (s.failed || 0) > 0
      ? 'ADA TEMUAN'
      : (s.pending || 0) > 0 || (s.not_tested || 0) > 0
      ? 'ON PROGRESS'
      : 'AMAN';
  lines.push(`*Status QA Hari Ini:* ${statusHariIni}`);

  waReportText.value = lines.join('\n');
};

const openWaModal = () => {
  if (!waReportText.value) {
    generateWaText();
  }
  showWaModal.value = true;
};

const copyWaText = async () => {
  try {
    await navigator.clipboard.writeText(waReportText.value);
    copied.value = true;
    setTimeout(() => {
      copied.value = false;
    }, 2500);
  } catch (e) {
    // Fallback copy for non-HTTPS local IPs (e.g. 192.168.x.x)
    const ta = document.createElement('textarea');
    ta.value = waReportText.value;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    copied.value = true;
    setTimeout(() => {
      copied.value = false;
    }, 2500);
  }
};

const sendToWhatsApp = () => {
  const encoded = encodeURIComponent(waReportText.value);
  window.open(`https://wa.me/?text=${encoded}`, '_blank');
};

const exportExcel = async () => {
  exporting.value = true;
  try {
    const queryParams = new URLSearchParams(filters).toString();
    const response = await api.get(`/reports/export/excel?${queryParams}`, {
      responseType: 'blob',
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `SISQA_Report_${Date.now()}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (e) {
    alert('Gagal mengekspor data Excel.');
  } finally {
    exporting.value = false;
  }
};

const exportPdf = async () => {
  exporting.value = true;
  try {
    const queryParams = new URLSearchParams(filters).toString();
    const response = await api.get(`/reports/export/pdf?${queryParams}`, {
      responseType: 'blob',
    });

    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `SISQA_Report_${Date.now()}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (e) {
    alert('Gagal mengunduh dokumen PDF.');
  } finally {
    exporting.value = false;
  }
};

onMounted(() => {
  fetchLookups();
  fetchReportData();
});
</script>
