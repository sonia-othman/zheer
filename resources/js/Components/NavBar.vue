<template>
  <nav class="bg-white border-b px-6 py-4 flex justify-between items-center relative">
    <!-- Left side: Empty or could contain logo/navigation -->
    <div class="flex items-center gap-6">
      <!-- Any other left-side content -->
    </div>

    <!-- Right side: Title and Notifications -->
    <div class="flex items-center gap-6">
      <!-- Dynamic Title -->
      <div class="text-xl font-bold text-gray-800">
        {{ currentPage }}
      </div>

      <!-- Notifications -->
      <div class="relative">
        <button @click="toggleDropdown" class="relative">
          <!-- Bell Icon from Heroicons -->
          <BellIcon class="w-6 h-6 text-gray-700" />
          <span v-if="notificationCount > 0"
                class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-semibold rounded-full px-1.5 py-0.5">
            {{ notificationCount }}
          </span>
        </button>

        <!-- 🧾 Notification dropdown -->
        <div v-if="showDropdown"
             class="absolute right-0 mt-2 w-72 bg-white border rounded shadow-lg z-50 max-h-96 overflow-y-auto">
          <div v-if="notifications.length" class="divide-y divide-gray-100">
            <div v-for="(n, i) in notifications.slice(0, 5)" :key="i" class="p-3 text-sm">
              <div :class="notificationClass(n.type)">
                {{ n.message }}
              </div>
              <div class="text-xs text-gray-400">{{ n.timestamp }}</div>
            </div>
            <div class="text-center py-2">
              <a href="/notifications" @click="closeDropdown" class="text-blue-600 hover:underline text-sm">🔗 See all notifications</a>
            </div>
          </div>
          <div v-else class="text-center py-4 text-sm text-gray-500">
            No notifications
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
// Import Heroicons
import { BellIcon } from "@heroicons/vue/24/solid";

const notifications = ref([])
const showDropdown = ref(false)
const notificationCount = ref(0)
const user = usePage().props.auth?.user || null

function toggleDropdown() {
  showDropdown.value = !showDropdown.value
}

function closeDropdown() {
  showDropdown.value = false
}

function notificationClass(type) {
  return {
    'text-green-600': type === 'success',
    'text-yellow-600': type === 'warning',
    'text-red-600': type === 'danger',
    'text-blue-600': type === 'info',
  }
}

onMounted(() => {
  window.Echo.channel('sensor-notifications')
    .listen('.SensorAlert', (e) => {
      notifications.value.unshift(e.alert)
      notificationCount.value++
    })

  document.addEventListener('click', (e) => {
    const dropdown = document.querySelector('.z-50')
    const button = e.target.closest('button')

    if (!dropdown?.contains(e.target) && !button) {
      closeDropdown()
    }
  })
})

// 🔠 Dynamic page title from current route
const routeMap = {
  '/dashboard': 'داشبۆرد',
  '/notifications': 'ئاگاداریەکان'
}
const currentPage = computed(() => routeMap[window.location.pathname] || 'سەرەکی')

</script>