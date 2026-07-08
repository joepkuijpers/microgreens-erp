<?php
include 'includes/header.php';
include 'includes/language.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Ongeldig crop profile ID.');
}

$stmt = $db->prepare("
    SELECT
        id,
        crop_name,
        blackout_days,
        grow_days_min,
        grow_days_max,
        growth_light_hours,
        temp_min,
        temp_max,
        humidity_min,
        humidity_max,
        seed_grams_per_tray,
        expected_yield_grams_per_tray,
        watering_per_day,
        ideal_ph,
        ideal_ec,
        irrigation_notes,
        notes,
        notes_internal
    FROM crop_profiles
    WHERE id = :id
");
$stmt->execute([':id' => $id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    die('Crop profile niet gevonden.');
}

$batches = $db->prepare("
    SELECT
        id,
        crop,
        sow_date,
        harvest_date,
        tray_count,
        status
    FROM grow_batches
    WHERE crop_profile_id = :id
    ORDER BY sow_date DESC, id DESC
");
$batches->execute([':id' => $id]);
$batchRows = $batches->fetchAll(PDO::FETCH_ASSOC);

$totalBatches = count($batchRows);
$totalTrays = 0;
$harvestedBatches = 0;
$activeBatches = 0;
$firstSowing = null;
$lastSowing = null;

foreach ($batchRows as $batch) {
    $totalTrays += (int)($batch['tray_count'] ?? 0);

    $status = mb_strtolower((string)($batch['status'] ?? ''));

    if (in_array($status, ['geoogst', 'harvested'], true)) {
        $harvestedBatches++;
    } else {
        $activeBatches++;
    }

    if (!empty($batch['sow_date'])) {
        if ($firstSowing === null || $batch['sow_date'] < $firstSowing) {
            $firstSowing = $batch['sow_date'];
        }

        if ($lastSowing === null || $batch['sow_date'] > $lastSowing) {
            $lastSowing = $batch['sow_date'];
        }
    }
}

$averageTrays = $totalBatches > 0
    ? round($totalTrays / $totalBatches, 1)
    : 0;
?>

<div class="main">
    <p>
        <a class="btn" href="crop_profiles.php">← Terug naar teeltprofielen</a>
    </p>

    <div class="card">
        <h2><?= htmlspecialchars((string)$profile['crop_name']) ?></h2>

        <table>
            <tr><th>ID</th><td><?= htmlspecialchars((string)$profile['id']) ?></td></tr>
            <tr><th>Gewas</th><td><?= htmlspecialchars((string)$profile['crop_name']) ?></td></tr>
            <tr><th>Blackout</th><td><?= htmlspecialchars((string)$profile['blackout_days']) ?> dagen</td></tr>
            <tr><th>Groeidagen</th><td><?= htmlspecialchars((string)$profile['grow_days_min']) ?> - <?= htmlspecialchars((string)$profile['grow_days_max']) ?> dagen</td></tr>
            <tr><th>Groeilicht</th><td><?= htmlspecialchars((string)$profile['growth_light_hours']) ?> uur/dag</td></tr>
            <tr><th>Temperatuur</th><td><?= htmlspecialchars((string)$profile['temp_min']) ?> - <?= htmlspecialchars((string)$profile['temp_max']) ?> °C</td></tr>
            <tr><th>Luchtvochtigheid</th><td><?= htmlspecialchars((string)$profile['humidity_min']) ?> - <?= htmlspecialchars((string)$profile['humidity_max']) ?>%</td></tr>
            <tr><th>Zaad / tray</th><td><?= htmlspecialchars(number_format((float)$profile['seed_grams_per_tray'], 1, ',', '.')) ?> g/tray</td></tr>
            <tr><th>Verwachte opbrengst</th><td><?= htmlspecialchars(number_format((float)$profile['expected_yield_grams_per_tray'], 1, ',', '.')) ?> g/tray</td></tr>
            <tr><th>Watergift</th><td><?= htmlspecialchars((string)$profile['watering_per_day']) ?> x per dag</td></tr>
            <tr><th>Ideale pH</th><td><?= htmlspecialchars((string)($profile['ideal_ph'] ?? '-')) ?></td></tr>
            <tr><th>Ideale EC</th><td><?= htmlspecialchars((string)($profile['ideal_ec'] ?? '-')) ?></td></tr>
            <tr><th>Irrigatie</th><td><?= nl2br(htmlspecialchars((string)($profile['irrigation_notes'] ?? '-'))) ?></td></tr>
            <tr><th>Notities</th><td><?= nl2br(htmlspecialchars((string)($profile['notes'] ?? '-'))) ?></td></tr>
            <tr><th>Interne notities</th><td><?= nl2br(htmlspecialchars((string)($profile['notes_internal'] ?? '-'))) ?></td></tr>
        </table>
    </div>

    <div class="card">
        <h2>Teeltprofiel statistieken</h2>

        <table>
            <tr><th>Totaal batches</th><td><?= htmlspecialchars((string)$totalBatches) ?></td></tr>
            <tr><th>Geoogste batches</th><td><?= htmlspecialchars((string)$harvestedBatches) ?></td></tr>
            <tr><th>Actieve batches</th><td><?= htmlspecialchars((string)$activeBatches) ?></td></tr>
            <tr><th>Totaal trays</th><td><?= htmlspecialchars((string)$totalTrays) ?></td></tr>
            <tr><th>Gemiddelde trays / batch</th><td><?= htmlspecialchars(number_format($averageTrays, 1, ',', '.')) ?></td></tr>
            <tr><th>Eerste zaaidatum</th><td><?= htmlspecialchars((string)($firstSowing ?? '-')) ?></td></tr>
            <tr><th>Laatste zaaidatum</th><td><?= htmlspecialchars((string)($lastSowing ?? '-')) ?></td></tr>
        </table>
    </div>

    <div class="card">
        <h2>Gekoppelde batches</h2>

        <?php if (empty($batchRows)): ?>
            <p>Geen gekoppelde batches gevonden.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Gewas</th>
                        <th>Status</th>
                        <th>Zaaidatum</th>
                        <th>Oogstdatum</th>
                        <th>Trays</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batchRows as $batch): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$batch['id']) ?></td>
                            <td><?= htmlspecialchars((string)$batch['crop']) ?></td>
                            <td><?= htmlspecialchars((string)$batch['status']) ?></td>
                            <td><?= htmlspecialchars((string)($batch['sow_date'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)($batch['harvest_date'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)$batch['tray_count']) ?></td>
                            <td>
                                <a href="batch_details.php?id=<?= urlencode((string)$batch['id']) ?>">Open batch</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>