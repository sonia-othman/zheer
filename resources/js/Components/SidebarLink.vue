<template>
  <Link 
    :href="route(href)" 
    class="flex flex-row-reverse items-center gap-2 px-4 py-2 rounded w-full transition"
    :class="{
      'bg-primary text-white font-bold': isActive,
      'text-primary-light hover:bg-gray-100': !isActive
    }"
  >
    <component :is="iconComponent" class="w-6 h-6" />
    <span>{{ text }}</span>
  </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { HomeIcon, BellIcon } from "@heroicons/vue/24/solid";
import { GlobeAltIcon } from "@heroicons/vue/24/outline";

// Props
const props = defineProps({
  href: String,
  icon: String,
  text: String,
  match: String
});

// Map icon names to components
const iconComponent = computed(() => {
  return {
    HomeIcon,
    BellIcon,
    GlobeAltIcon
  }[props.icon];
});

// Get current component
const page = usePage();
const isActive = computed(() => page.component === props.match);
</script>
