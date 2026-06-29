<?php
require 'config/database.php';

$db->exec("
CREATE TABLE IF NOT EXISTS finished_inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER,
    quantity REAL DEFAULT 0,
    unit TEXT
);
");

echo "Finished inventory tabel aangemaakt!";
?>
