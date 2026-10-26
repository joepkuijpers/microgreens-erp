<?php
// Language Loader
// Verwacht dat $languageCode al is ingesteld door header.php (via sessie)

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

$languageFile = __DIR__ . "/../languages/{$languageCode}.php";
if (!file_exists($languageFile)) {
    $languageFile = __DIR__ . '/../languages/nl.php';
}

$translations = require $languageFile;

// Optioneel module-fragment samenvoegen (bv. nl_b01_b02.php)
$extFile = preg_replace('/\.php$/', '_b01_b02.php', $languageFile);
if (file_exists($extFile)) {
    $translations = array_merge($translations, require $extFile);
}

function __(string $key): string {
    global $translations;
    if (isset($translations[$key])) {
        return $translations[$key];
    }
    return "[[" . $key . "]]";
}