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
  initialData: Object
});

const deviceId = ref(props.initialDeviceId);
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

let tempBatteryChart = null;
let countChart = null;

const formatDateLabel = (dateString, filterType) => {
  const date = new Date(dateString);
  switch(filterType) {
    case 'daily': return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    case 'weekly': return date.toLocaleDateString([], { weekday: 'short' });
    case 'monthly': return date.getDate();
    default: return date.toLocaleTimeString();
  }
};

const groupDataByFilter = (data, filterType) => {
  // For daily view or when data is already properly formatted
  if (filterType === 'daily' || (data[0] && data[0].temperature !== undefined && !Array.isArray(data[0].temperature))) {
    return data.map(entry => ({
      ...entry,
      label: filterType === 'daily' 
        ? formatDateLabel(entry.created_at, filterType) 
        : entry.date_label || entry.label
    }));
  }

  // For weekly/monthly data that needs processing
  let grouped = {};
  const now = new Date();
  
  if (filterType === 'monthly') {
    const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
    for (let day = 1; day <= daysInMonth; day++) {
      grouped[day] = {
        temperature: [],
        battery: [],
        count: 0,
        created_at: new Date(now.getFullYear(), now.getMonth(), day).toISOString()
      };
    }
  } else if (filterType === 'weekly') {
    for (let day = 0; day < 7; day++) {
      grouped[day] = {
        temperature: [],
        battery: [],
        count: 0,
        created_at: new Date(now.setDate(now.getDate() - now.getDay() + day)).toISOString()
      };
    }
  }

  data.forEach(entry => {
    const date = new Date(entry.created_at);
    const key = filterType === 'weekly' ? date.getDay() : date.getDate();

    if (!grouped[key]) {
      grouped[key] = {
        temperature: [],
        battery: [],
        count: 0,
        created_at: entry.created_at
      };
    }

    grouped[key].temperature.push(entry.temperature);
    grouped[key].battery.push(entry.battery);
    grouped[key].count += entry.count;
    grouped[key].created_at = entry.created_at; // Keep the most recent timestamp
  });

  return Object.entries(grouped).map(([key, values]) => {
    const label = filterType === 'weekly' 
      ? ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][key]
      : key;

    return {
      created_at: values.created_at,
      temperature: values.temperature.length ? 
        values.temperature.reduce((a, b) => a + b, 0) / values.temperature.length : 0,
      battery: values.battery.length ? 
        values.battery.reduce((a, b) => a + b, 0) / values.battery.length : 0,
      count: values.count,
      label: label
    };
  }).sort((a, b) => {
    return filterType === 'weekly' ? parseInt(a.label) - parseInt(b.label) : parseInt(a.label) - parseInt(b.label);
  });
};

const updateTempBatteryChart = (data, filterType) => {
  const labels = data.map(entry => entry.label || formatDateLabel(entry.created_at, filterType));
  const temperatureData = data.map(entry => entry.temperature);
  const batteryData = data.map(entry => entry.battery);

  const maxTemp = Math.max(...temperatureData);
  const minTemp = Math.min(...temperatureData);
  const maxTempIndex = temperatureData.indexOf(maxTemp);
  const minTempIndex = temperatureData.indexOf(minTemp);

  const maxBattery = Math.max(...batteryData);
  const minBattery = Math.min(...batteryData);
  const maxBatteryIndex = batteryData.indexOf(maxBattery);
  const minBatteryIndex = batteryData.indexOf(minBattery);
  
  const chartData = {
    labels,
    datasets: [
      {
        label: `🌡 ${t('dashboard.Temperature')} (°C)`,
        data: temperatureData,
        borderColor: '#ef4444',
        backgroundColor: 'rgba(239, 68, 68, 0.1)',
        tension: 0.4,
        yAxisID: 'y',
        pointBackgroundColor: temperatureData.map((_, idx) => 
          idx === maxTempIndex || idx === minTempIndex ? '#000' : '#ef4444'),
        pointRadius: temperatureData.map((_, idx) => 
          idx === maxTempIndex || idx === minTempIndex ? 6 : 3),
      },
      {
        label: `🔋 ${t('dashboard.Battery')} (V)`,
        data: batteryData,
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        tension: 0.4,
        yAxisID: 'y1',
        pointBackgroundColor: batteryData.map((_, idx) => 
          idx === maxBatteryIndex || idx === minBatteryIndex ? '#000' : '#10b981'),
        pointRadius: batteryData.map((_, idx) => 
          idx === maxBatteryIndex || idx === minBatteryIndex ? 6 : 3),
      }
    ]
  };

  if (!tempBatteryChart) {
    tempBatteryChart = new Chart(chartRef.value, {
      type: 'line',
      data: chartData,
      options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          tooltip: {
            callbacks: {
              title: context => filterType === 'weekly' ? 
                `${t('dashboard.Day')}: ${context[0].label}` : 
                filterType === 'monthly' ? 
                `${t('dashboard.Date')}: ${context[0].label}` : 
                `${t('dashboard.Time')}: ${context[0].label}`,
              afterBody: context => {
                const index = context[0].dataIndex;
                const tooltips = [];
                if (index === maxTempIndex) tooltips.push(t('dashboard.Highest Temperature'));
                else if (index === minTempIndex) tooltips.push(t('dashboard.Lowest Temperature'));
                if (index === maxBatteryIndex) tooltips.push(t('dashboard.Highest Battery'));
                else if (index === minBatteryIndex) tooltips.push(t('dashboard.Lowest Battery'));
                return tooltips;
              }
            }
          }
        },
        scales: {
          y: { 
            type: 'linear', 
            position: 'left', 
            title: { 
              display: true, 
              text: `${t('dashboard.Temperature')} (°C)` 
            } 
          },
          y1: { 
            type: 'linear', 
            position: 'right', 
            grid: { drawOnChartArea: false }, 
            title: { 
              display: true, 
              text: `${t('dashboard.Battery')} (V)` 
            } 
          },
        },
      },
    });
  } else {
    tempBatteryChart.data = chartData;
    tempBatteryChart.update();
  }
};

