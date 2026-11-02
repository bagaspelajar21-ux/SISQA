<template>
  <div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-900 p-6 text-white shadow-lg">
      <OfficeSkylineBg />
      <div class="pointer-events-none absolute -right-12 -top-12 h-56 w-56 rounded-full bg-blue-500/20 blur-2xl"></div>
      <div class="relative z-10">
        <div class="mb-2 inline-flex items-center rounded-full border border-blue-400/30 bg-blue-500/20 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-blue-300">
          Administrasi Sistem
        </div>
        <h2 class="text-lg font-black tracking-tight text-white">Pengingat &amp; Notifikasi</h2>
        <p class="mt-0.5 max-w-2xl text-xs leading-relaxed text-slate-300">
          Kirim pengingat QA atau report sekarang, atau jadwalkan untuk role/pengguna tertentu. Pengingat jadwal QA juga dibuat otomatis saat jadwal mulai dan berakhir.
        </p>
      </div>
    </section>

    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
      <div class="space-y-4">
        <div>
          <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Buat pengingat</h3>
          <p class="mt-1 text-[11px] text-slate-500">Notifikasi akan muncul di aplikasi dan di browser jika izin browser diaktifkan.</p>
        </div>

        <div class="flex flex-wrap gap-2">
          <button
            v-for="preset in presets"
            :key="preset.title"
            type="button"
            @click="applyPreset(preset)"
            class="cursor-pointer rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-[10px] font-bold text-blue-800 transition-colors hover:border-blue-300 hover:bg-blue-100"
          >
            {{ preset.label }}
          </button>
        </div>

        <form class="grid grid-cols-1 gap-4 lg:grid-cols-2" @submit.prevent="createReminder">
          <label class="text-[11px] font-bold text-slate-600">
            Judul
            <input
              v-model.trim="form.title"
              type="text"
              maxlength="160"
              required
              class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs font-normal focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
              placeholder="Contoh: Pengingat QA harian"
            />
          </label>

          <label class="text-[11px] font-bold text-slate-600">
            Waktu kirim
            <input
              v-model="form.scheduled_at"
              type="datetime-local"
              required
              class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs font-normal focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
          </label>

          <label class="text-[11px] font-bold text-slate-600 lg:col-span-2">
            Isi pengingat
            <textarea
              v-model.trim="form.message"
              rows="3"
              maxlength="5000"
              required
              class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs font-normal focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
              placeholder="Tulis pengingat QA atau report yang ingin disampaikan."
            ></textarea>
          </label>

          <label class="text-[11px] font-bold text-slate-600">
            Penerima
            <select
              v-model="form.recipient_type"
              class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs font-normal focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <option value="all">Semua pengguna aktif</option>
              <option value="role">Berdasarkan role</option>
              <option value="user">Pengguna tertentu</option>
            </select>
          </label>

          <label v-if="form.recipient_type === 'role'" class="text-[11px] font-bold text-slate-600">
            Role penerima
            <select
              v-model="form.recipient_role"
              class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs font-normal focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </label>

          <label v-if="form.recipient_type === 'user'" class="text-[11px] font-bold text-slate-600">
            Pengguna tujuan
            <select
              v-model="form.recipient_user_id"
              required
              class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs font-normal focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <option value="" disabled>Pilih pengguna aktif</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }} ({{ user.role }})
              </option>
            </select>
          </label>

          <div class="flex items-end lg:col-span-2">
            <button
              type="submit"
              :disabled="saving"
              class="cursor-pointer rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-extrabold text-white shadow-md shadow-blue-600/20 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {{ saving ? 'Menyimpan...' : 'Kirim / Jadwalkan Pengingat' }}
            </button>
          </div>
        </form>

        <p v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">{{ errorMessage }}</p>
        <p v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700">{{ successMessage }}</p>
      </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
      <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
          <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Pengingat terjadwal &amp; riwayat</h3>
          <p class="mt-1 text-[10px] text-slate-500">Pengingat yang sudah dikirim tetap tersimpan sebagai riwayat admin.</p>
        </div>
        <button type="button" @click="fetchReminders" class="cursor-pointer rounded-xl border border-slate-200 px-3 py-2 text-[10px] font-bold text-slate-600 transition-colors hover:bg-slate-50">
          Muat ulang
        </button>
      </div>

      <div v-if="loading" class="p-6 text-center text-xs text-slate-500">Memuat pengingat...</div>
      <div v-else-if="reminders.length === 0" class="p-8 text-center text-xs text-slate-500">Belum ada pengingat.</div>
      <div v-else class="divide-y divide-slate-100">
        <article v-for="reminder in reminders" :key="reminder.id" class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <h4 class="text-xs font-extrabold text-slate-900">{{ reminder.title }}</h4>
              <span
                :class="[
                  'rounded-full px-2 py-0.5 text-[9px] font-black uppercase',
                  reminder.delivered_at ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
                ]"
              >
                {{ reminder.delivered_at ? 'Terkirim' : 'Terjadwal' }}
              </span>
            </div>
            <p class="mt-1 whitespace-pre-line text-[11px] leading-relaxed text-slate-600">{{ reminder.message }}</p>
            <p class="mt-2 text-[10px] text-slate-400">
              {{ recipientLabel(reminder) }} · {{ reminder.delivered_at ? `Dikirim ${formatDateTime(reminder.delivered_at)}` : `Dikirim ${formatDateTime(reminder.scheduled_at)}` }}
              <span v-if="reminder.creator?.name"> · oleh {{ reminder.creator.name }}</span>
            </p>
          </div>
          <button
            v-if="!reminder.delivered_at"
            type="button"
            @click="cancelReminder(reminder)"
            class="shrink-0 cursor-pointer rounded-xl border border-rose-200 px-3 py-1.5 text-[10px] font-bold text-rose-700 transition-colors hover:bg-rose-50"
          >
            Batalkan
          </button>
        </article>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '../api';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const presets = [
  {
    label: 'Ingat QA hari ini',
    title: 'Pengingat QA harian',
    message: 'Jangan lupa melakukan QA hari ini sesuai jadwal dan mencatat hasil pengujian.',
  },
  {
    label: 'QA segera berakhir',
    title: 'Pengingat akhir waktu QA',
    message: 'Waktu QA harian segera berakhir. Mohon selesaikan pengujian dan catat hasilnya.',
  },
  {
    label: 'Kirim report',
    title: 'Pengingat pengiriman report',
    message: 'Jangan lupa mengirim report QA setelah pekerjaan hari ini selesai.',
  },
];

