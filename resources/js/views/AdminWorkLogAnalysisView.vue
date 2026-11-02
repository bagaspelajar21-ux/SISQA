<template>
  <div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-900 p-6 text-white shadow-lg">
      <OfficeSkylineBg />
      <div class="relative z-10 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <div class="mb-2 inline-flex items-center rounded-full border border-blue-400/30 bg-blue-500/20 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-blue-300">
            Analisis Aktivitas Kerja
          </div>
          <h2 class="text-xl font-black tracking-tight sm:text-2xl">Analisis Pekerjaan</h2>
          <p class="mt-1 max-w-2xl text-xs text-slate-300">
            Tinjau catatan pekerjaan seluruh pengguna berdasarkan periode, aktivitas harian, dan kontribusi tiap pengguna.
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            @click="analyzeWithAi"
            :disabled="!analysis || loading || downloading !== '' || aiLoading"
            class="rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-extrabold text-white shadow-lg transition-colors hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {{ aiLoading ? 'Menganalisis...' : 'Analisis dengan AI' }}
          </button>
          <button
            type="button"
            @click="downloadReport('pdf')"
            :disabled="loading || downloading !== '' || aiLoading"
            class="rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-extrabold text-white shadow-lg transition-colors hover:bg-rose-500 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {{ downloading === 'pdf' ? 'Menyiapkan PDF...' : 'Unduh PDF' }}
          </button>
          <button
            type="button"
            @click="downloadReport('excel')"
            :disabled="loading || downloading !== '' || aiLoading"
            class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-extrabold text-white shadow-lg transition-colors hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {{ downloading === 'excel' ? 'Menyiapkan Excel...' : 'Unduh Excel' }}
          </button>
        </div>
      </div>
    </section>

    <p v-if="errorMessage" role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ errorMessage }}
    </p>

    <section v-if="analysis" class="rounded-2xl border border-violet-200 bg-violet-50/80 px-4 py-3 text-xs leading-relaxed text-violet-900">
      Saat tombol Analisis dengan AI ditekan, uraian pekerjaan, tanggal, dan ringkasan aktivitas pada filter terpilih dikirim ke OpenAI.
      Nama dan ID pengguna tidak dikirim; maksimal 100 catatan per analisis.
    </section>

    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
      <form class="flex flex-wrap items-end gap-3" @submit.prevent="fetchAnalysis">
        <label class="text-[11px] font-bold text-slate-600">
          Dari tanggal
          <input
            v-model="filters.start_date"
            type="date"
            required
            class="mt-1 block rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs font-normal focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </label>
        <label class="text-[11px] font-bold text-slate-600">
          Sampai tanggal
          <input
            v-model="filters.end_date"
            type="date"
            required
            class="mt-1 block rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs font-normal focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </label>
        <label class="min-w-48 text-[11px] font-bold text-slate-600">
          Pengguna
          <select
            v-model="filters.user_id"
            class="mt-1 block w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs font-normal focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua pengguna</option>
            <option v-for="user in userOptions" :key="user.id" :value="String(user.id)">
              {{ user.name }}{{ user.deleted_at ? ' (akun dihapus)' : '' }}
            </option>
          </select>
        </label>
        <button
          type="submit"
          :disabled="loading"
          class="rounded-xl bg-blue-700 px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-50"
        >
          {{ loading ? 'Memuat...' : 'Tampilkan Analisis' }}
        </button>
      </form>
      <p class="mt-3 text-[10px] text-slate-500">Rentang analisis maksimal 366 hari.</p>
    </section>

    <LoadingSpinner v-if="loading && !analysis" text="Memuat analisis pekerjaan..." />

    <template v-else-if="analysis">
      <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article v-for="metric in metrics" :key="metric.label" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
          <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ metric.label }}</p>
          <p class="mt-2 text-3xl font-black text-slate-900">{{ metric.value }}</p>
          <p class="mt-1 text-[10px] text-slate-500">{{ metric.caption }}</p>
        </article>
      </section>

      <section class="grid grid-cols-1 gap-6 xl:grid-cols-5">
        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs xl:col-span-3">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
              <h3 class="text-sm font-extrabold text-slate-900">Aktivitas Harian</h3>
              <p class="mt-1 text-[11px] text-slate-500">Jumlah catatan pekerjaan per hari dalam periode terpilih.</p>
            </div>
            <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-[10px] font-bold text-blue-700">
              {{ formatDate(analysis.filters.start_date) }} – {{ formatDate(analysis.filters.end_date) }}
            </span>
          </div>
          <div class="mt-6 flex h-48 items-end gap-1 overflow-x-auto border-b border-slate-200 pb-1">
            <div
              v-for="day in analysis.daily_activity"
              :key="day.date"
              class="group flex h-full min-w-5 flex-1 flex-col items-center justify-end"
              :title="`${formatDate(day.date)}: ${day.entry_count} catatan`"
            >
              <span class="mb-1 text-[9px] font-bold text-slate-500">{{ day.entry_count || '' }}</span>
              <div
                class="w-full max-w-8 rounded-t-md bg-gradient-to-t from-blue-700 to-sky-400 transition-colors group-hover:from-indigo-700 group-hover:to-indigo-400"
                :style="{ height: `${barHeight(day.entry_count)}%` }"
              ></div>
              <span class="mt-1 text-[8px] text-slate-400">{{ day.date.slice(8, 10) }}</span>
            </div>
          </div>
          <div class="mt-2 flex justify-between text-[10px] text-slate-400">
            <span>{{ analysis.filters.start_date }}</span>
            <span>{{ analysis.filters.end_date }}</span>
          </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs xl:col-span-2">
          <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-extrabold text-slate-900">Kontribusi Pengguna</h3>
            <p class="mt-1 text-[11px] text-slate-500">Urutan berdasarkan jumlah catatan pekerjaan.</p>
          </div>
          <div v-if="analysis.users.length" class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-[10px] uppercase text-slate-500">
                <tr>
                  <th class="px-4 py-2.5">Pengguna</th>
                  <th class="px-3 py-2.5 text-right">Catatan</th>
                  <th class="px-4 py-2.5 text-right">Hari Aktif</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(user, index) in analysis.users" :key="user.user_id">
                  <td class="px-4 py-3">
                    <span class="mr-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-50 text-[10px] font-black text-blue-700">{{ index + 1 }}</span>
                    <span class="font-bold text-slate-800">{{ user.name }}</span>
                  </td>
                  <td class="px-3 py-3 text-right font-bold text-blue-700">{{ user.entry_count }}</td>
                  <td class="px-4 py-3 text-right text-slate-600">{{ user.active_days }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="px-5 py-8 text-center text-xs text-slate-400">Belum ada catatan pada periode ini.</p>
        </article>
      </section>

      <section v-if="aiAnalysis" class="rounded-2xl border border-violet-200 bg-white p-5 shadow-xs">
        <div class="mb-3 flex items-center gap-2">
          <h3 class="text-sm font-extrabold text-slate-900">Analisis AI</h3>
          <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-bold text-violet-700">OpenAI</span>
        </div>
        <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ aiAnalysis }}</p>
      </section>

      <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
          <div>
            <h3 class="text-sm font-extrabold text-slate-900">Rincian Pekerjaan</h3>
            <p class="mt-1 text-[11px] text-slate-500">{{ analysis.work_logs.length }} catatan pada rentang terpilih.</p>
          </div>
        </div>
        <div v-if="analysis.work_logs.length" class="overflow-x-auto">
          <table class="w-full min-w-[640px] text-left text-xs">
            <thead class="bg-slate-50 text-[10px] font-bold uppercase text-slate-500">
              <tr>
                <th class="px-5 py-3">Tanggal</th>
                <th class="px-5 py-3">Pengguna</th>
                <th class="px-5 py-3">Uraian Pekerjaan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="workLog in visibleWorkLogs" :key="workLog.id" class="align-top hover:bg-slate-50/70">
                <td class="whitespace-nowrap px-5 py-3 font-semibold text-slate-600">{{ formatDate(workLog.work_date) }}</td>
                <td class="whitespace-nowrap px-5 py-3 font-bold text-slate-800">{{ workLog.user_name }}</td>
                <td class="whitespace-pre-wrap px-5 py-3 leading-relaxed text-slate-700">{{ workLog.description }}</td>
              </tr>
            </tbody>
          </table>
          <div v-if="analysis.work_logs.length > pageSize" class="flex items-center justify-between border-t border-slate-100 px-5 py-3">
            <span class="text-[11px] text-slate-500">Halaman {{ currentPage }} dari {{ totalPages }}</span>
            <div class="flex gap-2">
              <button type="button" @click="currentPage--" :disabled="currentPage === 1" class="rounded-lg border border-slate-200 px-3 py-1.5 text-[11px] font-bold text-slate-600 disabled:opacity-40">Sebelumnya</button>
              <button type="button" @click="currentPage++" :disabled="currentPage === totalPages" class="rounded-lg border border-slate-200 px-3 py-1.5 text-[11px] font-bold text-slate-600 disabled:opacity-40">Berikutnya</button>
            </div>
          </div>
        </div>
        <p v-else class="px-5 py-8 text-center text-xs text-slate-400">Tidak ada pekerjaan untuk ditampilkan.</p>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '../api';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const today = new Date();
