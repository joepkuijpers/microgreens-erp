<?php
/**
 * Database Connection - Universal (Windows & Linux)
 * Kiest automatisch het juiste pad op basis van het besturingssysteem.
 */

function getDbConnection() {
    // Bepaal het basispad
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows Development
        $baseDir = 'C:/Users/joepk/Downloads/microgreens-erp/database';
        $dbName = 'MicrogreensERP_Development.sqlite';
    } else {
        // Linux Production (Raspberry Pi)
        $baseDir = '/var/www/html/microgreens/PHP/database';
        $dbName = 'MicrogreensERP_Live.sqlite';
    }

    $dbPath = $baseDir . '/' . $dbName;

    if (!file_exists($dbPath)) {
        // Uitgebreide foutmelding voor debugging
        throw new Exception("Database bestand niet gevonden op: $dbPath\nBestaande bestanden in map: " . print_r(scandir($baseDir), true));
    }

    try {
        $db = new PDO("sqlite:$dbPath");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $db;
    } catch (PDOException $e) {
        throw new Exception("Database connectie mislukt: " . $e->getMessage());
    }
}
?>
