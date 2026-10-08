<?php
// Language Loader
// Verwacht dat $languageCode al is ingesteld door header.php (via sessie)

// Fallback als $languageCode niet bestaat (voor safety)
if (!isset($languageCode)) {
    $languageCode = 'nl';
    if (isset($_SESSION['erp_language'])) {
        $languageCode = $_SESSION['erp_language'];
    } else {
        // Probeer uit database als fallback
        try {
            // Let op: $db is hier misschien nog niet beschikbaar als dit voor header.php wordt geladen
            // Maar in onze structuur laadt header.php dit bestand NA het instellen van de sessie.
        } catch (Exception $e) {}
    }
}

$allowedLanguages = ['nl', 'en', 'de', 'fr', 'es', 'it'];
if (!in_array($languageCode, $allowedLanguages, true)) {
    $languageCode = 'nl';
}

$languageFile = __DIR__ . "/../languages/{$languageCode}.php";

if (!file_exists($languageFile)) {
    $languageFile = __DIR__ . '/../languages/nl.php';
}

$translations = require $languageFile;

function __(string $key): string {
    global $translations;
    // Debug mode: toon [[key]] als het mist
    if (isset($translations[$key])) {
        return $translations[$key];
    }
    return "[[" . $key . "]]";
}
