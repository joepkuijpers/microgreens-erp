<?php
require_once __DIR__ . '/app/includes/auth.php';
auth_require_login();
include 'app/includes/header.php';
include 'app/db_connect.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$rows = $db->query("
    SELECT
        pb.id,
        pb.batch_code,
        pb.crop_type,
        pb.tray_count,
        pb.track_color,
        COALESCE(SUM(h.weight_grams), 0) AS total_harvest
    FROM production_batches pb
    LEFT JOIN germination_records gr ON gr.production_batch_id = pb.id
    LEFT JOIN harvests h ON h.germination_record_id = gr.id
    WHERE pb.status <> 'QUARANTINE'
    GROUP BY pb.id, pb.batch_code, pb.crop_type, pb.tray_count, pb.track_color
    ORDER BY pb.id
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>🌱 Opbrengst per batch</h1>

<?php if (empty($rows)): ?>
    <p>Nog geen batches gevonden.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Kleur</th>
                <th>Batch</th>
                <th>Gewas</th>
                <th>Trays</th>
                <th>Totale oogst</th>
                <th>Per tray</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                $trayCount = (float) $row['tray_count'];
                $totalHarvest = (float) $row['total_harvest'];
                $perTray = $trayCount > 0 ? $totalHarvest / $trayCount : 0;
                ?>
                <tr>
                    <td><span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:<?= htmlspecialchars($row['track_color']) ?>"></span></td>
                    <td><?= htmlspecialchars((string) $row['batch_code']) ?></td>
                    <td><?= htmlspecialchars((string) $row['crop_type']) ?></td>
                    <td><?= htmlspecialchars((string) $row['tray_count']) ?></td>
                    <td><?= htmlspecialchars((string) round($totalHarvest, 1)) ?> g</td>
                    <td><?= htmlspecialchars((string) round($perTray, 1)) ?> g</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include 'app/includes/footer.php'; ?>
