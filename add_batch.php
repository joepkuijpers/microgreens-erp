<?php
require 'config/database.php';

$db->exec("
INSERT INTO grow_batches (crop, sow_date, tray_count, tray_type, status)
VALUES ('Broccoli', date('now'), 1, '1020 tray', 'gezaaid')
");

echo "Teelt toegevoegd!";
?>