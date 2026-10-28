<?php
/**
 * Bereken het gecorrigeerde zaaigewicht op basis van de kiemkracht.
 * Leest uit germination_tests, gekoppeld via de batch (batch_code -> batch_id).
 */
function calculateSeedingDose($pdo, $batch_code, $target_weight_grams, $target_rate = 85.0) {
    $stmt = $pdo->prepare("SELECT t.sample_size, t.sprouted_count
                           FROM germination_tests t
                           JOIN production_batches b ON b.id = t.batch_id
                           WHERE b.batch_code = ? AND t.batch_id IS NOT NULL
                           ORDER BY t.id DESC LIMIT 1");
    $stmt->execute([$batch_code]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$test || $test['sample_size'] <= 0) {
        return [
            'calculated_weight' => round($target_weight_grams, 1),
            'germination_rate'  => null,
            'status_note'       => 'Geen kiemtest bekend; standaard dosering gebruikt.'
        ];
    }

    $actual_rate = ($test['sprouted_count'] / $test['sample_size']) * 100;

    if ($actual_rate < 50.0) {
        return [
            'calculated_weight' => round($target_weight_grams * ($target_rate / max($actual_rate, 1)), 1),
            'germination_rate'  => round($actual_rate, 1),
            'status_note'       => '⚠️ WAARSCHUWING: Kiemkracht zeer laag (' . round($actual_rate, 1) . '%). Overweeg afgeven batch!'
        ];
    }

    $adjusted_weight = $target_weight_grams * ($target_rate / $actual_rate);

    return [
        'calculated_weight' => round($adjusted_weight, 1),
        'germination_rate'  => round($actual_rate, 1),
        'status_note'       => 'Dosering aangepast op basis van ' . round($actual_rate, 1) . '% kiemkracht.'
    ];
}
