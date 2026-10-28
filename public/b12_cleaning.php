<?php
// B12 Cleaning & Sanitation - SKAL Compliance
ini_set("display_errors", 1); error_reporting(E_ALL);

$db_path = "/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite";
if (!file_exists($db_path)) { die("DB niet gevonden"); }

try {
    $db = new PDO("sqlite:$db_path");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) { die("DB Fout: " . $e->getMessage()); }

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $obj_type = $_POST["object_type"] ?? "Oppervlak";
    $obj_name = $_POST["object_name"] ?? "";
    $agent_id = (int)($_POST["agent_id"] ?? 0);
    $method = $_POST["method"] ?? "Handmatig";
    $operator = $_POST["operator"] ?? "Joep";
    $notes = $_POST["notes"] ?? "";
    
    $stmt = $db->prepare("SELECT name, is_organic_approved FROM cleaning_agents WHERE id = ?");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($agent) {
        $is_validated = ($agent['is_organic_approved'] == 1) ? 1 : 0;
        try {
            $db->prepare("INSERT INTO cleaning_logs (object_type, object_name, cleaned_at, operator_name, supplier_id, product_name, method, is_validated, notes) VALUES (?, ?, datetime('now'), ?, ?, ?, ?, ?, ?)")
               ->execute([$obj_type, $obj_name, $operator, $agent_id, $agent['name'], $method, $is_validated, $notes]);
            $status = $is_validated ? "✅ Gevalideerd" : "⚠️ Niet Gevalideerd";
            $msg = "<div style='background:#d4edda;color:#155724;padding:15px;border-radius:5px;margin-bottom:20px;'>Reiniging geregistreerd! Middel: {$agent['name']} ($status)</div>";
        } catch (Exception $e) { $msg = "<div style='color:red'>Fout: " . $e->getMessage() . "</div>"; }
    }
}

