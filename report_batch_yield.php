<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$rows = $db->query("
    SELECT
        g.id,
        g.crop,
        g.tray_count,
        COALESCE(SUM(h.weight_grams), 0) AS total_harvest
    FROM grow_batches g
    LEFT JOIN harvests h ON g.id = h.batch_id
    GROUP BY g.id, g.crop, g.tray_count
    ORDER BY g.id
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>🌱 Opbrengst per batch</h1>

<?php if (empty($rows)): ?>
    <p>Nog geen batches gevonden.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
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
                    <td><?= htmlspecialchars((string) $row['id']) ?></td>
                    <td><?= htmlspecialchars((string) $row['crop']) ?></td>
                    <td><?= htmlspecialchars((string) $row['tray_count']) ?></td>
                    <td><?= htmlspecialchars((string) round($totalHarvest, 1)) ?> g</td>
                    <td><?= htmlspecialchars((string) round($perTray, 1)) ?> g</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>