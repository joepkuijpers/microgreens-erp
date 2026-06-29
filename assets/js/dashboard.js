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

}

loadDashboard();
loadSensors();

setInterval(loadDashboard, 5000);
setInterval(loadSensors, 5000);