<?php
require 'config/database.php';

try { $db->exec("ALTER TABLE inventory ADD COLUMN unit_cost REAL DEFAULT 0"); } catch (Exception $e) {}

echo "Inventory kostprijs gecontroleerd!";
?>
