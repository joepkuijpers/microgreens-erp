<?php
require 'config/database.php';

$db->exec("
CREATE TABLE IF NOT EXISTS sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT,
    sale_date TEXT,
    product TEXT,
    quantity REAL,
    amount REAL,
    status TEXT
);
");

echo "Sales tabel aangemaakt!";
?>