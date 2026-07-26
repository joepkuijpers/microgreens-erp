<?php

$configuredPath = getenv('MICROGREENS_DB_PATH');

if (
    is_string($configuredPath) &&
    trim($configuredPath) !== ''
) {
    return trim($configuredPath);
}

$databaseFile = PHP_OS_FAMILY === 'Windows'
    ? 'MicrogreensERP_Development.sqlite'
    : 'MicrogreensERP_Live.sqlite';

return dirname(__DIR__)
    . DIRECTORY_SEPARATOR
    . 'database'
    . DIRECTORY_SEPARATOR
    . $databaseFile;
