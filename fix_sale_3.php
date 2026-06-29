<?php
require 'config/database.php';

$db->exec("
UPDATE sales
SET sale_date = '2026-06-14',
    customer_name = 'Test Klant'
WHERE id = 3
");

echo "Verkoop ID 3 gefixt!";
?>
