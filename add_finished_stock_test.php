<?php
require 'config/database.php';

$db->exec("
INSERT INTO finished_inventory
(product_id, quantity, unit)
VALUES
(1, 25, 'bakjes')
");

echo "25 bakjes toegevoegd!";
?>
