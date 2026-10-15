<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/auth.php';
$pageTitle = __('b09_trace_title');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { die(__('b09_err_invalid_sale')); }

$stmt = $db->prepare("
    SELECT
        s.id, s.sale_date, s.quantity, s.amount, s.status, s.product,
        s.harvest_id, s.batch_id,
        h.id AS harvest_id_real, h.harvest_date, h.weight_grams, h.quality_status, h.operator,
        pb.id AS pb_id, pb.batch_code, pb.crop_type, pb.status AS batch_status,
        pb.started_at, pb.completed_at, pb.planned_sow_date, pb.expected_harvest_date
    FROM sales s
    LEFT JOIN harvests h ON h.id = s.harvest_id
    LEFT JOIN production_batches pb ON pb.id = s.batch_id
    WHERE s.id = :id
");
$stmt->execute([':id' => $id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sale) { die(__('b09_err_sale_not_found')); }
?>
<div style="max-width: 900px; margin: 0 auto;">
    <h2 style="color:#2c3e50;">🔎 <?php echo __('b09_trace_title'); ?></h2>

    <div class="card" style="background:#fff;padding:20px;border-radius:8px;margin-bottom:20px;">
        <strong><?php echo __('b09_sale'); ?> #<?php echo (int)$sale['id']; ?></strong> →
        <strong><?php echo htmlspecialchars($sale['product'] ?? '-'); ?></strong> →
        <?php if (!empty($sale['harvest_id_real'])): ?>
            <span style="color:#27ae60;font-weight:bold;"><?php echo __('b09_harvest'); ?> #<?php echo (int)$sale['harvest_id_real']; ?></span>
        <?php else: ?><span style="color:#c0392b;"><?php echo __('b09_harvest'); ?> -</span><?php endif; ?>
        →
        <?php if (!empty($sale['pb_id'])): ?>
            <a href="trace.php?code=<?php echo urlencode((string)$sale['batch_code']); ?>" style="color:#2980b9;font-weight:bold;"><?php echo htmlspecialchars($sale['batch_code']); ?></a>
        <?php else: ?><span style="color:#c0392b;"><?php echo __('b09_batch'); ?> -</span><?php endif; ?>
    </div>

    <div class="card" style="background:#fff;padding:20px;border-radius:8px;margin-bottom:20px;">
        <h3 style="margin-top:0;"><?php echo __('b09_sale'); ?></h3>
        <p><strong><?php echo __('b09_sale_date'); ?>:</strong> <?php echo htmlspecialchars($sale['sale_date'] ?? '-'); ?></p>
        <p><strong><?php echo __('b09_package'); ?>:</strong> <?php echo htmlspecialchars($sale['product'] ?? '-'); ?></p>
        <p><strong><?php echo __('b09_weight_g'); ?>:</strong> <?php echo number_format((float)$sale['quantity'],1,',','.'); ?> g</p>
        <p><strong>€:</strong> <?php echo number_format((float)$sale['amount'],2,',','.'); ?></p>
        <p><strong><?php echo __('b09_status'); ?>:</strong> <?php echo htmlspecialchars($sale['status'] ?? '-'); ?></p>
    </div>

    <div class="card" style="background:#fff;padding:20px;border-radius:8px;margin-bottom:20px;">
        <h3 style="margin-top:0;">🌾 <?php echo __('b09_harvest'); ?></h3>
        <p><strong><?php echo __('b09_date'); ?>:</strong> <?php echo htmlspecialchars($sale['harvest_date'] ?? '-'); ?></p>
        <p><strong><?php echo __('b09_weight_g'); ?>:</strong> <?php echo htmlspecialchars((string)($sale['weight_grams'] ?? '-')); ?> g</p>
        <p><strong><?php echo __('b09_quality'); ?>:</strong> <?php echo htmlspecialchars($sale['quality_status'] ?? '-'); ?></p>
        <p><strong><?php echo __('b09_operator'); ?>:</strong> <?php echo htmlspecialchars($sale['operator'] ?? '-'); ?></p>
    </div>

    <div class="card" style="background:#fff;padding:20px;border-radius:8px;margin-bottom:20px;">
        <h3 style="margin-top:0;">🏷️ <?php echo __('b09_batch'); ?></h3>
        <p><strong><?php echo __('b09_batch_code'); ?>:</strong> <?php echo htmlspecialchars($sale['batch_code'] ?? '-'); ?></p>
        <p><strong><?php echo __('b09_crop'); ?>:</strong> <?php echo htmlspecialchars($sale['crop_type'] ?? '-'); ?></p>
        <p><strong><?php echo __('b09_sow_date'); ?>:</strong> <?php echo htmlspecialchars($sale['planned_sow_date'] ?? '-'); ?></p>
        <p><strong><?php echo __('b09_expected_harvest'); ?>:</strong> <?php echo htmlspecialchars($sale['expected_harvest_date'] ?? '-'); ?></p>
        <p><strong><?php echo __('b09_started'); ?>:</strong> <?php echo htmlspecialchars($sale['started_at'] ?? '-'); ?></p>
        <p><strong><?php echo __('b09_completed'); ?>:</strong> <?php echo htmlspecialchars($sale['completed_at'] ?? '-'); ?></p>
        <p><strong><?php echo __('b09_status'); ?>:</strong> <?php echo htmlspecialchars($sale['batch_status'] ?? '-'); ?></p>
    </div>

    <a href="list_sales.php" class="btn">← <?php echo __('b09_back'); ?></a>
</div>