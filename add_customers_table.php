<?php
require 'config/database.php';

$db->exec("
CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT,
    phone TEXT,
    notes TEXT
);
");

echo "Customers tabel aangemaakt!";
?>
