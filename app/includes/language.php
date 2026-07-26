<?php

require_once __DIR__ . '/../db_connect.php';

$languageCode = 'nl';

/*
|--------------------------------------------------------------------------
| Language debug mode
|--------------------------------------------------------------------------
| true  = ontbrekende sleutels tonen als [[translation_key]]
| false = ontbrekende sleutels tonen als translation_key
|--------------------------------------------------------------------------
*/
$languageDebug = true;

try {
    $stmt = $db->query("SELECT language_code FROM settings LIMIT 1");
    $value = $stmt->fetchColumn();

    if (!empty($value)) {
        $languageCode = strtolower(trim($value));
    }
} catch (Throwable $e) {
    // Fallback blijft Nederlands
}

$allowedLanguages = [
    'nl',
    'en',
    'de',
    'fr',
    'es',
    'it'
];

if (!in_array($languageCode, $allowedLanguages, true)) {
    $languageCode = 'nl';
}

$languageFile = __DIR__ . "/../languages/{$languageCode}.php";

if (!file_exists($languageFile)) {
    $languageFile = __DIR__ . '/../languages/nl.php';
}

$translations = require $languageFile;

function __(string $key): string
{
    global $translations, $languageDebug;

    if (isset($translations[$key])) {
        return $translations[$key];
    }

    return $languageDebug
        ? "[[{$key}]]"
        : $key;
}