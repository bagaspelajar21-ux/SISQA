<template>
  <div class="space-y-6">
    <!-- Modern Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950 to-emerald-950 p-6 text-white shadow-lg border border-slate-800">
      <OfficeSkylineBg />
      <div class="absolute -right-12 -top-12 w-56 h-56 rounded-full bg-emerald-500/20 blur-2xl pointer-events-none"></div>
      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-[10px] font-extrabold uppercase tracking-wider mb-2">
            <span>📖 Knowledge Base &amp; SOP</span>
          </div>
          <h2 class="text-lg font-black tracking-tight text-white">Panduan Penggunaan SISQA</h2>
          <p class="text-xs text-slate-300 mt-0.5">Dokumentasi operasional setiap menu, langkah petunjuk teknis, tips pengujian, dan verifikasi pengamatan Administrator.</p>
        </div>
        <div v-if="isAdmin" class="shrink-0">
          <button
            @click="openCreateModal"
            class="px-4 py-2 rounded-xl bg-blue-500 hover:bg-blue-400 text-xs font-bold text-white shadow-md shadow-blue-500/25 transition-all cursor-pointer"
          >
            + Tambah Panduan Baru
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Guide Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[11px] font-bold text-slate-400 uppercase">Total Modul Panduan</div>
          <div class="text-2xl font-black text-slate-900 mt-0.5">{{ guides.length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">📖</div>
      </div>
      <div class="bg-white rounded-2xl p-4 border border-emerald-200/80 shadow-xs flex items-center justify-between bg-gradient-to-b from-emerald-50/30 to-white">
        <div>
          <div class="text-[11px] font-bold text-emerald-700 uppercase">Sudah Diamati Admin</div>
          <div class="text-2xl font-black text-emerald-700 mt-0.5">{{ guides.filter(g => g.observation_status === 'Sudah Diamati').length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg">✅</div>
      </div>
      <div class="bg-white rounded-2xl p-4 border border-amber-200/80 shadow-xs flex items-center justify-between bg-gradient-to-b from-amber-50/30 to-white">
        <div>
          <div class="text-[11px] font-bold text-amber-700 uppercase">Belum Diamati</div>
          <div class="text-2xl font-black text-amber-700 mt-0.5">{{ guides.filter(g => g.observation_status !== 'Sudah Diamati').length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg">⏳</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Cari Panduan</label>
          <input
            v-model="filters.search"
            @input="fetchGuides"
            type="text"
            placeholder="Cari menu, judul, atau kata kunci..."
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Status Pengamatan Admin</label>
          <select
            v-model="filters.observation_status"
            @change="fetchGuides"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Status Pengamatan</option>
            <option value="Belum Diamati">Belum Diamati</option>
            <option value="Sudah Diamati">Sudah Diamati oleh Admin</option>
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

    <!-- Loading State -->
    <LoadingSpinner v-if="loading && guides.length === 0" text="Memuat panduan sistem..." />

    <!-- Table -->
    <div v-else class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div v-if="guides.length === 0" class="p-8">
        <EmptyState title="Tidak Ada Panduan" description="Belum ada panduan penggunaan yang cocok dengan kriteria filter." />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
              <th class="py-3 px-4">Judul & Menu</th>
              <th class="py-3 px-4">Kategori & Versi</th>
              <th class="py-3 px-4">Publikasi</th>
              <th class="py-3 px-4">Status Pengamatan</th>
              <th class="py-3 px-4">Admin Pengamat Terakhir</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <tr v-for="g in guides" :key="g.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-900">{{ g.title }}</div>
                <div class="text-[10px] text-slate-500 font-medium">Menu: {{ g.menu_name }}</div>
              </td>
              <td class="py-3.5 px-4">
                <div class="font-semibold text-slate-800">{{ g.category }}</div>
                <div class="text-[10px] text-slate-400 font-mono">v{{ g.version }}</div>
              </td>
              <td class="py-3.5 px-4">
                <Badge :value="g.status" />
              </td>
              <td class="py-3.5 px-4">
                <Badge :value="g.observation_status" />
              </td>
              <td class="py-3.5 px-4 text-slate-600">
                <div v-if="g.last_observer" class="font-medium text-slate-800">
                  ✓ {{ g.last_observer.name }}
                </div>
                <div v-if="g.last_observed_at" class="text-[10px] text-slate-400">
                  {{ formatDateTime(g.last_observed_at) }} (v{{ g.last_observed_version }})
                </div>
                <span v-else class="text-slate-400 italic">Belum Diamati</span>
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- Action: Tandai Sudah Diamati (Admin Only) -->
                  <button
                    v-if="isAdmin && g.observation_status !== 'Sudah Diamati'"
                    @click="markAsObserved(g)"
                    class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 font-bold text-[11px] transition-colors cursor-pointer"
                    title="Tandai Sudah Diamati"
                  >
                    ✓ Tandai Diamati
                  </button>
                  <button
                    @click="openDetailModal(g)"
                    class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-[11px] font-semibold transition-colors cursor-pointer"
                    title="Baca Panduan"
                  >
                    📖 Baca
                  </button>
                  <button
                    v-if="isAdmin"
                    @click="openEditModal(g)"
                    class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[11px] font-semibold transition-colors cursor-pointer"
                    title="Edit Panduan"
                  >
                    Edit
                  </button>
                  <button
                    v-if="isAdmin"
                    @click="confirmDelete(g)"
                    class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-[11px] font-semibold transition-colors cursor-pointer"
                    title="Hapus Panduan"
                  >
                    Hapus
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create / Edit Guide Modal -->
    <Modal
      :show="showFormModal"
      :title="isEdit ? 'Edit Panduan Penggunaan' : 'Tambah Panduan Penggunaan Baru'"
      subtitle="Setiap pembaruan panduan akan mereset status pengamatan menjadi Belum Diamati"
      size="xl"
      @close="showFormModal = false"
    >
      <form @submit.prevent="saveGuide" class="space-y-4 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Judul Panduan <span class="text-red-500">*</span></label>
            <input v-model="form.title" required type="text" placeholder="Contoh: Cara Mengeksekusi Test Case & Lapor Bug" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Menu Terkait <span class="text-red-500">*</span></label>
            <input v-model="form.menu_name" required type="text" placeholder="Contoh: Test Case & Eksekusi" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Kategori</label>
            <input v-model="form.category" type="text" placeholder="Contoh: Operasional QA / Administrasi" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Versi</label>
              <input v-model="form.version" type="text" placeholder="1.0" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Status <span class="text-red-500">*</span></label>
              <select v-model="form.status" required class="w-full py-2 px-3 border border-slate-300 rounded-lg">
                <option value="Published">Published</option>
                <option value="Draft">Draft</option>
                <option value="Archived">Archived</option>
              </select>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Tujuan Menu</label>
            <textarea v-model="form.menu_purpose" rows="2" placeholder="Jelaskan tujuan penggunaan menu ini..." class="w-full py-2 px-3 border border-slate-300 rounded-lg"></textarea>
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Fungsi Utama</label>
            <textarea v-model="form.menu_function" rows="2" placeholder="Jelaskan fitur dan fungsi utama..." class="w-full py-2 px-3 border border-slate-300 rounded-lg"></textarea>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Tips Penggunaan</label>
            <textarea v-model="form.tips" rows="2" placeholder="Tips agar pengujian lebih efektif..." class="w-full py-2 px-3 border border-slate-300 rounded-lg"></textarea>
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Solusi Masalah Umum (Troubleshooting)</label>
            <textarea v-model="form.troubleshooting" rows="2" placeholder="Penanganan kendala yang sering ditemui..." class="w-full py-2 px-3 border border-slate-300 rounded-lg"></textarea>
          </div>
        </div>

        <!-- Steps Builder -->
        <div class="border-t border-slate-200 pt-4">
          <div class="flex items-center justify-between mb-2">
            <label class="font-bold text-slate-800 uppercase tracking-wider text-[11px]">Langkah-Langkah Penggunaan ({{ form.steps.length }} Langkah)</label>
            <button type="button" @click="addStep" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 font-bold text-xs cursor-pointer">
              + Tambah Langkah
            </button>
          </div>

          <div v-if="form.steps.length === 0" class="p-4 bg-slate-50 rounded-xl text-center text-slate-400 italic">
            Belum ada langkah panduan. Klik "+ Tambah Langkah" untuk menambahkan instruksi.
          </div>

          <div v-else class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
            <div v-for="(st, idx) in form.steps" :key="idx" class="p-3 rounded-xl border border-slate-200 bg-slate-50/70 space-y-2">
              <div class="flex items-center justify-between gap-2">
                <span class="px-2 py-0.5 rounded bg-blue-600 text-white font-bold text-[10px]">Langkah #{{ idx + 1 }}</span>
                <input v-model="st.title" required type="text" placeholder="Judul Langkah *" class="flex-1 py-1 px-2.5 border border-slate-300 rounded bg-white" />
                <button type="button" @click="removeStep(idx)" class="px-2 py-1 rounded bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 font-bold text-[10px] cursor-pointer">
                  Hapus
                </button>
              </div>
              <textarea v-model="st.description" required rows="2" placeholder="Penjelasan detail langkah..." class="w-full py-1 px-2.5 border border-slate-300 rounded bg-white"></textarea>
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
          <button type="button" @click="showFormModal = false" class="px-3.5 py-1.5 border border-slate-200 rounded-lg font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
          <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg cursor-pointer">Simpan Panduan</button>
        </div>
      </form>
    </Modal>

    <!-- Detail Reader Modal -->
    <Modal
      :show="showDetailModal"
      :title="`Panduan: ${activeGuide?.title || ''}`"
      :subtitle="`Menu: ${activeGuide?.menu_name || ''} | Versi ${activeGuide?.version || '1.0'}`"
      size="xl"
      @close="showDetailModal = false"
    >
      <div v-if="activeGuide" class="space-y-6 text-xs">
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex items-center justify-between">
          <div class="space-y-1">
            <div class="text-base font-bold text-slate-900">{{ activeGuide.title }}</div>
            <div class="text-slate-500">Kategori: {{ activeGuide.category }} | Pembuat: {{ activeGuide.creator?.name }}</div>
          </div>
          <div class="flex items-center space-x-2">
            <Badge :value="activeGuide.status" />
            <Badge :value="activeGuide.observation_status" />
          </div>
        </div>

        <!-- Purpose & Functions -->
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-slate-50/50 p-3 rounded-xl border border-slate-100">
            <span class="font-bold text-slate-700 block mb-1">Tujuan Menu:</span>
            <div class="text-slate-600 leading-relaxed">{{ activeGuide.menu_purpose || '-' }}</div>
          </div>
          <div class="bg-slate-50/50 p-3 rounded-xl border border-slate-100">
            <span class="font-bold text-slate-700 block mb-1">Fungsi Utama:</span>
            <div class="text-slate-600 leading-relaxed">{{ activeGuide.menu_function || '-' }}</div>
          </div>
        </div>

        <!-- Steps -->
        <div>
          <h4 class="font-bold text-slate-900 text-xs mb-3 uppercase tracking-wider">Langkah-Langkah Penggunaan Menu:</h4>
          <div v-if="!activeGuide.steps || activeGuide.steps.length === 0" class="text-slate-400 italic">
            Belum ada langkah rinci yang disusun.
          </div>
          <div v-else class="space-y-3">
            <div
              v-for="st in activeGuide.steps"
              :key="st.id"
              class="p-3.5 rounded-xl border border-slate-200 bg-white flex items-start space-x-3"
            >
              <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs shrink-0 mt-0.5">
                {{ st.step_number }}
              </span>
              <div>
                <div class="font-bold text-slate-900 mb-1">{{ st.title }}</div>
                <div class="text-slate-600 whitespace-pre-line leading-relaxed">{{ st.description }}</div>
                <div v-if="st.notes" class="text-slate-400 text-[10px] mt-1.5 italic">Tips: {{ st.notes }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tips & Troubleshooting -->
        <div class="grid grid-cols-2 gap-4">
          <div v-if="activeGuide.tips" class="bg-amber-50/50 p-3 rounded-xl border border-amber-100 text-amber-900">
            <span class="font-bold block mb-1">💡 Tips Penggunaan:</span>
            <div class="text-amber-800 leading-relaxed">{{ activeGuide.tips }}</div>
          </div>
          <div v-if="activeGuide.troubleshooting" class="bg-red-50/50 p-3 rounded-xl border border-red-100 text-red-900">
            <span class="font-bold block mb-1">🔧 Solusi Masalah Umum:</span>
            <div class="text-red-800 leading-relaxed">{{ activeGuide.troubleshooting }}</div>
          </div>
        </div>

        <!-- Observation Audit Log -->
        <div class="pt-3 border-t border-slate-100">
          <h4 class="font-bold text-slate-900 text-xs mb-2">Riwayat Pengamatan Administrator:</h4>
          <div v-if="!activeGuide.observations || activeGuide.observations.length === 0" class="text-slate-400 italic">
            Panduan ini belum pernah ditandai sudah diamati.
          </div>
          <div v-else class="space-y-1.5">
            <div
              v-for="obs in activeGuide.observations"
              :key="obs.id"
              class="text-[11px] text-slate-600 flex items-center justify-between p-2 rounded-lg bg-slate-50"
            >
              <span>Diamati oleh: <strong>{{ obs.admin?.name }}</strong> pada versi <strong>v{{ obs.observed_version }}</strong></span>
              <span class="text-slate-400">{{ formatDateTime(obs.observed_at) }}</span>
            </div>
          </div>
        </div>
      </div>
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

const { isAdmin } = useAuth();

const loading = ref(false);
const guides = ref([]);

const filters = reactive({
  search: '',
  observation_status: '',
});

const showDetailModal = ref(false);
const activeGuide = ref(null);

const showFormModal = ref(false);
const isEdit = ref(false);
const currentId = ref(null);

const form = reactive({
  title: '',
  menu_name: '',
  category: 'Operasional QA',
  version: '1.0',
  status: 'Published',
  menu_purpose: '',
  menu_function: '',
  tips: '',
  troubleshooting: '',
  steps: [],
});

const formatDateTime = (str) => {
  if (!str) return '-';
  const d = new Date(str);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const fetchGuides = async () => {
  loading.value = true;
  try {
    const res = await api.get('/guides', { params: filters });
    guides.value = res.data.data;
  } catch (e) {
    console.error('Fetch guides error:', e);
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  filters.search = '';
  filters.observation_status = '';
  fetchGuides();
};

const addStep = () => {
  form.steps.push({
    step_number: form.steps.length + 1,
    title: '',
    description: '',
    notes: '',
  });
};

const removeStep = (idx) => {
  form.steps.splice(idx, 1);
  form.steps.forEach((s, i) => {
    s.step_number = i + 1;
  });
};

const openCreateModal = () => {
  isEdit.value = false;
  currentId.value = null;
  form.title = '';
  form.menu_name = '';
  form.category = 'Operasional QA';
  form.version = '1.0';
  form.status = 'Published';
  form.menu_purpose = '';
  form.menu_function = '';
  form.tips = '';
  form.troubleshooting = '';
  form.steps = [
    { step_number: 1, title: 'Buka Halaman Menu', description: 'Pilih menu terkait dari navigasi sidebar di sebelah kiri.', notes: '' },
    { step_number: 2, title: 'Gunakan Filter atau Tambah Data', description: 'Gunakan tombol aksi di kanan atas untuk menambah data atau filter untuk menyaring daftar.', notes: '' },
  ];
  showFormModal.value = true;
};

const openEditModal = async (g) => {
  try {
    const res = await api.get(`/guides/${g.id}`);
    const detail = res.data.guide;
    isEdit.value = true;
    currentId.value = detail.id;
    form.title = detail.title;
    form.menu_name = detail.menu_name;
    form.category = detail.category || 'Operasional QA';
    form.version = detail.version || '1.0';
    form.status = detail.status || 'Published';
    form.menu_purpose = detail.menu_purpose || '';
    form.menu_function = detail.menu_function || '';
    form.tips = detail.tips || '';
    form.troubleshooting = detail.troubleshooting || '';
    form.steps = (detail.steps || []).map((s, idx) => ({
      step_number: idx + 1,
      title: s.title || '',
      description: s.description || '',
      notes: s.notes || '',
    }));
    showFormModal.value = true;
  } catch (e) {
    alert('Gagal memuat detail panduan untuk diedit.');
  }
};

const saveGuide = async () => {
  try {
    const payload = {
      ...form,
      steps: form.steps.map((s, idx) => ({
        ...s,
        step_number: idx + 1,
      })),
    };
    if (isEdit.value) {
      await api.put(`/guides/${currentId.value}`, payload);
    } else {
      await api.post('/guides', payload);
    }
    showFormModal.value = false;
    fetchGuides();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan panduan.');
  }
};

const confirmDelete = async (g) => {
  if (!confirm(`Hapus panduan '${g.title}'?`)) return;
  try {
    await api.delete(`/guides/${g.id}`);
    fetchGuides();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menghapus panduan.');
  }
};

const markAsObserved = async (g) => {
  if (!confirm(`Tandai panduan '${g.title}' (v${g.version}) sebagai 'Sudah Diamati oleh Admin'?`)) return;
  try {
    await api.post(`/guides/${g.id}/observe`);
    fetchGuides();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menandai panduan.');
  }
};

const openDetailModal = async (g) => {
  try {
    const res = await api.get(`/guides/${g.id}`);
    activeGuide.value = res.data.guide;
    showDetailModal.value = true;
  } catch (e) {}
};

onMounted(() => {
  fetchGuides();
});
</script>
