<?php
/**
 * B10 Sensor Bridge — schrijft sensor_log-rijen automatisch naar climate_data.
 * Wordt aangeroepen vanuit b10_sensor_sync.php (al geïntegreerd in POST-handling).
 * Voor toekomstige Python-sensoren: roep sensorBridgeInsert($db, $rack_id, $temp, $hum) aan.
 */
function sensorBridgeInsert(PDO $db, string $rack_id, float $temp, float $hum): bool {
    try {
        $stmt = $db->prepare("INSERT INTO climate_data (timestamp, temperature, humidity, rack_id) VALUES (datetime('now'), ?, ?, ?)");
        return $stmt->execute([$temp, $hum, $rack_id]);
    } catch (Exception $e) {
        return false;
    }
}
