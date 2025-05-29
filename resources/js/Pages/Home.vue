<template>
    <AppLayout>

        <Head :title="$t('home.title')" />

        <!-- Loading state -->
        <div
            v-if="loading"
            class="flex items-center justify-center p-8"
        >
            <div class="w-8 h-8 border-b-2 border-blue-500 rounded-full animate-spin"></div>
            <span class="ml-2 text-gray-600">{{ $t('common.loading') }}</span>
        </div>

        <div
            v-else
            class="flex flex-wrap justify-start gap-5 p-4"
            :dir="$page.props.direction"
        >
            <!-- Card 1 -->
            <Card
                :icon="DoorOpen"
                :title="`${$t('home.first_device')}: ${$t('home.lab_door')}`"
                :device-id="devices[0]?.device_id || '-'"
                :description="devices[0]?.status ? $t('common.open') : $t('common.closed')"
                :value="devices[0] ? `${$t('home.temperature_short')}: ${devices[0].temperature}°C \n ${$t('home.battery_short')}: ${devices[0].battery}V` : ''"
                @click="devices[0] && goToDashboard(devices[0].device_id)"
            />

            <!-- Card 2 -->
            <Card
                :icon="Blinds"
                :title="`${$t('home.second_device')}: ${$t('home.lab_window')}`"
                :description="$t('common.not_available')"
                :value="$t('home.sensor_not_registered')"
                @click="() => handleSensorClick('motion')"
                class="bg-gray-50"
            />

            <!-- Card 3 -->
            <Card
                :icon="DoorClosedLocked"
                :title="`${$t('home.third_device')}: ${$t('home.department')}`"
                :description="$t('common.not_available')"
                :value="$t('home.sensor_not_registered')"
                @click="() => handleSensorClick('flame')"
                class="bg-gray-50"
            />

            <div
                v-if="devices.length === 0 && !loading"
                class="w-full py-8 text-center text-gray-500"
            >
                {{ $t('home.no_devices') }}
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import Card from "@/Components/Card.vue";
import { router } from '@inertiajs/vue3';
import { DoorOpen, Blinds, DoorClosedLocked } from 'lucide-vue-next';
import axios from 'axios';

const { t: $t } = useI18n();

const props = defineProps({
    initialStats: Object
});

// SOLUTION: Start with empty data, load after page renders
const devices = ref([]);
const loading = ref(true); // Start with loading state
const echoChannel = ref(null);

// SOLUTION: Load data via API after component mounts
const loadInitialData = async () => {
    try {
        const response = await axios.get('/api/home/stats');
        devices.value = response.data.devicesData || [];
    } catch (error) {
        console.error('Error loading home data:', error);
    } finally {
        loading.value = false;
    }
};

const goToDashboard = (deviceId) => {
    // No loading state needed - dashboard renders immediately
    router.get('/dashboard', { device_id: deviceId }, {
        preserveScroll: true
    });
};

const handleSensorClick = (sensorType) => {
    if (['motion', 'flame'].includes(sensorType)) {
        router.get('/dashboard', { unregistered: sensorType });
    } else {
        router.get('/dashboard', { device_id: sensorType });
    }
};

const handleRealtimeUpdate = (e) => {
    const updatedDevice = e.sensorData;
    const index = devices.value.findIndex(d => d.device_id === updatedDevice.device_id);

    if (index >= 0) {
        devices.value[index] = {
            ...devices.value[index],
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
    // SOLUTION: Load data after page renders
    loadInitialData();

    // Setup Echo listener
    echoChannel.value = window.Echo.channel('sensor-data')
        .listen('.SensorDataUpdated', handleRealtimeUpdate);
});

onUnmounted(() => {
    if (echoChannel.value) {
        window.Echo.leaveChannel('sensor-data');
    }
});
</script>
