async function loadCharts() {
    const response = await fetch('api/history.php');
    const data = await response.json();

    const labels = data.map(row => row.timestamp);
    const temperatures = data.map(row => row.temperature);

    const canvas = document.getElementById('temperatureChart');
    if (!canvas) return;

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Temperatuur °C',
                data: temperatures,
                borderWidth: 2,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

loadCharts();
