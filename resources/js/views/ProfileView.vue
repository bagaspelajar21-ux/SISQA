<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <!-- Modern Hero Profile Card -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 p-6 text-white shadow-lg border border-slate-800 flex items-center space-x-4">
      <OfficeSkylineBg />
      <div class="relative z-10 w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-2xl font-black text-white shadow-lg ring-2 ring-white/20 shrink-0">
        {{ currentUser?.name?.[0] || 'U' }}
      </div>
      <div class="relative z-10">
        <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-[10px] font-extrabold uppercase tracking-wider mb-1">
          <span>👤 Account &amp; Security Settings</span>
        </div>
        <h2 class="text-lg font-black tracking-tight text-white">{{ currentUser?.name }}</h2>
        <p class="text-xs text-slate-300">{{ currentUser?.email }} • Role: <span class="uppercase font-bold text-blue-300">{{ currentUser?.role }}</span></p>
      </div>
    </div>

    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Update Profile Form -->
        <form @submit.prevent="updateProfile" class="space-y-4 text-xs">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 pb-2 border-b border-slate-100">
            Informasi Pribadi
          </h3>

          <div v-if="profileSuccess" class="p-3 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200">
            Profil berhasil diperbarui.
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap</label>
            <input v-model="profileForm.name" required type="text" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Username</label>
            <input v-model="profileForm.username" type="text" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Alamat Email</label>
            <input v-model="profileForm.email" required type="email" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Peran / Hak Akses</label>
            <input :value="currentUser?.role?.toUpperCase()" disabled class="w-full py-2 px-3 border border-slate-200 bg-slate-50 rounded-lg text-slate-500 font-bold" />
          </div>

          <button
            type="submit"
            :disabled="profileLoading"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-xs transition-colors cursor-pointer disabled:opacity-50"
          >
            {{ profileLoading ? 'Menyimpan...' : 'Simpan Profil' }}
          </button>
        </form>

        <!-- Update Password Form -->
        <form @submit.prevent="updatePassword" class="space-y-4 text-xs">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 pb-2 border-b border-slate-100">
            Ubah Password
          </h3>

          <div v-if="passwordSuccess" class="p-3 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200">
            Password berhasil diperbarui.
          </div>

          <div v-if="passwordError" class="p-3 bg-red-50 text-red-700 rounded-xl border border-red-200">
            {{ passwordError }}
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Password Saat Ini</label>
            <input v-model="passwordForm.current_password" required type="password" placeholder="••••••••" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Password Baru (min. 8 karakter)</label>
            <input v-model="passwordForm.password" required minlength="8" type="password" placeholder="••••••••" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Konfirmasi Password Baru</label>
            <input v-model="passwordForm.password_confirmation" required minlength="8" type="password" placeholder="••••••••" class="w-full py-2 px-3 border border-slate-300 rounded-lg" />
          </div>

          <button
            type="submit"
            :disabled="passwordLoading"
            class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl shadow-xs transition-colors cursor-pointer disabled:opacity-50"
          >
            {{ passwordLoading ? 'Mengubah...' : 'Perbarui Password' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import api from '../api';
import { useAuth } from '../auth';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const { currentUser, setUser } = useAuth();

const profileLoading = ref(false);
const profileSuccess = ref(false);
const profileForm = reactive({
  name: '',
  username: '',
  email: '',
});

const passwordLoading = ref(false);
const passwordSuccess = ref(false);
const passwordError = ref('');
const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
});

onMounted(() => {
  if (currentUser.value) {
    profileForm.name = currentUser.value.name;
    profileForm.username = currentUser.value.username || '';
    profileForm.email = currentUser.value.email;
  }
});

const updateProfile = async () => {
  profileLoading.value = true;
  profileSuccess.value = false;
  try {
    const res = await api.put('/user/profile', profileForm);
    setUser(res.data.user);
    profileSuccess.value = true;
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal memperbarui profil.');
  } finally {
    profileLoading.value = false;
  }
};

const updatePassword = async () => {
  passwordLoading.value = true;
  passwordSuccess.value = false;
  passwordError.value = '';
  try {
    await api.put('/user/password', passwordForm);
    passwordSuccess.value = true;
    passwordForm.current_password = '';
    passwordForm.password = '';
    passwordForm.password_confirmation = '';
  } catch (e) {
    passwordError.value = e.response?.data?.message || 'Gagal mengubah password.';
  } finally {
    passwordLoading.value = false;
  }
};
</script>
