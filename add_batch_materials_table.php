<?php
require 'config/database.php';

$db->exec("
CREATE TABLE IF NOT EXISTS batch_materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_id INTEGER,
    inventory_id INTEGER,
    quantity_used REAL
);
");

echo "Batch materials tabel aangemaakt!";
?>
