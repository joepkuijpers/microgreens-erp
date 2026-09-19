<?php
require_once __DIR__ . "/../includes/db_connection.php";
require_once __DIR__ . "/../includes/auth.php";

$db = getDbConnection();
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["assign_batch"])) {
    $batch_id = (int)$_POST["batch_id"];
    $position = (int)$_POST["rack_position"];
    $rack_id = "RACK-A";
    $checkStmt = $db->prepare("SELECT id FROM production_batches WHERE rack_position = ? AND rack_id = ? AND started_at IS NOT NULL");
    $checkStmt->execute([$position, $rack_id]);
    if ($checkStmt->fetch()) {
        $error = "Positie $position is al bezet!";
    } else {
        $updateStmt = $db->prepare("UPDATE production_batches SET rack_position = ?, rack_id = ? WHERE id = ?");
        if ($updateStmt->execute([$position, $rack_id, $batch_id])) {
            $message = "Batch #$batch_id toegewezen aan Positie $position.";
        } else {
            $error = "Fout bij toewijzen.";
        }
    }
}

$sqlBatches = "SELECT id, batch_code, started_at FROM production_batches WHERE rack_position IS NULL AND started_at IS NOT NULL ORDER BY started_at DESC";
$batchesStmt = $db->query($sqlBatches);
$availableBatches = $batchesStmt ? $batchesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$sqlOccupied = "SELECT rack_position, batch_code FROM production_batches WHERE rack_id = 'RACK-A' AND started_at IS NOT NULL AND rack_position IS NOT NULL";
$occupiedStmt = $db->query($sqlOccupied);
$occupiedPositions = $occupiedStmt ? $occupiedStmt->fetchAll(PDO::FETCH_ASSOC) : [];
$occupiedMap = array_column($occupiedPositions, "batch_code", "rack_position");
?>
<div class="container mt-4">
    <h2>📦 Batch Toewijzing (Rack A)</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card"><div class="card-header">Nieuwe Toewijzing</div><div class="card-body">
                <form method="POST">
                    <div class="mb-3"><label>Kies Batch</label><select name="batch_id" class="form-select" required>
                        <option value="">-- Selecteer --</option>
                        <?php foreach ($availableBatches as $b): ?>
                            <option value="<?= $b["id"] ?>">#<?= $b["id"] ?> - <?= htmlspecialchars($b["batch_code"]) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                    <div class="mb-3"><label>Kies Positie (1-24)</label><select name="rack_position" class="form-select" required>
                        <option value="">-- Selecteer --</option>
                        <?php for ($i = 1; $i <= 24; $i++): ?>
                            <?php if (!isset($occupiedMap[$i])): ?><option value="<?= $i ?>">Positie <?= $i ?> (Vrij)</option><?php endif; ?>
                        <?php endfor; ?>
                    </select></div>
                    <button type="submit" name="assign_batch" class="btn btn-primary">Toewijzen</button>
                </form>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-header">Huidige Bezetting</div><div class="card-body">
                <ul class="list-group">
                    <?php foreach ($occupiedPositions as $pos): ?>
                        <li class="list-group-item d-flex justify-content-between">Positie <?= $pos["rack_position"] ?> <span class="badge bg-primary"><?= htmlspecialchars($pos["batch_code"]) ?></span></li>
                    <?php endforeach; ?>
                    <?php if (empty($occupiedPositions)): ?><li class="list-group-item text-muted">Geen batches toegewezen.</li><?php endif; ?>
                </ul>
            </div></div>
        </div>
    </div>
    <div class="mt-3"><a href="dashboard_full.php" class="btn btn-secondary">← Terug</a></div>
</div>