async function loadStatus() {
    try {
        const response = await fetch("api/status.php");
        const data = await response.json();

        const topSensorStatus = document.getElementById("topSensorStatus");
        if (topSensorStatus) {
            topSensorStatus.textContent = (data.status || "--").toUpperCase();
        }

        const topDatabaseStatus = document.getElementById("topDatabaseStatus");
        if (topDatabaseStatus) {
            topDatabaseStatus.textContent = (data.database || "--").toUpperCase();
        }

        const databaseStatus = document.getElementById("databaseStatus");
        if (databaseStatus) {
            databaseStatus.textContent = (data.database || "--").toUpperCase();
        }

        const statusLastUpdate = document.getElementById("statusLastUpdate");
        if (statusLastUpdate) {
            statusLastUpdate.textContent = data.last_update || "--";
        }

        const statusSecondsAgo = document.getElementById("statusSecondsAgo");
        if (statusSecondsAgo) {
            statusSecondsAgo.textContent = data.seconds_ago !== null ? data.seconds_ago + " sec" : "--";
        }

        const statusRefresh = document.getElementById("statusRefresh");
        if (statusRefresh) {
            statusRefresh.textContent = data.refresh_seconds ? data.refresh_seconds + " sec" : "--";
        }

        const topRefresh = document.getElementById("topRefresh");
        if (topRefresh) {
            topRefresh.textContent = data.refresh_seconds ? data.refresh_seconds + " sec" : "--";
        }

        const topClock = document.getElementById("topClock");
        if (topClock) {
            topClock.textContent = new Date().toLocaleTimeString("nl-NL");
        }
    } catch (error) {
        console.error("Status update failed:", error);
    }
}

async function updateSystemStatus() {
    try {
        const response = await fetch("api/system.php");
        const data = await response.json();

        const cpuTemp = document.getElementById("cpuTemp");
        if (cpuTemp) {
            cpuTemp.textContent = data.cpu_temp ?? "--";
        }

        const cpuLoad = document.getElementById("cpuLoad");
        if (cpuLoad) {
            cpuLoad.textContent = data.cpu_load_1m ?? "--";
        }

        const ramUsed = document.getElementById("ramUsed");
        if (ramUsed) {
            ramUsed.textContent =
                (data.ram_used_mb ?? "--") + " / " + (data.ram_total_mb ?? "--") + " MB";
        }

        const diskUsed = document.getElementById("diskUsed");
        if (diskUsed) {
            diskUsed.textContent =
                (data.disk_used_gb ?? "--") + " / " + (data.disk_total_gb ?? "--") + " GB";
        }

        const ip = document.getElementById("ip");
        if (ip) {
            ip.textContent = data.ip || "--";
        }

        const uptime = document.getElementById("uptime");
        if (uptime) {
            uptime.textContent = data.uptime || "--";
        }

        const topCpuTemp = document.getElementById("topCpuTemp");
        if (topCpuTemp) {
            topCpuTemp.textContent = data.cpu_temp ?? "--";
        }

        const topCpuLoad = document.getElementById("topCpuLoad");
        if (topCpuLoad) {
            topCpuLoad.textContent = data.cpu_load_1m ?? "--";
        }

        const topRam = document.getElementById("topRam");
        if (topRam) {
            topRam.textContent =
                (data.ram_used_mb ?? "--") + "/" + (data.ram_total_mb ?? "--") + " MB";
        }

        const topDisk = document.getElementById("topDisk");
        if (topDisk) {
            topDisk.textContent =
                (data.disk_used_gb ?? "--") + "/" + (data.disk_total_gb ?? "--") + " GB";
        }
    } catch (error) {
        console.error("System status update failed:", error);
    }
}

loadStatus();
updateSystemStatus();

setInterval(loadStatus, 1000);
setInterval(updateSystemStatus, 10000);