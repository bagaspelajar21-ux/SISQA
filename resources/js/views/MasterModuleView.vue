<template>
  <div class="space-y-6">
    <!-- Modern Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 p-6 text-white shadow-lg border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <OfficeSkylineBg />
      <div class="relative z-10">
        <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-[10px] font-extrabold uppercase tracking-wider mb-2">
          <span>🗂️ Module &amp; Sub-Menu Hierarchy</span>
        </div>
        <h2 class="text-lg font-black tracking-tight text-white">Master Data Modul (Menu Utama) &amp; Sub-Menu</h2>
        <p class="text-xs text-slate-300 mt-0.5">Kelola menu utama (modul seperti Master Data, Pembelian, Penjualan) beserta daftar sub-menu di dalamnya.</p>
      </div>
      <div class="relative z-10 flex items-center gap-2 shrink-0">
        <select
          v-model="filterAppId"
          @change="fetchModules"
          class="text-xs py-2 px-3 rounded-xl border border-white/20 bg-slate-900/90 font-semibold text-white"
        >
          <option value="">Semua Aplikasi</option>
          <option v-for="a in apps" :key="a.id" :value="a.id">{{ a.name }}</option>
        </select>
        <button
          @click="openCreateModal"
          class="px-4 py-2 rounded-xl bg-blue-500 hover:bg-blue-400 text-xs font-bold text-white shadow-md shadow-blue-500/25 transition-all cursor-pointer"
        >
          + Tambah Modul
        </button>
      </div>
    </div>

    <!-- Table with Expandable Dropdown Sub-Menus -->
    <LoadingSpinner v-if="loading && modules.length === 0" text="Memuat master modul..." />
    <div v-else class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-600 uppercase">
              <th class="py-3 px-4">Nama Modul (Menu Utama) &amp; Kode</th>
              <th class="py-3 px-4">Aplikasi Induk</th>
              <th class="py-3 px-4">Sub-Menu di Dalamnya</th>
              <th class="py-3 px-4">Urutan</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <template v-for="m in modules" :key="m.id">
              <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-900 flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-50 to-indigo-100 border border-blue-200/70 text-blue-600 flex items-center justify-center shrink-0">
                      <ModernIcon name="master-modules" size="sm" />
                    </span>
                    <div>
                      <div>{{ m.name }}</div>
                      <div class="text-[10px] font-mono text-blue-600 font-normal">{{ m.code }}</div>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-4 font-medium text-slate-800">{{ m.application?.name }}</td>
                <td class="py-3 px-4">
                  <button
                    type="button"
                    @click="toggleExpandSubMenus(m)"
                    class="px-2.5 py-1 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-[11px] font-bold inline-flex items-center gap-1.5 cursor-pointer transition-colors"
                  >
                    <ModernIcon name="master-menus" size="sm" />
                    <span>{{ m.menus_count || 0 }} Sub-Menu</span>
                    <span class="text-[10px]">{{ expandedModuleId === m.id ? '▲ Tutup' : '▼ Lihat Dropdown' }}</span>
                  </button>
                </td>
                <td class="py-3 px-4 text-slate-500 font-mono">#{{ m.order_index }}</td>
                <td class="py-3 px-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      @click="openAddSubMenuModal(m)"
                      class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-[11px] font-semibold cursor-pointer transition-colors"
                    >
                      + Sub-Menu
                    </button>
                    <button
                      @click="openEditModal(m)"
                      class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[11px] font-semibold cursor-pointer transition-colors"
                    >
                      Edit
                    </button>
                    <button
                      @click="confirmDelete(m)"
                      class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-[11px] font-semibold cursor-pointer transition-colors"
                    >
                      Hapus
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Expanded Sub-Menus Row (Dropdown Inside Module e.g. Master Data -> Sub-Menus) -->
              <tr v-if="expandedModuleId === m.id" class="bg-slate-50/90">
                <td colspan="5" class="px-6 py-4 border-l-4 border-blue-600">
                  <div class="flex items-center justify-between mb-2.5">
                    <div class="text-xs font-bold text-slate-800">
                      Daftar Sub-Menu di dalam Modul <span class="text-blue-600">{{ m.name }}</span> ({{ m.application?.name }}):
                    </div>
                    <button
                      @click="openAddSubMenuModal(m)"
                      class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold cursor-pointer"
                    >
                      + Tambah Sub-Menu ke {{ m.name }}
                    </button>
                  </div>

                  <div v-if="subMenusLoading" class="text-xs text-slate-400 italic py-2">Memuat daftar sub-menu...</div>
                  <div v-else-if="subMenusList.length === 0" class="text-xs text-slate-400 italic py-2">
                    Belum ada sub-menu di dalam modul {{ m.name }}.
                  </div>
                  <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                    <div
                      v-for="sub in subMenusList"
                      :key="sub.id"
                      class="p-2.5 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center justify-between"
                    >
                      <div class="min-w-0 pr-2 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-sky-50 border border-sky-200/70 text-sky-600 flex items-center justify-center shrink-0">
                          <ModernIcon name="master-menus" size="sm" />
                        </span>
                        <div class="min-w-0">
                          <div class="font-bold text-slate-800 truncate">{{ sub.name }}</div>
                          <div class="text-[10px] font-mono text-blue-600">{{ sub.code }}</div>
                        </div>
                      </div>
                      <button
                        @click="deleteSubMenu(sub, m)"
                        class="px-2 py-0.5 rounded bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-[10px] font-semibold shrink-0 cursor-pointer"
                      >
                        Hapus
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form Modul -->
    <Modal :show="showModal" :title="isEdit ? 'Ubah Modul (Menu Utama)' : 'Tambah Modul Baru'" @close="showModal = false">
      <form @submit.prevent="saveModule" class="space-y-4 text-xs">
        <div>
          <label class="block font-bold text-slate-700 mb-1">Aplikasi Induk *</label>
          <select v-model="form.application_id" required class="w-full py-2 px-3 border border-slate-300 rounded-lg">
            <option v-for="a in apps" :key="a.id" :value="a.id">{{ a.name }}</option>
          </select>
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Nama Modul (Menu Utama) *</label>
          <input v-model="form.name" required type="text" placeholder="Contoh: Master Data / Pembelian / Penjualan" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Kode Modul *</label>
          <input v-model="form.code" required type="text" placeholder="Contoh: MD" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
        </div>
        <div class="flex justify-end space-x-2 pt-3 border-t">
          <button type="button" @click="showModal = false" class="px-3 py-1.5 border rounded-lg cursor-pointer">Batal</button>
          <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white font-bold rounded-lg cursor-pointer">Simpan</button>
        </div>
      </form>
    </Modal>

    <!-- Modal Quick Add Sub-Menu Inside Module -->
    <Modal
      :show="showSubMenuModal"
      :title="`Tambah Sub-Menu di Dalam: ${selectedModuleForSub?.name || ''}`"
      :subtitle="`Aplikasi: ${selectedModuleForSub?.application?.name || ''}`"
      @close="showSubMenuModal = false"
    >
      <form @submit.prevent="saveSubMenu" class="space-y-4 text-xs">
        <div>
          <label class="block font-bold text-slate-700 mb-1">Nama Sub-Menu / Fitur *</label>
          <input v-model="subMenuForm.name" required type="text" placeholder="Contoh: Data Barang & Jasa / Master Gudang" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Kode Sub-Menu *</label>
          <input v-model="subMenuForm.code" required type="text" placeholder="Contoh: MD-M5" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Route / Path URL (Opsional)</label>
          <input v-model="subMenuForm.url_or_route" type="text" placeholder="/asis-erp/md/5" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
        </div>
        <div class="flex justify-end space-x-2 pt-3 border-t">
          <button type="button" @click="showSubMenuModal = false" class="px-3 py-1.5 border rounded-lg cursor-pointer">Batal</button>
          <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white font-bold rounded-lg cursor-pointer">Simpan Sub-Menu</button>
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
import ModernIcon from '../components/ModernIcon.vue';

