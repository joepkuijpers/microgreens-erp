<?php
$db = new PDO("sqlite:/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite"); $stmt = $db->query("SELECT value FROM system_settings WHERE key = 'climate_watchdog_enabled'"); $enabled = $stmt ? $stmt->fetchColumn() : "0"; if ($enabled !== "1") { exit("Watchdog muted via ERP setting\n"); }
// TEST MODE: Mute notifications
exit("Watchdog muted during testing\n");
chdir(__DIR__);
$dbPath = '/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite';

$db = new SQLite3($dbPath);
$db->busyTimeout(5000);
$db->exec("PRAGMA journal_mode=WAL;");

$telegram_bot_token = '8805308830:AAG5lU3uG4FnUkvT6WBC7g4VrfBEj43OBUI';
$telegram_chat_id = '8259850155';

// 1. Fetch last reading from sensor_log
$query = "SELECT timestamp, STRFTIME('%s', 'now', 'localtime') - STRFTIME('%s', timestamp) AS seconds_ago FROM sensor_log ORDER BY id DESC LIMIT 1";
$result = $db->querySingle($query, true);

$seconds_ago = $result['seconds_ago'] ?? 9999;
$minutes_ago = floor($seconds_ago / 60);

// 2. Trigger alarm if data gap > 15 minutes
if ($minutes_ago > 15) {
    $message = "🚨 *MICROGREENS ERP WATCHDOG ALARM*\n\n⚠️ No new climate data received for {$minutes_ago} minutes!\nLast record: " . ($result['timestamp'] ?? 'Unknown');
    
    $url = "https://api.telegram.org/bot{$telegram_bot_token}/sendMessage";
    $data = [
        'chat_id' => $telegram_chat_id,
        'text' => $message,
        'parse_mode' => 'Markdown',
        'disable_notification' => false
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);
    echo "ALERT: Gap of {$minutes_ago}m detected. Telegram sent.\n";
} else {
    echo "STATUS OK: Last data was {$minutes_ago}m ago.\n";
}
