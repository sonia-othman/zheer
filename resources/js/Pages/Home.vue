<template>
  <AppLayout>
    <div class="flex flex-wrap gap-4 justify-end p-4">
      <Card
        v-for="device in devices"
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
import { ref, onMounted, onUnmounted } from 'vue';
import AppLayout from "@/Layouts/AppLayout.vue";
import Card from "@/Components/Card.vue";
import { router } from '@inertiajs/vue3';
import { FireIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
  initialStats: Object
});

const devices = ref(props.initialStats.devicesData || []);

const goToDashboard = (deviceId) => {
  router.get('/dashboard', { device_id: deviceId }, { preserveScroll: true });
};

const handleRealtimeUpdate = (e) => {
  const updatedDevice = e.sensorData;
  const index = devices.value.findIndex(d => d.device_id === updatedDevice.device_id);
  
  if (index >= 0) {
    devices.value[index] = {
      device_id: updatedDevice.device_id,
      status: updatedDevice.status,
      temperature: updatedDevice.temperature,
      battery: updatedDevice.battery,
      count: updatedDevice.count,
      created_at: updatedDevice.created_at
    };
  } else {
    devices.value.push({
      device_id: updatedDevice.device_id,
      status: updatedDevice.status,
      temperature: updatedDevice.temperature,
      battery: updatedDevice.battery,
      count: updatedDevice.count,
      created_at: updatedDevice.created_at
    });
  }
};

onMounted(() => {
  window.Echo.channel('sensor-data')
    .listen('.SensorDataUpdated', handleRealtimeUpdate);
});

onUnmounted(() => {
  window.Echo.leaveChannel('sensor-data');
});
</script>