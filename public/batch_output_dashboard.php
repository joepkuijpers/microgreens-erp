<?php
require_once __DIR__ . '/../app/includes/db_connect.php';
require_once __DIR__ . '/../app/includes/layout_start.php';
$batches = $db->query("SELECT * FROM production_batches ORDER BY completed_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container" style="max-width: 1200px; margin: 20px auto; font-family: sans-serif;">
    <h1>Batch Output & Freeze-Dry Dashboard</h1>
    <table style="width: 100%; border-collapse: collapse;">
        <thead><tr style="background:#f0f0f0; text-align:left;"><th style="padding:10px; border:1px solid #ddd;">Batch</th><th style="padding:10px; border:1px solid #ddd;">Outputs</th><th style="padding:10px; border:1px solid #ddd;">Freeze-Dry Details</th></tr></thead>
        <tbody>
        <?php foreach ($batches as $b): 
            $oStmt = $db->prepare("SELECT * FROM production_outputs WHERE batch_id = :id");
            $oStmt->execute([':id' => $b['id']]); $outputs = $oStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <tr>
                <td style="padding:10px; border:1px solid #ddd;"><strong><?php echo $b['batch_code']; ?></strong> (<?php echo $b['actual_quantity']; ?> <?php echo $b['quantity_unit']; ?>)</td>
                <td style="padding:10px; border:1px solid #ddd;">
                    <?php foreach ($outputs as $o): ?>
                        <div><?php echo $o['output_type']; ?>: <?php echo $o['quantity']; ?><?php echo $o['unit']; ?> (<?php echo $o['status']; ?>)</div>
                        <?php if ($o['output_type'] == 'FREEZE_DRY_INPUT'): 
                            $pStmt = $db->prepare("SELECT * FROM freeze_dry_processes WHERE output_id = :id");
                            $pStmt->execute([':id' => $o['id']]); $p = $pStmt->fetch(PDO::FETCH_ASSOC);
                            if ($p): ?><div style="margin-left:15px; font-size:0.9em;">Machine: <?php echo $p['machine_identifier']; ?>, Yield: <strong><?php echo number_format($p['yield_percent'],1); ?>%</strong></div><?php endif;
                        endif; ?>
                    <?php endforeach; ?>
                </td>
                <td style="padding:10px; border:1px solid #ddd;"><a href="dashboard_full.php">Terug naar Master</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../app/includes/layout_end.php'; ?>
