<template>
  <div class="space-y-6">
    <!-- Modern Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 p-6 text-white shadow-lg border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <OfficeSkylineBg />
      <div class="relative z-10">
        <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-[10px] font-extrabold uppercase tracking-wider mb-2">
          <span>🏢 Master Data Management</span>
        </div>
        <h2 class="text-lg font-black tracking-tight text-white">Master Data Aplikasi</h2>
        <p class="text-xs text-slate-300 mt-0.5">Kelola daftar sistem dan aplikasi yang berada di bawah pengawasan kualitas tim QA.</p>
      </div>
      <button
        @click="openCreateModal"
        class="relative z-10 px-4 py-2 rounded-xl bg-blue-500 hover:bg-blue-400 text-xs font-bold text-white shadow-md shadow-blue-500/25 transition-all cursor-pointer shrink-0"
      >
        + Tambah Aplikasi
      </button>
    </div>

    <!-- Table -->
    <LoadingSpinner v-if="loading && apps.length === 0" text="Memuat master aplikasi..." />
    <div v-else class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-600 uppercase">
              <th class="py-3 px-4">Nama & Kode</th>
              <th class="py-3 px-4">Versi & Environment</th>
              <th class="py-3 px-4">Modul</th>
              <th class="py-3 px-4">Test Case</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="a in apps" :key="a.id" class="hover:bg-slate-50">
              <td class="py-3 px-4">
                <div class="font-bold text-slate-900">{{ a.name }}</div>
                <div class="text-[10px] font-mono text-blue-600">{{ a.code }}</div>
              </td>
              <td class="py-3 px-4">
                <span class="font-medium text-slate-800">v{{ a.version }}</span>
                <span class="text-slate-400 text-[10px] block">Env: {{ a.environment || 'Production' }}</span>
              </td>
              <td class="py-3 px-4 font-bold text-slate-700">{{ a.modules_count || 0 }} Modul</td>
              <td class="py-3 px-4 font-bold text-slate-700">{{ a.test_cases_count || 0 }} TC</td>
              <td class="py-3 px-4">
                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold', a.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600']">
                  {{ a.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button @click="openEditModal(a)" class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[11px] font-semibold cursor-pointer transition-colors">Edit</button>
                  <button @click="confirmDelete(a)" class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-[11px] font-semibold cursor-pointer transition-colors">Hapus</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form -->
    <Modal :show="showModal" :title="isEdit ? 'Ubah Aplikasi' : 'Tambah Aplikasi Baru'" @close="showModal = false">
      <form @submit.prevent="saveApp" class="space-y-4 text-xs">
        <div>
          <label class="block font-bold text-slate-700 mb-1">Nama Aplikasi *</label>
          <input v-model="form.name" required type="text" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Kode Unik Aplikasi *</label>
          <input v-model="form.code" required type="text" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Versi</label>
            <input v-model="form.version" type="text" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Environment</label>
            <input v-model="form.environment" type="text" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
          </div>
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Deskripsi</label>
          <textarea v-model="form.description" rows="2" class="w-full py-2 px-3 border border-slate-300 rounded-lg"></textarea>
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
const apps = ref([]);
const showModal = ref(false);
const isEdit = ref(false);
const currentId = ref(null);

const form = reactive({
  name: '',
  code: '',
  version: '1.0.0',
  environment: 'Production',
  description: '',
  is_active: true,
});

const fetchApps = async () => {
  loading.value = true;
  try {
    const res = await api.get('/applications');
    apps.value = res.data.data;
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  isEdit.value = false;
  form.name = '';
  form.code = '';
  form.version = '1.0.0';
  form.environment = 'Production';
  form.description = '';
  showModal.value = true;
};

const openEditModal = (a) => {
  isEdit.value = true;
  currentId.value = a.id;
  Object.assign(form, a);
  showModal.value = true;
};

const saveApp = async () => {
  try {
    if (isEdit.value) {
      await api.put(`/applications/${currentId.value}`, form);
    } else {
      await api.post('/applications', form);
    }
    showModal.value = false;
    fetchApps();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan.');
  }
};

const confirmDelete = async (a) => {
  if (confirm(`Hapus aplikasi '${a.name}'?`)) {
    await api.delete(`/applications/${a.id}`);
    fetchApps();
  }
};

onMounted(() => fetchApps());
</script>
