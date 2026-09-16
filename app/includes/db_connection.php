<?php
/**
 * Database Connectie voor Microgreens ERP
 * Maakt een PDO verbinding met de SQLite database
 */

function getDbConnection() {
    // Pad naar de database (relatief aan dit bestand)
    \ = __DIR__ . '/../database/MicrogreensERP_Development.sqlite';

    // Controleer of het bestand bestaat
    if (!file_exists(\)) {
        throw new Exception("Database bestand niet gevonden op: " . \);
    }

    // Maak de verbinding
    try {
        \ = new PDO("sqlite:" . \);
        
        // Zet error mode op Exception (belangrijk voor debugging)
        \->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Zet default fetch mode op Associative Array
        \->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Zet Foreign Keys aan (standaard uit in SQLite!)
        \->exec("PRAGMA foreign_keys = ON");
        
        return \;
    } catch (PDOException \) {
        throw new Exception("Database verbinding mislukt: " . \->getMessage());
    }
}
