let temperatureChart = null;

async function loadCharts() {
    const response = await fetch("api/history.php");
    const data = await response.json();

    const labels = data.map(row => row.timestamp);
    const temperatures = data.map(row => row.temperature);
    const humidity = data.map(row => row.humidity);
    const pressure = data.map(row => row.pressure);
    const light = data.map(row => row.light);

    const canvas = document.getElementById("temperatureChart");
    if (!canvas) return;

    if (temperatureChart === null) {
        temperatureChart = new Chart(canvas, {
            type: "line",
            data: {
                labels: labels,
                datasets: [
                    { label: "Temperatuur °C", data: temperatures, borderWidth: 2, tension: 0.3 },
                    { label: "Luchtvochtigheid %", data: humidity, borderWidth: 2, tension: 0.3 },
                    { label: "Licht lux", data: light, borderWidth: 2, tension: 0.3 },
                    { label: "Luchtdruk hPa", data: pressure, borderWidth: 2, tension: 0.3, hidden: true }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    } else {
        temperatureChart.data.labels = labels;
        temperatureChart.data.datasets[0].data = temperatures;
        temperatureChart.data.datasets[1].data = humidity;
        temperatureChart.data.datasets[2].data = light;
        temperatureChart.data.datasets[3].data = pressure;
        temperatureChart.update();
    }
}

loadCharts();
setInterval(loadCharts, 60000);