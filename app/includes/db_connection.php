<?php
// db_connection.php — één pad, één bron van waarheid.
// Bij verhuizing naar de 24/7-server: alleen ERP_DB_PATH veranderen.

declare(strict_types=1);

const ERP_DB_PATH = 'C:/Users/joepk/Downloads/microgreens-erp/database/MicrogreensERP_Development.sqlite';

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . ERP_DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
    ]);

    // Eén schrijver, dus WAL aan voor betrouwbare lezing tijdens schrijf-acties.
    $pdo->exec('PRAGMA journal_mode = WAL;');
    $pdo->exec('PRAGMA foreign_keys = ON;');

    return $pdo;
}