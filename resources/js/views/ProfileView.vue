<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <!-- Modern Hero Profile Card -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 p-6 text-white shadow-lg border border-slate-800 flex items-center space-x-4">
      <OfficeSkylineBg />
      <div class="relative z-10 w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-2xl font-black text-white shadow-lg ring-2 ring-white/20 shrink-0 overflow-hidden">
        <img v-if="currentUser?.profile_photo_url" :src="currentUser.profile_photo_url" :alt="`Foto profil ${currentUser.name}`" class="w-full h-full object-cover" />
        <span v-else>{{ currentUser?.name?.[0] || 'U' }}</span>
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

          <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="w-16 h-16 shrink-0 overflow-hidden rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-xl font-black text-white flex items-center justify-center">
              <img v-if="photoPreview || currentUser?.profile_photo_url" :src="photoPreview || currentUser.profile_photo_url" alt="Pratinjau foto profil" class="w-full h-full object-cover" />
              <span v-else>{{ profileForm.name?.[0] || 'U' }}</span>
            </div>
            <div class="min-w-0 flex-1">
              <label for="profile-photo" class="block font-bold text-slate-700 mb-1">Foto Profil</label>
              <input
                id="profile-photo"
                ref="photoInput"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                @change="handlePhotoChange"
                class="block w-full text-[11px] text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:font-bold file:text-blue-700 hover:file:bg-blue-100"
              />
              <p class="mt-1 text-[10px] text-slate-500">JPG, PNG, atau WEBP; maksimal 2 MB.</p>
            </div>
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
            <div class="relative">
              <input v-model="passwordForm.current_password" required :type="showCurrentPassword ? 'text' : 'password'" placeholder="••••••••" class="w-full py-2 pl-3 pr-11 border border-slate-300 rounded-lg" />
              <button type="button" @click="showCurrentPassword = !showCurrentPassword" :aria-label="showCurrentPassword ? 'Sembunyikan password saat ini' : 'Lihat password saat ini'" class="absolute inset-y-0 right-0 px-3 text-slate-500 hover:text-blue-700">
                <svg v-if="showCurrentPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.2A10.9 10.9 0 0112 5c5 0 9 4.5 10 7a11.8 11.8 0 01-3.1 4.3M6.2 6.2A11.8 11.8 0 002 12c1 2.5 5 7 10 7 1.2 0 2.3-.2 3.3-.6" /></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12zm10-3a3 3 0 100 6 3 3 0 000-6z" /></svg>
              </button>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Password Baru (min. 8 karakter)</label>
            <div class="relative">
              <input v-model="passwordForm.password" required minlength="8" :type="showNewPassword ? 'text' : 'password'" placeholder="••••••••" class="w-full py-2 pl-3 pr-11 border border-slate-300 rounded-lg" />
              <button type="button" @click="showNewPassword = !showNewPassword" :aria-label="showNewPassword ? 'Sembunyikan password baru' : 'Lihat password baru'" class="absolute inset-y-0 right-0 px-3 text-slate-500 hover:text-blue-700">
                <svg v-if="showNewPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.2A10.9 10.9 0 0112 5c5 0 9 4.5 10 7a11.8 11.8 0 01-3.1 4.3M6.2 6.2A11.8 11.8 0 002 12c1 2.5 5 7 10 7 1.2 0 2.3-.2 3.3-.6" /></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12zm10-3a3 3 0 100 6 3 3 0 000-6z" /></svg>
              </button>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Konfirmasi Password Baru</label>
            <div class="relative">
              <input v-model="passwordForm.password_confirmation" required minlength="8" :type="showPasswordConfirmation ? 'text' : 'password'" placeholder="••••••••" class="w-full py-2 pl-3 pr-11 border border-slate-300 rounded-lg" />
              <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" :aria-label="showPasswordConfirmation ? 'Sembunyikan konfirmasi password' : 'Lihat konfirmasi password'" class="absolute inset-y-0 right-0 px-3 text-slate-500 hover:text-blue-700">
                <svg v-if="showPasswordConfirmation" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.2A10.9 10.9 0 0112 5c5 0 9 4.5 10 7a11.8 11.8 0 01-3.1 4.3M6.2 6.2A11.8 11.8 0 002 12c1 2.5 5 7 10 7 1.2 0 2.3-.2 3.3-.6" /></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12zm10-3a3 3 0 100 6 3 3 0 000-6z" /></svg>
              </button>
            </div>
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
import { reactive, ref, onMounted, onUnmounted } from 'vue';
import api from '../api';
import { showAlert } from '../dialog';
import { useAuth } from '../auth';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const { currentUser, setUser } = useAuth();

const photoInput = ref(null);
const photoFile = ref(null);
const photoPreview = ref('');
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showPasswordConfirmation = ref(false);
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

const handlePhotoChange = (event) => {
  const file = event.target.files?.[0] || null;
  if (!file) return;

  if (photoPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(photoPreview.value);
  }
  photoFile.value = file;
  photoPreview.value = URL.createObjectURL(file);
};

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
    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('name', profileForm.name);
    formData.append('username', profileForm.username);
    formData.append('email', profileForm.email);
    if (photoFile.value) {
      formData.append('profile_photo', photoFile.value);
    }
    const res = await api.post('/user/profile', formData);
    setUser(res.data.user);
    photoFile.value = null;
    if (photoPreview.value.startsWith('blob:')) {
      URL.revokeObjectURL(photoPreview.value);
    }
    photoPreview.value = '';
    if (photoInput.value) photoInput.value.value = '';
    profileSuccess.value = true;
  } catch (e) {
    void showAlert(e.response?.data?.message || 'Gagal memperbarui profil.', { title: 'Profil Gagal Diperbarui' });
  } finally {
    profileLoading.value = false;
  }
};

onUnmounted(() => {
  if (photoPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(photoPreview.value);
  }
});

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
