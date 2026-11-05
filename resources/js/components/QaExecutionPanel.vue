<template>
  <div class="space-y-4">
    <!-- Step indicator -->
    <div class="flex items-center space-x-2 text-[11px] font-bold">
      <button
        @click="goToMenus"
        :class="['px-3 py-1.5 rounded-lg border transition-colors cursor-pointer', step === 'menus' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50']"
      >
        1. Pilih Submenu
      </button>
      <span class="text-slate-300">→</span>
      <button
        :disabled="!selectedMenu"
        @click="step = 'cases'"
        :class="['px-3 py-1.5 rounded-lg border transition-colors', step === 'cases' ? 'bg-blue-600 text-white border-blue-600' : selectedMenu ? 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 cursor-pointer' : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed']"
      >
        2. Test Case & Eksekusi
      </button>
    </div>

    <LoadingSpinner v-if="loadingMenus" text="Memuat cakupan submenu..." />

    <!-- Step 1: submenu coverage -->
    <div v-else-if="step === 'menus'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      <button
        v-for="menu in menus"
        :key="menu.menu_id"
        @click="selectMenu(menu)"
        class="text-left p-4 rounded-xl border border-slate-200 bg-white hover:border-blue-400 hover:shadow-sm transition-all cursor-pointer"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="font-bold text-slate-800 text-xs leading-snug">{{ menu.name }}</div>
          <span class="shrink-0 text-[10px] font-mono font-bold text-blue-600">{{ menu.total }} TC</span>
        </div>
        <div class="mt-2 flex flex-wrap gap-1 text-[10px] font-bold">
          <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700">PASS {{ menu.pass || 0 }}</span>
          <span class="px-1.5 py-0.5 rounded bg-red-50 text-red-700">FAIL {{ menu.failed || 0 }}</span>
          <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-700">PEND {{ menu.pending || 0 }}</span>
          <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">NT {{ menu.not_tested || 0 }}</span>
          <span class="px-1.5 py-0.5 rounded bg-slate-50 text-slate-500">— {{ menu.untested || 0 }}</span>
        </div>
        <div class="mt-2">
          <div class="flex justify-between text-[10px] font-bold text-slate-500 mb-1">
            <span>Progres</span><span class="text-blue-600">{{ menu.percentage }}%</span>
          </div>
          <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
            <div class="bg-blue-600 h-1.5 rounded-full" :style="{ width: `${menu.percentage}%` }"></div>
          </div>
        </div>
      </button>
      <EmptyState
        v-if="menus.length === 0"
        title="Tidak Ada Submenu"
        description="Jadwal ini belum memiliki cakupan submenu."
      />
    </div>

    <!-- Step 2: test cases -->
    <div v-else class="space-y-3">
      <div class="flex items-center justify-between">
        <div class="text-xs font-bold text-slate-700">
          📌 {{ selectedMenu?.name }}
          <span class="text-slate-400 font-semibold">({{ testCases.length }} test case)</span>
        </div>
        <button @click="goToMenus" class="text-[11px] font-bold text-blue-600 hover:underline cursor-pointer">← Ganti Submenu</button>
      </div>

      <LoadingSpinner v-if="loadingCases" text="Memuat test case..." />

      <div v-else class="border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
              <th class="py-2.5 px-3">Kode</th>
              <th class="py-2.5 px-3">Test Case</th>
              <th class="py-2.5 px-3">Prioritas</th>
              <th class="py-2.5 px-3">Status di Jadwal Ini</th>
              <th class="py-2.5 px-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="tc in testCases" :key="tc.id" class="hover:bg-slate-50/70">
              <td class="py-2.5 px-3 font-mono font-bold text-blue-600">{{ tc.test_case_code }}</td>
              <td class="py-2.5 px-3">
                <div class="font-semibold text-slate-800">{{ tc.title }}</div>
                <div class="text-[10px] text-slate-400 line-clamp-1">{{ tc.description || tc.expected_result }}</div>
              </td>
              <td class="py-2.5 px-3"><Badge :value="tc.priority" type="priority" :dot="false" /></td>
              <td class="py-2.5 px-3">
                <template v-if="tc.schedule_execution">
                  <Badge :value="tc.schedule_execution.status" />
                  <div class="text-[10px] text-slate-400 mt-0.5">
                    {{ tc.schedule_execution.tester }} · {{ formatDateTime(tc.schedule_execution.executed_at) }}
                  </div>
                </template>
                <span v-else class="text-[11px] text-slate-400 italic">Belum diuji</span>
              </td>
              <td class="py-2.5 px-3 text-right">
                <button
                  v-if="hasFeatureAccess('execute_test')"
                  @click="openExecuteModal(tc)"
                  class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-[11px] font-bold transition-colors cursor-pointer"
                >
                  {{ tc.schedule_execution ? '↻ Ulangi' : '▶ Testing' }}
                </button>
              </td>
            </tr>
            <tr v-if="testCases.length === 0">
              <td colspan="5" class="py-6 text-center text-slate-400">Tidak ada test case pada submenu ini.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Execute modal -->
    <Modal
      :show="showExecuteModal"
      :title="`Eksekusi: ${selectedTestCase?.test_case_code || ''}`"
      :subtitle="selectedTestCase?.title"
      size="lg"
      @close="showExecuteModal = false"
    >
      <form v-if="selectedTestCase" @submit.prevent="submitExecution" class="space-y-4 text-xs">
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-1">
          <div><span class="text-slate-500">Hasil yang Diharapkan:</span></div>
          <div class="font-semibold text-slate-800">{{ selectedTestCase.expected_result }}</div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1.5">Status Hasil <span class="text-red-500">*</span></label>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <label
              v-for="opt in statusOptions"
              :key="opt.value"
              :class="['flex items-center justify-center space-x-1.5 px-3 py-2 rounded-lg border text-[11px] font-bold cursor-pointer transition-colors', execForm.status === opt.value ? opt.activeClass : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50']"
            >
              <input v-model="execForm.status" type="radio" :value="opt.value" class="hidden" />
              <span>{{ opt.label }}</span>
            </label>
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Fitur yang Diuji</label>
          <input
            v-model="execForm.feature_description"
            type="text"
            placeholder="Contoh: Form Tambah Barang, Validasi Input, Tombol Simpan"
            class="w-full py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Hasil Aktual</label>
          <textarea
            v-model="execForm.actual_result"
            rows="3"
            placeholder="Jelaskan hasil aktual pengujian..."
            class="w-full py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
          ></textarea>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Catatan Tambahan</label>
          <textarea
            v-model="execForm.notes"
            rows="2"
            placeholder="Catatan opsional..."
            class="w-full py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
          ></textarea>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Evidence (Gambar / PDF / Video)</label>
          <input
            type="file"
            multiple
            accept="image/png,image/jpeg,image/webp,application/pdf,video/mp4"
            @change="handleExecFileChange"
            class="w-full text-[11px] text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:text-[11px] file:font-bold hover:file:bg-blue-100 cursor-pointer"
          />
          <p class="mt-1 text-[10px] text-slate-400">Maks 50MB per file. Format: PNG, JPG, WebP, PDF, MP4.</p>
        </div>

        <div class="flex justify-end space-x-2.5 pt-3 border-t border-slate-100">
          <button
            type="button"
            @click="showExecuteModal = false"
            class="px-4 py-2 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="executing"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition-colors disabled:opacity-50 cursor-pointer"
          >
            {{ executing ? 'Menyimpan...' : 'Simpan Hasil Testing' }}
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api';
import { useAuth } from '../auth';
import { showAlert } from '../dialog';
import Badge from './Badge.vue';
import Modal from './Modal.vue';
import EmptyState from './EmptyState.vue';
import LoadingSpinner from './LoadingSpinner.vue';

