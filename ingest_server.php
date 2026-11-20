<?php
// ingest_server.php — Pi sensor receiver. Run: php -S 0.0.0.0:8090 ingest_server.php
require __DIR__ . '/app/includes/config.php';
$db = new PDO('sqlite:' . ERP_DB);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$map = [];
$mapFile = __DIR__ . '/sensor_map.json';
if (is_file($mapFile)) { $map = json_decode(file_get_contents($mapFile), true) ?: []; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(200); echo "ingest ok\n"; exit; }

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['readings'])) { http_response_code(400); echo "bad json\n"; exit; }

$source = $data['source'] ?? 'unknown';
$rack   = $map[$source] ?? null;

$up = $db->prepare("
    INSERT INTO sensor_log (timestamp, rack_id, status, source, temperature, humidity, pressure, light)
    VALUES (?, ?, 'IOT', 'IOT', ?, ?, ?, ?)
    ON CONFLICT(timestamp) WHERE source='IOT' DO UPDATE SET
      temperature = COALESCE(temperature, excluded.temperature),
      humidity    = COALESCE(humidity,    excluded.humidity),
      pressure    = COALESCE(pressure,    excluded.pressure),
      light       = COALESCE(light,       excluded.light)
");

$seen = []; $new = 0;
foreach ($data['readings'] as $r) {
    $ts  = $r['timestamp'] ?? null;
    $key = $r['sensor_key'] ?? '';
    $val = $r['value'] ?? null;
    if ($ts === null || strpos($key, 'error') !== false) continue;
    $col = ['temperatuur'=>'temperature','luchtvochtigheid'=>'humidity','luchtdruk'=>'pressure','licht'=>'light'][$key] ?? null;
    if ($col === null) continue;
    $vals = ['temperature'=>null,'humidity'=>null,'pressure'=>null,'light'=>null];
    $vals[$col] = $val;
    $up->execute([$ts, $rack, $vals['temperature'], $vals['humidity'], $vals['pressure'], $vals['light']]);
    if (!isset($seen[$ts])) { $seen[$ts] = true; $new++; }
}

file_put_contents(__DIR__ . '/ingest.log',
    date('c') . " src=$source rack=" . ($rack ?? 'NULL') .
    " in=" . count($data['readings']) . " rows=$new\n", FILE_APPEND);
echo json_encode(['received' => count($data['readings']), 'rows' => $new]);