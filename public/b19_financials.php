<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$dbPath = '/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite';
if (!file_exists($dbPath)) {
    die("Database niet gevonden: " . $dbPath);
}

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connectie fout: " . $e->getMessage());
}

// Instellingen
$COST_SEED = 0.05;
$COST_ENERGY = 0.50;
$PRICE_SELL = 0.15;

// Query
$sql = "
    SELECT 
        pb.id, 
        pb.batch_code, 
        pb.crop_type, 
        pb.status, 
        pb.started_at, 
        pb.completed_at,
        (pb.planned_quantity * $COST_SEED) as seed_cost,
        ((julianday(coalesce(pb.completed_at, datetime('now'))) - julianday(pb.started_at)) * $COST_ENERGY) as energy_cost,
        (SELECT coalesce(SUM(pu.total_weight_g), 0) FROM packaging_units pu WHERE pu.batch_id = pb.id) * $PRICE_SELL as revenue
    FROM production_batches pb
    ORDER BY pb.started_at DESC
";

$stmt = $pdo->query($sql);
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B19 Financials</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; background: #f4f4f4; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #28a745; border-bottom: 2px solid #28a745; padding-bottom: 0.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #28a745; color: white; }
        .money { text-align: right; font-family: monospace; }
        .profit { color: #28a745; font-weight: bold; }
        .loss { color: #dc3545; font-weight: bold; }
        tr:hover { background-color: #f9f9f9; }
    </style>
</head>
<body>
<div class="container">
    <h1>💰 B19: Financiële Analyse</h1>
    <p><em>Kosten: Zaad €<?= $COST_SEED ?>/g, Energie €<?= $COST_ENERGY ?>/dag. Verkoop: €<?= $PRICE_SELL ?>/g.</em></p>
    
    <?php if (empty($batches)): ?>
        <p>Geen batches gevonden om te analyseren.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Batch Code</th>
                    <th>Gewas</th>
                    <th>Status</th>
                    <th class="money">Totale Kosten</th>
                    <th class="money">Opbrengst</th>
                    <th class="money">Winst / Verlies</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batches as $b): 
                    $total_cost = $b['seed_cost'] + $b['energy_cost'];
                    $profit = $b['revenue'] - $total_cost;
                    $class = ($profit >= 0) ? 'profit' : 'loss';
                ?>
                <tr>
                    <td><?= htmlspecialchars($b['batch_code']) ?></td>
                    <td><?= htmlspecialchars($b['crop_type']) ?></td>
                    <td><?= htmlspecialchars($b['status']) ?></td>
                    <td class="money">€ <?= number_format($total_cost, 2, ',', '.') ?></td>
                    <td class="money">€ <?= number_format($b['revenue'], 2, ',', '.') ?></td>
                    <td class="money <?= $class ?>">€ <?= number_format($profit, 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
