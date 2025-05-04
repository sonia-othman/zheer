<template>
  <AppLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <h2 class="text-xl font-semibold mb-4">Recent Notifications</h2>
            <div class="space-y-3">
              <div
                v-for="(n, i) in notifications"
                :key="i"
                :class="notificationClass(n.type)"
                class="p-4 rounded-lg shadow-sm"
              >
                <div class="flex justify-between items-start">
                  <div>
                    <span class="font-medium">{{ n.message }}</span>
                    <div class="text-xs mt-1">
                      Device: {{ n.device_id || 'Unknown' }}
                    </div>
                  </div>
                  <div class="text-xs text-gray-500 whitespace-nowrap ml-2">
                    {{ formatTime(n.timestamp) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
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

const notifications = ref([...props.initialNotifications].map(n => ({
  ...n,
  timestamp: new Date(n.timestamp).toLocaleString()
})))

function formatTime(timestamp) {
  return new Date(timestamp).toLocaleTimeString()
}

onMounted(() => {
  window.Echo.channel('sensor-notifications')
    .listen('.SensorAlert', (e) => {
      const alert = {
        device_id: e.alert.device_id || 'Unknown',
        type: e.alert.type,
        message: e.alert.message,
        timestamp: e.alert.timestamp ? new Date(e.alert.timestamp).toLocaleString() : new Date().toLocaleString()
      };
      
      notifications.value.unshift(alert);
    });
});

function notificationClass(type) {
  const base = 'border-l-4 pl-3'
  return {
    [base + ' border-green-500 bg-green-50']: type === 'success',
    [base + ' border-blue-500 bg-blue-50']: type === 'info',
    [base + ' border-yellow-500 bg-yellow-50']: type === 'warning',
    [base + ' border-red-500 bg-red-50']: type === 'danger',
  }
}
</script>