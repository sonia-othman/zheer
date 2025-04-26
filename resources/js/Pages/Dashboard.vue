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
    initialDeviceId: String,
  });

  const deviceId = ref(props.initialDeviceId);
  const tempBatteryFilter = ref('daily');
  const countFilter = ref('daily');

  let tempBatteryChart = null;
  let countChart = null;

  // Helper function to format dates based on filter type
  const formatDateLabel = (dateString, filterType) => {
    const date = new Date(dateString);
    
    switch(filterType) {
      case 'daily':
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      case 'weekly':
        return date.toLocaleDateString([], { weekday: 'short' });
      case 'monthly':
        return date.getDate();
      default:
        return date.toLocaleTimeString();
    }
  };

  // Helper function to create empty monthly structure
  const createEmptyMonthStructure = () => {
    const now = new Date();
    const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
    const monthStructure = {};
    
    for (let day = 1; day <= daysInMonth; day++) {
      monthStructure[day] = {
        temperature: [],
        battery: [],
        count: 0,
        created_at: new Date(now.getFullYear(), now.getMonth(), day).toISOString()
      };
    }
    
    return monthStructure;
  };

  // Modified groupDataByFilter function
  // Modified groupDataByFilter function to sum count instead of averaging
const groupDataByFilter = (data, filterType) => {
  if (filterType === 'daily') return data;

  let grouped = {};
  const now = new Date();
  
  // For monthly view, pre-fill with all days of the current month
  if (filterType === 'monthly') {
    const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
    for (let day = 1; day <= daysInMonth; day++) {
      grouped[day] = {
        temperature: [],
        battery: [],
        count: 0,  // Sum of counts, not average
        created_at: new Date(now.getFullYear(), now.getMonth(), day).toISOString()
      };
    }
  }

  // Process each data entry
  data.forEach(entry => {
    const date = new Date(entry.created_at);
    let key;

    if (filterType === 'weekly') {
      key = date.getDay();  // Group by day of week
    } else {  // Monthly
      key = date.getDate(); // Day of the month
    }

    if (!grouped[key]) {
      grouped[key] = {
        temperature: [],
        battery: [],
        count: 0,  // Initialize count to 0
        created_at: entry.created_at
      };
    }

    grouped[key].temperature.push(entry.temperature);
    grouped[key].battery.push(entry.battery);
    grouped[key].count += entry.count;  // Sum the count values
  });

  // Convert to array and format labels
  return Object.entries(grouped).map(([key, values]) => {
    let label;
    if (filterType === 'weekly') {
      const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
      label = days[key];
    } else {
      label = key; // Day of month (1-31)
    }

    return {
      created_at: values.created_at,
      temperature: values.temperature.length > 0 
        ? values.temperature.reduce((a, b) => a + b, 0) / values.temperature.length 
        : 0,
      battery: values.battery.length > 0 
        ? values.battery.reduce((a, b) => a + b, 0) / values.battery.length 
        : 0,
      count: values.count,  // Sum of counts
      label: label
    };
  }).sort((a, b) => {
    return filterType === 'weekly' ? parseInt(a.label) - parseInt(b.label) : parseInt(a.label) - parseInt(b.label);
  });
};

  // Modified fetchData to ensure we get all historical data
  const fetchData = async (type) => {
  try {
    const filterValue = type === 'tempBattery' ? tempBatteryFilter.value : countFilter.value;
    const response = await axios.get('/data/sensor', {
      params: { 
        device_id: deviceId.value,
        filter: filterValue,
        full_month: filterValue === 'monthly' // Add this parameter to get full month data
      }
    });

    let data = response.data;
    
    // Group data if not daily
    if (filterValue !== 'daily') {
      data = groupDataByFilter(data, filterValue);  // Use the modified groupDataByFilter
    }
    
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

  const updateTempBatteryChart = (data, filterType) => {
    const labels = data.map(entry => 
      entry.label || formatDateLabel(entry.created_at, filterType)
    );
    const temperatureData = data.map(entry => entry.temperature);
    const batteryData = data.map(entry => entry.battery);

    // Find min/max temperature points
    const maxTemp = Math.max(...temperatureData);
    const minTemp = Math.min(...temperatureData);
    const maxTempIndex = temperatureData.indexOf(maxTemp);
    const minTempIndex = temperatureData.indexOf(minTemp);

    // Find min/max battery points
    const maxBattery = Math.max(...batteryData);
    const minBattery = Math.min(...batteryData);
    const maxBatteryIndex = batteryData.indexOf(maxBattery);
    const minBatteryIndex = batteryData.indexOf(minBattery);

    if (!tempBatteryChart) {
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
              pointBackgroundColor: temperatureData.map((val, idx) => 
                idx === maxTempIndex || idx === minTempIndex ? '#000' : '#ef4444'),
              pointRadius: temperatureData.map((val, idx) => 
                idx === maxTempIndex || idx === minTempIndex ? 6 : 3),
            },
            {
              label: '🔋 Battery (V)',
              data: batteryData,
              borderColor: '#10b981',
              backgroundColor: 'rgba(16, 185, 129, 0.1)',
              tension: 0.4,
              yAxisID: 'y1',
              pointBackgroundColor: batteryData.map((val, idx) => 
                idx === maxBatteryIndex || idx === minBatteryIndex ? '#000' : '#10b981'),
              pointRadius: batteryData.map((val, idx) => 
                idx === maxBatteryIndex || idx === minBatteryIndex ? 6 : 3),
            },
          ],
        },
        options: {
          responsive: true,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            tooltip: {
              callbacks: {
                title: function(context) {
                  const label = context[0].label;
                  if (filterType === 'weekly') {
                    return `Day: ${label}`;
                  } else if (filterType === 'monthly') {
                    return `Date: ${label}`;
                  }
                  return `Time: ${label}`;
                },
                afterBody: function(context) {
                  const index = context[0].dataIndex;
                  const tooltips = [];
                  if (index === maxTempIndex) {
                    tooltips.push('🔥 Highest Temperature');
                  } else if (index === minTempIndex) {
                    tooltips.push('❄️ Lowest Temperature');
                  }
                  if (index === maxBatteryIndex) {
                    tooltips.push('⚡ Highest Battery');
                  } else if (index === minBatteryIndex) {
                    tooltips.push('🪫 Lowest Battery');
                  }
                  return tooltips;
                }
              }
            }
          },
          scales: {
            y: { type: 'linear', position: 'left', title: { display: true, text: 'Temperature (°C)' } },
            y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Battery (V)' } },
          },
        },
      });
    } else {
      tempBatteryChart.data.labels = labels;
      tempBatteryChart.data.datasets[0].data = temperatureData;
      tempBatteryChart.data.datasets[1].data = batteryData;
      
      // Update point styles for min/max
      tempBatteryChart.data.datasets[0].pointBackgroundColor = temperatureData.map((val, idx) => 
        idx === maxTempIndex || idx === minTempIndex ? '#000' : '#ef4444');
      tempBatteryChart.data.datasets[0].pointRadius = temperatureData.map((val, idx) => 
        idx === maxTempIndex || idx === minTempIndex ? 6 : 3);
      
      tempBatteryChart.data.datasets[1].pointBackgroundColor = batteryData.map((val, idx) => 
        idx === maxBatteryIndex || idx === minBatteryIndex ? '#000' : '#10b981');
      tempBatteryChart.data.datasets[1].pointRadius = batteryData.map((val, idx) => 
        idx === maxBatteryIndex || idx === minBatteryIndex ? 6 : 3);
      
      tempBatteryChart.update();
    }
  };

  const updateCountChart = (data, filterType) => {
  const labels = data.map(entry => 
    entry.label || formatDateLabel(entry.created_at, filterType)
  );
  const countData = data.map(entry => entry.count);  // Use total count here, no average

  // Find min/max count points for visual indication
  const maxCount = Math.max(...countData);
  const minCount = Math.min(...countData);
  const maxCountIndex = countData.indexOf(maxCount);
  const minCountIndex = countData.indexOf(minCount);

  if (!countChart) {
    countChart = new Chart(countChartRef.value, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: '🚪 Open Count',
          data: countData,
          backgroundColor: countData.map((val, idx) => 
            idx === maxCountIndex ? '#4c1d95' : 
            idx === minCountIndex ? '#a78bfa' : '#8b5cf6'),
          borderColor: countData.map((val, idx) => 
            idx === maxCountIndex ? '#4c1d95' : 
            idx === minCountIndex ? '#a78bfa' : '#7c3aed'),
          borderWidth: 1,
        }]
      },
      options: {
        responsive: true,
        plugins: {
          tooltip: {
            callbacks: {
              title: function(context) {
                const label = context[0].label;
                if (filterType === 'weekly') {
                  return `Day: ${label}`;
                } else if (filterType === 'monthly') {
                  return `Date: ${label}`;
                }
                return `Time: ${label}`;
              },
              afterBody: function(context) {
                const index = context[0].dataIndex;
                if (index === maxCountIndex) {
                  return ['🚀 Highest Activity'];
                } else if (index === minCountIndex) {
                  return ['🐢 Lowest Activity'];
                }
                return [];
              }
            }
          }
        },
        scales: {
          y: { beginAtZero: true, title: { display: true, text: 'Count' } },
        },
      },
    });
  } else {
    countChart.data.labels = labels;
    countChart.data.datasets[0].data = countData;
    
    // Update bar colors for min/max
    countChart.data.datasets[0].backgroundColor = countData.map((val, idx) => 
      idx === maxCountIndex ? '#4c1d95' : 
      idx === minCountIndex ? '#a78bfa' : '#8b5cf6');
    countChart.data.datasets[0].borderColor = countData.map((val, idx) => 
      idx === maxCountIndex ? '#4c1d95' : 
      idx === minCountIndex ? '#a78bfa' : '#7c3aed');
    
    countChart.update();
  }
};

  const handleRealtimeUpdate = (e) => {
    latestData.value = e.sensorData;

    const newLabel = new Date(e.sensorData.created_at).toLocaleTimeString();

    if (tempBatteryChart) {
      tempBatteryChart.data.labels.push(newLabel);
      tempBatteryChart.data.datasets[0].data.push(e.sensorData.temperature);
      tempBatteryChart.data.datasets[1].data.push(e.sensorData.battery);
      
      // For real-time updates, we won't highlight min/max points as they may change
      tempBatteryChart.data.datasets[0].pointBackgroundColor.push('#ef4444');
      tempBatteryChart.data.datasets[0].pointRadius.push(3);
      tempBatteryChart.data.datasets[1].pointBackgroundColor.push('#10b981');
      tempBatteryChart.data.datasets[1].pointRadius.push(3);
      
      tempBatteryChart.update();
    }

    if (countChart) {
      countChart.data.labels.push(newLabel);
      countChart.data.datasets[0].data.push(e.sensorData.count);
      
      // For real-time updates, use default color
      countChart.data.datasets[0].backgroundColor.push('#8b5cf6');
      countChart.data.datasets[0].borderColor.push('#7c3aed');
      
      countChart.update();
    }
  };

  watch(deviceId, () => {
    fetchData('tempBattery');
    fetchData('count');
  });

  watch(tempBatteryFilter, () => fetchData('tempBattery'));
  watch(countFilter, () => fetchData('count'));

  onMounted(() => {
    fetchData('tempBattery');
    fetchData('count');

    window.Echo.channel('sensor-data')
      .listen('.SensorDataUpdated', (e) => {
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
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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

            <div class="bg-white p-6 rounded-lg shadow">
              <h3 class="text-lg font-medium text-gray-900">Open Count</h3>
              <div class="mt-4">
                <p class="text-sm text-gray-500">Today/Period</p>
                <p class="text-2xl font-semibold">
                  {{ latestData?.count ?? '0' }}
                </p>
              </div>
            </div>
          </div>

          <!-- Temperature/Battery Chart -->
          <div class="bg-white p-6 rounded-lg shadow mt-4">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium text-gray-900">Temperature & Battery</h3>
              <select v-model="tempBatteryFilter" class="border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="daily">Today</option>
                <option value="weekly">This Week</option>
                <option value="monthly">This Month</option>
              </select>
            </div>
            <canvas ref="chartRef" height="120"></canvas>
            <div class="mt-2 text-sm text-gray-500">
              <span class="inline-block w-3 h-3 bg-black rounded-full mr-1"></span> Highest/Lowest points
            </div>
          </div>

          <!-- Door Open Count Chart -->
          <div class="bg-white p-6 rounded-lg shadow mt-4">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium text-gray-900">Door/Window Openings</h3>
              <select v-model="countFilter" class="border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="daily">Today</option>
                <option value="weekly">This Week</option>
                <option value="monthly">This Month</option>
              </select>
            </div>
            <div class="flex justify-between items-center mb-2">
              <p class="text-sm text-gray-500">Total Opens: {{ latestData?.count ?? '0' }}</p>
              <p class="text-sm text-gray-500">Last at: {{ latestData?.created_at ? new Date(latestData.created_at).toLocaleTimeString() : '--' }}</p>
            </div>
            <canvas ref="countChartRef" height="120"></canvas>
            <div class="mt-2 text-sm text-gray-500 flex items-center">
              <span class="inline-flex items-center mr-3">
                <span class="inline-block w-3 h-3 bg-purple-900 rounded-full mr-1"></span> Highest activity
              </span>
              <span class="inline-flex items-center">
                <span class="inline-block w-3 h-3 bg-purple-300 rounded-full mr-1"></span> Lowest activity
              </span>
            </div>
          </div>

        </div>
      </div>
    </AppLayout>
  </template>