<?php
require 'config/database.php';

$db->exec("
INSERT INTO sales
(customer_name, sale_date, product, quantity, amount, status)
VALUES
('Test Klant', date('now'), 'Broccoli microgreens', 1, 3.50, 'betaald')
");

echo "Verkoop toegevoegd!";
?>