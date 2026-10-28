let sensorChart = null;
let currentRange = '24h';

async function loadCharts(range = currentRange) {
    currentRange = range;

    const response = await fetch('api/history.php?range=' + range);
    const data = await response.json();

    const labels = data.map(row => row.timestamp);
    const temperatures = data.map(row => Number(row.temperature));
    const humidity = data.map(row => Number(row.humidity));
    const light = data.map(row => Number(row.light));

    const ctx = document.getElementById('temperatureChart');
    if (!ctx) return;

    if (sensorChart) {
        sensorChart.destroy();
    }

    sensorChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Temperatuur °C',
                    data: temperatures
                },
                {
                    label: 'Luchtvochtigheid %',
                    data: humidity
                },
                {
                    label: 'Licht lux',
                    data: light
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

loadCharts('24h');

setInterval(() => {
    loadCharts(currentRange);
}, 60000);