const updateCountChart = (data, filterType) => {
  const labels = data.map(entry => entry.label || formatDateLabel(entry.created_at, filterType));
  const countData = data.map(entry => entry.count);

  const maxCount = Math.max(...countData);
  const minCount = Math.min(...countData);
  const maxCountIndex = countData.indexOf(maxCount);
  const minCountIndex = countData.indexOf(minCount);

  const chartData = {
    labels,
    datasets: [{
      label: `🚪 ${t('dashboard.Open Count')}`,
      data: countData,
      backgroundColor: countData.map((_, idx) => 
        idx === maxCountIndex ? '#2A55A2' : 
        idx === minCountIndex ? '#2781F2' : '#23538F'),
      borderColor: countData.map((_, idx) => 
        idx === maxCountIndex ? '#2A55A2' : 
        idx === minCountIndex ? '#2781F2' : '#23538F'),
      borderWidth: 1,
    }]
  };

  if (!countChart) {
    countChart = new Chart(countChartRef.value, {
      type: 'bar',
      data: chartData,
      options: {
        responsive: true,
        plugins: {
          tooltip: {
            callbacks: {
              title: context => filterType === 'weekly' ? 
                `${t('dashboard.Day')}: ${context[0].label}` : 
                filterType === 'monthly' ? 
                `${t('dashboard.Date')}: ${context[0].label}` : 
                `${t('dashboard.Time')}: ${context[0].label}`,
              afterBody: context => {
                const index = context[0].dataIndex;
                return index === maxCountIndex ? [t('dashboard.Highest Activity')] : 
                      index === minCountIndex ? [t('dashboard.Lowest Activity')] : [];
              }
            }
          }
        },
        scales: {
          y: { 
            beginAtZero: true, 
            title: { 
              display: true, 
              text: t('dashboard.Open Count') 
            } 
          },
        },
      },
    });
  } else {
    countChart.data = chartData;
    countChart.update();
  }
};

const fetchData = async (type) => {
  try {
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
      latestData.value = data[data.length - 1];
      updateTempBatteryChart(data, filterValue);
    } else {
      updateCountChart(data, filterValue);
    }
  } catch (error) {
    console.error('Error fetching sensor data:', error);
  }
};

const handleRealtimeUpdate = (e) => {
  latestData.value = e.sensorData;
  const newLabel = new Date(e.sensorData.created_at).toLocaleTimeString();

  if (tempBatteryChart) {
    tempBatteryChart.data.labels.push(newLabel);
    tempBatteryChart.data.datasets[0].data.push(e.sensorData.temperature);
    tempBatteryChart.data.datasets[1].data.push(e.sensorData.battery);
    tempBatteryChart.data.datasets.forEach(d => {
      d.pointBackgroundColor.push('#2A55A2');
      d.pointRadius.push(3);
    });
    tempBatteryChart.update();
  }

  if (countChart) {
    countChart.data.labels.push(newLabel);
    countChart.data.datasets[0].data.push(e.sensorData.count);
    countChart.data.datasets[0].backgroundColor.push('#2A55A2');
    countChart.data.datasets[0].borderColor.push('#2781F2');
    countChart.update();
  }
};

