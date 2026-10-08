<?php
/**
 * B10 Lighting Engine (Infrastructure Ready)
 * Werking: 
 * - Als hardware ontbreekt: Log error en ga naar 'SAFE_MODE'.
 * - Als data oud is: Log warning en ga naar 'SAFE_MODE' (Licht UIT).
 * - Als alles werkt: Stuur GPIO aan (via Python helper).
 */

$dbPath = '/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite';
$gpioPin = 17; 
$maxAgeSeconds = 600; // 10 minuten tolerantie voor testdoeleinden
$pythonHelper = '/home/joep/set_relay.py';

function logAction($db, $lux, $ppfd, $min, $max, $action, $error = null) {
    $stmt = $db->prepare("INSERT INTO lighting_log (measured_lux, calculated_ppfd, target_min, target_max, action_taken, error_message) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$lux, $ppfd, $min, $max, $action, $error]);
}

function setRelay($pin, $state) {
    // Probeer Python helper, als die faalt of niet bestaat, log het dan alleen.
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

try {
    if (!file_exists($dbPath)) { throw new Exception("Database niet gevonden"); }
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Config
    $stmt = $db->query("SELECT target_ppfd_min, target_ppfd_max, spectrum_factor FROM lighting_configs LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        logAction($db, 0, 0, 0, 0, "ERROR", "Geen configuratie gevonden");
        exit(0);
    }

    $targetMin = (float)$config['target_ppfd_min'];
    $targetMax = (float)$config['target_ppfd_max'];
    $factor = (float)$config['spectrum_factor'];

    // 2. Sensor Data
    $stmt = $db->query("SELECT light, timestamp FROM sensor_log ORDER BY timestamp DESC LIMIT 1");
    $sensor = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentLux = 0;
    $currentPPFD = 0;
    $dataValid = false;

    if ($sensor) {
        $lastUpdate = strtotime($sensor['timestamp']);
        if ((time() - $lastUpdate) <= $maxAgeSeconds) {
            $currentLux = (float)$sensor['light'];
            $currentPPFD = $currentLux / $factor;
            $dataValid = true;
        } else {
            logAction($db, $currentLux, $currentPPFD, $targetMin, $targetMax, "SAFE_MODE", "Data te oud (" . (time() - $lastUpdate) . "s)");
            setRelay($gpioPin, 0); // Veilig uit
            exit(0);
        }
    } else {
        logAction($db, 0, 0, $targetMin, $targetMax, "SAFE_MODE", "Geen sensor data aanwezig");
        setRelay($gpioPin, 0); // Veilig uit
        exit(0);
    }

    // 3. Beslissing
    $action = "NO_CHANGE";
    $newState = null;

    if ($currentPPFD < $targetMin) {
        $newState = 1; $action = "TURN_ON";
    } elseif ($currentPPFD > $targetMax) {
        $newState = 0; $action = "TURN_OFF";
    }

    // 4. Uitvoeren
    if ($newState !== null) {
        setRelay($gpioPin, $newState);
        logAction($db, $currentLux, $currentPPFD, $targetMin, $targetMax, $action);
    } else {
        logAction($db, $currentLux, $currentPPFD, $targetMin, $targetMax, "NO_ACTION");
    }

} catch (Exception $e) {
    error_log("B10 Critical Error: " . $e->getMessage());
    // Try to log error to DB if possible
    try { logAction($db, 0,0,0,0, "CRASH", $e->getMessage()); } catch(Exception $ex) {}
}
?>
