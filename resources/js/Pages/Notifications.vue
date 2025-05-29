<template>
    <AppLayout>
        <div
            class="py-6"
            :dir="$t('common.direction')"
        >
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex flex-col gap-4 mb-4 sm:flex-row sm:justify-between sm:items-center">
                            <h2 class="text-xl font-semibold">{{ $t('notifications.recent') }}</h2>
                            <div class="flex gap-2">
                                <select
                                    v-model="filterType"
                                    class="px-6 py-2 text-sm border rounded"
                                >
                                    <option value="">{{ $t('notifications.all_types') }}</option>
                                    <option value="success">{{ $t('notifications.success') }}</option>
                                    <option value="info">{{ $t('notifications.info') }}</option>
                                    <option value="danger">{{ $t('notifications.danger') }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Loading state -->
                        <div
                            v-if="loading"
                            class="flex items-center justify-center py-8"
                        >
                            <div class="w-6 h-6 border-b-2 border-blue-500 rounded-full animate-spin"></div>
                            <span class="ml-2 text-gray-600">{{ $t('common.loading') }}</span>
                        </div>

                        <!-- Empty state message -->
                        <div
                            v-else-if="filteredNotifications.length === 0"
                            class="py-8 text-center text-gray-500"
                        >
                            {{ $t('notifications.no_notifications') }}
                        </div>

                        <!-- Notifications list -->
                        <div
                            v-else
                            class="space-y-3"
                        >
                            <div
                                v-for="notification in filteredNotifications"
                                :key="notification.id"
                                :class="notificationClass(notification.type)"
                                class="p-4 transition-all duration-200 rounded-lg shadow-sm hover:shadow-md"
                            >
                                <div class="flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-start">
                                    <div class="flex-1">
                                        <span class="font-medium">{{ getTranslatedMessage(notification) }}</span>
                                        <div class="mt-1 text-xs text-gray-600">
                                            {{ $t('notifications.device') }}: {{ notification.device_id ||
                                            $t('notifications.unknown') }}
                                        </div>
                                    </div>
                                    <div class="ml-2 text-xs text-gray-500 whitespace-nowrap">
                                        {{ formatTime(notification.timestamp) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Load More Button -->
                        <div
                            v-if="hasMore && !loading"
                            class="mt-6 text-center"
                        >
                            <button
                                @click="loadMore"
                                :disabled="loadingMore"
                                class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span
                                    v-if="loadingMore"
                                    class="flex items-center"
                                >
                                    <div class="w-4 h-4 mr-2 border-b-2 border-white rounded-full animate-spin"></div>
                                    {{ $t('common.loading') }}
                                </span>
                                <span v-else>{{ $t('notifications.load_more') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const { t, locale } = useI18n();

const props = defineProps({
    initialNotifications: {
        type: Array,
        default: () => []
    },
    hasMore: {
        type: Boolean,
        default: false
    },
    currentPage: {
        type: Number,
        default: 1
    }
});

// SOLUTION: Start with empty data, load after page renders
const notifications = ref([]);
const hasMore = ref(false);
const currentPage = ref(1);
const loading = ref(true); // Start with loading state
const loadingMore = ref(false);
const filterType = ref('');
const echoChannel = ref(null);

// SOLUTION: Load initial data via API
const loadInitialData = async () => {
    try {
        const response = await axios.get('/api/notifications/load-more', {
            params: { page: 1, limit: 20 }
        });

        notifications.value = response.data.notifications;
        hasMore.value = response.data.hasMore;
        currentPage.value = 1;
    } catch (error) {
        console.error('Error loading notifications:', error);
    } finally {
        loading.value = false;
    }
};

const filteredNotifications = computed(() => {
    if (!filterType.value) return notifications.value;
    return notifications.value.filter(n => n.type === filterType.value);
});

// Load more notifications
const loadMore = async () => {
    if (loadingMore.value || !hasMore.value) return;

    loadingMore.value = true;
    try {
        const response = await axios.get('/api/notifications/load-more', {
            params: {
                page: currentPage.value + 1,
                limit: 20
            }
        });

        notifications.value.push(...response.data.notifications);
        hasMore.value = response.data.hasMore;
        currentPage.value++;
    } catch (error) {
        console.error('Error loading more notifications:', error);
    } finally {
        loadingMore.value = false;
    }
};

// Watch for locale changes
watch(locale, () => {
    notifications.value = [...notifications.value];
});

const getTranslatedMessage = (notification) => {
    if (notification.translation_key) {
        try {
            let params = typeof notification.translation_params === 'string'
                ? JSON.parse(notification.translation_params)
                : notification.translation_params || {};

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
    if (!timestamp) return t('common.unknown_time');

    const date = new Date(timestamp);
    if (isNaN(date.getTime())) return t('common.invalid_time');

    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    // Show relative time for recent notifications
    if (diffInSeconds < 60) {
        return t('common.just_now');
    }

    if (diffInSeconds < 3600) { // Less than 1 hour
        const minutes = Math.floor(diffInSeconds / 60);
        return t('common.minutes_ago', { minutes });
    }

    if (diffInSeconds < 86400) { // Less than 24 hours
        const hours = Math.floor(diffInSeconds / 3600);
        return t('common.hours_ago', { hours });
    }

    // For older than 24 hours, show full date and time
    return date.toLocaleString(locale.value, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const notificationClass = (type) => {
    const base = 'border-l-4 pl-3';
    switch (type) {
        case 'success':
            return `${base} border-green-500 bg-green-50`;
        case 'info':
            return `${base} border-blue-500 bg-blue-50`;
        case 'danger':
            return `${base} border-red-500 bg-red-50`;
        default:
            return `${base} border-gray-500 bg-gray-50`;
    }
};

onMounted(() => {
    // SOLUTION: Load data after page renders
    loadInitialData();

    // Setup Echo listener
    echoChannel.value = window.Echo.channel('sensor-notifications')
        .listen('.SensorAlert', (e) => {
            const notification = {
                id: Date.now(),
                device_id: e.alert.device_id || t('notifications.unknown'),
                type: e.alert.type,
                message: e.alert.message,
                timestamp: e.alert.timestamp || new Date().toISOString(),
                translation_key: e.alert.translation_key || null,
                translation_params: e.alert.translation_params || {}
            };

            notifications.value.unshift(notification);

            if (notifications.value.length > 200) {
                notifications.value = notifications.value.slice(0, 200);
            }
        });
});

onUnmounted(() => {
    if (echoChannel.value) {
        window.Echo.leaveChannel('sensor-notifications');
    }
});
</script>
