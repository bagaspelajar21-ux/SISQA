<template>
  <div class="space-y-6">
    <!-- Modern Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-purple-950 to-indigo-950 p-6 text-white shadow-lg border border-slate-800 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <OfficeSkylineBg />
      <div class="relative z-10">
        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-purple-500/20 border border-purple-400/30 text-purple-300 text-[10px] font-extrabold uppercase tracking-wider mb-2">
          <span>👥 Access Control &amp; Role Permission Matrix</span>
        </div>
        <h2 class="text-xl font-black tracking-tight text-white">Manajemen Pengguna &amp; Ceklist Hak Akses Role User</h2>
        <p class="text-xs text-slate-300 mt-0.5 max-w-2xl">
          Kelola akun pengguna serta atur ceklist menu dan fitur apa saja yang ditampilkan pada tampilan pengguna ber-role <strong>User / QA Tester</strong>.
        </p>
      </div>

      <div class="relative z-10 flex flex-wrap items-center gap-2.5 shrink-0">
        <button
          type="button"
          @click="openRoleChecklistModal"
          class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-xs font-extrabold text-white shadow-lg shadow-purple-600/30 transition-all cursor-pointer flex items-center gap-1.5"
        >
          <span>☑️ Atur Ceklist Menu &amp; Fitur Role User</span>
        </button>

        <button
          type="button"
          @click="openCreateModal"
          class="px-4 py-2.5 rounded-xl bg-blue-500 hover:bg-blue-400 text-xs font-extrabold text-white shadow-md shadow-blue-500/25 transition-all cursor-pointer shrink-0"
        >
          + Tambah Pengguna
        </button>
      </div>
    </div>

    <!-- Role User Active Menu & Feature Summary Card -->
    <div class="bg-white rounded-2xl p-5 border border-purple-200/80 shadow-xs bg-gradient-to-r from-purple-50/40 via-white to-indigo-50/30">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 mb-3 border-b border-purple-100">
        <div>
          <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
            <span>🛡️ Ringkasan Hak Akses Default untuk Role User</span>
          </h3>
          <p class="text-[11px] text-slate-500 mt-0.5">
            Menu dan fitur yang dicentang di bawah ini akan otomatis keluar pada akun dengan Role <strong>User</strong>.
          </p>
        </div>
        <button
          type="button"
          @click="openRoleChecklistModal"
          class="px-3.5 py-1.5 rounded-xl bg-purple-100 hover:bg-purple-200 text-purple-800 text-xs font-bold transition-colors cursor-pointer shrink-0"
        >
          ⚙️ Ubah Ceklist Role User
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-xs">
        <div class="p-3.5 rounded-xl bg-white border border-slate-200/80">
          <div class="font-extrabold text-slate-700 mb-2 text-[11px] uppercase tracking-wider">
            📌 Menu Navigasi yang Aktif di Role User ({{ roleUserConfig.menus.length }} / {{ definitions.menus.length }}):
          </div>
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="m in definitions.menus"
              :key="m.key"
              :class="[
                'px-2.5 py-1 rounded-lg text-[11px] font-bold border transition-all',
                roleUserConfig.menus.includes(m.key)
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                  : 'bg-slate-50 text-slate-400 border-slate-200 line-through'
              ]"
            >
              {{ roleUserConfig.menus.includes(m.key) ? '✓' : '✕' }} {{ m.label }}
            </span>
          </div>
        </div>

        <div class="p-3.5 rounded-xl bg-white border border-slate-200/80">
          <div class="font-extrabold text-slate-700 mb-2 text-[11px] uppercase tracking-wider">
            ⚡ Fitur &amp; Tombol Aksi yang Aktif di Role User ({{ roleUserConfig.features.length }} / {{ definitions.features.length }}):
          </div>
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="f in definitions.features"
              :key="f.key"
              :class="[
                'px-2.5 py-1 rounded-lg text-[11px] font-bold border transition-all',
                roleUserConfig.features.includes(f.key)
                  ? 'bg-blue-50 text-blue-700 border-blue-200'
                  : 'bg-slate-50 text-slate-400 border-slate-200 line-through'
              ]"
            >
              {{ roleUserConfig.features.includes(f.key) ? '✓' : '✕' }} {{ f.label }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Users Table -->
    <LoadingSpinner v-if="loading && users.length === 0" text="Memuat data pengguna..." />
    <div v-else class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-600 uppercase">
              <th class="py-3 px-4">Nama &amp; Username</th>
              <th class="py-3 px-4">Email</th>
              <th class="py-3 px-4">Role</th>
              <th class="py-3 px-4">Hak Akses Menu &amp; Fitur</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4">Terdaftar</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50">
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-900">{{ u.name }}</div>
                <div class="text-[10px] text-slate-500 font-mono">@{{ u.username || '-' }}</div>
              </td>
              <td class="py-3.5 px-4 font-mono text-slate-700">{{ u.email }}</td>
              <td class="py-3.5 px-4">
                <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase', u.role === 'admin' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200']">
                  {{ u.role }}
                </span>
              </td>
              <td class="py-3.5 px-4">
                <span v-if="u.role === 'admin'" class="text-[11px] font-bold text-purple-700">
                  🛡️ Akses Penuh (Semua Menu &amp; Fitur)
                </span>
                <div v-else class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">
                    {{ u.permissions?.menus?.length || 0 }} Menu
                  </span>
                  <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold">
                    {{ u.permissions?.features?.length || 0 }} Fitur
                  </span>
                </div>
              </td>
              <td class="py-3.5 px-4">
                <button
                  @click="toggleActive(u)"
                  :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold transition-colors cursor-pointer', u.is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-red-50 text-red-700 hover:bg-red-100']"
                  title="Klik untuk mengubah status"
                >
                  {{ u.is_active ? '● Aktif' : '○ Nonaktif' }}
                </button>
              </td>
              <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                {{ formatDate(u.created_at) }}
              </td>
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button @click="openEditModal(u)" class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[11px] font-semibold cursor-pointer transition-colors">
                    Edit &amp; Akses
                  </button>
                  <button @click="confirmDelete(u)" class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-[11px] font-semibold cursor-pointer transition-colors">
                    Hapus
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form Tambah / Edit Pengguna (Beserta Ceklist Menu & Fitur) -->
    <Modal
      :show="showModal"
      :title="isEdit ? 'Ubah Pengguna & Hak Akses' : 'Tambah Pengguna Baru'"
      subtitle="Atur profil akun beserta ceklist menu dan fitur apabila pengguna memiliki role User"
      size="xl"
      @close="showModal = false"
    >
      <form @submit.prevent="saveUser" class="space-y-4 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap *</label>
            <input v-model="form.name" required type="text" class="w-full py-2 px-3 border border-slate-300 rounded-xl" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Username</label>
            <input v-model="form.username" type="text" class="w-full py-2 px-3 border border-slate-300 rounded-xl" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Alamat Email *</label>
            <input v-model="form.email" required type="email" class="w-full py-2 px-3 border border-slate-300 rounded-xl" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Role Pengguna *</label>
            <select v-model="form.role" required class="w-full py-2 px-3 border border-slate-300 rounded-xl font-bold">
              <option value="user">User / QA Tester (Sesuai Ceklist)</option>
              <option value="admin">Admin QA (Akses Penuh)</option>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block font-bold text-slate-700 mb-1">Password {{ isEdit ? '(Kosongkan jika tidak diubah)' : '*' }}</label>
            <input v-model="form.password" :required="!isEdit" type="password" minlength="8" placeholder="••••••••" class="w-full py-2 px-3 border border-slate-300 rounded-xl" />
          </div>
        </div>

        <!-- Checklist Menu & Fitur Khusus jika Role === 'user' -->
        <div v-if="form.role === 'user'" class="pt-3 border-t border-slate-200 space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="font-extrabold text-slate-900 uppercase tracking-wider text-[11px]">
                ☑️ Ceklist Menu &amp; Fitur untuk Pengguna Ini
              </h4>
              <p class="text-[11px] text-slate-500">Centang menu navigasi dan fitur aksi yang diizinkan tampil untuk pengguna ini.</p>
            </div>
            <button
              type="button"
              @click="resetUserFormToDefaultRole"
              class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] cursor-pointer"
            >
              🔄 Ikuti Default Role User
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Menus Checklist -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
              <div class="flex items-center justify-between pb-1.5 border-b border-slate-200">
                <span class="font-extrabold text-slate-800">📌 Daftar Menu Sidebar</span>
                <button type="button" @click="toggleAllUserFormMenus" class="text-[10px] font-bold text-blue-600 hover:underline cursor-pointer">
                  Pilih / Hapus Semua
                </button>
              </div>
              <div class="space-y-1.5 max-h-60 overflow-y-auto pr-1">
                <label
                  v-for="m in definitions.menus"
                  :key="m.key"
                  class="flex items-start gap-2.5 p-2 rounded-xl bg-white border border-slate-200/80 hover:border-blue-300 cursor-pointer"
                >
                  <input
                    type="checkbox"
                    :value="m.key"
                    v-model="form.permissions.menus"
                    class="mt-0.5 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 cursor-pointer"
                  />
                  <div>
                    <div class="font-bold text-slate-800">{{ m.label }}</div>
                    <div class="text-[10px] text-slate-500">{{ m.description }}</div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Features Checklist -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
              <div class="flex items-center justify-between pb-1.5 border-b border-slate-200">
                <span class="font-extrabold text-slate-800">⚡ Daftar Fitur &amp; Tombol Aksi</span>
                <button type="button" @click="toggleAllUserFormFeatures" class="text-[10px] font-bold text-blue-600 hover:underline cursor-pointer">
                  Pilih / Hapus Semua
                </button>
              </div>
              <div class="space-y-1.5 max-h-60 overflow-y-auto pr-1">
                <label
                  v-for="f in definitions.features"
                  :key="f.key"
                  class="flex items-start gap-2.5 p-2 rounded-xl bg-white border border-slate-200/80 hover:border-blue-300 cursor-pointer"
                >
                  <input
                    type="checkbox"
                    :value="f.key"
                    v-model="form.permissions.features"
                    class="mt-0.5 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 cursor-pointer"
                  />
                  <div>
                    <div class="font-bold text-slate-800">{{ f.label }}</div>
                    <div class="text-[10px] text-slate-500">{{ f.description }}</div>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
          <button type="button" @click="showModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer">
            Batal
          </button>
          <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold rounded-xl cursor-pointer">
            Simpan Pengguna
          </button>
        </div>
      </form>
    </Modal>

    <!-- Modal Global Role User Checklist -->
    <Modal
      :show="showRoleModal"
      title="🛡️ Konfigurasi Ceklist Menu & Fitur untuk Role User"
      subtitle="Centang menu navigasi dan fitur aksi yang diizinkan tampil pada seluruh akun ber-role User / QA Tester"
      size="xl"
      @close="showRoleModal = false"
    >
      <form @submit.prevent="saveRoleChecklist" class="space-y-4 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Menus Checklist -->
          <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
              <div>
                <div class="font-extrabold text-slate-900">📌 Menu Sidebar yang Ditampilkan</div>
                <div class="text-[10px] text-slate-500">Menu yang tidak dicentang akan disembunyikan dari sidebar Role User</div>
              </div>
              <button
                type="button"
                @click="toggleAllRoleMenus"
                class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-[10px] font-bold text-blue-600 hover:bg-blue-50 cursor-pointer shrink-0"
              >
                Pilih / Hapus Semua
              </button>
            </div>

            <div class="space-y-1.5 max-h-80 overflow-y-auto pr-1">
              <label
                v-for="m in definitions.menus"
                :key="m.key"
                class="flex items-start gap-2.5 p-2.5 rounded-xl bg-white border border-slate-200 hover:border-purple-300 cursor-pointer transition-colors"
              >
                <input
                  type="checkbox"
                  :value="m.key"
                  v-model="roleUserConfig.menus"
                  class="mt-0.5 w-4 h-4 rounded text-purple-600 focus:ring-purple-500 cursor-pointer"
                />
                <div>
                  <div class="font-bold text-slate-800">{{ m.label }}</div>
                  <div class="text-[10px] text-slate-500">{{ m.description }}</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Features Checklist -->
          <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
              <div>
                <div class="font-extrabold text-slate-900">⚡ Fitur &amp; Tombol Aksi yang Diizinkan</div>
                <div class="text-[10px] text-slate-500">Mengatur tombol tambah, eksekusi, upload Excel, dan ekspor laporan</div>
              </div>
              <button
                type="button"
                @click="toggleAllRoleFeatures"
                class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-[10px] font-bold text-blue-600 hover:bg-blue-50 cursor-pointer shrink-0"
              >
                Pilih / Hapus Semua
              </button>
            </div>

            <div class="space-y-1.5 max-h-80 overflow-y-auto pr-1">
              <label
                v-for="f in definitions.features"
                :key="f.key"
                class="flex items-start gap-2.5 p-2.5 rounded-xl bg-white border border-slate-200 hover:border-purple-300 cursor-pointer transition-colors"
              >
                <input
                  type="checkbox"
                  :value="f.key"
                  v-model="roleUserConfig.features"
                  class="mt-0.5 w-4 h-4 rounded text-purple-600 focus:ring-purple-500 cursor-pointer"
                />
                <div>
                  <div class="font-bold text-slate-800">{{ f.label }}</div>
                  <div class="text-[10px] text-slate-500">{{ f.description }}</div>
                </div>
              </label>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
          <span class="text-[11px] text-slate-500">
            💡 Perubahan ini akan langsung diterapkan ke seluruh akun dengan Role <strong>User</strong>.
          </span>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="showRoleModal = false"
              class="px-4 py-2 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="savingRole"
              class="px-5 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-extrabold cursor-pointer disabled:opacity-50"
            >
              {{ savingRole ? 'Menyimpan...' : '💾 Simpan Ceklist Role User' }}
            </button>
          </div>
        </div>
      </form>
    </Modal>

    <!-- Modern Delete Confirmation Dialog -->
    <ConfirmDialog
      :show="showDeleteConfirm"
      title="Hapus Akun Pengguna"
      :message="`Apakah Anda yakin ingin menghapus pengguna '${userToDelete?.name}' secara permanen?`"
      confirmText="Ya, Hapus Pengguna"
      cancelText="Batal"
      :isDanger="true"
      @confirm="executeDelete"
      @cancel="showDeleteConfirm = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '../api';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const loading = ref(false);
