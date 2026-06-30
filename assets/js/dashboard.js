async function loadStatus() {

    const response = await fetch('api/status.php');
    const data = await response.json();

    const status = document.getElementById("sensorStatus");
    const last = document.getElementById("statusLastUpdate");
    const seconds = document.getElementById("statusSecondsAgo");
    const refresh = document.getElementById("statusRefresh");
    const database = document.getElementById("databaseStatus");

    const topSensor = document.getElementById("topSensorStatus");
    const topDatabase = document.getElementById("topDatabaseStatus");
    const topRefresh = document.getElementById("topRefresh");
    const topClock = document.getElementById("topClock");

    if (status) {
        if (data.status === "online") {
            status.innerHTML = '<span class="alert-ok">🟢 Online</span>';
        } else {
            status.innerHTML = '<span class="alert-danger">🔴 Offline</span>';
        }
    }

    if (last) {
        last.innerText = data.last_update ?? "--";
    }

    if (seconds) {
        seconds.innerText = data.seconds_ago + " sec geleden";
    }

    if (refresh) {
        refresh.innerText = data.refresh_seconds + " sec";
    }

    if (database) {
        database.innerHTML = '<span class="alert-ok">OK</span>';
    }

    if (topSensor) {
        topSensor.innerText = data.status.toUpperCase();
    }

    if (topDatabase) {
        topDatabase.innerText = data.database.toUpperCase();
    }

    if (topRefresh) {
        topRefresh.innerText = data.refresh_seconds + " sec";
    }

    if (topClock) {
        topClock.innerText = new Date().toLocaleString('nl-NL');
    }
}