<?php
$pageTitle = __('module_b13');
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/organic_gate.php';

// Laatste meting per meter, met drempel + kostprijs via meter_type
$stmt = $db->prepare("
    SELECT l.reading_value, l.unit, l.log_date, l.log_time, l.source_type,
           m.meter_type, m.location,
           r.price_per_unit, r.currency,
           t.max_daily_usage, t.alert_active
    FROM utility_logs l
    JOIN meters m ON m.id = l.meter_id
    LEFT JOIN utility_rates r ON r.meter_type = m.meter_type
    LEFT JOIN utility_thresholds t ON t.meter_type = m.meter_type
    ORDER BY l.log_date DESC, l.log_time DESC
    LIMIT 20
");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$meters = $db->query("SELECT id, meter_type, location, unit FROM meters ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>
    </div>
    <h3><?= __('b13_latest') ?></h3>

    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;">
      <thead><tr>
        <th><?= __('b13_type') ?></th><th><?= __('b13_location') ?></th>
        <th><?= __('b13_value') ?></th><th><?= __('b13_unit') ?></th>
        <th><?= __('b13_date') ?></th><th><?= __('b13_source') ?></th>
        <th><?= __('b13_threshold') ?></th>
      </tr></thead>
      <tbody>
      <?php if (!$logs): ?>
        <tr><td colspan="7" style="text-align:center;color:#7f8c8d;padding:14px;"><?= __('b13_no_data') ?></td></tr>
      <?php else: foreach ($logs as $r):
        $over = ($r['alert_active'] && $r['max_daily_usage'] !== null && (float)$r['reading_value'] > (float)$r['max_daily_usage']);
        $col = $over ? '#f8d7da' : '#d4edda';
        $lbl = $over ? __('b13_alert') : __('b13_ok');
      ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['meter_type']) ?></strong></td>
          <td><?= htmlspecialchars($r['location']) ?></td>
          <td><?= htmlspecialchars($r['reading_value']) ?></td>
          <td><?= htmlspecialchars($r['unit']) ?></td>
          <td><?= htmlspecialchars($r['log_date'].' '.$r['log_time']) ?></td>
          <td><?= htmlspecialchars($r['source_type']) ?></td>
          <td><span style="padding:2px 8px;border-radius:10px;background:<?= $col ?>;"><?= $lbl ?></span></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    </div>

    <h3 style="margin-top:24px;"><?= __('b13_meters') ?></h3>
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;">
      <thead><tr><th><?= __('b13_type') ?></th><th><?= __('b13_location') ?></th><th><?= __('b13_unit') ?></th></tr></thead>
      <tbody>
      <?php foreach ($meters as $m): ?>
        <tr><td><strong><?= htmlspecialchars($m['meter_type']) ?></strong></td>
            <td><?= htmlspecialchars($m['location']) ?></td>
            <td><?= htmlspecialchars($m['unit']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <div class="next-step-bar">
      <div><a href="dashboard.php" style="color:#666;text-decoration:none;">← Dashboard</a></div>
      <div><a href="b28_watchdog_alerts.php">Watchdog →</a></div>
    </div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
