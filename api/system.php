<?php
header('Content-Type: application/json');
$dbPath = require_once __DIR__ . '/../config/database_path.php';

$isWindows = (stripos(PHP_OS, 'WIN') === 0);

function runCommand($cmd) {
    $output = shell_exec($cmd);
    return $output ? trim($output) : null;
}

// CPU-temperatuur (Linux/Pi only)
$cpuTemp = null;
if (!$isWindows) {
    $cpuTempRaw = runCommand("cat /sys/class/thermal/thermal_zone0/temp");
    if ($cpuTempRaw !== null && is_numeric($cpuTempRaw)) {
        $cpuTemp = round($cpuTempRaw / 1000, 1);
    }
}

// Load average (Linux/Unix only)
$load1 = $load5 = $load15 = null;
if (function_exists('sys_getloadavg')) {
    $load = sys_getloadavg();
    if ($load) {
        $load1  = round($load[0], 2);
        $load5  = round($load[1], 2);
        $load15 = round($load[2], 2);
    }
}

// RAM (Linux only, via /proc/meminfo)
$ramTotal = $ramAvailable = $ramUsed = null;
if (!$isWindows && @file_exists('/proc/meminfo')) {
    $memInfo = @file_get_contents('/proc/meminfo');
    if ($memInfo && preg_match('/MemTotal:\s+(\d+)/', $memInfo, $t) && preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $a)) {
        $ramTotal     = round($t[1] / 1024);
        $ramAvailable = round($a[1] / 1024);
        $ramUsed      = $ramTotal - $ramAvailable;
    }
}

// Disk (platform-onafhankelijk)
$diskRoot = $isWindows ? 'C:/' : '/';
$diskTotal = round(disk_total_space($diskRoot) / 1024 / 1024 / 1024, 1);
$diskFree  = round(disk_free_space($diskRoot) / 1024 / 1024 / 1024, 1);
$diskUsed  = round($diskTotal - $diskFree, 1);

// Uptime + IP (Linux only)
$uptime = $isWindows ? null : runCommand("uptime -p");
$ip     = $isWindows ? null : runCommand("hostname -I | awk '{print $1}'");
$hostname = gethostname();

// DB-grootte (platform-onafhankelijk)
$dbSize = file_exists($dbPath) ? round(filesize($dbPath) / 1024 / 1024, 2) : null;

// Model + OS (Linux/Pi only)
$model = $isWindows ? null : runCommand("cat /proc/device-tree/model 2>/dev/null");
$os = $isWindows ? PHP_OS : runCommand("cat /etc/os-release | grep PRETTY_NAME | cut -d= -f2 | tr -d '\"'");

echo json_encode([
    "status"           => "ok",
    "platform"         => $isWindows ? "windows" : "linux",
    "hostname"         => $hostname,
    "ip"               => $ip,
    "cpu_temp"         => $cpuTemp,
    "cpu_load_1m"      => $load1,
    "cpu_load_5m"      => $load5,
    "cpu_load_15m"     => $load15,
    "ram_used_mb"      => $ramUsed,
    "ram_total_mb"     => $ramTotal,
    "disk_used_gb"     => $diskUsed,
    "disk_total_gb"    => $diskTotal,
    "disk_free_gb"     => $diskFree,
    "uptime"           => $uptime,
    "database_size_mb" => $dbSize,
    "model"            => $model,
    "os"               => $os,
    "timestamp"        => date("Y-m-d H:i:s"),
], JSON_PRETTY_PRINT);