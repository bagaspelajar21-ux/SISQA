<template>
  <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold tracking-wide border', badgeClasses]">
    <span v-if="dot" :class="['w-1.5 h-1.5 rounded-full mr-1.5', dotClasses]"></span>
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  value: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    default: 'status', // status, severity, priority, test_status, observation
  },
  dot: {
    type: Boolean,
    default: true,
  },
});

const label = computed(() => props.value || '-');

const config = computed(() => {
  const val = (props.value || '').toUpperCase();

  // Test Results
  if (val === 'PASS') {
    return { bg: 'bg-[#DCFCE7]', text: 'text-[#166534]', border: 'border-green-200', dot: 'bg-green-600' };
  }
  if (val === 'FAILED') {
    return { bg: 'bg-[#FEE2E2]', text: 'text-[#991B1B]', border: 'border-red-200', dot: 'bg-red-600' };
  }
  if (val === 'PENDING') {
    return { bg: 'bg-[#FEF3C7]', text: 'text-[#92400E]', border: 'border-yellow-200', dot: 'bg-yellow-600' };
  }
  if (val === 'NOT TESTED') {
    return { bg: 'bg-[#F3F4F6]', text: 'text-[#4B5563]', border: 'border-gray-200', dot: 'bg-gray-500' };
  }

  // Bug Severity
  if (val === 'CRITICAL') {
    return { bg: 'bg-red-100', text: 'text-red-900', border: 'border-red-300', dot: 'bg-red-800' };
  }
  if (val === 'MAJOR') {
    return { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-200', dot: 'bg-red-600' };
  }
  if (val === 'MINOR') {
    return { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-500' };
  }
  if (val === 'TRIVIAL') {
    return { bg: 'bg-gray-100', text: 'text-gray-700', border: 'border-gray-200', dot: 'bg-gray-400' };
  }

  // Schedule & Bug Status
  if (val === 'IN PROGRESS') {
    return { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-200', dot: 'bg-blue-600' };
  }
  if (val === 'COMPLETED' || val === 'CLOSED' || val === 'PASSED') {
    return { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-600' };
  }
  if (val === 'READY FOR RETEST') {
    return { bg: 'bg-cyan-50', text: 'text-cyan-700', border: 'border-cyan-200', dot: 'bg-cyan-600' };
  }
  if (val === 'REOPENED') {
    return { bg: 'bg-rose-50', text: 'text-rose-700', border: 'border-rose-200', dot: 'bg-rose-600' };
  }
  if (val === 'OVERDUE') {
    return { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-200', dot: 'bg-red-600' };
  }
  if (val === 'CANCELLED' || val === 'ARCHIVED' || val === 'REJECTED') {
    return { bg: 'bg-gray-100', text: 'text-gray-600', border: 'border-gray-200', dot: 'bg-gray-400' };
  }

  // Guide Observation
  if (val === 'SUDAH DIAMATI' || val === 'SUDAH DIAMATI OLEH ADMIN') {
    return { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-600' };
  }
  if (val === 'BELUM DIAMATI') {
    return { bg: 'bg-gray-100', text: 'text-gray-600', border: 'border-gray-200', dot: 'bg-gray-400' };
  }
  if (val === 'PUBLISHED') {
    return { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-200', dot: 'bg-blue-600' };
  }
  if (val === 'DRAFT') {
    return { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-500' };
  }

  // Default fallback
  return { bg: 'bg-slate-100', text: 'text-slate-700', border: 'border-slate-200', dot: 'bg-slate-500' };
});

const badgeClasses = computed(() => `${config.value.bg} ${config.value.text} ${config.value.border}`);
const dotClasses = computed(() => config.value.dot);
</script>
