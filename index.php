<?php
/**
 * Microgreens ERP - Centrale Router
 * Gebruik: jouwdomein.nl/?module=naam_van_module
 */

// GEEN CACHE: toon altijd de actuele pagina
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Foutmeldingen tonen (productie: uit)
error_reporting(0);
ini_set('display_errors', 0);

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
    echo "<li><a href='b01_batch.php'>Batch aanmaken</a></li>";
    echo "<li><a href='b02_germination.php'>Kiemen</a></li>";
    echo "<li><a href='b03_growth_chamber.php'>Klimaat</a></li>";
    echo "<li><a href='b04_tray_management.php'>Trays</a></li>";
echo "<li><a href='b14_water_irrigation.php'>Water &amp; Irrigatie</a></li>";
    echo "";
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
