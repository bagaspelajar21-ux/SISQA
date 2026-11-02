<template>
  <div class="space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-blue-900 bg-gradient-to-r from-slate-950 via-blue-950 to-indigo-950 p-6 text-white shadow-lg">
      <OfficeSkylineBg />
      <div class="relative z-10 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <div class="mb-2 inline-flex items-center gap-2 rounded-full border border-blue-300/30 bg-blue-400/15 px-3 py-1 text-[11px] font-extrabold uppercase tracking-wider text-blue-200">
            <span>📆 Catatan Pekerjaan Pribadi</span>
          </div>
          <h2 class="text-xl font-black tracking-tight sm:text-2xl">Laporan Pekerjaan Mingguan</h2>
          <p class="mt-1 max-w-2xl text-xs text-blue-100/90">
            Catat pekerjaan setiap hari. Data hanya dapat dilihat dan dikelola oleh akun Anda, terpisah dari laporan QA.
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            @click="downloadPdf"
            :disabled="loading || downloadingPdf"
            class="rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-extrabold text-white shadow-lg transition-colors hover:bg-rose-500 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {{ downloadingPdf ? 'Menyiapkan PDF...' : '📄 Unduh PDF' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="errorMessage" role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ errorMessage }}
    </div>
    <div v-if="successMessage" role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ successMessage }}
    </div>

    <LoadingSpinner v-if="loading && !weekRange" text="Memuat catatan pekerjaan..." />

    <div v-else-if="weekRange" class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
      <div class="space-y-4 lg:col-span-7">
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
          <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-wrap items-end gap-3">
              <div>
                <label for="week-date" class="mb-1 block text-[11px] font-bold text-slate-600">Pilih tanggal dalam minggu</label>
                <input
                  id="week-date"
                  v-model="referenceDate"
                  type="date"
                  class="rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <button
                type="button"
                @click="fetchWorkLogs"
                :disabled="loading || !referenceDate"
                class="rounded-xl bg-blue-700 px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {{ loading ? 'Memuat...' : 'Tampilkan Minggu' }}
              </button>
            </div>
            <p class="text-xs font-semibold text-slate-500">
              Minggu {{ formatDate(weekRange.start_date) }} – {{ formatDate(weekRange.end_date) }}
            </p>
          </div>
        </section>

        <section
          v-for="day in weekDays"
          :key="day.date"
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs"
        >
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div>
              <h3 class="text-sm font-extrabold text-slate-900">{{ day.label }}</h3>
              <p class="mt-0.5 text-xs text-slate-500">{{ formatDate(day.date) }}</p>
            </div>
            <button
              v-if="editingDate !== day.date"
              type="button"
              @click="startNewEntry(day.date)"
              :disabled="loading"
              class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition-colors hover:bg-blue-100 disabled:opacity-50"
            >
              + Tambah Pekerjaan
            </button>
          </div>

          <div v-if="logsForDay(day.date).length" class="divide-y divide-slate-100">
            <article v-for="log in logsForDay(day.date)" :key="log.id" class="flex flex-col gap-3 py-3 sm:flex-row sm:items-start sm:justify-between">
              <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{{ log.description }}</p>
              <div class="flex shrink-0 gap-2">
                <button
                  type="button"
                  @click="startEditEntry(log)"
                  class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50"
                >
                  Ubah
                </button>
                <button
                  type="button"
                  @click="deleteEntry(log)"
                  :disabled="deletingId === log.id"
                  class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 transition-colors hover:bg-rose-50 disabled:opacity-50"
                >
                  {{ deletingId === log.id ? 'Menghapus...' : 'Hapus' }}
                </button>
              </div>
            </article>
          </div>
          <p v-else-if="editingDate !== day.date" class="py-4 text-xs text-slate-400">Belum ada catatan pekerjaan untuk hari ini.</p>

          <form
            v-if="editingDate === day.date"
            @submit.prevent="saveEntry"
            class="mt-3 space-y-3 rounded-xl border border-blue-100 bg-blue-50/50 p-4"
          >
            <div>
              <label :for="`entry-date-${day.date}`" class="mb-1 block text-[11px] font-bold text-slate-600">Tanggal pekerjaan</label>
              <input
                :id="`entry-date-${day.date}`"
                v-model="entryForm.work_date"
                type="date"
                :min="weekRange.start_date"
                :max="weekRange.end_date"
                required
                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
            </div>
            <div>
              <label :for="`entry-description-${day.date}`" class="mb-1 block text-[11px] font-bold text-slate-600">Pekerjaan yang dilakukan</label>
              <textarea
                :id="`entry-description-${day.date}`"
                v-model="entryForm.description"
                rows="3"
                maxlength="1000"
                required
                placeholder="Contoh: Menyusun materi presentasi dan koordinasi dengan tim"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>
            <div class="flex flex-wrap gap-2">
              <button
                type="submit"
                :disabled="saving"
                class="rounded-lg bg-blue-700 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-600 disabled:opacity-50"
              >
                {{ saving ? 'Menyimpan...' : editingId ? 'Simpan Perubahan' : 'Simpan Pekerjaan' }}
              </button>
              <button
                type="button"
                @click="cancelEdit"
                :disabled="saving"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50"
              >
                Batal
              </button>
            </div>
          </form>
        </section>
      </div>

      <aside class="lg:sticky lg:top-6 lg:col-span-5">
        <div class="overflow-hidden rounded-2xl border border-emerald-800/40 bg-[#0B141A] shadow-xl">
          <div class="flex items-center gap-3 border-b border-slate-700/60 bg-[#202C33] px-4 py-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600 text-sm font-extrabold text-white shadow-xs">
              💬
            </div>
            <div>
              <h3 class="text-xs font-extrabold text-white">Pratinjau Laporan WhatsApp</h3>
              <p class="text-[10px] font-medium text-emerald-400">Laporan Pekerjaan Mingguan</p>
            </div>
          </div>
          <div class="max-h-[min(70vh,48rem)] overflow-y-auto bg-[#0B141A] p-4">
            <pre class="min-h-32 whitespace-pre-wrap break-words rounded-2xl rounded-tl-none bg-[#005C4B] p-3.5 font-mono text-xs leading-relaxed text-[#E9EDEF] shadow-md">{{ whatsAppText }}</pre>
          </div>
          <div class="flex flex-col gap-2.5 border-t border-slate-700/60 bg-[#202C33] px-4 py-3.5 sm:flex-row">
            <button
              type="button"
              @click="copyWhatsAppText"
              :disabled="loading || workLogs.length === 0"
              class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-white px-4 py-2.5 text-xs font-extrabold text-slate-900 shadow-sm transition-colors hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 sm:w-1/2"
            >
              {{ copied ? '✅ Berhasil Disalin!' : '📋 Salin Teks WA' }}
            </button>
            <button
              type="button"
              @click="sendWhatsApp"
              :disabled="loading || workLogs.length === 0"
              class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-[#00A884] px-4 py-2.5 text-xs font-extrabold text-white transition-colors hover:bg-[#008f6f] disabled:cursor-not-allowed disabled:opacity-50 sm:w-1/2"
            >
              📲 Kirim ke WhatsApp
            </button>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '../api';
import { showConfirm } from '../dialog';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';
import { copyTextToClipboard, openWhatsAppMessage } from '../utils/whatsapp';

const referenceDate = ref(toLocalDateString(new Date()));
const weekRange = ref(null);
const workLogs = ref([]);
const editingDate = ref(null);
const editingId = ref(null);
const entryForm = reactive({ work_date: '', description: '' });
const loading = ref(false);
const saving = ref(false);
const deletingId = ref(null);
const downloadingPdf = ref(false);
const copied = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const weekDays = computed(() => {
  if (!weekRange.value) {
    return [];
  }

  const firstDay = new Date(`${weekRange.value.start_date}T12:00:00`);

  return Array.from({ length: 7 }, (_, index) => {
    const date = new Date(firstDay);
    date.setDate(firstDay.getDate() + index);

    return {
      date: toLocalDateString(date),
      label: date.toLocaleDateString('id-ID', { weekday: 'long' }),
    };
  });
});

const whatsAppText = computed(() => {
  if (!weekRange.value) {
    return '';
  }

  const startDate = new Date(`${weekRange.value.start_date}T12:00:00`);
  const endDate = new Date(`${weekRange.value.end_date}T12:00:00`);
  const sameYear = startDate.getFullYear() === endDate.getFullYear();
  const dateRange = sameYear && startDate.getMonth() === endDate.getMonth()
    ? `${startDate.getDate()} - ${endDate.getDate()} ${endDate.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}`
    : `${startDate.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', ...(sameYear ? {} : { year: 'numeric' }) })} - ${endDate.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}`;
  const lines = [`Report ${dateRange}`];

  if (workLogs.value.length === 0) {
    lines.push('', 'Belum ada catatan pekerjaan.');
  } else {
    workLogs.value.forEach((log, index) => {
      lines.push(`${index + 1}. ${log.description}`);
    });
  }

  return lines.join('\n').normalize('NFC');
});

function toLocalDateString(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

function formatDate(value) {
  if (!value) {
    return '-';
  }

  return new Date(`${value.slice(0, 10)}T12:00:00`).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}

function logsForDay(date) {
  return workLogs.value.filter((log) => log.work_date === date);
}

function clearMessages() {
  errorMessage.value = '';
  successMessage.value = '';
}

async function fetchWorkLogs() {
  loading.value = true;
  clearMessages();
  cancelEdit();

  try {
    const response = await api.get('/work-logs', {
      params: { week_of: referenceDate.value },
    });
    weekRange.value = response.data.week;
    workLogs.value = response.data.work_logs;
  } catch (error) {
    console.error('Fetch personal work logs error:', error);
    errorMessage.value = error.response?.data?.message || 'Catatan pekerjaan gagal dimuat.';
  } finally {
    loading.value = false;
  }
}

function startNewEntry(date) {
  clearMessages();
  editingId.value = null;
  editingDate.value = date;
  entryForm.work_date = date;
  entryForm.description = '';
}

function startEditEntry(log) {
  clearMessages();
  editingId.value = log.id;
  editingDate.value = log.work_date;
  entryForm.work_date = log.work_date;
  entryForm.description = log.description;
}

function cancelEdit() {
  editingId.value = null;
  editingDate.value = null;
  entryForm.work_date = '';
  entryForm.description = '';
}

async function saveEntry() {
  saving.value = true;
  clearMessages();

  const payload = {
    week_of: referenceDate.value,
    work_date: entryForm.work_date,
    description: entryForm.description.trim(),
  };

  try {
    if (editingId.value) {
      await api.put(`/work-logs/${editingId.value}`, payload);
      successMessage.value = 'Perubahan pekerjaan berhasil disimpan.';
    } else {
      await api.post('/work-logs', payload);
      successMessage.value = 'Pekerjaan berhasil ditambahkan.';
    }

    const response = await api.get('/work-logs', {
      params: { week_of: referenceDate.value },
    });
    weekRange.value = response.data.week;
    workLogs.value = response.data.work_logs;
    cancelEdit();
  } catch (error) {
    console.error('Save personal work log error:', error);
    errorMessage.value = error.response?.data?.message || 'Pekerjaan gagal disimpan.';
  } finally {
    saving.value = false;
  }
}

async function deleteEntry(log) {
  if (!await showConfirm('Hapus catatan pekerjaan ini? Catatan yang dihapus tidak dapat dikembalikan.', {
    title: 'Hapus Catatan Pekerjaan',
    confirmText: 'Ya, hapus catatan',
  })) {
    return;
  }

  deletingId.value = log.id;
  clearMessages();

  try {
    await api.delete(`/work-logs/${log.id}`);
    workLogs.value = workLogs.value.filter((item) => item.id !== log.id);
    if (editingId.value === log.id) {
      cancelEdit();
    }
    successMessage.value = 'Catatan pekerjaan berhasil dihapus.';
  } catch (error) {
    console.error('Delete personal work log error:', error);
    errorMessage.value = error.response?.data?.message || 'Catatan pekerjaan gagal dihapus.';
  } finally {
    deletingId.value = null;
  }
}

async function copyWhatsAppText() {
  clearMessages();

  try {
    await copyTextToClipboard(whatsAppText.value);
    copied.value = true;
    successMessage.value = 'Laporan disalin. Silakan tempel di WhatsApp.';
    window.setTimeout(() => {
      copied.value = false;
    }, 2500);
  } catch (error) {
    console.error('Copy personal work report error:', error);
    errorMessage.value = 'Laporan gagal disalin. Silakan coba kembali atau salin dari pratinjau.';
  }
}

function sendWhatsApp() {
  openWhatsAppMessage(whatsAppText.value);
}

async function downloadPdf() {
  downloadingPdf.value = true;
  clearMessages();

  try {
    const response = await api.get('/work-logs/export/pdf', {
      params: { week_of: referenceDate.value },
      responseType: 'blob',
    });
    const downloadUrl = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = `Laporan_Pekerjaan_${weekRange.value.start_date}_${weekRange.value.end_date}.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => window.URL.revokeObjectURL(downloadUrl), 1000);
  } catch (error) {
    console.error('Download personal work report PDF error:', error);
    errorMessage.value = 'PDF gagal diunduh. Silakan coba kembali.';
  } finally {
    downloadingPdf.value = false;
  }
}

onMounted(fetchWorkLogs);
</script>
