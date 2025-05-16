<template>
  <AppLayout>
    <Head :title="$t('home.title')" />
    
    <div class="flex flex-wrap gap-5 justify-start p-4" :dir="$page.props.direction">
      <Card
        v-for="device in devices"
        :key="device.device_id"
        :icon="FireIcon"
        :title="`${$t('home.device')} ${device.device_id}`"
        :description="device.status ? $t('common.open') : $t('common.closed')"
        :value="`${$t('home.temperature_short')}: ${device.temperature}°C / ${$t('home.battery_short')}: ${device.battery}V`"
        :device-id="device.device_id"
        @click="goToDashboard(device.device_id)"
      />

      <div v-if="devices.length === 0" class="w-full text-center text-gray-500">
        {{ $t('home.no_devices') }}
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n'; // 👈 Import this
import { Head } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import Card from "@/Components/Card.vue";
import { router } from '@inertiajs/vue3';
import { FireIcon } from "@heroicons/vue/24/outline";

// 👇 Use the translation function
const { t: $t } = useI18n();

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
console.log($t('home.temperature_short')); // Should output "T", "د", or "پ" depending on language
</script>
