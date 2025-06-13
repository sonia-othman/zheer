<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, watch, onUnmounted, computed } from 'vue';
import axios from 'axios';
import Chart from 'chart.js/auto';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';

// Initialize i18n
const { t } = useI18n();

const chartRef = ref(null);
const countChartRef = ref(null);
const latestData = ref(null);

const props = defineProps({
    initialDeviceId: String,
    initialData: Object,
    error: String,
});
const allData = ref(props.initialData || {
    latest: null,
    daily: [],
    weekly: [],
    monthly: [],
    statistics: null
});


const deviceId = ref(props.initialDeviceId || '1'); // FIXED: Set default device ID
const tempBatteryFilter = ref('daily');
const countFilter = ref('daily');

// Filter options with translations
const filterOptions = computed(() => ({
    daily: t('common.daily'),
    weekly: t('common.weekly'),
    monthly: t('common.monthly')
}));

const statusText = computed(() => ({
    open: t('common.open'),
    closed: t('common.closed')
}));

// ADDED: Computed property to show total count for the current filter period
const totalCountForPeriod = ref(0);

let tempBatteryChart = null;
let countChart = null;

const formatDateLabel = (dateString, filterType) => {
    const date = new Date(dateString);
    switch (filterType) {
        case 'daily': return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        case 'weekly': return date.toLocaleDateString([], { weekday: 'short' });
        case 'monthly': return date.getDate();
        default: return date.toLocaleTimeString();
    }
};
const updateTempBatteryChart = (data, filterType) => {
    // Handle empty data
    if (!data || data.length === 0) {
        return;
    }

    // Sort data by created_at to ensure chronological order
    const sortedData = [...data].sort((a, b) =>
        new Date(a.created_at) - new Date(b.created_at)
    );

    const labels = sortedData.map(entry => entry.label || formatDateLabel(entry.created_at, filterType));
    const temperatureData = sortedData.map(entry => entry.temperature ?? 0);
    const batteryData = sortedData.map(entry => entry.battery ?? 0);

    const maxTemp = Math.max(...temperatureData);
    const minTemp = Math.min(...temperatureData);
    const maxTempIndex = temperatureData.indexOf(maxTemp);
    const minTempIndex = temperatureData.indexOf(minTemp);

    const ctx = chartRef.value?.getContext('2d');

    const chartData = {
        labels,
        datasets: [
            {
                label: `🌡 ${t('dashboard.Temperature')}`,
                data: temperatureData,
                borderColor: 'rgba(239, 68, 68, 1)',
                backgroundColor: temperatureData.map((_, idx) =>
                    idx === maxTempIndex ? 'rgba(239, 68, 68, 0.8)' :
                        idx === minTempIndex ? 'rgba(59, 130, 246, 0.8)' : 'rgba(239, 68, 68, 0.2)'),
                tension: 0.4,
                yAxisID: 'y',
            },
            {
                label: `🔋 ${t('dashboard.Battery')}`,
                data: batteryData,
                borderColor: 'rgba(59, 130, 246, 1)',
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                tension: 0.4,
                yAxisID: 'y1',
            }
        ]
    };

    if (!tempBatteryChart && chartRef.value) {
        tempBatteryChart = new Chart(chartRef.value, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            title: context => {
                                const label = context[0].label;
                                if (filterType === 'weekly') {
                                    return `${t('dashboard.Day')}: ${label}`;
                                } else if (filterType === 'monthly') {
                                    return `${t('dashboard.Date')}: ${label}`;
                                } else {
                                    return `${t('dashboard.Time')}: ${label}`;
                                }
                            },
                            label: context => {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.dataset.label.includes('Temperature')
                                        ? `${context.parsed.y}°C`
                                        : `${context.parsed.y}V`;
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6B7280'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: t('dashboard.Temperature') + ' (°C)',
                            color: '#EF4444',
                            font: {
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: '#E5E7EB',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6B7280'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: t('dashboard.Battery') + ' (V)',
                            color: '#3B82F6',
                            font: {
                                weight: 'bold'
                            }
                        },
                        grid: {
                            drawOnChartArea: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6B7280'
                        }
                    },
                },
            },
        });
    } else if (tempBatteryChart) {
        tempBatteryChart.data = chartData;
        tempBatteryChart.update();
    }
};
const groupDataByFilter = (data, filterType) => {
    // Handle empty data
    if (!data || data.length === 0) {
        return [];
    }

    // For weekly and monthly, the backend already provides aggregated data
    // We just need to add proper labels
    if (filterType === 'weekly' || filterType === 'monthly') {
        return data.map(entry => ({
            ...entry,
            label: entry.date_label || entry.label || formatDateLabel(entry.created_at, filterType)
        }));
    }

    // For daily view, just add time labels
    return data.map(entry => ({
        ...entry,
        label: formatDateLabel(entry.created_at, filterType)
    }));
};

