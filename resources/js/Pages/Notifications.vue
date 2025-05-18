<template>
  <AppLayout>
    <div class="py-6" :dir="$t('common.direction')">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <h2 class="text-xl font-semibold mb-4">{{ $t('notifications.recent') }}</h2>
            
            <!-- Empty state message -->
            <div v-if="notifications.length === 0" class="text-center text-gray-500 py-8">
              {{ $t('notifications.no_notifications') }}
            </div>
            
            <!-- Notifications list -->
            <div v-else class="space-y-3">
              <div
                v-for="notification in notifications"
                :key="notification.id"
                :class="notificationClass(notification.type)"
                class="p-4 rounded-lg shadow-sm"
              >
                <div class="flex justify-between items-start">
                  <div>
                    <span class="font-medium">{{ getTranslatedMessage(notification) }}</span>
                    <div class="text-xs mt-1">
                      {{ $t('notifications.device') }}: {{ notification.device_id || $t('notifications.unknown') }}
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
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head } from '@inertiajs/vue3';

// Initialize i18n - destructure both t and locale
const { t, locale } = useI18n();

const props = defineProps({
  initialNotifications: {
    type: Array,
    default: () => []
  }
});

const notifications = ref(props.initialNotifications || []);

// Watch for locale changes
watch(locale, (newLocale) => {
  // Force reactivity by creating a new array
  notifications.value = [...notifications.value];
});

const getTranslatedMessage = (notification) => {
  if (notification.translation_key) {
    try {
      let params = typeof notification.translation_params === 'string' 
        ? JSON.parse(notification.translation_params)
        : notification.translation_params || {};

      // 🛠️ Round minutes if it exists
      if (params.minutes !== undefined) {
        params.minutes = Math.floor(params.minutes);
      }

      return t(notification.translation_key, params);
    } catch (e) {
      console.error('Translation error:', e);
      return notification.message;
    }
  }
  return notification.message;
};



const formatTime = (timestamp) => {
  const date = new Date(timestamp);
  return `${date.toLocaleDateString()} ${date.toLocaleTimeString()}`;
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

// Listen for real-time updates
window.Echo.channel('sensor-notifications')
  .listen('.SensorAlert', (e) => {
    const notification = {
      id: Date.now(), // temporary ID for new notifications
      device_id: e.alert.device_id || t('notifications.unknown'),
      type: e.alert.type,
      message: e.alert.message,
      timestamp: e.alert.timestamp || new Date().toISOString(),
      translation_key: e.alert.translation_key || null,
      translation_params: e.alert.translation_params || {}
    };
    
    // Add the new notification at the beginning of the array
    notifications.value.unshift(notification);
    
    // Limit the number of notifications shown (optional)
    if (notifications.value.length > 50) {
      notifications.value = notifications.value.slice(0, 50);
    }
  });
</script>