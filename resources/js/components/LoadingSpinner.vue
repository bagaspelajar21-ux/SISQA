<template>
  <div class="flex flex-col items-center justify-center p-8 text-slate-500">
    <div class="w-8 h-8 border-3 border-blue-600 border-t-transparent rounded-full animate-spin mb-3"></div>
    <span class="text-xs font-medium">{{ text }}</span>
  </div>
</template>

<script setup>
defineProps({
  text: {
    type: String,
    default: 'Memuat data sistem...',
  },
});
</script>
