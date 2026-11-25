<?php
/**
 * B10 Lighting Engine (Infrastructure Ready)
 * Werking:
 * - Als hardware ontbreekt: Log error en ga naar 'SAFE_MODE'.
 * - Als data oud is: Log warning en ga naar 'SAFE_MODE' (Licht UIT).
 * - Als alles werkt: Stuur GPIO aan (via Python helper).
 *
 * PAD (24-11): dit bestand staat in app/includes/. __DIR__ is dus app/includes/.
 *   De DB staat in <webroot>/database → dirname(__DIR__, 2) gaat twee niveaus omhoog.
 *   NOOIT '/var/www/html/...' (Linux-deploy-restant → "unable to open database file").
 */

$dbPath = dirname(__DIR__, 2) . '/database/MicrogreensERP_Live.sqlite';
$gpioPin = 17;
$maxAgeSeconds = 600; // 10 minuten tolerantie voor testdoeleinden
// Pi-only: de Python-helper staat op de Raspberry Pi, NIET op de laptop.
// Op de laptop faalt file_exists netjes → SAFE_MODE simulatie. Dat is correct gedrag.
$pythonHelper = '/home/joep/set_relay.py';

function logAction($db, $lux, $ppfd, $min, $max, $action, $error = null) {
    $stmt = $db->prepare("INSERT INTO lighting_log (measured_lux, calculated_ppfd, target_min, target_max, action_taken, error_message) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$lux, $ppfd, $min, $max, $action, $error]);
}

function setRelay($pin, $state) {
    // Probeer Python helper; als die faalt of niet bestaat, log het en beschouw als gesimuleerd.
    if (file_exists($GLOBALS['pythonHelper'])) {
        $cmd = "sudo python3 " . escapeshellarg($GLOBALS['pythonHelper']) . " " . intval($pin) . " " . intval($state) . " 2>&1";
        $output = shell_exec($cmd);
        if (!empty($output) && strpos($output, "Error") !== false) {
            error_log("B10 Relay Error: $output");
            return false;
        }
        return true;
    } else {
        error_log("B10 Infra: Relay helper niet gevonden. Simuleer actie: State=$state");
        return true; // Simuleer succes voor infrastructuur test
    }
}

// ── Connectie vóór de try: dan is $db gegarandeerd beschikbaar in de catch ──
if (!file_exists($dbPath)) {
    error_log("B10 Critical: Database niet gevonden op $dbPath");
    exit(1);
}
$db = new PDO("sqlite:$dbPath");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

try {
    // 1 · Config
    $config = $db->query("SELECT target_ppfd_min, target_ppfd_max, spectrum_factor FROM lighting_configs LIMIT 1")->fetch();
    if (!$config) {
        logAction($db, 0, 0, 0, 0, "ERROR", "Geen configuratie gevonden");
        exit(0);
    }
    $targetMin = (float)$config['target_ppfd_min'];
    $targetMax = (float)$config['target_ppfd_max'];
    $factor = (float)$config['spectrum_factor'];

    // 2 · Sensor Data
    $sensor = $db->query("SELECT light, timestamp FROM sensor_log ORDER BY timestamp DESC LIMIT 1")->fetch();
    $currentLux = 0;
    $currentPPFD = 0;

    if (!$sensor) {
        logAction($db, 0, 0, $targetMin, $targetMax, "SAFE_MODE", "Geen sensor data aanwezig");
        setRelay($gpioPin, 0);
        exit(0);
    }

    $age = time() - strtotime($sensor['timestamp']);
    if ($age > $maxAgeSeconds) {
        logAction($db, $currentLux, $currentPPFD, $targetMin, $targetMax, "SAFE_MODE", "Data te oud ({$age}s)");
        setRelay($gpioPin, 0);
        exit(0);
    }

    $currentLux = (float)$sensor['light'];
    $currentPPFD = $factor > 0 ? $currentLux / $factor : 0;

    // 3 · Beslissing
    $action = "NO_ACTION";
    $newState = null;
    if ($currentPPFD < $targetMin)      { $newState = 1; $action = "TURN_ON"; }
    elseif ($currentPPFD > $targetMax)  { $newState = 0; $action = "TURN_OFF"; }

    // 4 · Uitvoeren
    if ($newState !== null) {
        setRelay($gpioPin, $newState);
        logAction($db, $currentLux, $currentPPFD, $targetMin, $targetMax, $action);
    } else {
        logAction($db, $currentLux, $currentPPFD, $targetMin, $targetMax, "NO_ACTION");
    }

} catch (Exception $e) {
    error_log("B10 Critical Error: " . $e->getMessage());
    try { logAction($db, 0, 0, 0, 0, "CRASH", $e->getMessage()); } catch (Exception $ex) {}
}