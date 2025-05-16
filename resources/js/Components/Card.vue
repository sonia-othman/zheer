<!-- resources/js/Components/Card.vue -->
<template>
  <div class="card" @click="$emit('click')">
    <!-- Icon -->
    <component v-if="icon" :is="icon" class="w-8 h-8 text-gray-700" />

    <!-- Text Section -->
    <div class="flex-1 text-right">
      <h2 class="text-lg font-bold text-text">{{ title }}</h2>
      <p class="text-sm text-text">{{ description }}</p>
    </div>

    <!-- Bold Text on Left -->
    <span class="text-xl font-bold text-text">{{ value }}</span>
  </div>
</template>

<script setup>
import { defineProps } from "vue";

defineProps({
  icon: [Object, Function],  // Accept both Object and Function
  title: String,
  description: String,
  value: [String, Number],
  deviceId: String,
});

</script>

<style scoped>
.card {
  @apply w-[283px] h-[102px] bg-white shadow-lg rounded-lg flex flex-row-reverse items-center p-4 gap-2 cursor-pointer transition hover:bg-gray-100;
}
</style>