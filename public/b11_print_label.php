<?php
/**
 * B11 - Print Label Generator
 * Doel: Printvriendelijk label met QR-code voor traceability.
 * Usage: b11_print_label.php?label=PKG-GOLDEN-TEST-001
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$dbPath = __DIR__ . '/../database/MicrogreensERP_Live.sqlite';
if (!file_exists($dbPath)) { die("DB niet gevonden"); }
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Fout: " . $e->getMessage()); }

$label_code = $_GET['label'] ?? '';
$data = null;
$error = '';

if (!empty($label_code)) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                pu.label_code, pu.package_type, pu.total_weight_g, pu.best_before_date, 
                pu.organic_status_snapshot, pu.created_at,
                pb.batch_code, pb.crop_type,
                si.certificate_ref, si.organic_status as seed_organic_status
            FROM packaging_units pu
            JOIN production_batches pb ON pu.batch_id = pb.id
            LEFT JOIN seed_inventory si ON pb.crop_type = si.crop_type 
                AND si.organic_status = 'APPROVED'
            WHERE pu.label_code = ?
            ORDER BY pu.created_at DESC LIMIT 1
        ");
        $stmt->execute([$label_code]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) { $error = "Label niet gevonden."; }
    } catch (Exception $e) { $error = "Fout: " . $e->getMessage(); }
}

// Genereer QR Code Content (URL naar traceability of pure data string)
// Voor nu: Een string die een scanner kan lezen met de kerninfo
$qr_content = "";
if ($data) {
    $qr_content = "BATCH:{$data['batch_code']}\nPRODUCT:{$data['crop_type']}\nWEIGHT:{$data['total_weight_g']}g\nTHT:{$data['best_before_date']}\nORGANIC:{$data['organic_status_snapshot']}";
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Print Label - <?= htmlspecialchars($label_code) ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; background: #eee; padding: 20px; }
        .no-print { margin-bottom: 20px; background: #fff; padding: 15px; border-radius: 5px; }
        
        /* LABEL STIJL (A6 of 100x150mm formaat) */
        .label-container {
            width: 380px; /* ~100mm */
            height: 550px; /* ~150mm */
            background: #fff;
            border: 2px solid #000;
            padding: 15px;
            box-sizing: border-box;
            margin: 0 auto;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header .organic-logo { font-size: 12px; font-weight: bold; color: #006400; margin-top: 5px; }
        
        .product-info { font-size: 18px; line-height: 1.4; }
        .product-name { font-size: 28px; font-weight: bold; margin: 10px 0; text-transform: uppercase; }
        .batch-code { font-family: 'Courier New', monospace; font-size: 14px; background: #f0f0f0; padding: 2px 5px; }
        
        .details { margin-top: 10px; font-size: 16px; }
        .details div { margin-bottom: 5px; display: flex; justify-content: space-between; }
        .label-bold { font-weight: bold; }
        
        .qr-section { text-align: center; margin-top: 15px; border-top: 1px dashed #000; padding-top: 10px; }
        .qr-code { 
            width: 120px; height: 120px; 
            margin: 0 auto; 
            background-image: url('https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($qr_content) ?>');
            background-size: cover;
        }
        .qr-text { font-size: 10px; margin-top: 5px; color: #555; }
        
        .footer { font-size: 10px; text-align: center; margin-top: 10px; color: #666; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none; }
            .label-container { border: 1px solid #000; margin: 0; page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <h2>🖨️ Label Preview</h2>
    <?php if ($error): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($data): ?>
        <p><strong>Label:</strong> <?= htmlspecialchars($data['label_code']) ?></p>
        <button onclick="window.print()" style="padding:10px 20px; font-size:16px; cursor:pointer;">🖨️ Print Label</button>
        <br><br>
        <p><em>Tip: Gebruik 'Save as PDF' als je geen printer hebt aangesloten.</em></p>
    <?php else: ?>
        <form method="GET">
            <label>Label Code:</label>
            <input type="text" name="label" placeholder="Bijv. PKG-GOLDEN-TEST-001" style="padding:5px; width:200px;">
            <button type="submit">Toon Label</button>
        </form>
        <p><em>Voorbeeld: <a href="?label=PKG-GOLDEN-TEST-001">PKG-GOLDEN-TEST-001</a></em></p>
    <?php endif; ?>
</div>

<?php if ($data): ?>
<div class="label-container">
    <div>
        <div class="header">
            <h1><?= htmlspecialchars($data['crop_type']) ?></h1>
            <?php if ($data['organic_status_snapshot'] === 'APPROVED'): ?>
                <div class="organic-logo">🌱 BIOLOGISCH GETEELT</div>
                <div style="font-size:10px;">Certificaat: <?= htmlspecialchars($data['certificate_ref'] ?? 'Niet gevonden') ?></div>
            <?php else: ?>
                <div class="organic-logo">CONVENTIONEEL</div>
            <?php endif; ?>
        </div>

        <div class="product-info">
            <div class="product-name"><?= htmlspecialchars($data['crop_type']) ?></div>
            <div>Batch: <span class="batch-code"><?= htmlspecialchars($data['batch_code']) ?></span></div>
            <div>Netto Gewicht: <strong><?= number_format($data['total_weight_g'], 0) ?> gram</strong></div>
        </div>

        <div class="details">
            <div><span class="label-bold">THT Datum:</span> <span><?= date('d-m-Y', strtotime($data['best_before_date'])) ?></span></div>
            <div><span class="label-bold">Verpakt op:</span> <span><?= date('d-m-Y', strtotime($data['created_at'])) ?></span></div>
            <div><span class="label-bold">Verpakking:</span> <span><?= htmlspecialchars($data['package_type']) ?></span></div>
        </div>
    </div>

    <div class="qr-section">
        <div class="qr-code"></div>
        <div class="qr-text">Scan voor traceability info</div>
        <div style="font-size:9px; font-family:monospace; margin-top:5px;"><?= htmlspecialchars($data['label_code']) ?></div>
    </div>
    
    <div class="footer">
        Microgreens ERP &copy; <?= date('Y') ?>
    </div>
</div>
<?php endif; ?>

</body>
</html>
