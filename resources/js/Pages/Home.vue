<template>
  <AppLayout>
    <div class="flex flex-wrap gap-4 justify-end p-4">
      <Card
        v-for="device in stats.devicesData"
        :key="device.device_id"
        :icon="FireIcon"
        :title="`Device ${device.device_id}`"
        :description="device.status ? 'Open' : 'Closed'"
        :value="`T: ${device.temperature}°C / B: ${device.battery}V`"
        :device-id="device.device_id"
        @click="goToDashboard(device.device_id)"
      />
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted  } from 'vue';
import AppLayout from "@/Layouts/AppLayout.vue";
import Card from "@/Components/Card.vue";
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { FireIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
  initialStats: Object
});

const stats = ref(props.initialStats || {
  devices: 0,
  alerts: 0,
  devicesData: []
});

const goToDashboard = (deviceId) => {
  router.get('/dashboard', { 
    device_id: deviceId 
  }, {
    preserveScroll: true
  });
};

const fetchStats = async () => {
  try {
    const response = await axios.get('/data/statistics');
    stats.value = response.data;
  } catch (error) {
    console.error('Error fetching stats:', error);
  }
};

onMounted(() => {
  fetchStats(); 
  const interval = setInterval(fetchStats, 10000); 
  
  onUnmounted(() => clearInterval(interval));
});
</script>