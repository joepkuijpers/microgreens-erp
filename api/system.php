<?php
header('Content-Type: application/json');

function runCommand($cmd) {
    $output = shell_exec($cmd);
    return $output ? trim($output) : null;
}

$cpuTempRaw = runCommand("cat /sys/class/thermal/thermal_zone0/temp");
$cpuTemp = $cpuTempRaw ? round($cpuTempRaw / 1000, 1) : null;

$load = sys_getloadavg();

$memInfo = file_get_contents('/proc/meminfo');
preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatch);
preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availableMatch);

$ramTotal = isset($totalMatch[1]) ? round($totalMatch[1] / 1024) : null;
$ramAvailable = isset($availableMatch[1]) ? round($availableMatch[1] / 1024) : null;
$ramUsed = ($ramTotal && $ramAvailable) ? $ramTotal - $ramAvailable : null;

$diskTotal = round(disk_total_space("/") / 1024 / 1024 / 1024, 1);
$diskFree = round(disk_free_space("/") / 1024 / 1024 / 1024, 1);
$diskUsed = round($diskTotal - $diskFree, 1);

$uptime = runCommand("uptime -p");
$ip = runCommand("hostname -I | awk '{print $1}'");
$hostname = gethostname();

$dbPath = __DIR__ . '/../database/MicrogreensERP_Live.sqlite';
$dbSize = file_exists($dbPath) ? round(filesize($dbPath) / 1024 / 1024, 2) : null;

$model = runCommand("cat /proc/device-tree/model 2>/dev/null");
$os = runCommand("cat /etc/os-release | grep PRETTY_NAME | cut -d= -f2 | tr -d '\"'");

echo json_encode([
    "status" => "ok",
    "hostname" => $hostname,
    "ip" => $ip,
    "cpu_temp" => $cpuTemp,
    "cpu_load_1m" => round($load[0], 2),
    "cpu_load_5m" => round($load[1], 2),
    "cpu_load_15m" => round($load[2], 2),
    "ram_used_mb" => $ramUsed,
    "ram_total_mb" => $ramTotal,
    "disk_used_gb" => $diskUsed,
    "disk_total_gb" => $diskTotal,
    "disk_free_gb" => $diskFree,
    "uptime" => $uptime,
    "database_size_mb" => $dbSize,
    "model" => $model,
    "os" => $os,
    "timestamp" => date("Y-m-d H:i:s")
], JSON_PRETTY_PRINT);
