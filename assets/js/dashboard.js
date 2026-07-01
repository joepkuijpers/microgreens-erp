async function updateSystemStatus() {
    const response = await fetch("api/system.php");
    const data = await response.json();

    if (document.getElementById("cpuTemp")) {
        document.getElementById("cpuTemp").textContent = data.cpu_temp;
    }

    if (document.getElementById("cpuLoad")) {
        document.getElementById("cpuLoad").textContent = data.cpu_load_1m;
    }

    if (document.getElementById("ramUsed")) {
        document.getElementById("ramUsed").textContent =
            data.ram_used_mb + " / " + data.ram_total_mb;
    }

    if (document.getElementById("diskUsed")) {
        document.getElementById("diskUsed").textContent =
            data.disk_used_gb + " / " + data.disk_total_gb;
    }

    if (document.getElementById("ip")) {
        document.getElementById("ip").textContent = data.ip;
    }

    if (document.getElementById("uptime")) {
        document.getElementById("uptime").textContent = data.uptime;
    }
}

updateSystemStatus();
setInterval(updateSystemStatus, 10000);