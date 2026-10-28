<?php
require_once __DIR__ . "/../app/db_connect.php";

 = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $batch_code = trim($_POST["seed_batch_code"] ?? "");
    $crop_type = trim($_POST["crop_type"] ?? "");
    $sample_size = (int)($_POST["sample_size"] ?? 100);
    $sprouted_count = (int)($_POST["sprouted_count"] ?? 0);
    $notes = trim($_POST["notes"] ?? "");

    if (!empty($batch_code) && $sample_size > 0) {
        $stmt = $db->prepare("INSERT INTO bxx_germination_tests (seed_batch_code, crop_type, sample_size, sprouted_count, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$batch_code, $crop_type, $sample_size, $sprouted_count, $notes]);
        $message = "✅ Kiemtest voor " . htmlspecialchars($batch_code) . " succesvol opgeslagen!";
    }
}

$tests = $db->query("SELECT * FROM bxx_germination_tests ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Kiemtesten Beheer (BXX)</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background: #f4f6f8; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #2e7d32; color: white; }
        .form-group { margin-bottom: 12px; }
        label { display: block; font-weight: bold; margin-bottom: 4px; }
        input, textarea, button { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background: #2e7d32; color: white; border: none; font-weight: bold; cursor: pointer; padding: 10px; margin-top: 10px; }
        .badge { background: #e8f5e9; color: #2e7d32; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>

<div class="card">
    <h2>🧪 Nieuwe Kiemtest Invoeren</h2>
    <?php if ($message): ?>
        <p style="color: green; font-weight: bold;"><?= $message ?></p>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Zaad Batch Code (bijv. BATCH-2026-001):</label>
            <input type="text" name="seed_batch_code" required>
        </div>
        <div class="form-group">
            <label>Gewas Type (bijv. Radijs, Basilicum):</label>
            <input type="text" name="crop_type">
        </div>
        <div class="form-group">
            <label>Steekproef Aantal (bijv. 100):</label>
            <input type="number" name="sample_size" value="100" required>
        </div>
        <div class="form-group">
            <label>Aantal Gekiemd:</label>
            <input type="number" name="sprouted_count" required>
        </div>
        <div class="form-group">
            <label>Notities:</label>
            <textarea name="notes" rows="2"></textarea>
        </div>
        <button type="submit">Kiemtest Opslaan</button>
    </form>
</div>

<div class="card">
    <h2>📊 Kiemtest Overzicht</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Batch Code</th>
                <th>Gewas</th>
                <th>Gekiemd / Totaal</th>
                <th>Kiemkracht %</th>
                <th>Datum</th>
                <th>Notities</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tests as $t): 
                $rate = $t["sample_size"] > 0 ? round(($t["sprouted_count"] / $t["sample_size"]) * 100, 1) : 0;
            ?>
            <tr>
                <td>#<?= $t["id"] ?></td>
                <td><strong><?= htmlspecialchars($t["seed_batch_code"]) ?></strong></td>
                <td><?= htmlspecialchars($t["crop_type"] ?? "-") ?></td>
                <td><?= $t["sprouted_count"] ?> / <?= $t["sample_size"] ?></td>
                <td><span class="badge"><?= $rate ?>%</span></td>
                <td><?= $t["created_at"] ?></td>
                <td><?= htmlspecialchars($t["notes"] ?? "") ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>