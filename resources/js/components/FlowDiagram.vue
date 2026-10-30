<template>
  <div class="flow-diagram-container overflow-x-auto p-6 bg-slate-50 rounded-xl border border-slate-200">
    <div v-if="!steps || steps.length === 0" class="text-center py-8 text-slate-400 text-xs">
      Belum ada langkah yang disusun untuk flow ini.
    </div>

    <div v-else class="flex flex-col md:flex-row items-center md:items-start justify-center gap-4 py-4 min-w-[700px]">
      <div
        v-for="(step, index) in sortedSteps"
        :key="step.id || index"
        class="flex flex-col md:flex-row items-center"
      >
        <!-- Step Node -->
        <div
          :class="[
            'relative w-56 rounded-xl p-4 shadow-sm border transition-all duration-200 hover:shadow-md cursor-pointer',
            nodeColor(step.step_type)
          ]"
          @click="$emit('select-step', step)"
        >
          <!-- Step Type Badge & Number -->
          <div class="flex items-center justify-between mb-2">
            <span class="w-6 h-6 rounded-full bg-white/80 border border-slate-200 text-slate-800 text-xs font-bold flex items-center justify-center shadow-xs">
              {{ step.step_number }}
            </span>
            <span :class="['text-[10px] uppercase tracking-wider font-extrabold px-2 py-0.5 rounded-full', badgeColor(step.step_type)]">
              {{ step.step_type }}
            </span>
          </div>

          <!-- Title -->
          <h4 class="text-xs font-bold text-slate-900 line-clamp-2 mb-1.5">{{ step.title }}</h4>

          <!-- Activity Description -->
          <p v-if="step.activity_description" class="text-[11px] text-slate-600 line-clamp-3 mb-2 leading-relaxed">
            {{ step.activity_description }}
          </p>

          <!-- Actor & Expected Result tags -->
          <div class="flex flex-wrap gap-1 text-[10px] mt-2 pt-2 border-t border-slate-200/60">
            <span v-if="step.actor" class="bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-medium">
              👤 {{ step.actor }}
            </span>
            <span v-if="step.expected_result" class="bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-medium truncate max-w-[190px]">
              ✓ {{ step.expected_result }}
            </span>
          </div>

          <!-- Decision Branches summary -->
          <div v-if="step.decision_branches && step.decision_branches.length > 0" class="mt-2 text-[10px] bg-amber-50 text-amber-800 p-1.5 rounded border border-amber-200">
            <div class="font-bold mb-0.5">Cabang Keputusan:</div>
            <div v-for="(b, bIdx) in step.decision_branches" :key="bIdx" class="truncate">
              • {{ b.condition || b.label }} ➔ Langkah #{{ b.target_step_number }}
            </div>
          </div>
        </div>

        <!-- Connector Arrow -->
        <div v-if="index < sortedSteps.length - 1" class="my-2 md:my-0 md:mx-2 text-slate-400 flex items-center justify-center">
          <svg class="w-6 h-6 transform md:rotate-0 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  steps: {
    type: Array,
    default: () => [],
  },
});

defineEmits(['select-step']);

const sortedSteps = computed(() => {
  return [...props.steps].sort((a, b) => a.step_number - b.step_number);
});

const nodeColor = (type) => {
  switch (type) {
    case 'Start': return 'bg-emerald-50/80 border-emerald-300 hover:border-emerald-500';
    case 'End': return 'bg-purple-50/80 border-purple-300 hover:border-purple-500';
    case 'Decision': return 'bg-amber-50/80 border-amber-300 hover:border-amber-500';
    default: return 'bg-white border-blue-200 hover:border-blue-400';
  }
};

const badgeColor = (type) => {
  switch (type) {
    case 'Start': return 'bg-emerald-600 text-white';
    case 'End': return 'bg-purple-600 text-white';
    case 'Decision': return 'bg-amber-600 text-white';
    default: return 'bg-blue-600 text-white';
  }
};
</script>
