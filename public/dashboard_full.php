<?php
/**
 * Full Stack Dashboard (B01 - B08)
 * Toont de volledige staat van het Microgreens ERP systeem.
 */

require_once __DIR__ . '/../app/includes/db_connection.php';
require_once __DIR__ . '/../app/includes/auth.php';
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

<!-- START: Nieuwe Live Teelt & Capaciteit Widget -->
    <div style="margin-bottom: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        
        <!-- Widget A: Actieve Batch Status (B01 Engine) -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden;">
            <div style="background: #e3f2fd; padding: 15px; border-bottom: 1px solid #bbdefb;">
                <h3 style="margin: 0; color: #0d47a1; font-size: 1.1em;">🌱 Live Batch Status</h3>
            </div>
            <div id="live-batch-widget" style="padding: 20px; text-align: center; color: #666;">
                Data laden...
            </div>
        </div>

        <!-- Widget B: Visuele Rack Indeling (B04 Engine) -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden;">
            <div style="background: #e8f5e9; padding: 15px; border-bottom: 1px solid #c8e6c9;">
                <h3 style="margin: 0; color: #1b5e20; font-size: 1.1em;">🏭 Rack Capaciteit (RACK-A)</h3>
            </div>
            <div id="live-rack-widget" style="padding: 20px; text-align: center; color: #666;">
                Data laden...
            </div>
        </div>
    </div>

    <script>
    // Functie om data op te halen van de nieuwe modules
    async function fetchModule(moduleName) {
        try {
            const resp = await fetch('?module=' + moduleName);
            if (!resp.ok) throw new Error('Netwerk fout');
            return await resp.json();
        } catch (e) {
            console.error('Fout bij ' + moduleName, e);
            return null;
        }
    }

    // Render Batch Status
    function renderBatchStatus(data) {
        const container = document.getElementById('live-batch-widget');
        if (!data) { container.innerHTML = '❌ Fout bij laden'; return; }

        let html = '';
        
        if (data.active) {
            const isOverdue = data.overdue.some(b => b.id === data.active.id);
            const statusColor = isOverdue ? '#d32f2f' : '#388e3c';
            const statusText = isOverdue ? '⚠️ TE LAAT (Overdue)' : '✅ Actief';
            const bgColor = isOverdue ? '#ffebee' : '#e8f5e9';

            html += `<div style="background: ${bgColor}; padding: 15px; border-radius: 6px; border-left: 5px solid ${statusColor}; text-align: left;">
                <div style="font-size: 1.2em; font-weight: bold; color: ${statusColor};">${statusText}</div>
                <div style="font-size: 1.4em; margin: 10px 0;">${data.active.crop_name}</div>
                <div style="font-size: 0.9em; color: #555;">
                    Gezaaid: <strong>${data.active.sow_date}</strong><br>
                    Verwachte Oogst: <strong>${data.active.harvest_end_date || 'Nvt'}</strong><br>
                    Aantal Trays: <strong>${data.active.tray_count}</strong>
                </div>
            </div>`;
        } else {
            html += `<div style="padding: 20px; color: #999;">Geen actieve batch gevonden.<br><small>Start een nieuwe batch in de planner.</small></div>`;
        }

        // Toon waarschuwing als er andere overdue batches zijn
        const otherOverdue = data.overdue.filter(b => !data.active || b.id !== data.active.id);
        if (otherOverdue.length > 0) {
            html += `<div style="margin-top: 15px; padding: 10px; background: #fff3e0; color: #e65100; border-radius: 4px; font-size: 0.9em;">
                ⚠️ Er zijn ${otherOverdue.length} andere batch(es) te laat!
            </div>`;
        }

        container.innerHTML = html;
    }

    // Render Rack Visualisatie
    function renderRackVisual(data) {
        const container = document.getElementById('live-rack-widget');
        if (!data || !data.racks || data.racks.length === 0) {
            container.innerHTML = 'Geen rack data beschikbaar';
            return;
        }

        const rack = data.racks[0]; // We tonen RACK-A
        const summary = data.summary;
        
        let html = `<div style="margin-bottom: 15px; display: flex; justify-content: space-around; font-size: 0.9em;">
            <span style="color: #2e7d32;"><strong>${summary.free_positions}</strong> Vrij</span>
            <span style="color: #c62828;"><strong>${summary.occupied_positions}</strong> Bezet</span>
            <span style="color: #1565c0;"><strong>${summary.occupancy_percent}%</strong> Vol</span>
        </div>`;

        html += `<div style="display: inline-block; border: 2px solid #555; padding: 10px; border-radius: 4px; background: #fafafa;">`;

        // Loop door schappen (van boven naar beneden, dus 6 naar 1 of 1 naar 6, laten we 1-6 doen)
        for (let i = 1; i <= 6; i++) {
            const shelf = rack.shelves[i];
            if (!shelf) continue;

            html += `<div style="display: flex; gap: 5px; margin-bottom: 5px; align-items: center;">
                <div style="width: 20px; font-weight: bold; font-size: 0.8em; color: #555;">S${i}</div>`;
            
            shelf.forEach(slot => {
                const isOcc = slot.occupied;
                const bg = isOcc ? '#2e7d32' : '#e0e0e0';
                const color = isOcc ? '#fff' : '#aaa';
                const text = isOcc ? '🌱' : '';
                const title = isOcc ? `${slot.crop} (Batch ${slot.batch_id})` : 'Leeg';

                html += `<div style="width: 35px; height: 35px; background: ${bg}; color: ${color}; 
                         border-radius: 3px; display: flex; align-items: center; justify-content: center; 
                         font-size: 1.2em; cursor: default; border: 1px solid #ccc;" title="${title}">
                         ${text}
                       </div>`;
            });
            html += `</div>`;
        }
        html += `</div>`;

        container.innerHTML = html;
    }

    // Initialisatie bij laden
    (async function initDashboardWidgets() {
        const [batchData, rackData] = await Promise.all([
            fetchModule('b01_batch_queue'),
            fetchModule('b04_rack_capacity')
        ]);

        renderBatchStatus(batchData);
        renderRackVisual(rackData);
    })();
    </script>
    <!-- END: Nieuwe Live Teelt & Capaciteit Widget -->

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
                                $fdStmt = $db->prepare("SELECT * FROM freeze_dry_processes WHERE production_output_id = :id");
                                $fdStmt->execute([':id' => $o['id']]);
                                $fd = $fdStmt->fetch(PDO::FETCH_ASSOC);
                                if ($fd) {
                                    $fdFound = true;
                                    echo "<div style='background: #fff3e0; padding: 5px; border-radius: 4px; margin-bottom: 5px;'>";
                                   // Gebruik de juiste kolomnaam 'machine_id'
$machineName = $fd['machine_id'] ?? 'Onbekend';
echo "<strong>Machine:</strong> " . htmlspecialchars($machineName) . "<br>";

// Bereken yield percentage als de data er is
if (!empty($fd['final_weight_g']) && !empty($fd['input_weight_g'])) {
    $yield = ($fd['final_weight_g'] / $fd['input_weight_g']) * 100;
    echo "<span style='color: green; font-weight: bold;'>Yield: " . number_format($yield, 1) . "%</span>";
} else {
    // Toon status als yield nog niet berekend kan worden
    echo "<span style='color: orange;'>Status: " . htmlspecialchars($fd['status']) . "</span>";
}
                                    } else {
                                        echo "<span style='color: orange;'>Lopend...</span>";
                                    }
                                    echo "</div>";
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
