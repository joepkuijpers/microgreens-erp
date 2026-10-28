<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/auth.php';
require_once __DIR__ . '/app/includes/audit.php';

$startTime = microtime(true);
$flash = "";
$flashType = "";

// --- METER LIJST (prepared) ---
$meters = $db->query("SELECT id, meter_type, location, unit FROM meters ORDER BY meter_type")->fetchAll(PDO::FETCH_ASSOC);

// --- VERWERKEN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meter_id'])) {
    $meter_id = intval($_POST['meter_id']);
    $type = trim($_POST['utility_type'] ?? '');
    $raw = str_replace(',', '.', $_POST['reading_value'] ?? '');
    $value = floatval($raw);
    $unit = trim($_POST['unit'] ?? '');
    $date = date('Y-m-d');
    $time = date('H:i:s');

    // Sad path: ongeldige invoer
    if ($meter_id <= 0 || $value <= 0 || $type === '') {
        $flash = __('b13_invalid_input');
        $flashType = 'error';
    } else {
        $stmt = $db->prepare("INSERT INTO utility_logs (meter_id, utility_type, reading_value, unit, log_date, log_time) VALUES (:mid, :type, :val, :unit, :date, :time)");
        $stmt->bindValue(':mid', $meter_id, PDO::PARAM_INT);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':val', $value, PDO::PARAM_STR);
        $stmt->bindValue(':unit', $unit, PDO::PARAM_STR);
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
        $stmt->bindValue(':time', $time, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $newId = (int)$db->lastInsertId();

            // Kosten (prepared)
            $rs = $db->prepare("SELECT price_per_unit FROM utility_rates WHERE meter_type = :t");
            $rs->bindValue(':t', $type, PDO::PARAM_STR);
            $rs->execute();
            $rate = (float)$rs->fetchColumn();
            $cost = $value * $rate;

            // Sad path: drempel (prepared)
            $rt = $db->prepare("SELECT max_daily_usage FROM utility_thresholds WHERE meter_type = :t");
            $rt->bindValue(':t', $type, PDO::PARAM_STR);
            $rt->execute();
            $threshold = $rt->fetchColumn();

            if ($threshold && $value > $threshold) {
                $flash = sprintf(__('b13_alert'), $type, $value, $threshold);
                $flashType = 'warn';
            } else {
                $flash = sprintf(__('b13_success'), number_format($cost, 2));
                $flashType = 'success';
            }

            // Audit + actor + timers
            $u = auth_current_user();
            $operator = $u['display_name'] ?? 'system';
            $elapsed = round((microtime(true) - $startTime) * 1000);
            auditLog($db, 'UTILITY_READING', 'utility_logs', $newId, 'CREATE',
                $type . ' ' . $value . $unit . ' (€' . number_format($cost,2) . ') door ' . $operator . ' in ' . $elapsed . 'ms',
                null,
                ['meter' => $type, 'value' => $value, 'unit' => $unit, 'cost' => round($cost,2), 'operator' => $operator, 'reg_ms' => $elapsed]);
        }
    }
}

// --- GRAFIEK (laatste 7 dagen, prepared) ---
$chartData = []; $labels = [];
$types = ['Electricity', 'Water', 'Gas', 'Heat'];
$rs = $db->prepare("SELECT SUM(reading_value) FROM utility_logs WHERE utility_type = :t AND log_date = :d");
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d-m', strtotime($d));
    foreach ($types as $type) {
        $rs->bindValue(':t', $type, PDO::PARAM_STR);
        $rs->bindValue(':d', $d, PDO::PARAM_STR);
        $rs->execute();
        $val = $rs->fetchColumn();
        $chartData[$type][] = $val ? (float)$val : 0;
        $rs->bindValue(':t', $type, PDO::PARAM_STR);
        $rs->bindValue(':d', $d, PDO::PARAM_STR);
    }
}
$chartLabelsJson = json_encode($labels);
$chartDataJson = json_encode($chartData);

