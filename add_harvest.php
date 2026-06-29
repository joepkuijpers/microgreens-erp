<?php
require 'config/database.php';

$db->exec("
INSERT INTO harvests
(batch_id, harvest_date, weight_grams, quality_notes)
VALUES
(1, date('now'), 250, 'Mooie broccoli oogst')
");

echo "Oogst toegevoegd!";
?>