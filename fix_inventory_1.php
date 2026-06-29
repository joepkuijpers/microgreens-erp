<?php
require 'config/database.php';

$db->exec("UPDATE inventory SET quantity = 5, unit = 'KG' WHERE id = 1");

echo "Voorraad item 1 gecorrigeerd!";
?>