onMounted(() => {
  if (props.initialData) {
    latestData.value = props.initialData.latestData;
    updateTempBatteryChart(
      groupDataByFilter(props.initialData.tempBatteryData, tempBatteryFilter.value),
      tempBatteryFilter.value
    );
    updateCountChart(
      groupDataByFilter(props.initialData.countData, countFilter.value),
      countFilter.value
    );
  }

  window.Echo.channel('sensor-data')
    .listen('.SensorDataUpdated', handleRealtimeUpdate);
});

watch(deviceId, () => {
  fetchData('tempBattery');
  fetchData('count');
});

watch(tempBatteryFilter, () => fetchData('tempBattery'));
watch(countFilter, () => fetchData('count'));

onUnmounted(() => {
  window.Echo.leaveChannel('sensor-data');
});
</script>

<template>
  <Head :title="t('dashboard.title')" />
  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ t('dashboard.title') }}
      </h2>
    </template>

    <div class="py-6" :dir="t('common.direction')">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Current Status -->
          <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900">{{ t('dashboard.Current Status') }}</h3>
            <div class="mt-4">
              <p class="text-sm text-gray-500">{{ t('dashboard.Door & Window') }}</p>
              <p class="text-2xl font-semibold">
                {{ latestData?.status ? statusText.open : statusText.closed }}
              </p>
            </div>
          </div>

          <!-- Temperature -->
          <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900">{{ t('dashboard.Temperature') }}</h3>
            <div class="mt-4">
              <p class="text-sm text-gray-500">{{ t('dashboard.Current') }}</p>
              <p class="text-2xl font-semibold">
                {{ latestData?.temperature ?? '--' }}°C
              </p>
            </div>
          </div>

          <!-- Battery -->
          <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900">{{ t('dashboard.Battery') }}</h3>
            <div class="mt-4">
              <p class="text-sm text-gray-500">{{ t('dashboard.Voltage') }}</p>
              <p class="text-2xl font-semibold">
                {{ latestData?.battery ?? '--' }}V
              </p>
            </div>
          </div>

          <!-- Open Count -->
          <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900">{{ t('dashboard.Open Count') }}</h3>
            <div class="mt-4">
              <p class="text-sm text-gray-500">{{ t('dashboard.Current') }}</p>
              <p class="text-2xl font-semibold">
                {{ latestData?.count ?? '0' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Temperature & Battery Chart -->
        <div class="bg-white p-6 rounded-lg shadow mt-4">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">{{ t('dashboard.Temperature & Battery') }}</h3>
            <select 
              v-model="tempBatteryFilter" 
              class="border-gray-300 rounded-lg shadow-sm focus:ring-blue-800 focus:border-blue-800 text-sm"
            >
              <option v-for="(value, key) in filterOptions" :key="key" :value="key">
                {{ value }}
              </option>
            </select>
          </div>
          <canvas ref="chartRef" height="120"></canvas>
          <div class="mt-2 text-sm text-gray-500">
            <span class="inline-block w-3 h-3 bg-black rounded-full mr-1"></span> 
            {{ t('dashboard.Highest and Lowest Points') }}
          </div>
        </div>

        <!-- Open Count Chart -->
        <div class="bg-white p-6 rounded-lg shadow mt-4">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">{{ t('dashboard.Open Count') }}</h3>
            <select 
              v-model="countFilter" 
              class="border-gray-300 rounded-lg shadow-sm focus:ring-blue-800 focus:border-blue-800 text-sm"
            >
              <option v-for="(value, key) in filterOptions" :key="key" :value="key">
                {{ value }}
              </option>
            </select>
          </div>
          <div class="flex justify-between items-center mb-2">
            <p class="text-sm text-gray-500">{{ t('dashboard.Total Opens') }}: {{ latestData?.count ?? '0' }}</p>
            <p class="text-sm text-gray-500">{{ t('dashboard.Last Update') }}: {{ latestData?.created_at ? new Date(latestData.created_at).toLocaleTimeString() : '--' }}</p>
          </div>
          <canvas ref="countChartRef" height="120"></canvas>
          <div class="mt-2 text-sm text-gray-500 flex items-center">
            <span class="inline-flex items-center mr-3">
              <span class="inline-block w-3 h-3 bg-blue-500 rounded-full mr-1"></span> 
              {{ t('dashboard.Highest Activity') }}
            </span>
            <span class="inline-flex items-center">
              <span class="inline-block w-3 h-3 bg-blue-900 rounded-full mr-1"></span> 
              {{ t('dashboard.Lowest Activity') }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>