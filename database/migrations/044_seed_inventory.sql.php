<?php
// Migration 044: seed_lots + seed_inventory
// Veilig: IF NOT EXISTS + INSERT OR IGNORE

const DB = 'C:/Users/joepk/Downloads/microgreens-erp/database/MicrogreensERP_Development.sqlite';
$db = new PDO('sqlite:' . DB, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$db->exec('PRAGMA foreign_keys = ON;');

$db->beginTransaction();

// 1. seed_lots — variëteit + biologische status
$db->exec("
    CREATE TABLE IF NOT EXISTS seed_lots (
        id                      INTEGER PRIMARY KEY AUTOINCREMENT,
        variety                 TEXT    NOT NULL,
        organic_status          TEXT    NOT NULL DEFAULT 'BIO',
        certificate_valid_until TEXT,
        created_at              TEXT    NOT NULL DEFAULT (datetime('now'))
    );
");

// 2. seed_inventory — fysieke voorraad per lot per leverancier
$db->exec("
    CREATE TABLE IF NOT EXISTS seed_inventory (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        seed_lot_id INTEGER NOT NULL REFERENCES seed_lots(id),
        supplier_id INTEGER NOT NULL REFERENCES suppliers(id),
        stock_grams REAL    NOT NULL DEFAULT 0,
        created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
    );
");

// 3. Testrij: leverancier (als er nog geen is)
$db->exec("
    INSERT OR IGNORE INTO suppliers (id, name) VALUES (1, 'Testleverancier');
");

// 4. Testrij: zaadlot
$db->exec("
    INSERT OR IGNORE INTO seed_lots (id, variety, organic_status, certificate_valid_until)
    VALUES (1, 'Radicchio', 'BIO', '2027-12-31');
");

// 5. Testrij: voorraad
$db->exec("
    INSERT OR IGNORE INTO seed_inventory (id, seed_lot_id, supplier_id, stock_grams)
    VALUES (1, 1, 1, 500.0);
");

$db->commit();

// Verificatie
echo "=== seed_lots ===\n";
foreach ($db->query('SELECT * FROM seed_lots') as $r) print_r($r);
echo "\n=== seed_inventory ===\n";
foreach ($db->query('SELECT * FROM seed_inventory') as $r) print_r($r);
echo "\nOK — 2 tabellen + 3 testrijen geplaatst.\n";
