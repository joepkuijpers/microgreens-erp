<?php

$db = new PDO('sqlite:MicrogreensERP_Live.sqlite');

$db->exec("
INSERT INTO inventory (item_name)
VALUES ('Broccoli Zaad');
");

echo "Voorraaditem toegevoegd!";

?>