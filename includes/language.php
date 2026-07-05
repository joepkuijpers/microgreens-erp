<?php

require_once __DIR__ . '/../db_connect.php';

$languageCode = 'nl';

try {
    $stmt = $db->query("SELECT language_code FROM settings LIMIT 1");
    $value = $stmt->fetchColumn();

    if (!empty($value)) {
        $languageCode = strtolower(trim($value));
    }
} catch (Throwable $e) {
    // Als de database of tabel niet beschikbaar is,
    // blijft Nederlands de standaardtaal.
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
    global $translations;

    return $translations[$key] ?? $key;
}