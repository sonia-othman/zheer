<script setup>
import { computed } from 'vue'; 
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3'; 

const { t } = useI18n();

const props = defineProps({
  sensorType: String 
});

const errorMessages = computed(() => ({
  motion: t('errors.sensor_not_registered') || 'This sensor not registered!',
  flame: t('errors.sensor_not_registered') || 'This sensor not registered!',
  door: t('errors.sensor_not_registered') || 'This sensor not registered!'
}));

const goHome = () => {
  router.get('/');
};
</script>

<template>
  <div class="error-page">
    <div class="error-content">
      <div class="text-6xl mb-4">⚠️</div>
      <h1 class="text-2xl font-bold mb-6 text-red-600">
        {{ errorMessages[sensorType] || 'Sensor not registered!' }}
      </h1>
     
      <button 
        @click="goHome" 
        class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-200 shadow-md"
      >
        {{ t('common.back_to_home') || 'Back to Home' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.error-page {
  @apply fixed inset-0 flex items-center justify-center bg-gray-50;
  z-index: 1000;
}
.error-content {
  @apply text-center p-8 max-w-md bg-white rounded-xl shadow-lg;
}
</style>