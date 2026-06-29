async function loadDashboard(){

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

loadDashboard();

setInterval(loadDashboard,5000);