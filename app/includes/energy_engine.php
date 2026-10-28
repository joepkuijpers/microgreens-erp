<?php

if (!isset($db) || !($db instanceof PDO)) {
    throw new RuntimeException('Databaseverbinding niet beschikbaar in energy_engine.php');
}

/**
 * Berekent het dagelijks energieverbruik (kWh) van één apparaat.
 */
function calculateDailyKwh(float $wattage, float $hoursPerDay): float
{
    return ($wattage * $hoursPerDay) / 1000;
}

/**
 * Totaal energieverbruik (kWh) per dag van alle actieve apparatuur.
 */
function getTotalDailyKwh(PDO $db): float
{
    $stmt = $db->query("
        SELECT
            wattage,
            hours_per_day
        FROM equipment
        WHERE is_active = 1
    ");

    $total = 0.0;

    foreach ($stmt as $row) {
        $total += calculateDailyKwh(
            (float) $row['wattage'],
            (float) $row['hours_per_day']
        );
    }

    return round($total, 3);
}

/**
 * Totale kWh per dag voor één rek.
 */
function getRackDailyKwh(PDO $db, string $rackName): float
{
    $stmt = $db->prepare("
        SELECT
            wattage,
            hours_per_day
        FROM equipment
        WHERE is_active = 1
          AND rack_name = :rack
    ");

    $stmt->execute([
        ':rack' => $rackName
    ]);

    $total = 0.0;

    foreach ($stmt as $row) {
        $total += calculateDailyKwh(
            (float) $row['wattage'],
            (float) $row['hours_per_day']
        );
    }

    return round($total, 3);
}