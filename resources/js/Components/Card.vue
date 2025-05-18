<template>
  <div
    class="card group"
    @click="$emit('click')"
    dir="ltr"
    :style="{ marginLeft: 'auto' }"
  >
    <!-- Layout container with appropriate icon positioning -->
    <div class="flex items-center w-full gap-4">
      <!-- Icon, positioned based on direction -->
      <component 
        v-if="icon && direction === 'ltr'" 
        :is="icon" 
        class="w-10 h-10 text-primary"
      />

      <!-- Text Section -->
      <div class="flex flex-col justify-between flex-1">
        <h2 class="text-lg font-semibold text-gray-800">
          {{ title }}
          <br />
          <span class="text-base font-normal">{{ deviceId }}</span>
        </h2>

        <p class="text-sm text-gray-500">{{ description }}</p>

        <div class="mt-2 flex flex-wrap gap-2">
          <div class="px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-full whitespace-nowrap">
            {{ value.split('\n')[0] }}
          </div>
          <div class="px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full whitespace-nowrap">
            {{ value.split('\n')[1] }}
          </div>
        </div>
      </div>
      
      <!-- Icon when in RTL mode, positioned at the end -->
      <component 
        v-if="icon && direction === 'rtl'" 
        :is="icon" 
        class="w-10 h-10 text-primary"
      />
    </div>
  </div>
</template>

<script setup>
import { defineProps, computed } from "vue";
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  icon: [Object, Function],
  title: String,
  description: String,
  value: [String, Number],
  deviceId: String,
});

// Get direction from the page props
const page = usePage();
const direction = computed(() => {
  return page.props.direction || 'ltr';
});
</script>

<style scoped>
.card {
  @apply w-[340px] h-[140px] bg-white shadow-md rounded-2xl p-5 cursor-pointer transition hover:shadow-xl hover:bg-gray-50;
}
</style>