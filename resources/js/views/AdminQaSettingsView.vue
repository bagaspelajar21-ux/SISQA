<template>
  <div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-900 p-6 text-white shadow-lg">
      <OfficeSkylineBg />
      <div class="pointer-events-none absolute -right-12 -top-12 h-56 w-56 rounded-full bg-blue-500/20 blur-2xl"></div>
      <div class="relative z-10">
        <div class="mb-2 inline-flex items-center rounded-full border border-blue-400/30 bg-blue-500/20 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-blue-300">
          Administrasi Sistem
        </div>
        <h2 class="text-lg font-black tracking-tight text-white">Pengaturan QA &amp; Motivasi Harian</h2>
        <p class="mt-0.5 max-w-2xl text-xs leading-relaxed text-slate-300">
          Atur masa akses setelah jadwal QA berakhir dan kelola kutipan yang ditampilkan saat pengguna masuk.
        </p>
      </div>
    </section>

    <form class="space-y-5" @submit.prevent="saveSettings">
      <section class="space-y-5 rounded-2xl border border-sky-200 bg-white p-5 shadow-xs">
        <div>
          <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Batas waktu QA</h3>
          <p class="mt-1 text-[11px] text-slate-500">
            Pengaturan berlaku untuk Test Management, Bug Tracking, Retesting, bukti QA, dan aktivitas jadwal terkait.
          </p>
        </div>

        <div v-if="loading" class="rounded-xl bg-slate-50 p-4 text-xs text-slate-500">
          Memuat pengaturan...
        </div>
        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <label class="text-[11px] font-bold text-slate-600">
            Kebijakan setelah jadwal berakhir
            <select
              v-model="settings.qa_lock_mode"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/15"
            >
              <option value="grace_then_lock">Masa toleransi, lalu terkunci</option>
              <option value="lock_for_duration">Terkunci sementara, lalu terbuka otomatis</option>
              <option value="follow_schedule">Kunci hingga jadwal QA berikutnya dimulai</option>
            </select>
          </label>

          <label v-if="settings.qa_lock_mode !== 'follow_schedule'" class="text-[11px] font-bold text-slate-600">
            {{ settings.qa_lock_mode === 'grace_then_lock' ? 'Masa toleransi (menit)' : 'Durasi penguncian (menit)' }}
            <input
              v-model.number="settings.qa_lock_minutes"
              type="number"
              :min="settings.qa_lock_mode === 'lock_for_duration' ? 1 : 0"
              max="1440"
              required
              class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-semibold text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/15"
            />
            <span class="mt-1 block font-normal text-slate-500">
              <template v-if="settings.qa_lock_mode === 'grace_then_lock'">
                User masih dapat bekerja selama waktu ini setelah jadwal berakhir. Nilai 0 berarti langsung terkunci.
              </template>
              <template v-else-if="settings.qa_lock_mode === 'lock_for_duration'">
                QA terkunci segera setelah jadwal berakhir, lalu terbuka otomatis setelah durasi ini.
              </template>
            </span>
          </label>
          <div v-else class="rounded-xl border border-blue-100 bg-blue-50 px-3 py-3 text-[11px] leading-relaxed text-blue-900">
            Akses terkunci setelah jadwal berakhir dan dibuka otomatis ketika jadwal QA berikutnya pada modul yang sama dimulai.
            Jika belum ada jadwal berikutnya, sistem menampilkan status menunggu jadwal Admin.
          </div>
        </div>
      </section>

      <section class="space-y-5 rounded-2xl border border-amber-200 bg-white p-5 shadow-xs sm:p-6">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
          <div class="min-w-0">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Kartu motivasi saat login</h3>
            <p class="mt-1 max-w-2xl text-xs leading-relaxed text-slate-500">
              Kutipan berganti berdasarkan tanggal WIB dan muncul setiap kali pengguna login.
            </p>
          </div>
          <label class="inline-flex w-fit items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs font-bold text-amber-900">
            <input v-model="settings.daily_motivation_enabled" type="checkbox" class="h-4 w-4 accent-amber-600" />
            Aktifkan kartu motivasi
          </label>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
          <label class="block min-w-0 text-xs font-bold text-slate-600">
            Gambar desain motivasi
            <input
              type="file"
              accept="image/png,image/jpeg,image/webp"
              :disabled="loading || imageUploading || !settings.daily_motivation_enabled"
              class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm font-normal text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-100 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-blue-800"
              @change="uploadImage"
            />
            <span class="mt-1.5 block font-normal leading-relaxed text-slate-500">PNG, JPG, atau WEBP, maksimal 5 MB.</span>
          </label>
          <button
            v-if="imagePreview"
            type="button"
            :disabled="imageUploading"
            class="justify-self-start rounded-xl border border-rose-200 px-4 py-2.5 text-xs font-bold text-rose-700 transition hover:bg-rose-50 disabled:opacity-50 lg:justify-self-end"
            @click="deleteImage"
          >
            Hapus gambar
          </button>
        </div>

        <img
          v-if="imagePreview"
          :src="imagePreview"
          alt="Pratinjau gambar motivasi harian"
          class="max-h-64 w-full rounded-2xl border border-slate-200 bg-slate-50 object-contain p-2"
        />

        <label class="block text-xs font-bold text-slate-700">
          Daftar deskripsi motivasi
          <span class="mt-1 block font-normal leading-relaxed text-slate-500">
            Tulis satu kutipan pada setiap baris. Kutipan akan ditampilkan bergantian pada kartu motivasi.
          </span>
          <textarea
            v-model="quotesText"
            rows="9"
            maxlength="50000"
            :disabled="loading || !settings.daily_motivation_enabled"
            class="mt-3 min-h-56 w-full resize-y rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm font-normal leading-7 text-slate-800 focus:border-amber-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/15 disabled:cursor-not-allowed disabled:opacity-60"
            placeholder="Tulis satu kutipan pada setiap baris"
          ></textarea>
          <span class="mt-2 block font-normal leading-relaxed text-slate-500">
            Satu kutipan per baris, maksimal 100 kutipan dan 500 karakter per kutipan.
          </span>
        </label>
      </section>

      <p v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
        {{ errorMessage }}
      </p>
      <p v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700">
        {{ successMessage }}
      </p>

      <button
        type="submit"
        :disabled="loading || saving"
        class="cursor-pointer rounded-xl bg-sky-700 px-5 py-2.5 text-xs font-extrabold text-white shadow-md transition hover:bg-sky-600 disabled:cursor-not-allowed disabled:opacity-60"
      >
        {{ saving ? 'Menyimpan...' : 'Simpan Pengaturan QA' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '../api';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const settings = reactive({
  qa_lock_mode: 'grace_then_lock',
  qa_lock_minutes: 0,
  daily_motivation_enabled: true,
  daily_motivations: [],
  daily_motivation_image_path: null,
});
const quotesText = ref('');
const imagePreview = ref('');
const loading = ref(true);
const saving = ref(false);
const imageUploading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const loadSettings = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await api.get('/admin/qa-settings');
    Object.assign(settings, response.data.settings);
    quotesText.value = (settings.daily_motivations || []).join('\n');
    imagePreview.value = settings.daily_motivation_image_path
      ? `/storage/${settings.daily_motivation_image_path.replace(/^\/+/, '')}`
      : '';
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Pengaturan QA gagal dimuat.';
  } finally {
    loading.value = false;
  }
};

