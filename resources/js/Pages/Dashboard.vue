<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'; 
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed, onUnmounted } from 'vue';
import axios from 'axios';
import Chart from 'chart.js/auto';

const chartRef = ref(null);
const countChartRef = ref(null);
const latestData = ref(null);
const props = defineProps({
  initialDeviceId: String 
});
const deviceId = ref(props.initialDeviceId);


let tempBatteryChart = null;
let countChart = null;

const fetchData = async () => {
  try {
    const response = await axios.get('/data/sensor', {
      params: { device_id: deviceId.value }
    });
    console.log('📦 Sensor response:', response.data); // ← Add this if it's not already

    const data = response.data;
    latestData.value = data[data.length - 1];
    
    updateCharts(data);
  } catch (error) {
    console.error('Error fetching sensor data:', error);
  }
};

const updateCharts = (data) => {
  const labels = data.map(entry => new Date(entry.created_at).toLocaleTimeString());
  const temperatureData = data.map(entry => entry.temperature);
  const batteryData = data.map(entry => entry.battery);
  const countData = data.map(entry => entry.count);

  // Temperature/Battery Chart
  if (tempBatteryChart) {
    tempBatteryChart.data.labels = labels;
    tempBatteryChart.data.datasets[0].data = temperatureData;
    tempBatteryChart.data.datasets[1].data = batteryData;
  } else {
    tempBatteryChart = new Chart(chartRef.value, {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: '🌡 Temperature (°C)',
            data: temperatureData,
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            tension: 0.4,
            yAxisID: 'y',
          },
          {
            label: '🔋 Battery (V)',
            data: batteryData,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            yAxisID: 'y1',
          }
        ],
      },
      options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        scales: {
          y: {
            type: 'linear',
            position: 'left',
            title: { display: true, text: 'Temperature (°C)' }
          },
          y1: {
            type: 'linear',
            position: 'right',
            grid: { drawOnChartArea: false },
            title: { display: true, text: 'Battery (V)' }
          }
        }
      }
    });
  }

  // Door Open Count Chart
  if (countChart) {
    countChart.data.labels = labels;
    countChart.data.datasets[0].data = countData;
  } else {
    countChart = new Chart(countChartRef.value, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: '🚪 Open Count',
          data: countData,
          backgroundColor: '#8b5cf6',
          borderColor: '#7c3aed',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            title: { display: true, text: 'Count' }
          }
        }
      }
    });
  }

  // Update both charts
  tempBatteryChart?.update();
  countChart?.update();
};

const handleRealtimeUpdate = (e) => {
  console.log('📡 pusher event received:', e);
  
  // Update reactive data
  latestData.value = e.sensorData;
  
  // Get current chart data
  const currentLabels = tempBatteryChart?.data.labels || [];
  const currentTemps = tempBatteryChart?.data.datasets[0].data || [];
  const currentBattery = tempBatteryChart?.data.datasets[1].data || [];
  const currentCounts = countChart?.data.datasets[0].data || [];
  
  // Add new data point
  const newLabel = new Date(e.sensorData.created_at).toLocaleTimeString();
  
  // Update charts if they exist
  if (tempBatteryChart) {
    tempBatteryChart.data.labels = [...currentLabels, newLabel].slice(-10);
    tempBatteryChart.data.datasets[0].data = [...currentTemps, e.sensorData.temperature].slice(-10);
    tempBatteryChart.data.datasets[1].data = [...currentBattery, e.sensorData.battery].slice(-10);
    tempBatteryChart.update();
  }

  if (countChart) {
    countChart.data.labels = [...currentLabels, newLabel].slice(-10);
    countChart.data.datasets[0].data = [...currentCounts, e.sensorData.count].slice(-10);
    countChart.update();
  }
};

watch(deviceId, fetchData);
onMounted(() => {
  fetchData();
  
  window.Echo.channel('sensor-data')
   .listen('.SensorDataUpdated', (e) => {
  console.log('📡 Pusher event received:', e);
  handleRealtimeUpdate(e);
});

   
});

onUnmounted(() => {
  window.Echo.leaveChannel('sensor-data');
});


</script>


<template>
  <Head title="Dashboard" />

  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Door/Window Sensor Dashboard
      </h2>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900">Current Status</h3>
            <div class="mt-4">
              <p class="text-sm text-gray-500">Door/Window</p>
              <p class="text-2xl font-semibold">
                {{ latestData?.status ? 'Open' : 'Closed' }}
              </p>
            </div>
          </div>

          <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900">Temperature</h3>
            <div class="mt-4">
              <p class="text-sm text-gray-500">Current</p>
              <p class="text-2xl font-semibold">
                {{ latestData?.temperature ?? '--' }}°C
              </p>
            </div>
          </div>

          <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900">Battery</h3>
            <div class="mt-4">
              <p class="text-sm text-gray-500">Voltage</p>
              <p class="text-2xl font-semibold">
                {{ latestData?.battery ?? '--' }}V
              </p>
            </div>
          </div>
        </div>

        <!-- Temperature/Battery Chart -->
        <div class="bg-white p-6 rounded-lg shadow">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Temperature & Battery</h3>
          <canvas ref="chartRef" height="120"></canvas>
        </div>

        <!-- Door Open Count Chart -->
        <div class="bg-white p-6 rounded-lg shadow">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Door/Window Openings</h3>
          <div class="flex justify-between items-center mb-2">
            <p class="text-sm text-gray-500">Total Opens: {{ latestData?.count ?? '0' }}</p>
            <p class="text-sm text-gray-500">Last at: {{ latestData?.created_at ? new Date(latestData.created_at).toLocaleTimeString() : '--' }}</p>
          </div>
          <canvas ref="countChartRef" height="120"></canvas>
        </div>
      </div>
    </div>
  </AppLayout>
</template>