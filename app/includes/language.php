<?php
// Language Loader
// Verwacht dat $languageCode al is ingesteld door header.php (via sessie)

// Fallback als $languageCode niet bestaat (voor safety)
if (!isset($languageCode)) {
    $languageCode = 'nl';
    if (isset($_SESSION['erp_language'])) {
        $languageCode = $_SESSION['erp_language'];
    }
}

$allowedLanguages = ['nl', 'en', 'de', 'fr', 'es', 'it'];
if (!in_array($languageCode, $allowedLanguages, true)) {
    $languageCode = 'nl';
}

// Eerst het gevraagde taalbestand proberen
$languageFile = __DIR__ . "/../languages/{$languageCode}.php";
$translations = file_exists($languageFile) ? require $languageFile : null;

// Vangst: leeg of beschadigd bestand (geen array) -> terugval op nl.
// Dit is de fix: lege 0-byte bestanden (de/es/fr/it) bestaan, maar
// bevatten geen array. Zonder deze check zou alles [[sleutel]] tonen.
if (!is_array($translations) || empty($translations)) {
    $translations = require __DIR__ . '/../languages/nl.php';
}

function __(string $key): string {
    global $translations;
    if (isset($translations[$key])) {
        return $translations[$key];
    }
    return "[[" . $key . "]]";
}