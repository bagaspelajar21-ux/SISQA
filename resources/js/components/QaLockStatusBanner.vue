<template>
  <section
    v-if="!isAdmin && status?.locked"
    class="flex items-start gap-4 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-950 shadow-sm sm:px-6 sm:py-5"
    aria-live="polite"
  >
    <span class="mt-0.5 text-xl sm:text-2xl">🔒</span>
    <div class="space-y-1.5">
      <p class="text-base font-extrabold sm:text-lg">QA untuk modul ini sedang terkunci.</p>
      <p v-if="status.schedule_code" class="leading-relaxed">
        Jadwal {{ status.schedule_code }} telah berakhir.
      </p>
      <p v-if="remainingSeconds !== null" class="font-semibold leading-relaxed tabular-nums">
        Akses dibuka otomatis dalam {{ formattedRemaining }}.
      </p>
      <p v-else-if="status.waiting_for_schedule" class="max-w-3xl leading-relaxed">
        Akses dibuka saat jadwal QA berikutnya pada modul ini dimulai. Menunggu Admin menetapkan jadwal.
      </p>
    </div>
  </section>
  <p v-else-if="errorMessage && moduleId" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="status">
    {{ errorMessage }}
  </p>
</template>

<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import { useAuth } from '../auth';
import api from '../api';

const props = defineProps({
  moduleId: {
    type: [String, Number],
    default: '',
  },
});
const emit = defineEmits(['unlocked']);
const { isAdmin } = useAuth();
const status = ref(null);
const now = ref(Date.now());
const errorMessage = ref('');
let refreshInterval = null;
let clockInterval = null;
let wasLocked = false;
let checkedUnlockAt = null;
let activeModuleId = '';

const remainingSeconds = computed(() => {
  if (!status.value?.unlock_at) return null;
  return Math.max(0, Math.ceil((new Date(status.value.unlock_at).getTime() - now.value) / 1000));
});

const formattedRemaining = computed(() => {
  const seconds = remainingSeconds.value || 0;
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const remaining = seconds % 60;

  return [hours, minutes, remaining].map((part) => String(part).padStart(2, '0')).join(':');
});

const refreshStatus = async () => {
  if (!props.moduleId || isAdmin.value) {
    status.value = null;
    return;
  }

  const moduleId = String(props.moduleId);

  try {
    const response = await api.get('/qa-access/status', {
      params: { module_id: moduleId },
    });
    if (moduleId !== activeModuleId) return;
    status.value = response.data.status;
    errorMessage.value = '';

    if (wasLocked && !status.value.locked) {
      emit('unlocked');
    }
    wasLocked = status.value.locked;
    if (status.value.unlock_at !== checkedUnlockAt) {
      checkedUnlockAt = null;
    }
  } catch (error) {
    if (moduleId !== activeModuleId) return;
    errorMessage.value = error.response?.data?.message || 'Status penguncian modul gagal dimuat.';
  }
};

const stopTimers = () => {
  if (refreshInterval !== null) {
    window.clearInterval(refreshInterval);
    refreshInterval = null;
  }
  if (clockInterval !== null) {
    window.clearInterval(clockInterval);
    clockInterval = null;
  }
};

watch(
  () => props.moduleId,
  async (moduleId) => {
    stopTimers();
    status.value = null;
    errorMessage.value = '';
    wasLocked = false;
    checkedUnlockAt = null;
    activeModuleId = moduleId ? String(moduleId) : '';
    if (!moduleId || isAdmin.value) return;

    await refreshStatus();
    refreshInterval = window.setInterval(refreshStatus, 15000);
    clockInterval = window.setInterval(() => {
      now.value = Date.now();
      if (remainingSeconds.value === 0 && checkedUnlockAt !== status.value.unlock_at) {
        checkedUnlockAt = status.value.unlock_at;
        refreshStatus();
      }
    }, 1000);
  },
  { immediate: true },
);

onUnmounted(stopTimers);
</script>
