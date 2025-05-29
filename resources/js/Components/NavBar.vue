<template>
    <nav
        class="flex items-center justify-end h-16 px-6 bg-white border-b"
        dir="ltr"
    >
        <!-- Right aligned content -->
        <div class="flex items-center gap-6">
            <!-- Title -->
            <div class="text-xl font-bold text-gray-800">
                {{ $t(currentPageTitle) }}
            </div>

            <!-- Notifications -->
            <div class="relative">
                <button
                    @click="toggleDropdown"
                    class="relative"
                    ref="notificationBtn"
                >
                    <BellIcon class="w-6 h-6 text-gray-700" />
                    <span
                        v-if="notificationCount > 0"
                        class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-semibold rounded-full px-1.5 py-0.5"
                    >
                        {{ notificationCount }}
                    </span>
                </button>

                <!-- 🧾 Notification dropdown -->
                <div
                    v-if="showDropdown"
                    class="absolute right-0 z-50 mt-2 overflow-y-auto bg-white border rounded shadow-lg w-72 max-h-96"
                    ref="dropdownMenu"
                >
                    <div
                        v-if="notifications.length"
                        class="divide-y divide-gray-100"
                    >
                        <div
                            v-for="(n, i) in notifications.slice(0, 5)"
                            :key="i"
                            class="p-3 text-sm"
                        >
                            <div :class="notificationClass(n.type)">
                                {{ n.translation_key ? $t(n.translation_key, n.translation_params) : n.message }}
                            </div>

                            <div class="text-xs text-gray-400">{{ n.timestamp }}</div>
                        </div>
                        <div class="py-2 text-center">
                            <a
                                href="/notifications"
                                @click="closeDropdown"
                                class="text-sm text-blue-600 hover:underline"
                            >🔗 {{ $t('common.see_all_notifications') }}</a>
                        </div>
                    </div>
                    <div
                        v-else
                        class="py-4 text-sm text-center text-gray-500"
                    >
                        {{ $t('common.no_notifications') }}
                    </div>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { BellIcon } from "@heroicons/vue/24/solid";
import { useI18n } from 'vue-i18n';

const { t: $t } = useI18n();
const notifications = ref([]);
const showDropdown = ref(false);
const notificationCount = ref(0);
const notificationBtn = ref(null);
const dropdownMenu = ref(null);

function toggleDropdown() {
    showDropdown.value = !showDropdown.value;
}

function closeDropdown() {
    showDropdown.value = false;
}

function notificationClass(type) {
    // Add your notification type styling logic here
    return 'font-medium';
}

onMounted(() => {
    window.Echo.channel('sensor-notifications')
        .listen('.SensorAlert', (e) => {
            notifications.value.unshift(e.alert);
            notificationCount.value++;
        });

    // Improved click outside handler
    document.addEventListener('click', (e) => {
        // Only process if dropdown is currently shown
        if (showDropdown.value) {
            // Check if click target is outside both the button and dropdown
            const clickedButton = notificationBtn.value?.contains(e.target);
            const clickedDropdown = dropdownMenu.value?.contains(e.target);

            if (!clickedButton && !clickedDropdown) {
                closeDropdown();
            }
        }
    });
});

const routeMap = {
    '/dashboard': 'common.dashboard',
    '/notifications': 'common.notifications'
};
const currentPageTitle = computed(() => routeMap[window.location.pathname] || 'common.home');
</script>
