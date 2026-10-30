<template>
  <svg
    :class="sizeClass"
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <!-- Dashboard Analytics -->
    <template v-if="name === 'dashboard'">
      <rect x="3" y="3" width="8" height="9" rx="2.5" fill="currentColor" fill-opacity="0.22" stroke="currentColor" stroke-width="1.8" />
      <rect x="13" y="3" width="8" height="5" rx="2" stroke="currentColor" stroke-width="1.8" />
      <rect x="13" y="10" width="8" height="11" rx="2.5" fill="currentColor" fill-opacity="0.22" stroke="currentColor" stroke-width="1.8" />
      <rect x="3" y="14" width="8" height="7" rx="2" stroke="currentColor" stroke-width="1.8" />
    </template>

    <!-- QA Schedule & Timeline -->
    <template v-else-if="name === 'schedules'">
      <rect x="3" y="4.5" width="18" height="16.5" rx="3" fill="currentColor" fill-opacity="0.16" stroke="currentColor" stroke-width="1.8" />
      <path d="M3 9.5H21" stroke="currentColor" stroke-width="1.8" />
      <path d="M8 3V6M16 3V6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
      <circle cx="8.5" cy="14" r="1.2" fill="currentColor" />
      <circle cx="12" cy="14" r="1.2" fill="currentColor" />
      <path d="M14.5 16.5L16 18L18.5 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
    </template>

    <!-- Test Management & Checklist -->
    <template v-else-if="name === 'test-cases'">
      <rect x="4" y="3" width="16" height="18" rx="3" fill="currentColor" fill-opacity="0.16" stroke="currentColor" stroke-width="1.8" />
      <path d="M8 9L9.8 10.8L13 7.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M15 9.5H16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
      <path d="M8 14.5L9.8 16.3L13 13" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M15 15H16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    </template>

    <!-- Bug Tracking / Defect Shield -->
    <template v-else-if="name === 'bugs'">
      <rect x="7" y="7" width="10" height="12" rx="5" fill="currentColor" fill-opacity="0.2" stroke="currentColor" stroke-width="1.8" />
      <path d="M9.5 7V5.5C9.5 4.12 10.62 3 12 3C13.38 3 14.5 4.12 14.5 5.5V7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
      <path d="M3.5 10.5L7 11.5M20.5 10.5L17 11.5M3 14.5H7M17 14.5H21M4.5 18.5L7.5 17M19.5 18.5L16.5 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
      <path d="M12 10V16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
    </template>

    <!-- Retesting / Verification Cycle -->
    <template v-else-if="name === 'retests'">
      <path d="M20 11A8 8 0 0 0 5.3 6.6L4 8M4 4V8H8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M4 13A8 8 0 0 0 18.7 17.4L20 16M20 20V16H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M9.5 12L11.3 13.8L14.8 10.2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
    </template>

    <!-- Application Flow / Nodes -->
    <template v-else-if="name === 'flows'">
      <rect x="3" y="3.5" width="6.5" height="6.5" rx="2" fill="currentColor" fill-opacity="0.22" stroke="currentColor" stroke-width="1.8" />
      <rect x="14.5" y="3.5" width="6.5" height="6.5" rx="2" stroke="currentColor" stroke-width="1.8" />
      <rect x="8.75" y="14" width="6.5" height="6.5" rx="2" fill="currentColor" fill-opacity="0.22" stroke="currentColor" stroke-width="1.8" />
      <path d="M9.5 6.75H14.5M6.25 10V14C6.25 15.65 7.5 17 9 17M17.75 10V14C17.75 15.65 16.5 17 15 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    </template>

    <!-- Knowledge Base / Guide Book -->
    <template v-else-if="name === 'guides'">
      <path d="M12 6C10.2 4.6 7.4 4.2 4.5 4.8C3.6 5 3 5.8 3 6.7V17.8C3 18.8 3.9 19.6 4.9 19.4C7.5 18.9 10.1 19.3 12 20.5C13.9 19.3 16.5 18.9 19.1 19.4C20.1 19.6 21 18.8 21 17.8V6.7C21 5.8 20.4 5 19.5 4.8C16.6 4.2 13.8 4.6 12 6Z" fill="currentColor" fill-opacity="0.16" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
      <path d="M12 6V20.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    </template>

    <!-- QA Report / Analytics Document -->
    <template v-else-if="name === 'reports'">
      <path d="M6 3H14.5L19 7.5V19C19 20.1 18.1 21 17 21H6C4.9 21 4 20.1 4 19V5C4 3.9 4.9 3 6 3Z" fill="currentColor" fill-opacity="0.16" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
      <path d="M14 3V8H19" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
      <path d="M8 16V13M11.5 16V11M15 16V14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
    </template>

    <!-- WhatsApp Daily Progress Report -->
    <template v-else-if="name === 'wa-report'">
      <path d="M12 3C7.03 3 3 6.86 3 11.62C3 13.5 3.63 15.24 4.7 16.66L3.6 20.5L7.62 19.44C8.94 20.13 10.42 20.5 12 20.5C16.97 20.5 21 16.64 21 11.88C21 7.12 16.97 3 12 3Z" fill="currentColor" fill-opacity="0.2" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
      <path d="M9.2 8.8C9.5 8.8 9.8 8.9 10 9.4L10.6 10.8C10.7 11.1 10.6 11.4 10.4 11.6L9.9 12.1C10.4 13 11.2 13.7 12.1 14.2L12.6 13.7C12.8 13.5 13.1 13.4 13.4 13.5L14.8 14.1C15.2 14.3 15.3 14.6 15.3 14.9C15.2 15.8 14.3 16.4 13.4 16.3C10.6 16 8.2 13.7 7.9 10.8C7.8 9.9 8.4 9 9.2 8.8Z" fill="currentColor" />
    </template>

    <!-- Activity Logs / Audit Pulse -->
    <template v-else-if="name === 'activity-logs'">
      <circle cx="12" cy="12" r="9" fill="currentColor" fill-opacity="0.14" stroke="currentColor" stroke-width="1.8" />
      <path d="M7 12H9.5L11 8.5L13.2 15.5L14.8 12H17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
    </template>

    <!-- Master Data Parent Folder / Cube -->
    <template v-else-if="name === 'master-data'">
      <path d="M12 2.8L20 7.3V16.7L12 21.2L4 16.7V7.3L12 2.8Z" fill="currentColor" fill-opacity="0.18" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
      <path d="M4 7.3L12 11.8L20 7.3M12 11.8V21.2" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
    </template>

    <!-- Master Aplikasi (Corporate Building / App Window) -->
    <template v-else-if="name === 'master-apps'">
      <rect x="4" y="3.5" width="11" height="17.5" rx="2" fill="currentColor" fill-opacity="0.2" stroke="currentColor" stroke-width="1.8" />
      <path d="M15 9.5H18.5C19.33 9.5 20 10.17 20 11V21H15" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
      <path d="M7.5 7.5H11.5M7.5 11H11.5M7.5 14.5H11.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
      <path d="M9.5 21V18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    </template>

    <!-- Master Modul (Menu Utama / Layered Stack) -->
    <template v-else-if="name === 'master-modules'">
      <path d="M12 3.5L20.5 8L12 12.5L3.5 8L12 3.5Z" fill="currentColor" fill-opacity="0.24" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
      <path d="M3.5 12.5L12 17L20.5 12.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M3.5 16.5L12 21L20.5 16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
    </template>

    <!-- Master Sub-Menu (Sub-Menu / Feature Nodes) -->
    <template v-else-if="name === 'master-menus'">
      <rect x="3.5" y="4" width="7" height="6" rx="1.8" fill="currentColor" fill-opacity="0.24" stroke="currentColor" stroke-width="1.8" />
      <rect x="12.5" y="13" width="8" height="6.5" rx="1.8" fill="currentColor" fill-opacity="0.18" stroke="currentColor" stroke-width="1.8" />
      <path d="M7 10V14.5C7 15.6 7.9 16.5 9 16.5H12.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
      <circle cx="16.5" cy="7" r="2.2" stroke="currentColor" stroke-width="1.7" />
    </template>

    <!-- Manajemen Pengguna (Users & Access Shield) -->
    <template v-else-if="name === 'users'">
      <circle cx="9.5" cy="8" r="3.5" fill="currentColor" fill-opacity="0.22" stroke="currentColor" stroke-width="1.8" />
      <path d="M3.5 19.5C3.5 16.2 6.2 14 9.5 14C12.8 14 15.5 16.2 15.5 19.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
      <circle cx="16.8" cy="9.2" r="2.5" stroke="currentColor" stroke-width="1.7" />
      <path d="M17.2 14.2C19.3 14.7 20.8 16.4 20.8 18.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
    </template>

    <!-- Default / Shield -->
    <template v-else>
      <path d="M12 3L19.5 6.2V12.2C19.5 16.9 16.3 20.6 12 22C7.7 20.6 4.5 16.9 4.5 12.2V6.2L12 3Z" fill="currentColor" fill-opacity="0.2" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
      <path d="M9 12.2L11.2 14.4L15.5 9.8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
    </template>
  </svg>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  name: {
    type: String,
    required: true,
  },
  size: {
    type: String,
    default: 'md', // sm, md, lg
  },
});

const sizeClass = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'w-4 h-4';
    case 'lg':
      return 'w-6 h-6';
    default:
      return 'w-[18px] h-[18px]';
  }
});
</script>

