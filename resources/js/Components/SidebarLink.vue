<template>
  <a 
    :href="href" 
    class="flex items-center space-x-2 w-full px-4 py-2 rounded transition"
    :class="{
      'bg-primary-default text-white': isActive,
      'text-primary-light': !isActive,
      'active:bg-primary-700': !isActive
    }"
  >
    <component :is="icon" class="w-6 h-6" :class="{'text-white': isActive, 'text-primary-light': !isActive}" />
    <span :class="{'text-white': isActive, 'text-primary-light': !isActive}">{{ text }}</span>
  </a>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';

// Define props
defineProps(["href", "icon", "text"]);

// Determine if the current page is active by checking the current route
const { component } = usePage();

const isActive = computed(() => {
  return component === text || href === window.location.pathname; // Compare with the actual href
});
</script>
