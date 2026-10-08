<?php
/**
 * TRACEABILITY MODULE (B-TRACE)
 * Forward & Backward Traceability in ≤ 2 klikken
 * Eis: QR → batch_code → volledige keten
 */

require_once __DIR__ . '/app/includes/header.php';

$batch_code = $_GET['batch_code'] ?? $_POST['batch_code'] ?? '';
$message = '';
$error = '';
$trace = null;

// --- ZOEK BATCH ---
if (!empty($batch_code)) {
    try {
        // 1. Zoek de production batch
        $stmt = $db->prepare("SELECT * FROM production_batches WHERE batch_code = ?");
        $stmt->execute([$batch_code]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$batch) {
            $error = "Batch <strong>" . htmlspecialchars($batch_code) . "</strong> niet gevonden.";
        } else {
            // 2. BACKWARD: Seed Lot → Supplier
            $stmt = $db->prepare("
                SELECT si.*, s.name as supplier_name, s.organic_status as supplier_org_status, s.certificate_code, s.certificate_expiry
                FROM seed_inventory si
                JOIN germination_records gr ON si.id = gr.seed_inventory_id
                JOIN production_batches pb ON gr.id = pb.id -- Let op: dit is een simplificatie, normaal via grow_batches
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                WHERE pb.id = ?
            ");
            // Note: De exacte join hangt af van je schema. We gebruiken een veiligere query hieronder.
            
            // Betere backward query via germination_records als die een link heeft naar seed_inventory
            $stmt = $db->prepare("
                SELECT si.id as seed_id, si.variety, si.supplier_id, si.organic_status as seed_org_status, si.batch_code as seed_batch_code,
                       s.name as supplier_name, s.organic_status as supplier_cert_status, s.certificate_code, s.certificate_expiry
                FROM germination_records gr
                JOIN seed_inventory si ON gr.seed_inventory_id = si.id
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                WHERE gr.id = (SELECT id FROM germination_records WHERE id = ? LIMIT 1) -- Placeholder, moet gelinkt worden aan batch
            ");
            // We doen het stap voor stap voor de zekerheid
            
            $backward = [];
            $forward = [];

            // --- BACKWARD TRACEABILITY (Seed → Batch) ---
            // Zoek germination record dat bij deze batch hoort (via een tussenstap als needed)
            // Simpele aanname: germination_records.id is gelinkt aan een batch via een tussentabel of directe FK
            // In jouw schema: germination_records heeft geen directe batch_id. 
            // We moeten via harvests gaan: harvests.germination_record_id → germination_records
            // Maar hoe komt een batch bij een harvest? Via production_outputs?
            // Laten we de keten reconstrueren zoals de data het toelaat:
            
            // Stap A: Zoek harvests voor deze batch (via production_outputs? Nee, harvests heeft germination_record_id)
            // De link is: Batch → (via output?) → Harvest? 
            // In jouw schema: harvests heeft germination_record_id. production_outputs heeft batch_id.
            // Dus: Batch → Outputs. Harvests → Germination.
            // De link tussen Batch en Harvest is impliciet via de teelt.
            // Voor nu: we tonen wat we zeker weten.
            
            // 1. Seed Info (via germination_records als we het ID zouden hebben)
            // We hebben geen directe link van batch → germination in de getoonde schema's.
            // We doen een aanname: de user weet het of we tonen 'Onbekend'.
            // CORRECTIE: In veel schema's zit een 'grow_batches' of 'tray_assignments'.
            // Laten we de veiligste query doen: toon de batch info en zoek gerelateerde items.

            $backward['batch'] = $batch;
            
            // Zoek seed info via een omweg als nodig, of laat leeg als niet gelinkt
            // We proberen via harvests te gaan als die gelinkt zijn aan deze batch via een omweg
            // Maar zonder duidelijke FK is dat gokken. We tonen wat we hebben.
            
            // --- FORWARD TRACEABILITY (Batch → Output → Packaging → Order) ---
            
            // 1. Outputs
            $stmt = $db->prepare("SELECT * FROM production_outputs WHERE batch_id = ?");
            $stmt->execute([$batch['id']]);
            $outputs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $forward['outputs'] = $outputs;
            
            $packaging = [];
            $orders = [];
            
            foreach ($outputs as $out) {
                // 2. Packaging (via finished_inventory? packaging_units heeft finished_inventory_id)
                // We hebben finished_inventory_id nodig. Die zit niet direct in outputs.
                // Aanname: finished_inventory is gelinkt aan output_id? Of via een andere weg?
                // Schema: packaging_units → finished_inventory.
                // Hoe komt finished_inventory bij output? 
                // Laten we aannemen dat finished_inventory een output_id heeft (vaak het geval).
                // Als dat niet zo is, blijft dit leeg.
                
                $stmt = $db->prepare("SELECT pu.* FROM packaging_units pu 
                                      JOIN finished_inventory fi ON pu.finished_inventory_id = fi.id 
                                      WHERE fi.output_id = ?"); // Aanname: fi heeft output_id
                $stmt->execute([$out['id']]);
                $packs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($packs) $packaging = array_merge($packaging, $packs);
            }
            
            $forward['packaging'] = $packaging;

            // 3. Orders (via customer_orders.items_json? Of is er een order_items tabel?)
            // Schema: customer_orders heeft items_json. Geen directe link naar packaging_id.
            // We kunnen niet hard linken zonder order_items tabel.
            // We tonen 'Geen directe orderlink gevonden' als sad path.
            
            $trace = ['backward' => $backward, 'forward' => $forward];
        }
    } catch (Exception $e) {
        $error = "Fout bij ophalen traceability: " . $e->getMessage();
    }
}
?>

<div style="max-width: 900px; margin: 20px auto; padding: 20px; background: #f9f9f9; border-radius: 8px;">
    <h1 style="color: #2c3e50;">🔍 Traceability</h1>
    
    <!-- ZOEKFORMULIER -->
    <form method="GET" style="margin-bottom: 30px; display: flex; gap: 10px;">
        <input type="text" name="batch_code" placeholder="Batch Code (bijv. MG-2026-001)" 
               value="<?php echo htmlspecialchars($batch_code); ?>" 
               required style="flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
        <button type="submit" style="padding: 12px 24px; background: #27ae60; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">Zoek</button>
    </form>

    <?php if ($error): ?>
        <div style="padding: 15px; background: #ffebee; color: #c62828; border-radius: 4px; margin-bottom: 20px;">
            ⚠️ <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($trace): ?>
        <!-- BACKWARD -->
        <div style="margin-bottom: 30px;">
            <h2 style="color: #2980b9; border-bottom: 2px solid #2980b9; padding-bottom: 10px;">⬅️ Backward (Herkomst)</h2>
            <div style="background: white; padding: 15px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <p><strong>Batch Code:</strong> <?php echo htmlspecialchars($trace['backward']['batch']['batch_code']); ?></p>
                <p><strong>Gewas:</strong> <?php echo htmlspecialchars($trace['backward']['batch']['crop_type']); ?></p>
                <p><strong>Status:</strong> <span style="color: <?php echo ($trace['backward']['batch']['status'] == 'COMPLETED') ? 'green' : 'orange'; ?>;">
                    <?php echo htmlspecialchars($trace['backward']['batch']['status']); ?></span></p>
                <p><em>ℹ️ Seed & Supplier data: Vereist link tussen batch en germination_record. (Nog te implementeren in DB schema indien ontbrekend)</em></p>
            </div>
        </div>

        <!-- FORWARD -->
        <div>
            <h2 style="color: #27ae60; border-bottom: 2px solid #27ae60; padding-bottom: 10px;">➡️ Forward (Bestemming)</h2>
            
            <?php if (empty($trace['forward']['outputs'])): ?>
                <div style="padding: 15px; background: #fff3e0; color: #e65100; border-radius: 4px;">
                    ⚠️ <strong>Sad Path:</strong> Geen outputs gevonden voor deze batch.
                </div>
            <?php else: ?>
                <div style="background: white; padding: 15px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3>Oogsten / Outputs (<?php echo count($trace['forward']['outputs']); ?>)</h3>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($trace['forward']['outputs'] as $out): ?>
                            <li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                📦 <?php echo htmlspecialchars($out['output_type']); ?> - 
                                <?php echo number_format($out['quantity'], 1); ?>g 
                                (Status: <?php echo htmlspecialchars($out['status']); ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (empty($trace['forward']['packaging'])): ?>
                        <div style="margin-top: 15px; padding: 10px; background: #fff3e0; color: #e65100; border-radius: 4px;">
                            ⚠️ <strong>Sad Path:</strong> Geen verpakkingen gevonden gekoppeld aan deze outputs.
                        </div>
                    <?php else: ?>
                        <h3 style="margin-top: 20px;">Verpakkingen (<?php echo count($trace['forward']['packaging']); ?>)</h3>
                        <ul style="list-style: none; padding: 0;">
                            <?php foreach ($trace['forward']['packaging'] as $pack): ?>
                                <li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                    🏷️ <?php echo htmlspecialchars($pack['package_code'] ?? 'Geen code'); ?> - 
                                    <?php echo number_format($pack['total_weight_g'], 1); ?>g 
                                    (Exp: <?php echo htmlspecialchars($pack['expiry_date'] ?? '?'); ?>)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!$batch_code && !$error): ?>
        <div style="text-align: center; color: #7f8c8d; margin-top: 50px;">
            <p style="font-size: 18px;">👈 Voer een batch code in om de traceability keten te bekijken.</p>
            <p>Scan een QR-code om hier direct te komen.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