const nowForInput = () => {
  const date = new Date(Date.now() - new Date().getTimezoneOffset() * 60000);
  return date.toISOString().slice(0, 16);
};

const form = reactive({
  title: '',
  message: '',
  recipient_type: 'all',
  recipient_role: 'user',
  recipient_user_id: '',
  scheduled_at: nowForInput(),
});

const users = ref([]);
const reminders = ref([]);
const loading = ref(false);
const saving = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const applyPreset = (preset) => {
  form.title = preset.title;
  form.message = preset.message;
};

const fetchReminders = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    const response = await api.get('/admin/reminders');
    reminders.value = response.data.reminders;
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Daftar pengingat gagal dimuat.';
  } finally {
    loading.value = false;
  }
};

const fetchRecipients = async () => {
  try {
    const response = await api.get('/admin/reminders/recipients');
    users.value = response.data.users;
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Daftar pengguna aktif gagal dimuat.';
  }
};

const createReminder = async () => {
  saving.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  try {
    const response = await api.post('/admin/reminders', {
      ...form,
      recipient_user_id: form.recipient_type === 'user' ? Number(form.recipient_user_id) : null,
      scheduled_at: new Date(form.scheduled_at).toISOString(),
    });
    successMessage.value = response.data.message;
    form.title = '';
    form.message = '';
    form.recipient_type = 'all';
    form.recipient_user_id = '';
    form.scheduled_at = nowForInput();
    await fetchReminders();
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Pengingat gagal dibuat.';
  } finally {
    saving.value = false;
  }
};

const cancelReminder = async (reminder) => {
  if (!window.confirm(`Batalkan pengingat "${reminder.title}"?`)) return;
  errorMessage.value = '';
  try {
    await api.delete(`/admin/reminders/${reminder.id}`);
    await fetchReminders();
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Pengingat gagal dibatalkan.';
  }
};

const recipientLabel = (reminder) => {
  if (reminder.recipient_type === 'role') return `Role ${reminder.recipient_role}`;
  if (reminder.recipient_type === 'user') return `Pengguna ${reminder.recipient_user?.name || reminder.recipient_user_id}`;
  return 'Semua pengguna aktif';
};

const formatDateTime = (value) => {
  if (!value) return '-';
  return new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
};

onMounted(() => {
  fetchRecipients();
  fetchReminders();
});
</script>
