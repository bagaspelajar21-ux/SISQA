<template>
  <div class="text-center py-12 px-4 rounded-xl border border-dashed border-slate-200 bg-white">
    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
      <slot name="icon">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
      </slot>
    </div>
    <h3 class="text-sm font-semibold text-slate-900 mb-1">{{ title }}</h3>
    <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">{{ description }}</p>
    <slot name="action" />
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: 'Tidak Ada Data',
  },
  description: {
    type: String,
    default: 'Belum ada data yang cocok dengan kriteria atau filter saat ini.',
  },
});
</script>
