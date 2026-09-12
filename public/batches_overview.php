<?php
require_once __DIR__ . "/../app/db_connect.php";

$batches = $db->query("SELECT * FROM grow_batches ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Teeltbatches Overzicht</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background: #f4f6f8; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #1565c0; color: white; }
        .dose-badge { background: #e3f2fd; color: #1565c0; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .status { text-transform: uppercase; font-size: 12px; font-weight: bold; padding: 3px 6px; border-radius: 3px; background: #eee; }
    </style>
</head>
<body>

<div class="card">
    <h2>🌱 Actieve Teeltbatches & Zaaddosering</h2>
    <table>
        <thead>
            <tr>
                <th>Batch ID</th>
                <th>Zaad Batch Code</th>
                <th>Berekende Zaaddosis</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($batches as $b): ?>
            <tr>
                <td>#<?= $b["id"] ?></td>
                <td><strong><?= htmlspecialchars($b["seed_batch_code"] ?? "Geen zaadcode") ?></strong></td>
                <td>
                    <?php if (!empty($b["calculated_seed_dose"])): ?>
                        <span class="dose-badge"><?= $b["calculated_seed_dose"] ?> gram</span>
                    <?php else: ?>
                        <span style="color:#888;">Niet berekend</span>
                    <?php endif; ?>
                </td>
                <td><span class="status"><?= htmlspecialchars($b["status"] ?? "nieuw") ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>