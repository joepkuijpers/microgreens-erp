<?php

$databasePath = require __DIR__ . '/../config/database_path.php';

$db = new PDO('sqlite:' . $databasePath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
