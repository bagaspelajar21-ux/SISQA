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
        class="fixed inset-0 z-[110] overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="$emit('cancel')"
      >
        <div class="relative bg-white rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden w-full max-w-md my-auto">
          <!-- Top Gradient Accent Bar -->
          <div
            :class="[
              'h-2 w-full bg-gradient-to-r',
              isDanger
                ? 'from-red-500 via-rose-500 to-orange-500'
                : 'from-blue-600 via-indigo-600 to-purple-600'
            ]"
          ></div>

          <div class="p-6 sm:p-7 text-center">
            <!-- Modern Glowing Icon Badge -->
            <div
              :class="[
                'w-16 h-16 rounded-2xl mx-auto flex items-center justify-center mb-4 shadow-sm ring-8',
                isDanger
                  ? 'bg-gradient-to-br from-red-50 to-rose-100 text-red-600 ring-red-50/80 border border-red-200/60'
                  : 'bg-gradient-to-br from-blue-50 to-indigo-100 text-blue-600 ring-blue-50/80 border border-blue-200/60'
              ]"
            >
              <svg v-if="isDanger" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>

            <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight mb-1.5">
              {{ title }}
            </h3>
            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-6">
              {{ message }}
            </p>

            <div class="grid grid-cols-2 gap-3">
              <button
                type="button"
                @click="$emit('cancel')"
                class="py-2.5 px-4 border border-slate-200 rounded-xl text-xs font-extrabold text-slate-700 bg-slate-50 hover:bg-slate-100 transition-all cursor-pointer"
              >
                {{ cancelText }}
              </button>
              <button
                type="button"
                @click="$emit('confirm')"
                :class="[
                  'py-2.5 px-4 rounded-xl text-xs font-extrabold text-white shadow-lg transition-all cursor-pointer',
                  isDanger
                    ? 'bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 shadow-red-600/25'
                    : 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-blue-600/25'
                ]"
              >
                {{ confirmText }}
              </button>
            </div>
          </div>
        </div>
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
