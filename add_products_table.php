<?php
require 'config/database.php';

$db->exec("
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    category TEXT,
    unit TEXT,
    sale_price REAL,
    notes TEXT
);
");

echo "Products tabel aangemaakt!";
?>
