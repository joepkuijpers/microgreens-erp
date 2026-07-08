<?php
include 'includes/header.php';
include 'includes/language.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die(__('invalid_batch_id'));
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
?>

<div class="main">

    <p>
        <a class="btn" href="crop_profiles.php">
            ← Terug naar Crop Profiles
        </a>
    </p>

    <div class="card">
        <h2><?= htmlspecialchars($profile['crop_name']) ?></h2>

        <table>
            <tr>
                <th>ID</th>
                <td><?= htmlspecialchars($profile['id']) ?></td>
            </tr>

            <tr>
                <th>Crop</th>
                <td><?= htmlspecialchars($profile['crop_name']) ?></td>
            </tr>

            <tr>
                <th>Blackout</th>
                <td><?= htmlspecialchars($profile['blackout_days']) ?> dagen</td>
            </tr>

            <tr>
                <th>Grow days</th>
                <td><?= htmlspecialchars($profile['grow_days_min']) ?> - <?= htmlspecialchars($profile['grow_days_max']) ?></td>
            </tr>

            <tr>
                <th>Growth light</th>
                <td><?= htmlspecialchars($profile['growth_light_hours']) ?> uur/dag</td>
            </tr>

            <tr>
                <th>Temperature</th>
                <td><?= htmlspecialchars($profile['temp_min']) ?> - <?= htmlspecialchars($profile['temp_max']) ?> °C</td>
            </tr>

            <tr>
                <th>Humidity</th>
                <td><?= htmlspecialchars($profile['humidity_min']) ?> - <?= htmlspecialchars($profile['humidity_max']) ?> %</td>
            </tr>

            <tr>
                <th>Seed</th>
                <td><?= number_format((float)$profile['seed_grams_per_tray'],1,',','.') ?> g/tray</td>
            </tr>

            <tr>
                <th>Expected yield</th>
                <td><?= number_format((float)$profile['expected_yield_grams_per_tray'],1,',','.') ?> g/tray</td>
            </tr>

            <tr>
                <th>Watering</th>
                <td><?= htmlspecialchars($profile['watering_per_day']) ?> x per dag</td>
            </tr>

            <tr>
                <th>Ideal pH</th>
                <td><?= htmlspecialchars($profile['ideal_ph'] ?? '-') ?></td>
            </tr>

            <tr>
                <th>Ideal EC</th>
                <td><?= htmlspecialchars($profile['ideal_ec'] ?? '-') ?></td>
            </tr>

            <tr>
                <th>Irrigation</th>
                <td><?= nl2br(htmlspecialchars($profile['irrigation_notes'] ?? '-')) ?></td>
            </tr>

            <tr>
                <th>Notes</th>
                <td><?= nl2br(htmlspecialchars($profile['notes'] ?? '-')) ?></td>
            </tr>

            <tr>
                <th>Internal notes</th>
                <td><?= nl2br(htmlspecialchars($profile['notes_internal'] ?? '-')) ?></td>
            </tr>
        </table>
    </div>

</div>

<?php include 'includes/footer.php'; ?>