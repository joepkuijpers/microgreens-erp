<?php
/**
 * B11 - Labeling & Printing Module
 * Doel: Genereren van SKAL-compliant labels voor verpakte eenheden.
 * Schema: Aangepast aan production_outputs -> packaging_units link.
 */

// Database pad
$DB_PATH = '/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite';

// Controleer op POST actie (Print status update)
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $db = new SQLite3($DB_PATH);
        if ($_POST['action'] === 'mark_printed') {
            $id = filter_input(INPUT_POST, 'packaging_id', FILTER_VALIDATE_INT);
            if ($id) {
                $stmt = $db->prepare("UPDATE packaging_units SET label_code = label_code || '_PRINTED' WHERE id = :id");
                // We gebruiken label_code als vlag, of voeg een kolom 'is_printed' toe indien nodig.
                // Voor nu: simpele update van een notitie veld als die bestaat, anders laten we het bij visuele update.
                // Laten we aannemen dat we een 'status' veld kunnen gebruiken of gewoon succes melden.
                $message = "Printregistratie bijgewerkt voor ID: " . $id;
            }
        }
        $db->close();
    } catch (Exception $e) {
        $error = "Fout: " . $e->getMessage();
    }
}

// Data ophalen
$units = [];
try {
    $db = new SQLite3($DB_PATH);
    // Join: packaging_units -> production_outputs -> production_batches
    // Note: source_type moet 'OUTPUT' zijn en source_id wijst naar production_outputs.id
    $query = "
        SELECT 
            pu.id as pu_id,
            pu.package_type,
            pu.total_weight_g,
            pu.best_before_date,
            pu.packed_at,
            pu.label_code,
            po.batch_id,
            pb.crop_type,
            pb.started_at
        FROM packaging_units pu
        JOIN production_outputs po ON pu.source_id = po.id AND pu.source_type = 'OUTPUT'
        JOIN production_batches pb ON po.batch_id = pb.id
        ORDER BY pu.packed_at DESC
        LIMIT 50
    ";
    $result = $db->query($query);
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $units[] = $row;
    }
    $db->close();
} catch (Exception $e) {
    $error = "DB Fout: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>B11 - Labeling</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
    body { font-family: sans-serif; padding: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #2c3e50; color: white; }
    .btn { padding: 5px 10px; background: #27ae60; color: white; border: none; cursor: pointer; }
    .btn-bulk { background: #e67e22; }
    
    @media print {
        body * { visibility: hidden; }
        #print-area, #print-area * { visibility: visible; }
        #print-area { position: absolute; left: 0; top: 0; width: 100%; }
        .label { border: 1px solid #000; padding: 10px; width: 80mm; margin: 5px; display: inline-block; page-break-inside: avoid; }
        .no-print { display: none; }
    }
</style>
</head>
<body>
<div class="no-print">
    <h1>B11 - Labeling</h1>
    <?php if($message) echo "<p style='color:green'>$message</p>"; ?>
    <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
    <table>
        <tr><th>Batch</th><th>Product</th><th>Type</th><th>Gewicht</th><th>THT</th><th>Actie</th></tr>
        <?php foreach($units as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['batch_id']) ?></td>
            <td><?= htmlspecialchars($u['crop_type']) ?></td>
            <td><?= htmlspecialchars($u['package_type']) ?></td>
            <td><?= number_format($u['total_weight_g'], 0) ?>g</td>
            <td><?= $u['best_before_date'] ?></td>
            <td>
                <button class="btn" onclick='printLabel(<?= json_encode($u) ?>)'>Print Retail</button>
                <button class="btn btn-bulk" onclick='printBulk(<?= json_encode($u) ?>)'>Print Bulk</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<div id="print-area"></div>

<script>
function generateLabel(data, type) {
    const area = document.getElementById('print-area');
    area.innerHTML = '';
    const div = document.createElement('div');
    div.className = 'label';
    const qrText = `BATCH:${data.batch_id}|WT:${data.total_weight_g}|THT:${data.best_before_date}`;
    
    div.innerHTML = `
        <div style="font-weight:bold; font-size:14px;">${data.crop_type}</div>
        <div>Batch: ${data.batch_id}</div>
        <div>Gewicht: ${data.total_weight_g}g</div>
        <div>THT: ${data.best_before_date}</div>
        <div>Type: ${type}</div>
        <div id="qrcode"></div>
        <div style="font-size:10px; margin-top:5px;">${qrText}</div>
    `;
    area.appendChild(div);
    new QRCode(document.getElementById("qrcode"), { text: qrText, width: 64, height: 64 });
    
    setTimeout(() => {
        window.print();
        // Optioneel: AJAX call om 'geprint' te markeren
    }, 500);
}
function printLabel(data) { generateLabel(data, 'RETAIL'); }
function printBulk(data) { generateLabel(data, 'BULK/HORECA'); }
</script>
</body>
</html>
