<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-[100] overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6 md:p-8"
        @click.self="$emit('close')"
      >
        <div
          class="bg-white rounded-2xl shadow-[0_25px_60px_-15px_rgba(2,6,23,0.65)] border border-slate-200/95 overflow-hidden w-full transition-all transform max-h-[88vh] flex flex-col ring-1 ring-sky-400/25 my-auto"
          :class="sizeClass"
          @click.stop
        >
          <!-- Top Accent Gradient Line -->
          <div class="h-1.5 w-full bg-gradient-to-r from-sky-400 via-blue-600 to-indigo-600 shrink-0"></div>

          <!-- Header -->
          <div class="px-6 py-4 border-b border-slate-200/80 flex items-center justify-between bg-gradient-to-r from-slate-900 via-[#0b1a3a] to-[#102654] text-white shrink-0">
            <div class="flex items-center space-x-3 min-w-0 pr-3">
              <div class="w-2 h-7 rounded-full bg-gradient-to-b from-sky-400 to-blue-500 shrink-0 shadow-[0_0_10px_rgba(56,189,248,0.6)]"></div>
              <div class="min-w-0">
                <h3 class="text-sm sm:text-base font-extrabold text-white tracking-tight truncate">{{ title }}</h3>
                <p v-if="subtitle" class="text-[11px] text-sky-200/80 mt-0.5 truncate">{{ subtitle }}</p>
              </div>
            </div>
            <button
              type="button"
              @click="$emit('close')"
              class="text-sky-200/70 hover:text-white rounded-xl p-2 hover:bg-white/10 border border-transparent hover:border-sky-400/30 transition-all cursor-pointer shrink-0"
              title="Tutup"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Body -->
          <div class="p-6 overflow-y-auto grow bg-white text-slate-800">
            <slot />
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer" class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/90 flex items-center justify-end space-x-3 shrink-0">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  show: Boolean,
  title: String,
  subtitle: String,
  size: {
    type: String,
    default: 'md', // sm, md, lg, xl, full
  },
});

defineEmits(['close']);

const sizeClass = computed(() => {
  switch (props.size) {
    case 'sm': return 'max-w-md';
    case 'lg': return 'max-w-3xl';
    case 'xl': return 'max-w-4xl';
    case 'full': return 'max-w-6xl';
    default: return 'max-w-xl';
  }
});
</script>
