<?php
/**
 * Microgreens ERP - Centrale Router
 * Gebruik: jouwdomein.nl/?module=naam_van_module
 */

// Foutmeldingen tonen (zet op 0 in productie)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Bepaal welke module gevraagd wordt
$module = $_GET['module'] ?? 'home';

// 2. Veiligheid: Alleen letters, cijfers en underscores toestaan
$module = preg_replace('/[^a-z0-9_]/', '', $module);

// 3. Als er geen module is, toon een welkomstpagina
if ($module === 'home' || $module === '') {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🌱 Microgreens ERP Systeem</h1>";
    echo "<p>Systeem is actief. Kies een module:</p>";
    echo "<ul>";
    echo "<li><a href='?module=b01_batch_queue'>B01: Batch Queue</a></li>";
    echo "<li><a href='?module=b02_crop_profile'>B02: Crop Profile</a></li>";
    echo "<li><a href='?module=b03_growth_stage'>B03: Growth Stage</a></li>";
    echo "<li><a href='?module=b04_rack_capacity'>B04: Rack Capacity</a></li>";
    echo "<li><a href='?module=b05_seed_planning'>B05: Seed Planning</a></li>";
    echo "<li><a href='?module=b06_production_actions'>B06: Production Actions</a></li>";
    echo "</ul>";
    exit;
}

// 4. Bouw het pad naar de module (gebruik DIRECTORY_SEPARATOR voor cross-platform)
$filePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $module . '.php';

// 5. Laad de module als deze bestaat
if (file_exists($filePath)) {
    require_once $filePath;
} else {
    // Module niet gevonden
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Module niet gevonden',
        'requested_module' => $module,
        'file_checked' => $filePath
    ]);
}
