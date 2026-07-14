<?php

include '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: sensors.php');
    exit;
}

$light_min = filter_input(INPUT_POST, 'light_min', FILTER_VALIDATE_INT);
$light_max = filter_input(INPUT_POST, 'light_max', FILTER_VALIDATE_INT);

$temp_min = filter_input(INPUT_POST, 'temp_min', FILTER_VALIDATE_FLOAT);
$temp_max = filter_input(INPUT_POST, 'temp_max', FILTER_VALIDATE_FLOAT);

$humidity_min = filter_input(INPUT_POST, 'humidity_min', FILTER_VALIDATE_FLOAT);
$humidity_max = filter_input(INPUT_POST, 'humidity_max', FILTER_VALIDATE_FLOAT);

$refresh_seconds = filter_input(INPUT_POST, 'refresh_seconds', FILTER_VALIDATE_INT);

if (
    $light_min === false ||
    $light_max === false ||
    $temp_min === false ||
    $temp_max === false ||
    $humidity_min === false ||
    $humidity_max === false ||
    $refresh_seconds === false
) {
    header("Location: sensors.php?error=invalid");
    exit;
}

if (
    $light_min >= $light_max ||
    $temp_min >= $temp_max ||
    $humidity_min >= $humidity_max ||
    $refresh_seconds < 1
) {
    header("Location: sensors.php?error=validation");
    exit;
}

$sql = "
UPDATE settings
SET
    light_min = ?,
    light_max = ?,
    temp_min = ?,
    temp_max = ?,
    humidity_min = ?,
    humidity_max = ?,
    refresh_seconds = ?
WHERE id = 1
";

$stmt = $db->prepare($sql);

$stmt->execute([
    $light_min,
    $light_max,
    $temp_min,
    $temp_max,
    $humidity_min,
    $humidity_max,
    $refresh_seconds
]);

header("Location: sensors.php?saved=1");
exit;
