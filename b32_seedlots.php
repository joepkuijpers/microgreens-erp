<?php
require_once __DIR__ . '/app/includes/header.php';
require_once __DIR__ . '/app/includes/audit.php';
require_once __DIR__ . '/app/includes/auth.php';
$message = ""; $messageType = "";
$u = auth_current_user(); $operator = is_array($u) ? ($u['display_name'] ?? 'system') : 'system';

// ── Opslaan: blackout-standaard per zaadlot ──
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["lot_id"])) {
    $lotId = (int)$_POST["lot_id"];
    try {
        $db->beginTransaction();
        $cur = $db->prepare("SELECT id, variety, blackout_days, blackout_light_hours FROM seed_lots WHERE id = :id");
        $cur->execute([':id' => $lotId]);
        $lot = $cur->fetch(PDO::FETCH_ASSOC);
        if (!$lot) { throw new RuntimeException(__('b32_lot_not_found')); }

        $reset = isset($_POST["reset_blackout"]);
        $rawDays  = trim($_POST["blackout_days"] ?? '');
        $rawLight = trim($_POST["blackout_light_hours"] ?? '');
        if ($reset) {
            $newDays = null; $newLight = null;
        } else {
            $newDays  = $rawDays  === '' ? null : (int)   $rawDays;
            $newLight = $rawLight === '' ? null : (float) $rawLight;
            if ($newDays !== null && ($newDays < 0 || $newDays > 30)) { throw new RuntimeException(__('b02_blackout_days_invalid')); }
        }
        $db->prepare("UPDATE seed_lots SET blackout_days = :d, blackout_light_hours = :l WHERE id = :id")
           ->execute([':d' => $newDays, ':l' => $newLight, ':id' => $lotId]);
        auditLog($db, 'B32_LOT_BLACKOUT_UPDATE', 'seed_lots', $lotId, 'CORRECTION',
            "Blackout-standaard aangepast voor lot #{$lotId} ({$lot['variety']})." . ($reset ? " Teruggezet naar gewasrecept." : ""),
            ['blackout_days' => $lot['blackout_days'], 'blackout_light_hours' => $lot['blackout_light_hours']],
            ['blackout_days' => $newDays, 'blackout_light_hours' => $newLight, 'operator' => $operator],
            'seed_lot', $lotId);
        $db->commit();
        $message = __('b32_lot_saved'); $messageType = "success";
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $message = $e->getMessage(); $messageType = "error";
    }
}

// ── Lijst: elk lot + zijn gewasrecept (voor de placeholder) ──
$lots = $db->query("
    SELECT sl.id, sl.variety, sl.current_stock_grams,
           sl.blackout_days, sl.blackout_light_hours,
           s.name AS supplier,
           cp.blackout_days AS profiel_days
    FROM seed_lots sl
    LEFT JOIN suppliers s ON s.id = sl.supplier_id
    LEFT JOIN crop_profiles cp ON cp.crop_name = sl.variety
    ORDER BY sl.variety ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<div style="max-width:1000px; margin:0 auto;">
    <?php if($message): ?>
        <div style="background:<?php echo $messageType==='error'?'#ffebee':'#e8f5e9'; ?>; color:<?php echo $messageType==='error'?'#c62828':'#2e7d32'; ?>; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <div style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1);">
        <h2 style="margin-top:0;"><?php echo htmlspecialchars(__('b32_title')); ?></h2>
        <p style="color:#666; font-size:0.85rem;"><?php echo htmlspecialchars(__('b32_intro')); ?></p>
        <?php if (empty($lots)): ?>
            <p><?php echo htmlspecialchars(__('b32_none')); ?></p>
        <?php else: ?>
        <table style="width:100%; border-collapse:collapse;">
            <thead><tr style="text-align:left; border-bottom:2px solid #ddd;">
                <th style="padding:8px;"><?php echo htmlspecialchars(__('b32_th_variety')); ?></th>
                <th style="padding:8px;"><?php echo htmlspecialchars(__('b32_th_supplier')); ?></th>
                <th style="padding:8px;"><?php echo htmlspecialchars(__('b32_th_stock')); ?></th>
                <th style="padding:8px;"><?php echo htmlspecialchars(__('b32_th_profile')); ?></th>
                <th style="padding:8px;"><?php echo htmlspecialchars(__('b32_th_lot')); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($lots as $l): ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:8px;"><?php echo htmlspecialchars($l['variety']); ?></td>
                    <td style="padding:8px;"><?php echo htmlspecialchars($l['supplier'] ?? '-'); ?></td>
                    <td style="padding:8px;"><?php echo number_format((float)$l['current_stock_grams'],1); ?>g</td>
                    <td style="padding:8px; color:#888;"><?php echo $l['profiel_days'] !== null ? (int)$l['profiel_days'].'d' : '—'; ?></td>
                    <td style="padding:8px;">
                        <form method="POST" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <input type="hidden" name="lot_id" value="<?php echo (int)$l['id']; ?>">
                            <input type="number" name="blackout_days" min="0" max="30" step="1"
                                   value="<?php echo $l['blackout_days'] !== null ? (int)$l['blackout_days'] : ''; ?>"
                                   placeholder="<?php echo $l['profiel_days'] !== null ? (int)$l['profiel_days'] : 'leeg'; ?>"
                                   style="width:70px;padding:4px;border:1px solid #ddd;border-radius:4px;">
                            <?php if ($l['blackout_days'] !== null): ?>
                                <span style="color:#f57c00;font-weight:bold;font-size:0.8em;"><?php echo htmlspecialchars(__('b32_badge_lot')); ?></span>
                                <button type="submit" name="reset_blackout" value="1"
                                        style="background:#eee;color:#333;border:none;padding:3px 8px;border-radius:4px;cursor:pointer;font-size:0.8em;"><?php echo htmlspecialchars(__('b32_btn_reset')); ?></button>
                            <?php endif; ?>
                            <button type="submit" style="background:#1976d2;color:#fff;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-size:0.8em;"><?php echo htmlspecialchars(__('b32_btn_save')); ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/app/includes/layout_end.php'; ?>
</main></div></body></html>