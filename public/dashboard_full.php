<?php
/**
 * Full Stack Dashboard (B01 - B08)
 * Toont de volledige staat van het Microgreens ERP systeem.
 */

require_once __DIR__ . '/../app/includes/db_connect.php';
require_once __DIR__ . '/../app/includes/layout_start.php';

// Fetch data voor de cards
$stats = [];
$stats['batches'] = $db->query("SELECT COUNT(*) FROM production_batches")->fetchColumn();
$stats['outputs'] = $db->query("SELECT COUNT(*) FROM production_outputs")->fetchColumn();
$stats['processes'] = $db->query("SELECT COUNT(*) FROM freeze_dry_processes")->fetchColumn();
$stats['measurements'] = $db->query("SELECT COUNT(*) FROM measurements")->fetchColumn();
$stats['findings'] = $db->query("SELECT COUNT(*) FROM b02_findings WHERE status != 'RESOLVED'")->fetchColumn();
$stats['allocations'] = $db->query("SELECT COUNT(*) FROM tray_assignments")->fetchColumn();
?>

<div class="container" style="max-width: 1400px; margin: 20px auto; font-family: 'Segoe UI', sans-serif;">
    <h1 style="color: #333;">Microgreens ERP - Master Dashboard (B01-B08)</h1>
    <p style="margin-bottom: 30px; color: #666;">Volledig overzicht van Allocatie tot Freeze-Dry Processen.</p>

    <!-- KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; border-left: 5px solid #2196f3;">
            <h3 style="margin: 0; color: #0d47a1;">Productie Batches</h3>
            <p style="font-size: 2em; margin: 10px 0 0 0; font-weight: bold;"><?php echo $stats['batches']; ?></p>
            <small>B05</small>
        </div>
        <div style="background: #e8f5e9; padding: 20px; border-radius: 8px; border-left: 5px solid #4caf50;">
            <h3 style="margin: 0; color: #1b5e20;">Outputs</h3>
            <p style="font-size: 2em; margin: 10px 0 0 0; font-weight: bold;"><?php echo $stats['outputs']; ?></p>
            <small>B07 (Fresh/FD)</small>
        </div>
        <div style="background: #fff3e0; padding: 20px; border-radius: 8px; border-left: 5px solid #ff9800;">
            <h3 style="margin: 0; color: #e65100;">FD Processen</h3>
            <p style="font-size: 2em; margin: 10px 0 0 0; font-weight: bold;"><?php echo $stats['processes']; ?></p>
            <small>B08</small>
        </div>
        <div style="background: #f3e5f5; padding: 20px; border-radius: 8px; border-left: 5px solid #9c27b0;">
            <h3 style="margin: 0; color: #4a148c;">Metingen</h3>
            <p style="font-size: 2em; margin: 10px 0 0 0; font-weight: bold;"><?php echo $stats['measurements']; ?></p>
            <small>B06 (General)</small>
        </div>
        <div style="background: #ffebee; padding: 20px; border-radius: 8px; border-left: 5px solid #f44336;">
            <h3 style="margin: 0; color: #b71c1c;">Open Issues</h3>
            <p style="font-size: 2em; margin: 10px 0 0 0; font-weight: bold;"><?php echo $stats['findings']; ?></p>
            <small>B02</small>
        </div>
        <div style="background: #e0f7fa; padding: 20px; border-radius: 8px; border-left: 5px solid #00bcd4;">
            <h3 style="margin: 0; color: #006064;">Allocaties</h3>
            <p style="font-size: 2em; margin: 10px 0 0 0; font-weight: bold;"><?php echo $stats['allocations']; ?></p>
            <small>B01</small>
        </div>
    </div>

    <!-- Tabs of Sections -->
    <div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
        <div style="background: #f5f5f5; padding: 10px 20px; border-bottom: 1px solid #ddd;">
            <h2 style="margin: 0; font-size: 1.2em;">Recente Productie & Output Ketens</h2>
        </div>
        
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #eee; text-align: left;">
                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Batch (B05)</th>
                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Allocatie (B01)</th>
                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Output Splitsing (B07)</th>
                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Freeze-Dry (B08)</th>
                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Metingen (B06)</th>
                </tr>
            </thead>
            <tbody>
            <?php
            // Haal recente batches met alle details
            $sql = "SELECT * FROM production_batches ORDER BY created_at DESC LIMIT 10";
            $batches = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            foreach ($batches as $b): 
                // B01: Allocatie
                $allocStmt = $db->prepare("SELECT COUNT(*) as cnt FROM tray_assignments WHERE batch_id = :id");
                $allocStmt->execute([':id' => $b['id']]);
                $allocCount = $allocStmt->fetchColumn();

                // B07: Outputs
                $outStmt = $db->prepare("SELECT * FROM production_outputs WHERE batch_id = :id");
                $outStmt->execute([':id' => $b['id']]);
                $outputs = $outStmt->fetchAll(PDO::FETCH_ASSOC);

                // B06: Metingen (gekoppeld via entity_links of direct als we dat zouden doen, hier simpel gehouden op batch context)
                // Voor nu tonen we algemene metingen als er geen specifieke link is, of we slaan over.
                // Laten we kijken naar entity_links (B04)
                $linkStmt = $db->prepare("SELECT COUNT(*) FROM entity_links WHERE source_entity_type = 'production_batches' AND source_entity_id = :id");
                $linkStmt->execute([':id' => $b['id']]);
                $linkCount = $linkStmt->fetchColumn();
            ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px; vertical-align: top;">
                        <strong><?php echo htmlspecialchars($b['batch_code']); ?></strong><br>
                        <small>Status: <?php echo $b['status']; ?></small><br>
                        <small><?php echo $b['actual_quantity']; ?> <?php echo $b['quantity_unit']; ?></small>
                    </td>
                    <td style="padding: 12px; vertical-align: top;">
                        <?php if ($allocCount > 0): ?>
                            <span style="color: green;">✓ <?php echo $allocCount; ?> trays</span>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 12px; vertical-align: top;">
                        <?php if (empty($outputs)): ?>
                            <span style="color: #999;">Geen outputs</span>
                        <?php else: ?>
                            <?php foreach ($outputs as $o): ?>
                                <div style="margin-bottom: 5px;">
                                    <strong><?php echo $o['output_type']; ?></strong>: <?php echo $o['quantity']; ?><?php echo $o['unit']; ?>
                                    <br><small style="color: #666;"><?php echo $o['status']; ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 12px; vertical-align: top;">
                        <?php 
                        // Check voor freeze dry processes bij deze batch (via outputs)
                        $fdFound = false;
                        foreach ($outputs as $o) {
                            if ($o['output_type'] == 'FREEZE_DRY_INPUT') {
                                $fdStmt = $db->prepare("SELECT * FROM freeze_dry_processes WHERE output_id = :id");
                                $fdStmt->execute([':id' => $o['id']]);
                                $fd = $fdStmt->fetch(PDO::FETCH_ASSOC);
                                if ($fd) {
                                    $fdFound = true;
                                    echo "<div style='background: #fff3e0; padding: 5px; border-radius: 4px; margin-bottom: 5px;'>";
                                    echo "<strong>Machine:</strong> " . htmlspecialchars($fd['machine_identifier']) . "<br>";
                                    if ($fd['yield_percent']) {
                                        echo "<span style='color: green; font-weight: bold;'>Yield: " . number_format($fd['yield_percent'], 1) . "%</span>";
                                    } else {
                                        echo "<span style='color: orange;'>Lopend...</span>";
                                    }
                                    echo "</div>";
                                }
                            }
                        }
                        if (!$fdFound && count($outputs) > 0) echo "<small style='color:#999;'>Geen FD proces</small>";
                        ?>
                    </td>
                    <td style="padding: 12px; vertical-align: top;">
                        <?php if ($linkCount > 0): ?>
                            <span style="color: purple;">✓ <?php echo $linkCount; ?> links</span>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 30px; text-align: right;">
        <a href="batch_output_dashboard.php" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">Gedetailleerd Output Dashboard →</a>
    </div>
</div>

<?php require_once __DIR__ . '/../app/includes/layout_end.php'; ?>
