<template>
  <AppLayout>
  <div class="space-y-2 mt-0">
    <div
      v-for="(n, i) in notifications"
      :key="i"
      :class="notificationClass(n.type)"
    >
      {{ n.message }}
      <div class="text-xs text-gray-400">{{ n.timestamp }}</div>
    </div>
  </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, onMounted } from 'vue'

const props = defineProps({
  initialNotifications: Array
})

const notifications = ref([...props.initialNotifications])

onMounted(() => {
  window.Echo.channel('sensor-notifications')
    .listen('.SensorAlert', (e) => {
      console.log("Sensor alert received:", e.alert); // Log the received alert
      notifications.value.unshift(e.alert)
    })
})


function notificationClass(type) {
  return {
    'bg-green-100 text-green-800 p-3 rounded': type === 'success',
    'bg-yellow-100 text-yellow-800 p-3 rounded': type === 'warning',
    'bg-red-100 text-red-800 p-3 rounded': type === 'danger',
  }
}
</script>
 
