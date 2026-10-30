<template>
  <div class="space-y-6">
    <!-- Modern Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-cyan-950 to-blue-900 p-6 text-white shadow-lg border border-slate-800">
      <OfficeSkylineBg />
      <div class="absolute -right-12 -top-12 w-56 h-56 rounded-full bg-cyan-500/20 blur-2xl pointer-events-none"></div>
      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-full bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-[10px] font-extrabold uppercase tracking-wider mb-2">
            <span>🔄 Fix Verification Cycle</span>
          </div>
          <h2 class="text-lg font-black tracking-tight text-white">Siklus Verifikasi Pengujian Ulang (Retesting)</h2>
          <p class="text-xs text-slate-300 mt-0.5">Uji kembali bug berstatus 'Ready for Retest' untuk memastikan perbaikan dari developer tuntas sebelum tiket ditutup (Closed).</p>
        </div>
        <div v-if="hasFeatureAccess('create_retest')" class="shrink-0">
          <button
            @click="openNewRetestModal"
            class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-xs font-bold text-white shadow-md shadow-cyan-600/30 transition-all flex items-center space-x-1.5 cursor-pointer"
          >
            <span>+ Input Hasil Retesting</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Retest Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[11px] font-bold text-slate-400 uppercase">Total Riwayat Retest</div>
          <div class="text-2xl font-black text-slate-900 mt-0.5">{{ retests.length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg">🔄</div>
      </div>
      <div class="bg-white rounded-2xl p-4 border border-cyan-200/80 shadow-xs flex items-center justify-between bg-gradient-to-b from-cyan-50/30 to-white">
        <div>
          <div class="text-[11px] font-bold text-cyan-700 uppercase">Antrean Siap Retest</div>
          <div class="text-2xl font-black text-cyan-700 mt-0.5">{{ readyBugs.length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center text-lg">🎯</div>
      </div>
      <div class="bg-white rounded-2xl p-4 border border-emerald-200/80 shadow-xs flex items-center justify-between bg-gradient-to-b from-emerald-50/30 to-white">
        <div>
          <div class="text-[11px] font-bold text-emerald-700 uppercase">Retest Passed</div>
          <div class="text-2xl font-black text-emerald-700 mt-0.5">{{ retests.filter(r => r.status === 'Passed').length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg">✅</div>
      </div>
      <div class="bg-white rounded-2xl p-4 border border-red-200/80 shadow-xs flex items-center justify-between bg-gradient-to-b from-red-50/30 to-white">
        <div>
          <div class="text-[11px] font-bold text-red-700 uppercase">Failed / Reopened</div>
          <div class="text-2xl font-black text-red-700 mt-0.5">{{ retests.filter(r => r.status === 'Failed' || r.status === 'Reopened').length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-red-100 text-red-700 flex items-center justify-center text-lg">❌</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Status Retesting</label>
          <select
            v-model="filters.status"
            @change="fetchRetests"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Status</option>
            <option value="Passed">Passed (Lulus Verifikasi)</option>
            <option value="Failed">Failed (Masih Bermasalah)</option>
            <option value="Reopened">Reopened (Dibuka Kembali)</option>
            <option value="Pending Retest">Pending Retest</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Aplikasi</label>
          <select
            v-model="filters.application_id"
            @change="fetchRetests"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Aplikasi</option>
            <option v-for="app in apps" :key="app.id" :value="app.id">{{ app.name }}</option>
          </select>
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

    <!-- Table -->
    <LoadingSpinner v-if="loading && retests.length === 0" text="Memuat riwayat retesting..." />
    <div v-else class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div v-if="retests.length === 0" class="p-8">
        <EmptyState title="Belum Ada Riwayat Retesting" description="Tidak ada data retesting yang cocok dengan filter saat ini." />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
              <th class="py-3 px-4">Bug Terkait</th>
              <th class="py-3 px-4">Aplikasi & Versi</th>
              <th class="py-3 px-4">Tester</th>
              <th class="py-3 px-4">Tanggal Retest</th>
              <th class="py-3 px-4">Status Hasil</th>
              <th class="py-3 px-4">Hasil Aktual & Catatan</th>
              <th class="py-3 px-4 text-right">Evidence</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <tr v-for="rt in retests" :key="rt.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-900">{{ rt.bug?.title }}</div>
                <div class="text-[10px] font-mono text-red-600 font-bold">{{ rt.bug?.bug_code }}</div>
              </td>
              <td class="py-3.5 px-4">
                <div class="font-medium text-slate-800">{{ rt.bug?.application?.name }}</div>
                <div class="text-[10px] text-slate-500">Versi: {{ rt.tested_version || '-' }}</div>
              </td>
              <td class="py-3.5 px-4 text-slate-800 font-medium">
                👤 {{ rt.tester?.name }}
              </td>
              <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                {{ formatDateTime(rt.retest_date) }}
              </td>
              <td class="py-3.5 px-4">
                <Badge :value="rt.status" />
              </td>
              <td class="py-3.5 px-4 max-w-xs">
                <div class="text-slate-800 line-clamp-1 font-medium">{{ rt.actual_result }}</div>
                <div v-if="rt.notes" class="text-slate-500 text-[10px] line-clamp-1 mt-0.5">{{ rt.notes }}</div>
              </td>
              <td class="py-3.5 px-4 text-right">
                <span v-if="!rt.evidences || rt.evidences.length === 0" class="text-slate-400 italic text-[11px]">Tidak ada</span>
                <div v-else class="flex justify-end space-x-1">
                  <a
                    v-for="ev in rt.evidences"
                    :key="ev.id"
                    :href="`/api/files/retest/${ev.id}/download`"
                    target="_blank"
                    class="px-2 py-0.5 rounded bg-blue-50 text-blue-600 hover:bg-blue-100 font-bold text-[10px]"
                    title="Unduh Evidence"
                  >
                    📎 Bukti
                  </a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Retest Submission Modal -->
    <Modal
      :show="showRetestModal"
      title="Formulir Eksekusi Retesting"
      subtitle="Verifikasi apakah bug telah berhasil diperbaiki"
      size="lg"
      @close="showRetestModal = false"
    >
      <form @submit.prevent="submitRetest" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Tiket Bug (Ready for Retest / Open) <span class="text-red-500">*</span></label>
          <select
            v-model="retestForm.bug_id"
            required
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="" disabled>Pilih Tiket Bug</option>
            <option v-for="b in readyBugs" :key="b.id" :value="b.id">
              [{{ b.bug_code }}] {{ b.title }} (Status: {{ b.status }})
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Versi Aplikasi yang Diuji</label>
            <input
              v-model="retestForm.tested_version"
              type="text"
              placeholder="Contoh: 3.4.2-patch1"
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Hasil Retesting <span class="text-red-500">*</span></label>
            <select
              v-model="retestForm.status"
              required
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500 font-bold"
            >
              <option value="Passed">Passed (Perbaikan Berhasil -> Bug Closed)</option>
              <option value="Failed">Failed (Bug Masih Terjadi -> Bug Reopened)</option>
              <option value="Reopened">Reopened (Buka Kembali Tiket)</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Hasil Aktual Pengujian Ulang <span class="text-red-500">*</span></label>
          <textarea
            v-model="retestForm.actual_result"
            rows="3"
            required
            placeholder="Jelaskan kondisi pengujian setelah perbaikan diaplikasikan..."
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
          ></textarea>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan</label>
          <textarea
            v-model="retestForm.notes"
            rows="2"
            placeholder="Catatan untuk developer atau tim QA..."
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
          ></textarea>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Lampiran Evidence Hasil Retesting (PNG, JPG, PDF, MP4)</label>
          <input
            type="file"
            multiple
            @change="handleRetestFiles"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 bg-slate-50"
          />
        </div>

        <div class="flex justify-end space-x-2.5 pt-4 border-t border-slate-100">
          <button
            type="button"
            @click="showRetestModal = false"
            class="px-4 py-2 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer disabled:opacity-50"
          >
            {{ saving ? 'Menyimpan...' : 'Simpan Hasil Retesting' }}
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '../api';
import { useAuth } from '../auth';
import Badge from '../components/Badge.vue';
import Modal from '../components/Modal.vue';
import EmptyState from '../components/EmptyState.vue';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const { hasFeatureAccess } = useAuth();
const loading = ref(false);
const saving = ref(false);
const retests = ref([]);
const readyBugs = ref([]);
const apps = ref([]);

const filters = reactive({
  status: '',
  application_id: '',
});

const showRetestModal = ref(false);
const retestFiles = ref([]);
const retestForm = reactive({
  bug_id: '',
  tested_version: '',
  status: 'Passed',
  actual_result: '',
  notes: '',
});

const formatDateTime = (str) => {
  if (!str) return '-';
  const d = new Date(str);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const fetchLookups = async () => {
  try {
    const [appRes, bugRes] = await Promise.all([
      api.get('/cascade/apps'),
      api.get('/bugs?per_page=50'),
    ]);
    apps.value = appRes.data;
    readyBugs.value = bugRes.data.data;
  } catch (e) {}
};

const fetchRetests = async () => {
  loading.value = true;
  try {
    const res = await api.get('/retests', { params: filters });
    retests.value = res.data.data;
  } catch (e) {
    console.error('Fetch retests error:', e);
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  filters.status = '';
  filters.application_id = '';
  fetchRetests();
};

const openNewRetestModal = () => {
  retestForm.bug_id = readyBugs.value[0]?.id || '';
  retestForm.tested_version = '';
  retestForm.status = 'Passed';
  retestForm.actual_result = '';
  retestForm.notes = '';
  retestFiles.value = [];
  showRetestModal.value = true;
};

const handleRetestFiles = (e) => {
  retestFiles.value = Array.from(e.target.files);
};

const submitRetest = async () => {
  saving.value = true;
  try {
    const fd = new FormData();
    Object.keys(retestForm).forEach((k) => {
      if (retestForm[k]) fd.append(k, retestForm[k]);
    });
    retestFiles.value.forEach((file) => {
      fd.append('evidences[]', file);
    });

    await api.post('/retests', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    showRetestModal.value = false;
    fetchRetests();
    fetchLookups();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan hasil retesting.');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchLookups();
  fetchRetests();
});
</script>