const savingRole = ref(false);
const users = ref([]);
const showModal = ref(false);
const showRoleModal = ref(false);
const isEdit = ref(false);
const currentId = ref(null);

const showDeleteConfirm = ref(false);
const userToDelete = ref(null);

const definitions = reactive({
  menus: [],
  features: [],
});

const roleUserConfig = reactive({
  menus: ['/', '/schedules', '/test-cases', '/bugs', '/retests', '/flows', '/guides', '/reports', '/reports/wa', '/activity-logs'],
  features: ['execute_test', 'create_test', 'import_test_excel', 'create_bug', 'edit_bug', 'import_bug_excel', 'create_retest', 'manage_flow', 'export_report'],
});

const form = reactive({
  name: '',
  username: '',
  email: '',
  password: '',
  role: 'user',
  is_active: true,
  permissions: {
    menus: [],
    features: [],
  },
});

const formatDate = (str) => {
  if (!str) return '-';
  const d = new Date(str);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const fetchUsersAndPermissions = async () => {
  loading.value = true;
  try {
    const [uRes, pRes] = await Promise.all([
      api.get('/users'),
      api.get('/role-permissions'),
    ]);
    users.value = uRes.data.data;
    if (pRes.data?.definitions) {
      definitions.menus = pRes.data.definitions.menus || [];
      definitions.features = pRes.data.definitions.features || [];
    }
    if (pRes.data?.role_user) {
      roleUserConfig.menus = [...(pRes.data.role_user.menus || [])];
      roleUserConfig.features = [...(pRes.data.role_user.features || [])];
    }
  } finally {
    loading.value = false;
  }
};

const openRoleChecklistModal = () => {
  showRoleModal.value = true;
};

const toggleAllRoleMenus = () => {
  if (roleUserConfig.menus.length === definitions.menus.length) {
    roleUserConfig.menus = [];
  } else {
    roleUserConfig.menus = definitions.menus.map((m) => m.key);
  }
};

const toggleAllRoleFeatures = () => {
  if (roleUserConfig.features.length === definitions.features.length) {
    roleUserConfig.features = [];
  } else {
    roleUserConfig.features = definitions.features.map((f) => f.key);
  }
};

const saveRoleChecklist = async () => {
  savingRole.value = true;
  try {
    await api.put('/role-permissions', {
      menus: roleUserConfig.menus,
      features: roleUserConfig.features,
    });
    showRoleModal.value = false;
    await fetchUsersAndPermissions();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan ceklist Role User.');
  } finally {
    savingRole.value = false;
  }
};

const resetUserFormToDefaultRole = () => {
  form.permissions.menus = [...roleUserConfig.menus];
  form.permissions.features = [...roleUserConfig.features];
};

const toggleAllUserFormMenus = () => {
  if (form.permissions.menus.length === definitions.menus.length) {
    form.permissions.menus = [];
  } else {
    form.permissions.menus = definitions.menus.map((m) => m.key);
  }
};

const toggleAllUserFormFeatures = () => {
  if (form.permissions.features.length === definitions.features.length) {
    form.permissions.features = [];
  } else {
    form.permissions.features = definitions.features.map((f) => f.key);
  }
};

const openCreateModal = () => {
  isEdit.value = false;
  form.name = '';
  form.username = '';
  form.email = '';
  form.password = '';
  form.role = 'user';
  form.is_active = true;
  form.permissions.menus = [...roleUserConfig.menus];
  form.permissions.features = [...roleUserConfig.features];
  showModal.value = true;
};

const openEditModal = (u) => {
  isEdit.value = true;
  currentId.value = u.id;
  form.name = u.name;
  form.username = u.username || '';
  form.email = u.email;
  form.password = '';
  form.role = u.role;
  form.is_active = u.is_active;
  form.permissions.menus = [...(u.permissions?.menus || roleUserConfig.menus)];
  form.permissions.features = [...(u.permissions?.features || roleUserConfig.features)];
  showModal.value = true;
};

const saveUser = async () => {
  try {
    if (isEdit.value) {
      await api.put(`/users/${currentId.value}`, form);
    } else {
      await api.post('/users', form);
    }
    showModal.value = false;
    fetchUsersAndPermissions();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan data pengguna.');
  }
};

const toggleActive = async (u) => {
  try {
    await api.patch(`/users/${u.id}/toggle-active`);
    fetchUsersAndPermissions();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal mengubah status.');
  }
};

const confirmDelete = (u) => {
  userToDelete.value = u;
  showDeleteConfirm.value = true;
};

const executeDelete = async () => {
  if (!userToDelete.value) return;
  try {
    await api.delete(`/users/${userToDelete.value.id}`);
    showDeleteConfirm.value = false;
    fetchUsersAndPermissions();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menghapus pengguna.');
  }
};

onMounted(() => fetchUsersAndPermissions());
</script>