// --- TOTALE KOSTEN MAAND (prepared) ---
$totalCost = 0;
$currentMonth = date('Y-m');
$rc = $db->prepare("SELECT l.reading_value, r.price_per_unit FROM utility_logs l JOIN utility_rates r ON l.utility_type = r.meter_type WHERE l.log_date LIKE :m");
$rc->bindValue(':m', $currentMonth . '%', PDO::PARAM_STR);
$rc->execute();
foreach ($rc->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $totalCost += ($row['reading_value'] * $row['price_per_unit']);
}
?>
<?php if ($flash): ?>
  <div style="padding:12px;margin:12px 0;border-radius:6px;
    background:<?= $flashType==='error' ? '#fee2e2' : ($flashType==='warn' ? '#fef3c7' : '#dcfce7') ?>;
    color:<?= $flashType==='error' ? '#991b1b' : ($flashType==='warn' ? '#92400e' : '#166534') ?>;">
    <?= htmlspecialchars($flash) ?>
  </div>
<?php endif; ?>

<div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.1);margin-bottom:20px;">
  <h2 style="margin-top:0;"><?= __('b13_title') ?></h2>
  <form method="POST" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
    <div>
      <label><?= __('b13_meter') ?></label><br>
      <select name="meter_id" required style="padding:8px;">
        <?php foreach ($meters as $m): ?>
          <option value="<?= (int)$m['id'] ?>" data-type="<?= htmlspecialchars($m['meter_type']) ?>" data-unit="<?= htmlspecialchars($m['unit']) ?>">
            <?= htmlspecialchars($m['meter_type']) ?> — <?= htmlspecialchars($m['location']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div id="price_info" style="margin-top:6px;font-size:13px;color:#6b7280;"></div>
    </div>
    <div>
      <label id="val_label"><?= __('b13_val') ?></label><br>
      <input type="number" step="0.01" name="reading_value" required style="padding:8px;width:140px;">
    </div>
    <input type="hidden" name="utility_type" id="utility_type">
    <input type="hidden" name="unit" id="unit">
    <button type="submit" style="padding:8px 16px;background:#7c3aed;color:#fff;border:none;border-radius:6px;cursor:pointer;"><?= __('b13_submit') ?></button>
  </form>
<script>
(function(){
  var rates = {"Electricity":0.25,"Water":1.8,"Gas":1.2,"Heat":0.15};
  var sel = document.querySelector('select[name=meter_id]');
  var lbl = document.getElementById('val_label');
  var info = document.getElementById('price_info');
  function upd(){
    var o = sel.options[sel.selectedIndex];
    if(!o || !o.value){ info.textContent=''; return; }
    var unit = o.getAttribute('data-unit');
    var type = o.getAttribute('data-type');
    lbl.textContent = 'Stand (' + unit + ')';
    var p = rates[type];
    info.textContent = (p!==undefined) ? ('€' + p.toFixed(2) + '/' + unit) : '';
  }
  sel.addEventListener('change', upd);
  upd();
})();
</script>
</div>

<div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:20px;">
  <div style="background:#fff;padding:16px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.1);flex:1;min-width:160px;">
    <div style="color:#666;font-size:13px;"><?= __('b13_total_cost') ?></div>
    <div style="font-size:24px;font-weight:bold;">€ <?= number_format($totalCost, 2) ?></div>
  </div>
</div>

<div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.1);">
  <h3 style="margin-top:0;"><?= __('b13_chart') ?></h3>
  <canvas id="usageChart" height="100"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  var sel = document.querySelector('select[name=meter_id]');
  function sync(){ var o = sel.options[sel.selectedIndex]; if(o){ document.getElementById('utility_type').value = o.dataset.type; document.getElementById('unit').value = o.dataset.unit; } }
  sel.addEventListener('change', sync); sync();

  var ctx = document.getElementById('usageChart');
  if (ctx) { new Chart(ctx, { type:'line',
    data:{ labels:<?= $chartLabelsJson ?>, datasets:[
      {label:'Electricity',data:<?= json_encode($chartData['Electricity']) ?>,borderColor:'#ca8a04',tension:.3},
      {label:'Water',data:<?= json_encode($chartData['Water']) ?>,borderColor:'#2563eb',tension:.3},
      {label:'Gas',data:<?= json_encode($chartData['Gas']) ?>,borderColor:'#6b7280',tension:.3},
      {label:'Heat',data:<?= json_encode($chartData['Heat']) ?>,borderColor:'#dc2626',tension:.3}
    ]},
    options:{responsive:true,scales:{y:{beginAtZero:true}}}
  }); }
});
</script>

<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