const loading = ref(false);
const modules = ref([]);
const apps = ref([]);
const filterAppId = ref('');
const showModal = ref(false);
const isEdit = ref(false);
const currentId = ref(null);

const expandedModuleId = ref(null);
const subMenusLoading = ref(false);
const subMenusList = ref([]);

const showSubMenuModal = ref(false);
const selectedModuleForSub = ref(null);
const subMenuForm = reactive({
  application_id: '',
  module_id: '',
  name: '',
  code: '',
  url_or_route: '',
});

const form = reactive({
  application_id: '',
  name: '',
  code: '',
  order_index: 0,
});

const fetchModules = async () => {
  loading.value = true;
  try {
    const [modRes, appRes] = await Promise.all([
      api.get('/modules', { params: { application_id: filterAppId.value || undefined, per_page: 50 } }),
      api.get('/cascade/apps'),
    ]);
    modules.value = modRes.data.data;
    apps.value = appRes.data;
  } finally {
    loading.value = false;
  }
};

const toggleExpandSubMenus = async (m) => {
  if (expandedModuleId.value === m.id) {
    expandedModuleId.value = null;
    return;
  }
  expandedModuleId.value = m.id;
  subMenusLoading.value = true;
  try {
    const res = await api.get(`/cascade/menus?module_id=${m.id}`);
    subMenusList.value = res.data;
  } finally {
    subMenusLoading.value = false;
  }
};

