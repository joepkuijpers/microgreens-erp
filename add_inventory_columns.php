<?php
require 'config/database.php';

try {
    $db->exec("ALTER TABLE inventory ADD COLUMN category TEXT");
} catch (Exception $e) {}

try {
    $db->exec("ALTER TABLE inventory ADD COLUMN quantity REAL DEFAULT 0");
} catch (Exception $e) {}

try {
    $db->exec("ALTER TABLE inventory ADD COLUMN unit TEXT");
} catch (Exception $e) {}

echo "Inventory kolommen gecontroleerd/toegevoegd!";
?>