<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95 translate-y-2"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 translate-y-2"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-[120] flex items-center justify-center overflow-y-auto bg-slate-950/65 p-4 backdrop-blur-sm"
        @click.self="$emit('cancel')"
      >
        <section
          class="w-full max-w-md overflow-hidden rounded-3xl border border-white/70 bg-white shadow-2xl shadow-slate-950/30 ring-1 ring-slate-900/10"
          role="alertdialog"
          aria-modal="true"
          :aria-label="title"
        >
          <div
            :class="[
              'h-1.5 bg-gradient-to-r',
              isDanger ? 'from-rose-500 via-red-500 to-orange-400' : 'from-sky-500 via-blue-600 to-violet-500',
            ]"
          ></div>
          <div class="p-6 sm:p-7">
            <div class="flex items-start gap-4">
              <div
                :class="[
                  'flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ring-8',
                  isDanger ? 'bg-rose-50 text-rose-600 ring-rose-50/70' : 'bg-blue-50 text-blue-600 ring-blue-50/70',
                ]"
              >
                <svg v-if="isDanger" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0Z" />
                </svg>
                <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16v-4m0-4h.01M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
                </svg>
              </div>
              <div class="min-w-0 pt-0.5">
                <h2 class="text-base font-black tracking-tight text-slate-900">{{ title }}</h2>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ message }}</p>
              </div>
            </div>

            <div class="mt-7 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200"
                @click="$emit('cancel')"
              >
                {{ cancelText }}
              </button>
              <button
                type="button"
                :class="[
                  'rounded-xl px-4 py-2.5 text-sm font-extrabold text-white shadow-lg transition focus:outline-none focus:ring-4',
                  isDanger
                    ? 'bg-gradient-to-r from-rose-600 to-red-600 shadow-rose-600/20 hover:from-rose-700 hover:to-red-700 focus:ring-rose-200'
                    : 'bg-gradient-to-r from-blue-600 to-indigo-600 shadow-blue-600/20 hover:from-blue-700 hover:to-indigo-700 focus:ring-blue-200',
                ]"
                @click="$emit('confirm')"
              >
                {{ confirmText }}
              </button>
            </div>
          </div>
        </section>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  show: Boolean,
  title: {
    type: String,
    default: 'Konfirmasi Tindakan',
  },
  message: {
    type: String,
    default: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
  },
  confirmText: {
    type: String,
    default: 'Ya, Lanjutkan',
  },
  cancelText: {
    type: String,
    default: 'Batal',
  },
  isDanger: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['confirm', 'cancel']);
</script>
