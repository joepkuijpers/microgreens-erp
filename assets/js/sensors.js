let temperatureChart = null;

async function loadCharts() {

    const response = await fetch("api/history.php");
    const data = await response.json();

    const labels = data.map(item => item.timestamp);
    const temperatures = data.map(item => item.temperature);
    const humidity = data.map(item => item.humidity);
    const pressure = data.map(item => item.pressure);
    const light = data.map(item => item.light);

    const canvas = document.getElementById("temperatureChart");

    if (!canvas) return;

    if (temperatureChart !== null) {
        temperatureChart.destroy();
    }

    temperatureChart = new Chart(canvas, {

        type: "line",

        data: {

            labels: labels,

            datasets: [

                {
                    label: "Temperatuur (°C)",
                    data: temperatures,
                    borderWidth: 2,
                    tension: 0.3
                },

                {
                    label: "Luchtvochtigheid (%)",
                    data: humidity,
                    borderWidth: 2,
                    tension: 0.3
                },

                {
                    label: "Luchtdruk (hPa)",
                    data: pressure,
                    borderWidth: 2,
                    tension: 0.3,
                    hidden: true
                },

                {
                    label: "Licht (lux)",
                    data: light,
                    borderWidth: 2,
                    tension: 0.3,
                    hidden: true
                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            interaction: {
                mode: "index",
                intersect: false
            },

            plugins: {

                legend: {
                    display: true
                }

            },

            scales: {

                x: {
                    display: true
                },

                y: {
                    beginAtZero: false
                }

            }

        }

    });

}

loadCharts();

setInterval(loadCharts, 5000);