const todayString = toDateString(today);
const firstOfMonth = toDateString(new Date(today.getFullYear(), today.getMonth(), 1));
const filters = reactive({
  start_date: firstOfMonth,
  end_date: todayString,
  user_id: '',
});
const analysis = ref(null);
const userOptions = ref([]);
const loading = ref(false);
const downloading = ref('');
const aiLoading = ref(false);
const aiAnalysis = ref('');
const errorMessage = ref('');
const currentPage = ref(1);
const pageSize = 15;

const metrics = computed(() => {
  if (!analysis.value) return [];
  const summary = analysis.value.summary;
  return [
    { label: 'Total Catatan', value: summary.total_entries, caption: 'Catatan pekerjaan dalam periode' },
    { label: 'Hari Aktif', value: summary.active_days, caption: 'Hari dengan pekerjaan tercatat' },
    { label: 'Kontributor', value: summary.contributors, caption: 'Pengguna dengan catatan pekerjaan' },
    { label: 'Rata-rata Harian', value: summary.average_entries_per_active_day, caption: 'Catatan per hari aktif' },
  ];
});

const totalPages = computed(() => Math.max(1, Math.ceil((analysis.value?.work_logs.length || 0) / pageSize)));
const visibleWorkLogs = computed(() => {
  const logs = analysis.value?.work_logs || [];
  const start = (currentPage.value - 1) * pageSize;
  return logs.slice(start, start + pageSize);
});

