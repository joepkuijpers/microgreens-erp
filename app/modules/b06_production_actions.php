<?php
/**
 * Module: b06_production_actions.php
 * Doel: Voert automatische batch-rotatie uit (oogst -> nieuw)
 * Engine: production_planner_engine.php
 */

// 1. Database connectie laden
// Zorg dat dit bestand bestaat in app/includes/
\ = __DIR__ . '/../includes/db_connection.php';
if (file_exists(\)) {
    require_once \;
} else {
    // Fallback als db_connection.php nog niet bestaat (voor testdoeleinden)
    // In productie moet je dit bestand echt maken!
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connectie bestand niet gevonden. Maak app/includes/db_connection.php']);
    exit;
}

// 2. Engine laden
require_once __DIR__ . '/../includes/production_planner_engine.php';

try {
    // 3. Connectie ophalen
    \ = getDbConnection(); 
    
    // 4. Functie uitvoeren
    \ = rotateActiveBatch(\);
    
    // 5. Resultaat teruggeven als JSON
    header('Content-Type: application/json');
    echo json_encode(\, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception \) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Er ging iets mis in de module',
        'message' => \->getMessage(),
        'file' => \->getFile()
    ]);
}
