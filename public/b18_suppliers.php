<?php
// B18 Supplier Management - FINAL FIX (Null Safety + Delete + Date Click)
ini_set("display_errors", 1); error_reporting(E_ALL);

$db_path = "/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite";
if (!file_exists($db_path)) { die("DB niet gevonden"); }

try {
    $db = new PDO("sqlite:$db_path");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) { die("DB Fout: " . $e->getMessage()); }

$msg = "";
$msgType = "";
$today = date("Y-m-d");

// --- VERWERKING ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // 1. VERWIJDEREN
    if (isset($_POST["delete_id"])) {
        $del_id = (int)$_POST["delete_id"];
        try {
            $stmt = $db->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$del_id]);
            $msg = "🗑️ Leverancier succesvol verwijderd!";
            $msgType = "success";
        } catch (Exception $e) {
            $msg = "❌ Fout bij verwijderen: " . $e->getMessage();
            $msgType = "danger";
        }
        echo "<script>window.location.href='b18_suppliers.php';</script>";
        exit;
    }

    // 2. TOEVOEGEN / BEWERKEN
    $name = trim($_POST["name"] ?? "");
    $email = $_POST["contact_email"] ?? "";
    $cert_code = $_POST["certificate_code"] ?? "";
    $cert_expiry = $_POST["certificate_expiry"] ?? "";
    
    if (!empty($name)) {
        $is_expired = ($cert_expiry && $cert_expiry < $today) ? 1 : 0;
        $status = $is_expired ? "EXPIRED" : "VALID";
        $validated = $is_expired ? 0 : 1;

        try {
            $check = $db->prepare("SELECT id FROM suppliers WHERE LOWER(name) = LOWER(?)");
            $check->execute([$name]);
            $existing = $check->fetchColumn();

            if ($existing) {
                $stmt = $db->prepare("UPDATE suppliers SET contact_email=?, certificate_code=?, certificate_expiry=?, organic_status=?, is_skal_validated=? WHERE id=?");
                $stmt->execute([$email, $cert_code, $cert_expiry, $status, $validated, $existing]);
                $msg = "✅ Leverancier '{$name}' bijgewerkt!";
                $msgType = "success";
            } else {
                $stmt = $db->prepare("INSERT INTO suppliers (name, contact_email, certificate_code, certificate_expiry, organic_status, is_skal_validated) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $cert_code, $cert_expiry, $status, $validated]);
                $msg = "✅ Nieuwe leverancier '{$name}' toegevoegd!";
                $msgType = "success";
            }
        } catch (Exception $e) {
            $msg = "❌ Fout: " . $e->getMessage();
            $msgType = "danger";
        }
    }
}

$suppliers = $db->query("SELECT * FROM suppliers ORDER BY is_skal_validated ASC, certificate_expiry ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B18 Leveranciers</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: sans-serif; background: #f4f6f9; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #333; }
        input { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 16px; }
        button { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px; font-size: 14px; }
        .btn-save { background: #007bff; color: white; }
        .btn-cancel { background: #6c757d; color: white; }
        .btn-edit { background: #ffc107; color: #333; padding: 5px 10px; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 10px; }
        
        .date-wrapper { position: relative; height: 52px; margin-top: 8px; }
        .date-wrapper input { pointer-events: none; height: 100%; }
        .date-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; cursor: pointer; background: transparent; border: none; z-index: 10; }
        .date-overlay:hover { background: rgba(0,123,255,0.05); }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; color: white; }
        .bg-green { background: #28a745; }
        .bg-red { background: #dc3545; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div style="padding:20px;"><a href="dashboard.php" style="background:#2c3e50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;font-weight:bold;display:inline-block;">← Terug naar Dashboard</a></div>
<div class="container">
    <h1>🏭 B18: Leveranciers & Certificaten</h1>
    
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= $msg ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="id" id="editId">
        <label><strong>Naam Leverancier *</strong></label>
        <input type="text" name="name" id="editName" required placeholder="Bijv. BioZaad NL">
        
        <label><strong>Email Contact</strong></label>
        <input type="email" name="contact_email" id="editEmail" placeholder="contact@bedrijf.nl">
        
        <div style="display:flex; gap:15px;">
            <div style="flex:1;">
                <label><strong>Certificaat Code</strong></label>
                <input type="text" name="certificate_code" id="editCertCode" placeholder="Bijv. NL-BIO-01">
            </div>
            <div style="flex:1;">
                <label><strong>Verloopdatum *</strong></label>
                <div class="date-wrapper">
                    <input type="date" name="certificate_expiry" id="editExpiry" required>
                    <button type="button" class="date-overlay" onclick="document.getElementById('editExpiry').showPicker()"></button>
                </div>
            </div>
        </div>
        <br>
        <button type="submit" class="btn-save">💾 Opslaan / Bijwerken</button>
        <button type="button" class="btn-cancel" onclick="location.reload()">Annuleren</button>
    </form>

    <h3 style="margin-top:30px;">Huidige Leveranciers</h3>
    <table>
        <thead>
            <tr>
                <th>Naam</th>
                <th>Email</th>
                <th>Code</th>
                <th>Verloop</th>
                <th>Status</th>
                <th>Actie</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($suppliers as $s): 
                // FIX: Zet NULL waarden om naar lege string om deprecated warnings te voorkomen
                $sName = $s['name'] ?? '';
                $sEmail = $s['contact_email'] ?? '';
                $sCode = $s['certificate_code'] ?? '-';
                $sExpiry = $s['certificate_expiry'] ?? '';
                
                $isExpired = ($s['organic_status'] === 'EXPIRED');
                $badgeClass = $isExpired ? 'bg-red' : 'bg-green';
                $statusText = $isExpired ? 'VERLOPEN' : 'Geldig';
                $rowStyle = $isExpired ? 'background:#fff5f5;' : '';
            ?>
            <tr style="<?= $rowStyle ?>">
                <td><strong><?= htmlspecialchars($sName) ?></strong></td>
                <td><?= htmlspecialchars($sEmail) ?></td>
                <td><?= htmlspecialchars($sCode) ?></td>
                <td><?= $sExpiry ?></td>
                <td><span class="badge <?= $badgeClass ?>"><?= $statusText ?></span></td>
                <td>
                    <button class="btn-edit" onclick="edit(<?= $s['id'] ?>, '<?= addslashes($sName) ?>', '<?= addslashes($sEmail) ?>', '<?= addslashes($sCode) ?>', '<?= $sExpiry ?>')">✏️</button>
                    
                    <button class="btn-delete" onclick="deleteSupplier(<?= $s['id'] ?>, '<?= addslashes($sName) ?>')">🗑️</button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($suppliers)): ?>
            <tr><td colspan="6" style="text-align:center;color:#999;">Geen leveranciers gevonden.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function edit(id, name, email, cert, expiry) {
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editCertCode').value = cert;
    document.getElementById('editExpiry').value = expiry;
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function deleteSupplier(id, name) {
    if(confirm('Weet u zeker dat u leverancier "' + name + '" wilt verwijderen?')) {
        var f = document.createElement('form');
        f.method = 'POST';
        var i = document.createElement('input');
        i.type = 'hidden';
        i.name = 'delete_id';
        i.value = id;
        f.appendChild(i);
        document.body.appendChild(f);
        f.submit();
    }
}
</script>
</body>
</html>
