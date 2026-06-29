<?php

$db = new PDO('sqlite:' . __DIR__ . '/../MicrogreensERP_Live.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

?>