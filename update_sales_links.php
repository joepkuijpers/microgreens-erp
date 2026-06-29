<?php
require 'config/database.php';

try { $db->exec("ALTER TABLE sales ADD COLUMN customer_id INTEGER"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE sales ADD COLUMN product_id INTEGER"); } catch (Exception $e) {}

echo "Sales koppelingen gecontroleerd!";
?>
