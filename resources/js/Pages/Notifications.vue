<template>
  <AppLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <h2 class="text-xl font-semibold mb-4">Recent Notifications</h2>
            <div class="space-y-3">
              <div
                v-for="notification in notifications"
                :key="notification.id"
                :class="notificationClass(notification.type)"
                class="p-4 rounded-lg shadow-sm"
              >
                <div class="flex justify-between items-start">
                  <div>
                    <span class="font-medium">{{ notification.message }}</span>
                    <div class="text-xs mt-1">
                      Device: {{ notification.device_id || 'Unknown' }}
                    </div>
                  </div>
                  <div class="text-xs text-gray-500 whitespace-nowrap ml-2">
                    {{ formatTime(notification.timestamp) }}
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
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
  initialNotifications: {
    type: Array,
    default: () => []
  }
});

const notifications = ref(props.initialNotifications);

const formatTime = (timestamp) => {
  return new Date(timestamp).toLocaleTimeString();
};

const notificationClass = (type) => {
  const base = 'border-l-4 pl-3';
  switch(type) {
    case 'success':
      return `${base} border-green-500 bg-green-50`;
    case 'info':
      return `${base} border-blue-500 bg-blue-50`;
    case 'warning':
      return `${base} border-yellow-500 bg-yellow-50`;
    case 'danger':
      return `${base} border-red-500 bg-red-50`;
    default:
      return `${base} border-gray-500 bg-gray-50`;
  }
};

// Real-time updates
window.Echo.channel('sensor-notifications')
  .listen('.SensorAlert', (e) => {
    notifications.value.unshift({
      id: Date.now(), // temporary ID for new notifications
      device_id: e.alert.device_id || 'Unknown',
      type: e.alert.type,
      message: e.alert.message,
      timestamp: e.alert.timestamp || new Date().toISOString()
    });
  });
</script>