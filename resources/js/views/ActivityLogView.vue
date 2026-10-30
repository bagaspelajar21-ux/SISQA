<template>
  <div class="space-y-6">
    <!-- Modern Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-blue-950 p-6 text-white shadow-lg border border-slate-800">
      <OfficeSkylineBg />
      <div class="absolute -right-12 -top-12 w-56 h-56 rounded-full bg-blue-500/15 blur-2xl pointer-events-none"></div>
      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-[10px] font-extrabold uppercase tracking-wider mb-2">
            <span>⏱️ Security &amp; Audit Trail</span>
          </div>
          <h2 class="text-lg font-black tracking-tight text-white">Activity Logs &amp; Jejak Audit Sistem</h2>
          <p class="text-xs text-slate-300 mt-0.5">
            {{ isAdmin ? 'Rekaman riwayat seluruh aktivitas, autentikasi, dan perubahan data dalam sistem SISQA.' : 'Rekaman riwayat aktivitas akun pengujian Anda.' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Cari Log</label>
          <input
            v-model="filters.search"
            @input="fetchLogs"
            type="text"
            placeholder="Cari aksi atau deskripsi..."
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Modul</label>
          <select
            v-model="filters.module"
            @change="fetchLogs"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Modul</option>
            <option value="Auth">Auth</option>
            <option value="Master Data">Master Data</option>
            <option value="Jadwal QA">Jadwal QA</option>
            <option value="Test Management">Test Management</option>
            <option value="Bug Tracking">Bug Tracking</option>
            <option value="Retesting">Retesting</option>
            <option value="Flow Aplikasi">Flow Aplikasi</option>
            <option value="Panduan SISQA">Panduan SISQA</option>
            <option value="QA Report">QA Report</option>
            <option value="Pengguna">Pengguna</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal Mulai</label>
          <input
            type="date"
            v-model="filters.start_date"
            @change="fetchLogs"
            class="w-full text-xs py-1.5 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </div>
        <div class="flex items-end">
          <button
            @click="resetFilters"
            class="w-full py-2 px-3 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-600 transition-colors cursor-pointer"
          >
            Reset Filter
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <LoadingSpinner v-if="loading && logs.length === 0" text="Memuat riwayat aktivitas sistem..." />

    <!-- Table -->
    <div v-else class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div v-if="logs.length === 0" class="p-8">
        <EmptyState title="Tidak Ada Catatan Aktivitas" description="Belum ada aktivitas yang terekam pada filter ini." />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
              <th class="py-3 px-4">Waktu</th>
              <th class="py-3 px-4">Pengguna</th>
              <th class="py-3 px-4">Modul & Aksi</th>
              <th class="py-3 px-4">Deskripsi Aktivitas</th>
              <th class="py-3 px-4">Hasil</th>
              <th class="py-3 px-4">IP Address</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="py-3 px-4 whitespace-nowrap text-slate-500 font-mono text-[11px]">
                {{ formatDateTime(log.created_at) }}
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <span v-if="log.user" class="font-bold text-slate-800">
                  {{ log.user.name }} ({{ log.user.role }})
                </span>
                <span v-else class="text-slate-400 italic">Sistem / Tamu</span>
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="font-semibold text-blue-600">{{ log.module }}</span>
                <div class="text-[10px] font-mono text-slate-400">{{ log.action }}</div>
              </td>
              <td class="py-3 px-4 text-slate-700 leading-relaxed max-w-md">
                {{ log.description }}
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <span :class="['px-2 py-0.5 rounded text-[10px] font-bold', log.result === 'Success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700']">
                  {{ log.result }}
                </span>
              </td>
              <td class="py-3 px-4 whitespace-nowrap text-slate-400 font-mono text-[11px]">
                {{ log.ip_address || '-' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '../api';
import { useAuth } from '../auth';
import EmptyState from '../components/EmptyState.vue';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const { isAdmin } = useAuth();

const loading = ref(false);
const logs = ref([]);

const filters = reactive({
  search: '',
  module: '',
  start_date: '',
});

const formatDateTime = (str) => {
  if (!str) return '-';
  const d = new Date(str);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

const fetchLogs = async () => {
  loading.value = true;
  try {
    const res = await api.get('/activity-logs', { params: filters });
    logs.value = res.data.data;
  } catch (e) {
    console.error('Fetch activity logs error:', e);
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  filters.search = '';
  filters.module = '';
  filters.start_date = '';
  fetchLogs();
};

onMounted(() => {
  fetchLogs();
});
</script>
