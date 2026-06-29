<?php
require 'config/database.php';

$db->exec("
CREATE TABLE IF NOT EXISTS harvests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_id INTEGER,
    harvest_date TEXT,
    weight_grams REAL,
    quality_notes TEXT
);
");

echo "Harvests tabel aangemaakt!";
?>