// 2. FIXED: Updated updateCountChart function
const updateCountChart = (data, filterType) => {
    // Handle empty data
    if (!data || data.length === 0) {
        totalCountForPeriod.value = 0;
        return;
    }

    // Sort data by created_at to ensure chronological order
    const sortedData = [...data].sort((a, b) =>
        new Date(a.created_at) - new Date(b.created_at)
    );

    const labels = sortedData.map(entry => entry.date_label || formatDateLabel(entry.created_at, filterType));
    const countData = sortedData.map(entry => entry.count || 0);

    // For total count, use the LAST count value in the period (not sum)
totalCountForPeriod.value = countData.length; // Just number of rows = events

    const maxCount = Math.max(...countData);
    const minCount = Math.min(...countData);
    const maxCountIndex = countData.indexOf(maxCount);
    const minCountIndex = countData.indexOf(minCount);

    const ctx = countChartRef.value?.getContext('2d');
    let gradient;
    if (ctx) {
        gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.8)');
        gradient.addColorStop(1, 'rgba(29, 78, 216, 0.8)');
    }

    const chartData = {
        labels,
        datasets: [{
            label: `🚪 ${t('dashboard.Open Count')}`,
            data: countData,
            backgroundColor: countData.map((_, idx) =>
                idx === maxCountIndex ? 'rgba(239, 68, 68, 0.8)' :
                    idx === minCountIndex ? 'rgba(16, 185, 129, 0.8)' : gradient || 'rgba(59, 130, 246, 0.8)'),
            borderColor: countData.map((_, idx) =>
                idx === maxCountIndex ? 'rgba(239, 68, 68, 1)' :
                    idx === minCountIndex ? 'rgba(16, 185, 129, 1)' : 'rgba(29, 78, 216, 1)'),
            borderWidth: 1,
            borderRadius: 6,
            borderSkipped: false,
        }]
    };

    if (!countChart && countChartRef.value) {
        countChart = new Chart(countChartRef.value, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            title: context => {
                                const label = context[0].label;
                                if (filterType === 'weekly') {
                                    return `${t('dashboard.Day')}: ${label}`;
                                } else if (filterType === 'monthly') {
                                    return `${t('dashboard.Date')}: ${label}`;
                                } else {
                                    return `${t('dashboard.Time')}: ${label}`;
                                }
                            },
                            afterBody: context => {
                                const index = context[0].dataIndex;
                                const count = context[0].raw;
                                const messages = [];

                                if (index === maxCountIndex) {
                                    messages.push(`🔥 ${t('dashboard.Highest Activity')}`);
                                } else if (index === minCountIndex) {
                                    messages.push(`❄️ ${t('dashboard.Lowest Activity')}`);
                                }

                                return messages;
                            },
                            label: context => {
                                return `${t('dashboard.Open Count')}: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6B7280'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#E5E7EB',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6B7280'
                        },
                        title: {
                            display: true,
                            text: t('dashboard.Open Count'),
                            color: '#3B82F6',
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                },
            },
        });
    } else if (countChart) {
        countChart.data = chartData;
        countChart.update();
    }
};
const isLoading = ref({
    tempBattery: false,
    count: false,
    latest: false
});

const errors = ref({
    tempBattery: null,
    count: null,
    latest: null
});

const fetchData = async (type) => {
    const loadingKey = type === 'tempBattery' ? 'tempBattery' : 'count';

    try {
        isLoading.value[loadingKey] = true;
        errors.value[loadingKey] = null;

        const filterValue = type === 'tempBattery' ? tempBatteryFilter.value : countFilter.value;
        const response = await axios.get('/data/sensor', {
            params: {
                device_id: deviceId.value,
                filter: filterValue,
                full_month: filterValue === 'monthly'
            }
        });

        const data = filterValue !== 'daily' ? groupDataByFilter(response.data, filterValue) : response.data;

        if (type === 'tempBattery') {

            updateTempBatteryChart(data, filterValue);
        } else {
            updateCountChart(data, filterValue);
        }
    } catch (error) {
        errors.value[loadingKey] = error.response?.data?.message || 'Failed to load data';
    } finally {
        isLoading.value[loadingKey] = false;
    }
};

const fetchLatestData = async () => {
    if (!deviceId.value) return;

    try {
        isLoading.value.latest = true;
        errors.value.latest = null;

        const response = await axios.get('/data/latest', {
            params: { device_id: deviceId.value }
        });

        latestData.value = response.data;
    } catch (error) {
        errors.value.latest = error.response?.data?.message || 'Failed to load latest data';
    } finally {
        isLoading.value.latest = false;
    }
};

const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};


const debouncedFetchData = debounce(fetchData, 300);

const handleRealtimeUpdate = (event) => {
    if (event.sensorData.device_id === deviceId.value) {
        latestData.value = event.sensorData;
        debouncedFetchData('tempBattery');
        debouncedFetchData('count');
    }
};

onMounted(async () => {
    if (deviceId.value && !props.isUnregisteredSensor) {
        await fetchLatestData();

        setTimeout(() => {
            if (deviceId.value) {
                fetchData('tempBattery');
                fetchData('count');
            }
        }, 100);
    }

    window.Echo.channel('sensor-data')
        .listen('.SensorDataUpdated', handleRealtimeUpdate);
});

watch(deviceId, () => {
    if (deviceId.value && !props.isUnregisteredSensor) {
        fetchLatestData();
        debouncedFetchData('tempBattery');
        debouncedFetchData('count');
    }
});

watch(tempBatteryFilter, () => debouncedFetchData('tempBattery'));
watch(countFilter, () => debouncedFetchData('count'));

const retryFetch = (type) => {
    fetchData(type);
};

let refreshInterval = null;
const startPeriodicRefresh = () => {
    refreshInterval = setInterval(() => {
        if (deviceId.value) {
            fetchLatestData();
        }
    }, 30000);
};

const stopPeriodicRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
};

onUnmounted(() => {
    window.Echo.leaveChannel('sensor-data');
    stopPeriodicRefresh();
});
const errorMessage = computed(() => {
    if (!props.error) return '';
    return t(`dashboard.${props.error}_sensor_not_registered`);
});
</script>

<template>

    <Head :title="t('dashboard.title')" />
    <AppLayout>

        <!-- Normal dashboard view - ONLY show this when NOT unregistered sensor -->
        <div>
            <h2 class="mb-4 text-xl font-semibold leading-tight text-gray-800">
                {{ t('dashboard.title') }}
            </h2>

            <div
                class="py-6"
                :dir="t('common.direction')"
            >
                <div
                    v-if="error"
                    class="p-4 mb-4 text-red-700 bg-red-100 rounded-lg"
                >
                    {{ error }}
                </div>

                <div class="mx-auto space-y-6 max-w-7xl sm:px-6 lg:px-8">
                    <!-- Status Cards -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <!-- Current Status -->
                        <div
                            class="p-6 transition-shadow bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md">
                            <h3 class="text-lg font-medium text-gray-900">{{ t('dashboard.Current Status') }}</h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500">{{ t('dashboard.Door & Window') }}</p>
                                <p
                                    class="text-2xl font-semibold"
                                    :class="latestData?.status ? 'text-red-500' : 'text-green-500'"
                                >
                                    {{ latestData?.status ? statusText.open : statusText.closed }}
                                    <span class="ml-2 text-xl">{{ latestData?.status ? '🚪' : '🔒' }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Temperature -->
                        <div
                            class="p-6 transition-shadow bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md">
                            <h3 class="text-lg font-medium text-gray-900">{{ t('dashboard.Temperature') }}</h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500">{{ t('dashboard.Current') }}</p>
                                <p
                                    class="text-2xl font-semibold"
                                    :class="{
                                        'text-red-500': latestData?.temperature > 30,
                                        'text-blue-500': latestData?.temperature < 15,
                                        'text-gray-700': latestData?.temperature >= 15 && latestData?.temperature <= 30
                                    }"
                                >
                                    {{ latestData?.temperature ?? '--' }}°C
                                    <span class="ml-2 text-xl">{{
                                        latestData?.temperature > 30 ? '🔥' :
                                            latestData?.temperature <
                                                15
                                                ? '❄️'
                                                : '🌡'
                                    }}</span
                                        >
                                </p>
                            </div>
                        </div>

                        <!-- Battery -->
                        <div
                            class="p-6 transition-shadow bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md">
                            <h3 class="text-lg font-medium text-gray-900">{{ t('dashboard.Battery') }}</h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500">{{ t('dashboard.Voltage') }}</p>
                                <p
                                    class="text-2xl font-semibold"
                                    :class="{
                                        'text-red-500': latestData?.battery < 3,
                                        'text-yellow-500': latestData?.battery >= 3 && latestData?.battery < 3.5,
                                        'text-green-500': latestData?.battery >= 3.5
                                    }"
                                >
                                    {{ latestData?.battery ?? '--' }}V
                                    <span class="ml-2 text-xl">{{
                                        latestData?.battery <
                                            3
                                            ? '🪫'
                                            :
                                            latestData?.battery
                                                >= 3.5 ? '🔋' : '⚠️'
                                    }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Open Count -->
                        <div
                            class="p-6 transition-shadow bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md">
                            <h3 class="text-lg font-medium text-gray-900">{{ t('dashboard.door activity') }}</h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500">{{ t('dashboard.Open Count') }}</p>
                                <p class="text-2xl font-semibold text-blue-600">
                                    {{ latestData?.count ?? '0' }}
                                    <span class="ml-2 text-xl">📊</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Temperature & Battery Chart -->
                    <div
                        class="p-6 transition-shadow bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">{{ t('dashboard.Temperature & Battery') }}
                            </h3>
                            <select
                                v-model="tempBatteryFilter"
                                class="px-6 py-3 text-sm border-gray-200 shadow-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 bg-gray-50"
                            >
                                <option
                                    v-for="(value, key) in filterOptions"
                                    :key="key"
                                    :value="key"
                                >
                                    {{ value }}
                                </option>
                            </select>
                        </div>
                        <div class="h-80">
                            <canvas ref="chartRef"></canvas>
                        </div>
                        <div class="flex flex-wrap gap-4 mt-4 text-sm text-gray-600">
                            <div class="flex items-center">
                                <span class="inline-block w-3 h-3 mr-2 bg-red-500 rounded-full"></span>
                                {{ t('dashboard.Temperature') }}
                            </div>
                            <div class="flex items-center">
                                <span class="inline-block w-3 h-3 mr-2 bg-blue-500 rounded-full"></span>
                                {{ t('dashboard.Battery') }}
                            </div>
                            <div class="flex items-center">
                                <span class="inline-block w-3 h-3 mr-2 bg-red-700 rounded-full"></span>
                                {{ t('dashboard.Highest Temperature') }}
                            </div>
                            <div class="flex items-center">
                                <span class="inline-block w-3 h-3 mr-2 bg-blue-300 rounded-full"></span>
                                {{ t('dashboard.Lowest Temperature') }}
                            </div>
                        </div>
                    </div>

                    <!-- Open Count Chart -->
                    <div
                        class="p-6 transition-shadow bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">{{ t('dashboard.Open Count') }}</h3>
                            <select
                                v-model="countFilter"
                                class="px-6 py-3 text-sm border-gray-200 shadow-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 bg-gray-50"
                            >
                                <option
                                    v-for="(value, key) in filterOptions"
                                    :key="key"
                                    :value="key"
                                >
                                    {{ value }}
                                </option>
                            </select>
                        </div>
                        <!-- FIXED: Show total count for the current filter period -->
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm text-gray-500">
                                {{ t('dashboard.Total Opens') }} ({{ filterOptions[countFilter] }}):
                                <span class="font-medium text-blue-600">{{ totalCountForPeriod }}</span>
                            </p>
                            <p class="text-sm text-gray-500">{{ t('dashboard.Last Update') }}: <span
                                    class="font-medium">{{ latestData?.created_at ? new
                                        Date(latestData.created_at).toLocaleTimeString() : '--' }}</span></p>
                        </div>
                        <div class="h-80">
                            <canvas ref="countChartRef"></canvas>
                        </div>
                        <div class="flex flex-wrap gap-4 mt-4 text-sm text-gray-600">
                            <div class="flex items-center">
                                <span class="inline-block w-3 h-3 mr-3 bg-blue-500 rounded-full"></span>
                                {{ t('dashboard.Normal Activity') }}
                            </div>
                            <div class="flex items-center">
                                <span class="inline-block w-3 h-3 mr-3 bg-red-500 rounded-full"></span>
                                {{ t('dashboard.Highest Activity') }}
                            </div>
                            <div class="flex items-center">
                                <span class="inline-block w-3 h-3 mr-3 bg-green-500 rounded-full"></span>
                                {{ t('dashboard.Lowest Activity') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
