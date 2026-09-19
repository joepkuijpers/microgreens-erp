<?php
/**
 * Database Connectie voor Microgreens ERP
 * Versie 4: Hardcoded absoluut pad (Garantie voor Windows)
 */

function getDbConnection() {
    // We gebruiken het absolute pad dat we weten dat werkt
    $rootDir = 'C:/Users/joepk/Downloads/microgreens-erp';
    
    // Bouw het pad naar de database
    $dbPath = $rootDir . '/database/MicrogreensERP_Development.sqlite';

    // Normaliseer slashes voor Windows
    $dbPath = str_replace('/', DIRECTORY_SEPARATOR, $dbPath);

    // Debug: (Optioneel)
    // error_log("DB Path: " . $dbPath);

    if (!file_exists($dbPath)) {
        throw new Exception("Database bestand niet gevonden op: " . $dbPath);
    }
    
    if (!is_readable($dbPath)) {
        throw new Exception("Database bestand is niet leesbaar: " . $dbPath);
    }

    try {
        $dsn = "sqlite:" . $dbPath;
        $db = new PDO($dsn);
        
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec("PRAGMA foreign_keys = ON");
        
        return $db;
    } catch (PDOException $e) {
        throw new Exception("Database verbinding mislukt: " . $e->getMessage());
    }
}