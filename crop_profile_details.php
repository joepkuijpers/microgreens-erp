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
    SELECT *
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
?>

<div class="main">
    <p>
        <a class="btn" href="crop_profiles.php">← Terug naar Crop Profiles</a>
    </p>

    <div class="card">
        <h2><?= htmlspecialchars($profile['crop_name']) ?></h2>

        <table>
            <tr><th>ID</th><td><?= htmlspecialchars((string)$profile['id']) ?></td></tr>
            <tr><th>Crop</th><td><?= htmlspecialchars($profile['crop_name']) ?></td></tr>
            <tr><th>Blackout</th><td><?= htmlspecialchars((string)$profile['blackout_days']) ?> dagen</td></tr>
            <tr><th>Grow days</th><td><?= htmlspecialchars((string)$profile['grow_days_min']) ?> - <?= htmlspecialchars((string)$profile['grow_days_max']) ?> dagen</td></tr>
            <tr><th>Growth light</th><td><?= htmlspecialchars((string)$profile['growth_light_hours']) ?> uur/dag</td></tr>
            <tr><th>Temperature</th><td><?= htmlspecialchars((string)$profile['temp_min']) ?> - <?= htmlspecialchars((string)$profile['temp_max']) ?> °C</td></tr>
            <tr><th>Humidity</th><td><?= htmlspecialchars((string)$profile['humidity_min']) ?> - <?= htmlspecialchars((string)$profile['humidity_max']) ?>%</td></tr>
            <tr><th>Seed / tray</th><td><?= number_format((float)$profile['seed_grams_per_tray'], 1, ',', '.') ?> g/tray</td></tr>
            <tr><th>Expected yield</th><td><?= number_format((float)$profile['expected_yield_grams_per_tray'], 1, ',', '.') ?> g/tray</td></tr>
            <tr><th>Watering</th><td><?= htmlspecialchars((string)$profile['watering_per_day']) ?> x per dag</td></tr>
            <tr><th>Ideal pH</th><td><?= htmlspecialchars((string)($profile['ideal_ph'] ?? '-')) ?></td></tr>
            <tr><th>Ideal EC</th><td><?= htmlspecialchars((string)($profile['ideal_ec'] ?? '-')) ?></td></tr>
            <tr><th>Irrigation</th><td><?= nl2br(htmlspecialchars($profile['irrigation_notes'] ?? '-')) ?></td></tr>
            <tr><th>Notes</th><td><?= nl2br(htmlspecialchars($profile['notes'] ?? '-')) ?></td></tr>
            <tr><th>Internal notes</th><td><?= nl2br(htmlspecialchars($profile['notes_internal'] ?? '-')) ?></td></tr>
        </table>
    </div>

    <div class="card">
        <h2>Linked Grow Batches</h2>

        <?php if (empty($batchRows)): ?>
            <p>No linked grow batches found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Crop</th>
                        <th>Status</th>
                        <th>Sowing</th>
                        <th>Harvest</th>
                        <th>Trays</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batchRows as $batch): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$batch['id']) ?></td>
                            <td><?= htmlspecialchars($batch['crop']) ?></td>
                            <td><?= htmlspecialchars($batch['status']) ?></td>
                            <td><?= htmlspecialchars($batch['sow_date'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($batch['harvest_date'] ?? '-') ?></td>
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