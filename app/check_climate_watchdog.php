<?php
// check_climate_watchdog.php — de échte motor. Draait gepland elke ~5 min.
require_once __DIR__ . '/../app/includes/config.php';
require_once __DIR__ . '/includes/watchdog_engine.php';

// --- instellingen ---
$token = getenv('Watchdogbot');
$chat  = getenv('WatchdogbotChat');
$gap_min      = 15;   // geen data langer dan dit = alarm
$temp_min = 15; $temp_max = 30;   // °C
$hum_min  = 40; $hum_max  = 85;   // % RV

// --- DB via config (geen hardcoded pad meer) ---
$db = new PDO('sqlite:' . ERP_DB);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- 1. hartslag: is er verse data? ---
$row = $db->query("SELECT id, timestamp, temperature, humidity, rack_id
                   FROM sensor_log WHERE source='IOT' ORDER BY id DESC LIMIT 1")
          ->fetch(PDO::FETCH_ASSOC);

$alerts = [];
if (!$row) {
    $alerts[] = ['CRITICAL', 'GEEN_DATA', 'Er is nog geen enkele sensor meting in de databank.'];
} else {
    $minutes_ago = floor((time() - strtotime($row['timestamp'])) / 60);
    if ($minutes_ago > $gap_min) {
        $alerts[] = ['WARNING', 'SENSOR_GAP', "Geen nieuwe meting al {$minutes_ago} min. Laatste: {$row['timestamp']}."];
    }
    // --- 2. klimaatgrenzen (alleen als er een waarde is) ---
    if ($row['temperature'] !== null && ($row['temperature'] < $temp_min || $row['temperature'] > $temp_max)) {
        $alerts[] = ['CRITICAL', 'TEMP_UIT_BAND', "Temperatuur {$row['temperature']}°C buiten {$temp_min}-{$temp_max}°C (rek {$row['rack_id']})."];
    }
    if ($row['humidity'] !== null && ($row['humidity'] < $hum_min || $row['humidity'] > $hum_max)) {
        $alerts[] = ['WARNING', 'HUM_UIT_BAND', "Vochtigheid {$row['humidity']}% buiten {$hum_min}-{$hum_max}% (rek {$row['rack_id']})."];
    }
}

// --- 3. hartslag wegslaan (zodat B28 "systeem live" kan tonen) ---
watchdogHeartbeat();

// --- 4. alerts wegschrijven (dubbele alerts voorkomen) ---
$ins = $db->prepare("INSERT INTO system_alerts (alert_type, severity, message, resolved)
                     VALUES (?, ?, ?, 0)");
foreach ($alerts as [$sev, $type, $msg]) {
    $exists = $db->prepare("SELECT COUNT(*) FROM system_alerts
                            WHERE resolved=0 AND alert_type=? AND message=?");
    $exists->execute([$type, $msg]);
    if (!$exists->fetchColumn()) {
        $ins->execute([$type, $sev, $msg]);
        watchdogLog('Alert aangemaakt', ['type' => $type, 'msg' => $msg]);
    }
}

// --- 5. Telegram (alleen als token + chat bestaan) ---
if (!empty($alerts) && $token && $chat) {
    $txt = "🚨 MICROGREENS ERP\n" . implode("\n", array_map(fn($a) => "• {$a[1]}: {$a[2]}", $alerts));
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query(['chat_id' => $chat, 'text' => $txt]),
        'timeout' => 10,
    ]]);
    @file_get_contents("https://api.telegram.org/bot{$token}/sendMessage", false, $ctx);
}

echo empty($alerts) ? "OK: alles binnen normen.\n" : "ALERTS: " . count($alerts) . "\n";