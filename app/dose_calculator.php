<?php
/**
 * Bereken het gecorrigeerde zaaigewicht op basis van de kiemkracht (Bxx).
 *
 * @param PDO $pdo SQLite database connectie
 * @param string $batch_code De batchcode van het zaad (bijv. 'RAD-2026-01')
 * @param float $target_weight_grams Standaard zaaigewicht in gram bij 85% kiemkracht
 * @param float $target_rate Standaard beoogde kiemkracht (standaard 85%)
 * @return array Bevat 'calculated_weight', 'germination_rate', en 'status_note'
 */
function calculateSeedingDose($pdo, $batch_code, $target_weight_grams, $target_rate = 85.0) {
    $stmt = $pdo->prepare("SELECT sample_size, sprouted_count FROM bxx_germination_tests 
                           WHERE seed_batch_code = ? 
                           ORDER BY id DESC LIMIT 1");
    $stmt->execute([$batch_code]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);

    // Als er geen test bekend is, hanteren we 100% van de standaard dosering
    if (!$test || $test['sample_size'] <= 0) {
        return [
            'calculated_weight' => round($target_weight_grams, 1),
            'germination_rate' => null,
            'status_note'       => 'Geen kiemtest bekend; standaard dosering gebruikt.'
        ];
    }

    $actual_rate = ($test['sprouted_count'] / $test['sample_size']) * 100;

    // Bij heel lage kiemkracht (< 50%) waarschuwen dat de batch niet geschikt is
    if ($actual_rate < 50.0) {
        return [
            'calculated_weight' => round($target_weight_grams * ($target_rate / max($actual_rate, 1)), 1),
            'germination_rate' => round($actual_rate, 1),
            'status_note'       => '⚠️ WAARSCHUWING: Kiemkracht zeer laag (' . round($actual_rate, 1) . '%). Overweeg afgeen batch!'
        ];
    }

    // Bereken gecorrigeerd gewicht
    $adjusted_weight = $target_weight_grams * ($target_rate / $actual_rate);

    return [
        'calculated_weight' => round($adjusted_weight, 1),
        'germination_rate' => round($actual_rate, 1),
        'status_note'       => 'Dosering aangepast op basis van ' . round($actual_rate, 1) . '% kiemkracht.'
    ];
}
