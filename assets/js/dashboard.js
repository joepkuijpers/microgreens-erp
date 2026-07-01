async function loadStatus() {

    const response = await fetch("api/status.php");
    const data = await response.json();

    if (document.getElementById("topSensorStatus")) {
        document.getElementById("topSensorStatus").textContent = data.status.toUpperCase();
    }

    if (document.getElementById("topDatabaseStatus")) {
        document.getElementById("topDatabaseStatus").textContent = data.database.toUpperCase();
    }

    if (document.getElementById("topRefresh")) {
        document.getElementById("topRefresh").textContent = data.refresh_seconds + " sec";
    }

    if (document.getElementById("topClock")) {
        document.getElementById("topClock").textContent =
            new Date().toLocaleTimeString('nl-NL');
    }
}

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
    if (document.getElementById("topCpuTemp")) {
    document.getElementById("topCpuTemp").textContent = data.cpu_temp;
}

if (document.getElementById("topCpuLoad")) {
    document.getElementById("topCpuLoad").textContent = data.cpu_load_1m;
}

if (document.getElementById("topRam")) {
    document.getElementById("topRam").textContent =
        data.ram_used_mb + "/" + data.ram_total_mb + " MB";
}

if (document.getElementById("topDisk")) {
    document.getElementById("topDisk").textContent =
        data.disk_used_gb + "/" + data.disk_total_gb + " GB";
}
}

loadStatus();
updateSystemStatus();

setInterval(loadStatus, 1000);
setInterval(updateSystemStatus, 10000);