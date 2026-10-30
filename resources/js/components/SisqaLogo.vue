<template>
  <div class="inline-flex items-center select-none" :class="gapClass">
    <!-- Official SISQA Logo Image Container -->
    <div
      :class="[
        'relative flex items-center justify-center shrink-0 bg-white overflow-hidden transition-transform duration-300',
        sizeClasses,
        glow
          ? 'shadow-2xl shadow-blue-500/35 ring-2 ring-white/80'
          : 'shadow-md shadow-slate-950/30 ring-1 ring-slate-200/80'
      ]"
    >
      <img
        :src="logoUrl"
        alt="SISQA Logo"
        :class="[
          'w-full h-full object-contain',
          size === 'xl' || size === 'lg' ? 'p-1.5 scale-105' : 'p-0.5 scale-110'
        ]"
      />
    </div>

    <!-- Brand Typography (for Sidebar & Header) -->
    <div v-if="showText" class="flex flex-col min-w-0">
      <div class="flex items-center space-x-1.5">
        <span
          :class="[
            'font-black tracking-tight leading-none',
            textSizeClass,
            theme === 'dark' ? 'text-white' : 'text-slate-900'
          ]"
        >
          SIS<span class="text-blue-500">QA</span>
        </span>
        <span
          v-if="badge"
          :class="[
            'px-1.5 py-0.5 rounded-md text-[9px] font-extrabold uppercase tracking-wider border leading-none',
            theme === 'dark'
              ? 'bg-blue-500/20 text-blue-300 border-blue-400/30'
              : 'bg-blue-50 text-blue-700 border-blue-200'
          ]"
        >
          PRO
        </span>
      </div>
      <span
        v-if="subtitle"
        :class="[
          'font-medium tracking-wide truncate mt-0.5',
          subtitleSizeClass,
          theme === 'dark' ? 'text-slate-400' : 'text-slate-500'
        ]"
      >
        {{ subtitle }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const logoUrl = '/images/sisqa-logo.jpg';

const props = defineProps({
  size: {
    type: String,
    default: 'md', // sm, md, lg, xl
  },
  showText: {
    type: Boolean,
    default: true,
  },
  subtitle: {
    type: String,
    default: 'Quality Assurance',
  },
  theme: {
    type: String,
    default: 'dark', // dark | light
  },
  badge: {
    type: Boolean,
    default: true,
  },
  glow: {
    type: Boolean,
    default: false,
  },
});

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'w-8 h-8 rounded-xl';
    case 'lg':
      return 'w-16 h-16 rounded-2xl';
    case 'xl':
      return 'w-24 h-24 rounded-3xl';
    default:
      return 'w-10 h-10 rounded-xl';
  }
});

const textSizeClass = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'text-sm';
    case 'lg':
      return 'text-2xl';
    case 'xl':
      return 'text-3xl';
    default:
      return 'text-base';
  }
});

const subtitleSizeClass = computed(() => {
  switch (props.size) {
    case 'lg':
    case 'xl':
      return 'text-xs';
    default:
      return 'text-[10px]';
  }
});

const gapClass = computed(() => {
  return props.size === 'lg' || props.size === 'xl' ? 'space-x-3.5' : 'space-x-2.5';
});
</script>
