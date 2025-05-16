<template>
  <div 
    class="card" 
    @click="$emit('click')"
    :class="{ 'rtl-card': $page.props.isRtl }"
  >
    <!-- Icon -->
    <component v-if="icon" :is="icon" class="w-8 h-8 text-gray-700" />

    <!-- Text Section -->
    <div class="flex-1" :class="$page.props.isRtl ? 'text-right' : 'text-left'">
      <h2 class="text-lg font-bold text-text">{{ title }}</h2>
      <p class="text-sm text-text">{{ description }}</p>
    </div>

    <!-- Value Section -->
    <span class="text-xl font-bold text-text">{{ value }}</span>
  </div>
</template>

<script setup>
import { defineProps } from "vue";
import { usePage } from '@inertiajs/vue3';

defineProps({
  icon: [Object, Function],
  title: String,
  description: String,
  value: [String, Number],
  deviceId: String,
});
</script>

<style scoped>
.card {
  @apply w-[283px] h-[102px] bg-white shadow-lg rounded-lg flex items-center p-4 gap-2 cursor-pointer transition hover:bg-gray-100;
}

.rtl-card {
  @apply flex-row-reverse;
}
</style>