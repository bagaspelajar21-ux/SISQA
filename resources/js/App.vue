<template>
  <component :is="isPublicRoute ? 'div' : Layout">
    <router-view />
  </component>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import Layout from './components/Layout.vue';

const route = useRoute();
const isPublicRoute = computed(() => !!route.meta?.public);
</script>
