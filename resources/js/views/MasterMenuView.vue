<template>
  <div class="space-y-6">
    <!-- Modern Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 p-6 text-white shadow-lg border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <OfficeSkylineBg />
      <div class="relative z-10">
        <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-[10px] font-extrabold uppercase tracking-wider mb-2">
          <span>📌 Sub-Menu &amp; Feature Endpoints</span>
        </div>
        <h2 class="text-lg font-black tracking-tight text-white">Master Data Sub-Menu / Fitur</h2>
        <p class="text-xs text-slate-300 mt-0.5">Kelola daftar halaman sub-menu dan endpoint fitur di dalam setiap modul aplikasi.</p>
      </div>
      <button
        @click="openCreateModal"
        class="relative z-10 px-4 py-2 rounded-xl bg-blue-500 hover:bg-blue-400 text-xs font-bold text-white shadow-md shadow-blue-500/25 transition-all cursor-pointer shrink-0"
      >
        + Tambah Sub-Menu
      </button>
    </div>

    <!-- Table -->
    <LoadingSpinner v-if="loading && menus.length === 0" text="Memuat master menu..." />
    <div v-else class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-600 uppercase">
              <th class="py-3 px-4">Nama Sub-Menu &amp; Kode</th>
              <th class="py-3 px-4">Aplikasi &amp; Menu Utama (Modul)</th>
              <th class="py-3 px-4">Route / URL</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="mn in menus" :key="mn.id" class="hover:bg-slate-50">
              <td class="py-3 px-4">
                <div class="font-bold text-slate-900">📌 {{ mn.name }}</div>
                <div class="text-[10px] font-mono text-blue-600">{{ mn.code }}</div>
              </td>
              <td class="py-3 px-4">
                <div class="font-bold text-slate-800">{{ mn.application?.name || '-' }}</div>
                <span class="inline-block mt-1 px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200/70 text-[10px] font-semibold">
                  🗂️ {{ mn.module?.name || '-' }}
                </span>
              </td>
              <td class="py-3 px-4 font-mono text-slate-500 text-[11px]">{{ mn.url_or_route || '-' }}</td>
              <td class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button @click="openEditModal(mn)" class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[11px] font-semibold cursor-pointer transition-colors">Edit</button>
                  <button @click="confirmDelete(mn)" class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-[11px] font-semibold cursor-pointer transition-colors">Hapus</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form -->
    <Modal :show="showModal" :title="isEdit ? 'Ubah Menu' : 'Tambah Menu Baru'" @close="showModal = false">
      <form @submit.prevent="saveMenu" class="space-y-4 text-xs">
        <div>
          <label class="block font-bold text-slate-700 mb-1">Aplikasi *</label>
          <select v-model="form.application_id" @change="handleAppChange" required class="w-full py-2 px-3 border border-slate-300 rounded-lg">
            <option v-for="a in apps" :key="a.id" :value="a.id">{{ a.name }}</option>
          </select>
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Modul Terkait *</label>
          <select v-model="form.module_id" required class="w-full py-2 px-3 border border-slate-300 rounded-lg">
            <option v-for="m in moduleOptions" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Nama Menu *</label>
          <input v-model="form.name" required type="text" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Kode Menu *</label>
          <input v-model="form.code" required type="text" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Route / Path URL</label>
          <input v-model="form.url_or_route" type="text" placeholder="/path/to/feature" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
        </div>
        <div class="flex justify-end space-x-2 pt-3 border-t">
          <button type="button" @click="showModal = false" class="px-3 py-1.5 border rounded-lg">Batal</button>
          <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white font-bold rounded-lg">Simpan</button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '../api';
import Modal from '../components/Modal.vue';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const loading = ref(false);
const menus = ref([]);
const apps = ref([]);
const moduleOptions = ref([]);
const showModal = ref(false);
const isEdit = ref(false);
const currentId = ref(null);

const form = reactive({
  application_id: '',
  module_id: '',
  name: '',
  code: '',
  url_or_route: '',
});

const fetchMenus = async () => {
  loading.value = true;
  try {
    const [menuRes, appRes] = await Promise.all([
      api.get('/menus'),
      api.get('/cascade/apps'),
    ]);
    menus.value = menuRes.data.data;
    apps.value = appRes.data;
  } finally {
    loading.value = false;
  }
};

const handleAppChange = async () => {
  form.module_id = '';
  moduleOptions.value = [];
  if (form.application_id) {
    const res = await api.get(`/cascade/modules?application_id=${form.application_id}`);
    moduleOptions.value = res.data;
    form.module_id = moduleOptions.value[0]?.id || '';
  }
};

const openCreateModal = () => {
  isEdit.value = false;
  form.application_id = apps.value[0]?.id || '';
  form.name = '';
  form.code = '';
  form.url_or_route = '';
  handleAppChange();
  showModal.value = true;
};

const openEditModal = async (mn) => {
  isEdit.value = true;
  currentId.value = mn.id;
  form.application_id = mn.application_id;
  await handleAppChange();
  form.module_id = mn.module_id;
  form.name = mn.name;
  form.code = mn.code;
  form.url_or_route = mn.url_or_route || '';
  showModal.value = true;
};

const saveMenu = async () => {
  try {
    if (isEdit.value) {
      await api.put(`/menus/${currentId.value}`, form);
    } else {
      await api.post('/menus', form);
    }
    showModal.value = false;
    fetchMenus();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan.');
  }
};

const confirmDelete = async (mn) => {
  if (confirm(`Hapus menu '${mn.name}'?`)) {
    await api.delete(`/menus/${mn.id}`);
    fetchMenus();
  }
};

onMounted(() => fetchMenus());
</script>