const uploadImage = async (event) => {
  const image = event.target.files?.[0];
  if (!image) return;

  imageUploading.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const formData = new FormData();
    formData.append('image', image);
    const response = await api.post('/admin/qa-settings/motivation-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    imagePreview.value = `${response.data.image_url}?v=${Date.now()}`;
    settings.daily_motivation_image_path = response.data.image_url.replace(/^\/storage\//, '');
    successMessage.value = response.data.message;
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gambar motivasi gagal diunggah.';
  } finally {
    imageUploading.value = false;
    event.target.value = '';
  }
};

const deleteImage = async () => {
  imageUploading.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const response = await api.delete('/admin/qa-settings/motivation-image');
    imagePreview.value = '';
    settings.daily_motivation_image_path = null;
    successMessage.value = response.data.message;
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gambar motivasi gagal dihapus.';
  } finally {
    imageUploading.value = false;
  }
};

const saveSettings = async () => {
  const quotes = quotesText.value
    .split(/\r?\n/)
    .map((quote) => quote.trim())
    .filter(Boolean);

  if (quotes.length === 0) {
    errorMessage.value = 'Tambahkan minimal satu kutipan motivasi.';
    successMessage.value = '';
    return;
  }

  saving.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const response = await api.put('/admin/qa-settings', {
      ...settings,
      qa_lock_minutes: settings.qa_lock_mode === 'follow_schedule' ? 0 : settings.qa_lock_minutes,
      daily_motivations: quotes,
    });
    Object.assign(settings, response.data.settings);
    quotesText.value = settings.daily_motivations.join('\n');
    successMessage.value = response.data.message;
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Pengaturan QA gagal disimpan.';
  } finally {
    saving.value = false;
  }
};

onMounted(loadSettings);
</script>