const props = defineProps({
  scheduleId: { type: [Number, String], required: true },
});
const emit = defineEmits(['executed']);

const { hasFeatureAccess } = useAuth();

const step = ref('menus');
const loadingMenus = ref(false);
const loadingCases = ref(false);
const menus = ref([]);
const selectedMenu = ref(null);
const testCases = ref([]);

const showExecuteModal = ref(false);
const selectedTestCase = ref(null);
const executing = ref(false);
const execForm = ref({ status: 'PASS', feature_description: '', actual_result: '', notes: '', files: [] });

const statusOptions = [
  { value: 'PASS', label: 'PASS', activeClass: 'bg-emerald-600 text-white border-emerald-600' },
  { value: 'FAILED', label: 'FAILED', activeClass: 'bg-red-600 text-white border-red-600' },
  { value: 'PENDING', label: 'PENDING', activeClass: 'bg-amber-500 text-white border-amber-500' },
  { value: 'NOT TESTED', label: 'NOT TESTED', activeClass: 'bg-slate-600 text-white border-slate-600' },
];

const formatDateTime = (str) => {
  if (!str) return '-';
  const d = new Date(str);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
};

const fetchMenus = async () => {
  loadingMenus.value = true;
  try {
    const res = await api.get(`/schedules/${props.scheduleId}/menus`);
    menus.value = res.data.menus || [];
  } catch (e) {
    void showAlert(e.response?.data?.message || 'Gagal memuat cakupan submenu jadwal.', { title: 'Cakupan Gagal Dimuat' });
  } finally {
    loadingMenus.value = false;
  }
};

const selectMenu = async (menu) => {
  selectedMenu.value = menu;
  step.value = 'cases';
  loadingCases.value = true;
  try {
    const res = await api.get(`/schedules/${props.scheduleId}/test-cases`, {
      params: { menu_id: menu.menu_id },
    });
    testCases.value = res.data.test_cases || [];
  } catch (e) {
    void showAlert(e.response?.data?.message || 'Gagal memuat test case.', { title: 'Test Case Gagal Dimuat' });
    testCases.value = [];
  } finally {
    loadingCases.value = false;
  }
};

const goToMenus = () => {
  step.value = 'menus';
  selectedMenu.value = null;
  testCases.value = [];
  fetchMenus();
};

const openExecuteModal = (tc) => {
  selectedTestCase.value = tc;
  execForm.value = { status: 'PASS', feature_description: '', actual_result: '', notes: '', files: [] };
  showExecuteModal.value = true;
};

const handleExecFileChange = (e) => {
  execForm.value.files = Array.from(e.target.files);
};

const submitExecution = async () => {
  executing.value = true;
  try {
    const fd = new FormData();
    fd.append('status', execForm.value.status);
    fd.append('qa_schedule_id', props.scheduleId);
    if (execForm.value.feature_description) fd.append('feature_description', execForm.value.feature_description);
    if (execForm.value.actual_result) fd.append('actual_result', execForm.value.actual_result);
    if (execForm.value.notes) fd.append('notes', execForm.value.notes);
    execForm.value.files.forEach((file) => fd.append('evidences[]', file));

    await api.post(`/test-cases/${selectedTestCase.value.id}/execute`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    showExecuteModal.value = false;
    void showAlert('Hasil testing berhasil disimpan.', { title: 'Eksekusi Tersimpan' });
    emit('executed');
    await selectMenu(selectedMenu.value);
    await fetchMenus();
  } catch (e) {
    void showAlert(e.response?.data?.message || 'Gagal menyimpan eksekusi pengujian.', { title: 'Eksekusi Gagal Disimpan' });
  } finally {
    executing.value = false;
  }
};

onMounted(fetchMenus);
</script>