const openAddSubMenuModal = (m) => {
  selectedModuleForSub.value = m;
  subMenuForm.application_id = m.application_id;
  subMenuForm.module_id = m.id;
  subMenuForm.name = '';
  subMenuForm.code = `${m.code}-M${(m.menus_count || 0) + 1}`;
  subMenuForm.url_or_route = '';
  showSubMenuModal.value = true;
};

const saveSubMenu = async () => {
  try {
    await api.post('/menus', subMenuForm);
    showSubMenuModal.value = false;
    await fetchModules();
    if (expandedModuleId.value === selectedModuleForSub.value?.id) {
      const res = await api.get(`/cascade/menus?module_id=${selectedModuleForSub.value.id}`);
      subMenusList.value = res.data;
    } else if (selectedModuleForSub.value) {
      await toggleExpandSubMenus(selectedModuleForSub.value);
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan sub-menu.');
  }
};

const deleteSubMenu = async (sub, m) => {
  if (!confirm(`Hapus sub-menu '${sub.name}' dari modul ${m.name}?`)) return;
  try {
    await api.delete(`/menus/${sub.id}`);
    const res = await api.get(`/cascade/menus?module_id=${m.id}`);
    subMenusList.value = res.data;
    await fetchModules();
  } catch (e) {
    alert('Gagal menghapus sub-menu.');
  }
};

const openCreateModal = () => {
  isEdit.value = false;
  form.application_id = filterAppId.value || apps.value[0]?.id || '';
  form.name = '';
  form.code = '';
  showModal.value = true;
};

const openEditModal = (m) => {
  isEdit.value = true;
  currentId.value = m.id;
  Object.assign(form, m);
  showModal.value = true;
};

const saveModule = async () => {
  try {
    if (isEdit.value) {
      await api.put(`/modules/${currentId.value}`, form);
    } else {
      await api.post('/modules', form);
    }
    showModal.value = false;
    fetchModules();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan.');
  }
};

const confirmDelete = async (m) => {
  if (confirm(`Hapus modul '${m.name}'?`)) {
    await api.delete(`/modules/${m.id}`);
    fetchModules();
  }
};

onMounted(() => fetchModules());
</script>