function toDateString(date) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function formatDate(value) {
  if (!value) return '-';
  return new Date(`${value.slice(0, 10)}T12:00:00`).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}

function barHeight(count) {
  const maximum = Math.max(1, ...(analysis.value?.daily_activity || []).map((day) => day.entry_count));
  return count > 0 ? Math.max(8, (count / maximum) * 100) : 0;
}

function requestParams() {
  return {
    start_date: filters.start_date,
    end_date: filters.end_date,
    ...(filters.user_id ? { user_id: filters.user_id } : {}),
  };
}

async function fetchAnalysis() {
  loading.value = true;
  errorMessage.value = '';
  aiAnalysis.value = '';
  try {
    const response = await api.get('/admin/work-log-analysis/summary', { params: requestParams() });
    analysis.value = response.data;
    userOptions.value = response.data.user_options || [];
    currentPage.value = 1;
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Analisis pekerjaan gagal dimuat.';
  } finally {
    loading.value = false;
  }
}

async function analyzeWithAi() {
  aiLoading.value = true;
  errorMessage.value = '';
  aiAnalysis.value = '';
  try {
    const response = await api.post('/admin/work-log-analysis/ai', requestParams());
    aiAnalysis.value = response.data.analysis;
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Analisis AI gagal dijalankan.';
  } finally {
    aiLoading.value = false;
  }
}

async function downloadReport(format) {
  downloading.value = format;
  errorMessage.value = '';
  try {
    const response = await api.get(`/admin/work-log-analysis/export/${format}`, {
      params: requestParams(),
      responseType: 'blob',
    });
    const mimeType = format === 'pdf'
      ? 'application/pdf'
      : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    const url = window.URL.createObjectURL(new Blob([response.data], { type: mimeType }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `Analisis_Pekerjaan_${filters.start_date}_${filters.end_date}.${format === 'pdf' ? 'pdf' : 'xlsx'}`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => window.URL.revokeObjectURL(url), 1000);
  } catch (error) {
    errorMessage.value = `Report ${format.toUpperCase()} gagal diunduh.`;
  } finally {
    downloading.value = '';
  }
}

onMounted(fetchAnalysis);
</script>