$agents = $db->query("SELECT id, name, brand, is_organic_approved, certificate_ref FROM cleaning_agents ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$logs = $db->query("SELECT cl.*, ca.is_organic_approved as agent_approved FROM cleaning_logs cl LEFT JOIN cleaning_agents ca ON cl.supplier_id = ca.id ORDER BY cl.cleaned_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// --- TOEVOEGEN LOGICA cleaning_logs ---
if (isset($_GET['action']) && $_GET['action'] == 'add_new' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $cols_cleaning_logs = [];
        $vals_cleaning_logs = [];
        $bind_cleaning_logs = [];

        if (isset($_POST['object_name']) && $_POST['object_name'] != '') {
            $cols_cleaning_logs[] = "object_name";
            $vals_cleaning_logs[] = "?";
            $bind_cleaning_logs[] = $_POST['object_name'];
        }

        if (isset($_POST['product_name']) && $_POST['product_name'] != '') {
            $cols_cleaning_logs[] = "product_name";
            $vals_cleaning_logs[] = "?";
            $bind_cleaning_logs[] = $_POST['product_name'];
        }

        if (isset($_POST['method']) && $_POST['method'] != '') {
            $cols_cleaning_logs[] = "method";
            $vals_cleaning_logs[] = "?";
            $bind_cleaning_logs[] = $_POST['method'];
        }

        if (isset($_POST['cleaned_at']) && $_POST['cleaned_at'] != '') {
            $cols_cleaning_logs[] = "cleaned_at";
            $vals_cleaning_logs[] = "?";
            $bind_cleaning_logs[] = $_POST['cleaned_at'];
        }

        if (isset($_POST['operator_name']) && $_POST['operator_name'] != '') {
            $cols_cleaning_logs[] = "operator_name";
            $vals_cleaning_logs[] = "?";
            $bind_cleaning_logs[] = $_POST['operator_name'];
        }

        if (isset($_POST['is_validated']) && $_POST['is_validated'] != '') {
            $cols_cleaning_logs[] = "is_validated";
            $vals_cleaning_logs[] = "?";
            $bind_cleaning_logs[] = $_POST['is_validated'];
        }

        if (!empty($cols_cleaning_logs)) {
            $sql = "INSERT INTO cleaning_logs (" . implode(",", $cols_cleaning_logs) . ") VALUES (" . implode(",", $vals_cleaning_logs) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bind_cleaning_logs);
            $success_msg_cleaning_logs = "✅ Reiniging succesvol toegevoegd!";
        }
    } catch (Exception $e) {
        $error_msg_cleaning_logs = "Fout: " . $e->getMessage();
    }
}
// --- EINDE TOEVOEGEN LOGICA ---

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B12 Reiniging</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: sans-serif; background: #f4f6f9; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #333; }
        label { display: block; margin: 15px 0 5px; font-weight: 600; }
        select, input, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 16px; }
        button { width: 100%; padding: 15px; background: #17a2b8; color: white; border: none; border-radius: 4px; font-size: 18px; font-weight: bold; cursor: pointer; margin-top: 20px; }
        .log-item { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 5px solid #ccc; display: flex; justify-content: space-between; align-items: start; }
        .log-valid { border-left-color: #28a745; }
        .log-invalid { border-left-color: #dc3545; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; color: white; }
        .bg-green { background: #28a745; }
        .bg-red { background: #dc3545; }
    </style>
</head>
<body>
<div style="padding:20px;"><a href="dashboard.php" style="background:#2c3e50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;font-weight:bold;display:inline-block;">← Terug naar Dashboard</a></div>
<div class="container">
    <h1>🧼 B12: Reiniging & Hygiëne</h1>
    <div style="margin: 20px 0; padding: 15px; background: #e8f5e9; border-radius: 5px; border-left: 5px solid #27ae60;">
        <a href="?action=add_new" style="text-decoration: none; color: #27ae60; font-weight: bold; font-size: 1.1em;">
            ➕ Nieuwe Reiniging
        </a>
    </div>
    
    <?= $msg ?>
    
    <form method="POST">
        <label>Wat wordt gereinigd?</label>
        <div style="display:flex; gap:10px;">
            <select name="object_type" style="flex:1;">
                <option>Oppervlak</option><option>Apparatuur</option><option>Kweekruimte</option><option>GN-Bakken</option>
            </select>
            <input type="text" name="object_name" placeholder="Bijv. Snijtafel 1" required style="flex:2;">
        </div>

        <label>Reinigingsmiddel:</label>
        <select name="agent_id" required id="agentSelect" onchange="updateAgentInfo()">
            <option value="">-- Kies Middelen --</option>
            <?php foreach($agents as $a): ?>
            <option value="<?= $a['id'] ?>" data-approved="<?= $a['is_organic_approved'] ?>">
                <?= htmlspecialchars($a['name']) ?> (<?= htmlspecialchars($a['brand']) ?>)
            </option>
            <?php endforeach; ?>
        </select>
        <div id="agentInfo" style="font-size:0.9em; margin-top:5px; color:#666;"></div>

        <label>Methode:</label>
        <select name="method"><option>Handmatig</option><option>Sproeien</option><option>Dompelen</option></select>

        <label>Notities:</label>
        <textarea name="notes" rows="2" placeholder="Bijv. 2% oplossing"></textarea>

        <label>Medewerker:</label>
        <input type="text" name="operator" value="Joep">

        <button type="submit">REINIGING REGISTREREN</button>
    </form>

    <h3 style="margin-top:40px;">📜 Recent Gereinigd</h3>
    <?php if(empty($logs)): ?><p>Geen logs gevonden.</p><?php else: ?>
        <?php foreach($logs as $l): 
            $validClass = $l['agent_approved'] ? 'log-valid' : 'log-invalid';
            $badge = $l['agent_approved'] ? '<span class="badge bg-green">Organic OK</span>' : '<span class="badge bg-red">Niet Gevalideerd</span>';
        ?>
        <div class="log-item <?= $validClass ?>">
            <div>
                <strong><?= htmlspecialchars($l['object_name']) ?></strong> (<?= $l['object_type'] ?>)<br>
                <small><?= $l['cleaned_at'] ?> door <?= htmlspecialchars($l['operator_name']) ?></small><br>
                Middel: <strong><?= htmlspecialchars($l['product_name']) ?></strong>
            </div>
            <div><?= $badge ?></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function updateAgentInfo() {
    var select = document.getElementById('agentSelect');
    var option = select.options[select.selectedIndex];
    var approved = option.getAttribute('data-approved');
    var infoDiv = document.getElementById('agentInfo');
    if (approved == '1') { infoDiv.innerHTML = "✅ Dit middel is Organic Gevalideerd."; infoDiv.style.color = "green"; }
    else if (approved == '0') { infoDiv.innerHTML = "⚠️ Dit middel is NIET Organic Gevalideerd."; infoDiv.style.color = "red"; }
    else { infoDiv.innerHTML = ""; }
}
</script>

<!-- FORMULIER cleaning_logs -->
<?php if (isset($_GET['action']) && $_GET['action'] == 'add_new'): ?>
<div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-top: 20px; border: 1px solid #ddd;">
    <h2>➕ Nieuwe Reiniging</h2>
    <?php if (isset($success_msg_cleaning_logs)): ?><div style="color: green; margin-bottom: 10px; font-weight:bold;"><?php echo $success_msg_cleaning_logs; ?></div><?php endif; ?>
    <?php if (isset($error_msg_cleaning_logs)): ?><div style="color: red; margin-bottom: 10px; font-weight:bold;"><?php echo $error_msg_cleaning_logs; ?></div><?php endif; ?>
    
    <form method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
<div><label style='display:block; margin-bottom:5px; font-weight:bold;'>Object / Ruimte</label><input type='text' name='object_name' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;' required></div><div><label style='display:block; margin-bottom:5px; font-weight:bold;'>Gebruikt Middel</label><input type='text' name='product_name' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;' required></div><div><label style='display:block; margin-bottom:5px; font-weight:bold;'>Methode</label><input type='text' name='method' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;' required></div><div><label style='display:block; margin-bottom:5px; font-weight:bold;'>Datum/Tijd</label><input type='datetime-local' name='cleaned_at' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;' required></div><div><label style='display:block; margin-bottom:5px; font-weight:bold;'>Uitvoerder</label><input type='text' name='operator_name' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;' required></div><div><label style='display:block; margin-bottom:5px; font-weight:bold;'>Gevalideerd?</label><select name='is_validated' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;'><option value='1'>1</option><option value='0'>0</option></select></div>
        </div>
        <div style="margin-top: 20px;">
            <button type="submit" style="background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px;">💾 Opslaan</button>
            <a href="?" style="margin-left: 10px; text-decoration: none; color: #555;">Annuleren</a>
        </div>
    </form>
</div>
<?php endif; ?>

</body>
</html>
