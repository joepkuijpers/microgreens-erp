<?php
header('Content-Type: application/json');

echo json_encode([
    "temperature" => "22.3",
    "humidity" => "57",
    "pressure" => "1013",
    "light" => "8450"
]);
?>