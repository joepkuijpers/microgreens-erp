<?php
/**
 * TRACEABILITY MODULE (B-TRACE)
 * QR -> batch_code -> volledige forward/backward keten in <= 2 klikken
 * Bewezen route (live DB 2026-10-08):
 *   BACKWARD: production_batches -> batch_materials -> seed_inventory -> seed_lots -> suppliers
 *   FORWARD : production_batches -> production_outputs (fresh + freeze-dry takken)
 */
require_once __DIR__ . '/app/includes/header.php';

$batch_code = trim($_GET['batch_code'] ?? '');
$error = '';
$batch = null;
$seed = null;
$outputs = [];
$packaging = [];

if ($batch_code !== '') {
    try {
        $stmt = $db->prepare("SELECT * FROM production_batches WHERE batch_code = ?");
        $stmt->execute([$batch_code]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$batch) {
            $error = "Batch <strong>" . htmlspecialchars($batch_code) . "</strong> niet gevonden.";
        } else {
            // BACKWARD: zaad + leverancier + certificaat
            $stmt = $db->prepare("
                SELECT DISTINCT sl.variety, sl.organic_status, sl.certificate_number,
                       sl.certificate_valid_until, s.name AS supplier, s.is_skal_validated
                FROM batch_materials bm
                JOIN seed_inventory si ON si.id = bm.inventory_id
                LEFT JOIN seed_lots sl ON sl.id = si.seed_lot_id
                LEFT JOIN suppliers s  ON s.id = si.supplier_id
                WHERE bm.batch_id = ?");
            $stmt->execute([$batch['id']]);
            $seed = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            // FORWARD: outputs (fresh + freeze-dry takken onder dezelfde batch)
            $stmt = $db->prepare("SELECT * FROM production_outputs WHERE batch_id = ? ORDER BY id DESC");
            $stmt->execute([$batch['id']]);
            $outputs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $error = "Fout bij traceability: " . htmlspecialchars($e->getMessage());
    }
}

// Organic gate: certificaatstatus bepalen
function cert_state($valid_until) {
    if (empty($valid_until)) return ['orange', 'Geen vervaldatum'];
    if ($valid_until < date('Y-m-d')) return ['red', 'VERLOPEN'];
    return ['green', 'Geldig t/m ' . htmlspecialchars($valid_until)];
}
?>
<div style="max-width:900px;margin:20px auto;padding:20px;background:#f9f9f9;border-radius:8px;font-family:sans-serif;">
    <h1 style="color:#2c3e50;">🔍 Traceability</h1>

    <form method="GET" style="margin-bottom:24px;display:flex;gap:10px;">
        <input type="text" name="batch_code" placeholder="Batch Code (bijv. MG-2026-001)"
               value="<?php echo htmlspecialchars($batch_code); ?>" required
               style="flex:1;padding:12px;border:1px solid #ddd;border-radius:4px;font-size:16px;">
        <button type="submit" style="padding:12px 24px;background:#27ae60;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:16px;">Zoek</button>
    </form>

    <?php if ($error): ?>
        <div style="padding:15px;background:#ffebee;color:#c62828;border-radius:4px;">⚠️ <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($batch): ?>
        <!-- BACKWARD -->
        <div style="margin-bottom:28px;">
            <h2 style="color:#2980b9;border-bottom:2px solid #2980b9;padding-bottom:8px;">⬅️ Backward (Herkomst)</h2>
            <div style="background:#fff;padding:16px;border-radius:6px;box-shadow:0 2px 4px rgba(0,0,0,.08);">
                <p><strong>Batch:</strong> <?php echo htmlspecialchars($batch['batch_code']); ?>
                   &nbsp;|&nbsp; <strong>Gewas:</strong> <?php echo htmlspecialchars($batch['crop_type']); ?>
                   &nbsp;|&nbsp; <strong>Status:</strong> <?php echo htmlspecialchars($batch['status']); ?></p>

                <?php if ($seed): ?>
                    <hr style="border:0;border-top:1px solid #eee;margin:12px 0;">
                    <p><strong>🌱 Zaad:</strong> <?php echo htmlspecialchars($seed['variety'] ?? '?'); ?>
                       &nbsp;(<strong>Bio:</strong> <?php echo htmlspecialchars($seed['organic_status'] ?? '?'); ?>)</p>
                    <p><strong>🏢 Leverancier:</strong> <?php echo htmlspecialchars($seed['supplier'] ?: 'Onbekend (leverancier niet gekoppeld)'); ?>
                       <?php if (!empty($seed['is_skal_validated'])): ?> ✅ SKAL gevalideerd <?php endif; ?></p>
                    <?php list($c, $t) = cert_state($seed['certificate_valid_until']); ?>
                    <p><strong>📜 Certificaat:</strong>
                       <span style="color:<?php echo $c; ?>;font-weight:bold;"><?php echo $t; ?></span>
                       <?php if (!empty($seed['certificate_number'])): ?> (nr. <?php echo htmlspecialchars($seed['certificate_number']); ?>)<?php endif; ?></p>
                    <?php if ($c === 'red'): ?>
                        <div style="margin-top:10px;padding:10px;background:#ffebee;color:#c62828;border-radius:4px;">
                            🚫 <strong>Organic Gate:</strong> certificaat verlopen — batch mag niet als biologisch verkoopbaar worden vrijgegeven.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="margin-top:10px;padding:10px;background:#fff3e0;color:#e65100;border-radius:4px;">
                        ⚠️ <strong>Sad Path:</strong> geen zaadallocatie (batch_materials) gevonden voor deze batch.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- FORWARD -->
        <div>
            <h2 style="color:#27ae60;border-bottom:2px solid #27ae60;padding-bottom:8px;">➡️ Forward (Output-takken)</h2>
            <?php if (empty($outputs)): ?>
                <div style="padding:15px;background:#fff3e0;color:#e65100;border-radius:4px;">⚠️ Geen outputs geregistreerd voor deze batch.</div>
            <?php else: ?>
                <div style="background:#fff;padding:16px;border-radius:6px;box-shadow:0 2px 4px rgba(0,0,0,.08);">
                    <ul style="list-style:none;padding:0;margin:0;">
                        <?php foreach ($outputs as $o): ?>
                            <li style="padding:10px 0;border-bottom:1px solid #eee;">
                                <?php echo $o['output_type'] === 'FRESH' ? '🥬' : ($o['output_type'] === 'WASTE' ? '🗑️' : '❄️'); ?>
                                <strong><?php echo htmlspecialchars($o['output_type']); ?></strong> —
                                <?php echo number_format($o['quantity'], 1); ?> <?php echo htmlspecialchars($o['unit']); ?>
                                <span style="color:#7f8c8d;">(status: <?php echo htmlspecialchars($o['status']); ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p style="font-size:.9em;color:#7f8c8d;margin-top:12px;">
                        ℹ️ Verse en vriesdroog-takken lopen onder dezelfde Production Batch (conditionele branch).
                    </p>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ($batch_code === '' && !$error): ?>
        <div style="text-align:center;color:#7f8c8d;margin-top:40px;">
            <p>👈 Voer een batch code in of scan een QR-code.</p>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
