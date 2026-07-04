<?php

$translations = require __DIR__ . '/../languages/nl.php';

function __(string $key): string
{
    global $translations;

    return $translations[$key] ?? $key;
}