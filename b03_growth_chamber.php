<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/auth.php';

$flash = "";
$flashType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rack_id     = trim($_POST["rack_id"] ?? "");
    $temperature = $_POST["temperature"] ?? "";
    $humidity    = $_POST["humidity"] ?? "";

    // Sad path: verplichte velden
    if ($rack_id === "" || $temperature === "" || $humidity === "") {
        $flash = "❌ Vul rack, temperatuur en luchtvochtigheid in.";
        $flashType = "error";
    } elseif (!is_numeric($temperature) || !is_numeric($humidity)) {
        // Sad path: ongeldige getallen
        $flash = "❌ Temperatuur en RV moeten geldige getallen zijn.";
        $flashType = "error";
    } else {
        $temperature = (float)$temperature;
        $humidity    = (float)$humidity;
        $now = date("Y-m-d H:i:s");

        $stmt = $db->prepare("INSERT INTO climate_data (timestamp, temperature, humidity, rack_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$now, $temperature, $humidity, $rack_id]);
        $newId = (int)$db->lastInsertId();

        // Audit-event (bewijs, actor uit auth)
        $currentUser = auth_current_user();
        $actorId = $currentUser['id'] ?? null;
        $desc = "Klimaatmeting: {$temperature}C / {$humidity}% RV op {$rack_id}";
        $audit = $db->prepare("INSERT INTO audit_events (event_type, table_name, record_id, action, description, actor_user_id, entity_type, entity_id) VALUES ('CLIMATE_READING','climate_data',?,'CREATE',?,?,'climate_data',?)");
        $audit->execute([$newId, $desc, $actorId, $newId]);

        // Sad path: buiten normale band -> waarschuwing (geen blokkade)
        if ($humidity < 40 || $humidity > 80 || $temperature < 15 || $temperature > 30) {
            $flash = "⚠️ Opgeslagen, maar waarde buiten normale band (RV 40–80%, temp 15–30°C). Afwijking vastgelegd in audit.";
            $flashType = "warn";
        } else {
            $flash = "✅ Klimaatmeting opgeslagen op {$rack_id}.";
            $flashType = "ok";
        }
    }
}

// Racks voor dropdown
$racks = $db->query("SELECT name FROM racks ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Recentste metingen + afgeleide batch (locatie + tijdsvenster)
$rows = $db->query("SELECT id, timestamp, temperature, humidity, rack_id FROM climate_data ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

$batchStmt = $db->prepare("SELECT pb.batch_code FROM production_batches pb WHERE pb.rack_id = :r AND :ts BETWEEN pb.started_at AND COALESCE(pb.completed_at, datetime('now')) ORDER BY pb.started_at DESC LIMIT 1");
$countStmt = $db->prepare("SELECT COUNT(*) FROM production_batches pb WHERE pb.rack_id = :r AND :ts BETWEEN pb.started_at AND COALESCE(pb.completed_at, datetime('now'))");


?>

<div style="max-width:1000px;margin:0 auto;padding:16px;">
  <h2>🌡️ <?= __('b03_title') ?></h2>

  <?php if ($flash): ?>
    <p style="font-weight:bold;color:<?= $flashType==='ok'?'green':($flashType==='warn'?'#b8860b':'red') ?>;"><?= htmlspecialchars($flash) ?></p>
  <?php endif; ?>

  <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,.1);margin-bottom:20px;">
    <h2><?= __('b03_new_measurement') ?></h2>
    <form method="POST">
      <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;">
        <div><label><?= __('b03_rack') ?></label><br>
          <select name="rack_id" required>
            <?php foreach ($racks as $r): ?>
              <option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label><?= __('b03_temp_label') ?></label><br><input type="number" step="0.1" name="temperature" required></div>
        <div><label><?= __('b03_rh_label') ?></label><br><input type="number" step="0.1" name="humidity" required></div>
        <div><button type="submit" style="padding:9px 16px;background:#2e7d32;color:#fff;border:none;border-radius:4px;cursor:pointer;"><?= __('b03_save') ?></button></div>
      </div>
    </form>
  </div>

  <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,.1);">
    <h2><?= __('b03_last_measurements') ?></h2>
    <table style="width:100%;border-collapse:collapse;">
      <thead><tr style="background:#f0f0f0;text-align:left;">
        <th style="padding:8px;border:1px solid #ddd;"><?= __('b03_time') ?></th>
        <th style="padding:8px;border:1px solid #ddd;"><?= __('b03_rack') ?></th>
        <th style="padding:8px;border:1px solid #ddd;"><?= __('b03_temp') ?></th>
        <th style="padding:8px;border:1px solid #ddd;"><?= __('b03_rh') ?></th>
        <th style="padding:8px;border:1px solid #ddd;"><?= __('b03_batch_derived') ?></th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $row):
          $ts = $row['timestamp']; $rk = $row['rack_id'];
          $batch = null; $ambiguous = false;
          if ($rk !== null && $rk !== "") {
              $batchStmt->execute([':r'=>$rk, ':ts'=>$ts]);
              $batch = $batchStmt->fetchColumn() ?: null;
              $countStmt->execute([':r'=>$rk, ':ts'=>$ts]);
              $ambiguous = ((int)$countStmt->fetchColumn()) > 1;
          }
      ?>
        <tr>
          <td style="padding:8px;border:1px solid #ddd;"><?= htmlspecialchars($ts) ?></td>
          <td style="padding:8px;border:1px solid #ddd;"><?= htmlspecialchars($rk ?? '—') ?></td>
          <td style="padding:8px;border:1px solid #ddd;"><?= htmlspecialchars((string)$row['temperature']) ?></td>
          <td style="padding:8px;border:1px solid #ddd;"><?= htmlspecialchars((string)$row['humidity']) ?></td>
          <td style="padding:8px;border:1px solid #ddd;">
            <?php if ($batch === null): ?>
              <span style="color:#999;"><?= __('b03_none') ?></span>
            <?php elseif ($ambiguous): ?>
              <span style="color:#b8860b;" title="<?= __('b03_ambiguous') ?>"><?= htmlspecialchars($batch) ?> ⚠️</span>
            <?php else: ?>
              <?= htmlspecialchars($batch) ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/app/includes/footer.php'; ?>
