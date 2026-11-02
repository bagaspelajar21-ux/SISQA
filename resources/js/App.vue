<template>
  <component :is="isPublicRoute ? 'div' : Layout">
    <router-view />
  </component>
  <DialogHost />
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import DialogHost from './components/DialogHost.vue';
import Layout from './components/Layout.vue';

const route = useRoute();
const isPublicRoute = computed(() => !!route.meta?.public);
</script>
