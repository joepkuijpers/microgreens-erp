async function loadDashboard() {
    const response = await fetch('api/dashboard.php');
    const data = await response.json();

    document.getElementById("products").innerText = data.products;
    document.getElementById("inventory").innerText = data.inventory;
    document.getElementById("batches").innerText = data.batches;
    document.getElementById("sales").innerText = data.sales;
    document.getElementById("customers").innerText = data.customers;
    document.getElementById("suppliers").innerText = data.suppliers;
    document.getElementById("harvests").innerText = data.harvests;
    document.getElementById("revenue").innerText = "€ " + data.revenue;
    document.getElementById("expenses").innerText = "€ " + data.expenses;
    document.getElementById("profit").innerText = "€ " + data.profit;
}

async function loadSensors() {
    const response = await fetch('api/sensors.php');
    const data = await response.json();

    document.getElementById("temperature").innerText = data.temperature + " °C";
    document.getElementById("humidity").innerText = data.humidity + " %";
    document.getElementById("pressure").innerText = data.pressure + " hPa";
    document.getElementById("light").innerText = data.light + " lux";

    const sensorTimestamp = document.getElementById("sensorTimestamp");
    if (sensorTimestamp) {
        sensorTimestamp.innerText = data.timestamp ?? "--";
    }
}

async function loadStatus() {
    const response = await fetch('api/status.php');
    const data = await response.json();

    const status = document.getElementById("sensorStatus");
    const last = document.getElementById("statusLastUpdate");

    if (!status || !last) return;

    if (data.status === "online") {
        status.innerHTML = '<span class="alert-ok">🟢 Sensoren Online</span>';
    } else {
        status.innerHTML = '<span class="alert-danger">🔴 Sensoren Offline</span>';
    }

    last.innerText = data.last_update ?? "--";
}

async function loadAlerts() {
    const response = await fetch('api/sensors.php');
    const data = await response.json();

    const alerts = document.getElementById("alerts");
    if (!alerts) return;

    let items = [];

    if (Number(data.light) < 100) {
        items.push('<span class="alert-danger">🔴 Licht is laag: ' + data.light + ' lux</span>');
    }

    if (Number(data.temperature) > 28) {
        items.push('<span class="alert-danger">🔴 Temperatuur te hoog: ' + data.temperature + ' °C</span>');
    }

    if (Number(data.temperature) < 18) {
        items.push('<span class="alert-warning">🔵 Temperatuur te laag: ' + data.temperature + ' °C</span>');
    }

    if (items.length === 0) {
        items.push('<span class="alert-ok">🟢 Alle systemen werken normaal</span>');
    }

    alerts.innerHTML = items.map(item => "<li>" + item + "</li>").join("");
}

loadDashboard();
loadSensors();
loadStatus();
loadAlerts();

setInterval(loadDashboard, 5000);
setInterval(loadSensors, 5000);
setInterval(loadStatus, 60000);
setInterval(loadAlerts, 60000);