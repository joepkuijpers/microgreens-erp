<?php
include 'db_connect.php';

$db->exec("
    CREATE TABLE IF NOT EXISTS inventory_transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        inventory_id INTEGER,
        transaction_date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        type TEXT NOT NULL,
        quantity_change REAL NOT NULL,
        quantity_before REAL,
        quantity_after REAL,
        unit TEXT,
        note TEXT,
        reference_type TEXT,
        reference_id INTEGER,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
");

echo "inventory_transactions tabel is aangemaakt of bestond al.";
