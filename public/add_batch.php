<?php
require_once '../app/includes/auth.php';
// auth_require_login(); // Uit voor test
include '../app/includes/header.php';
include '../app/includes/language.php';
include '../app/includes/sidebar.php';
include '../app/db_connect.php';

$inventoryItems = $db->query("SELECT id, item_name, quantity, unit FROM inventory ORDER BY item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$formData = ['crop'=>'','sow_date'=>'','expected_harvest_date'=>'','tray_count'=>1,'tray_type'=>'1020','inventory_id'=>'','seed_amount'=>'','status'=>'Groeiend'];
$error = null; $success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = ['crop'=>trim($_POST['crop']??''), 'sow_date'=>trim($_POST['sow_date']??''), 'expected_harvest_date'=>trim($_POST['expected_harvest_date']??''), 'tray_count'=>(int)($_POST['tray_count']??1), 'tray_type'=>trim($_POST['tray_type']??'1020'), 'inventory_id'=>(int)($_POST['inventory_id']??0), 'seed_amount'=>(float)($_POST['seed_amount']??0), 'status'=>trim($_POST['status']??'Groeiend')];
    try {
        require_once '../app/b01_allocation_engine.php';
        $engine = new B01AllocationEngine($db);
        $result = $engine->createBatchWithAllocation($formData);
        header("Location: add_batch.php?success=1"); exit;
    } catch (Exception $e) { $error = $e->getMessage(); }
}
if (isset($_GET['success'])) $success = "Batch succesvol aangemaakt!";
?>
<div class="main">
<h1 style="font-size:28px; margin-bottom:20px;">🌱 Nieuwe Batch</h1>

<?php if($error): ?>
<div style="background:#f8d7da;color:#721c24;padding:15px;margin-bottom:20px;border:1px solid #f5c6cb;border-radius:8px;font-size:16px;">
    <strong>⚠️ Fout:</strong> <?=htmlspecialchars($error)?>
</div>
<?php endif; ?>

<?php if($success): ?>
<div style="background:#d4edda;color:#155724;padding:15px;margin-bottom:20px;border:1px solid #c3e6cb;border-radius:8px;font-size:16px;">
    ✅ <?=$success?>
</div>
<?php endif; ?>

<div style="background:#fff;padding:25px;border-radius:12px;box-shadow:0 4px 10px rgba(0,0,0,0.1);max-width:650px;">
<form method="post">
    <!-- Gewas -->
    <div style="margin-bottom:20px;">
        <label style="display:block;font-weight:bold;margin-bottom:8px;font-size:16px;">Gewas</label>
        <input type="text" name="crop" value="<?=htmlspecialchars($formData['crop'])?>" required style="width:100%;padding:14px;font-size:18px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;">
    </div>

    <!-- Datums (Grote Klikbare Balken) -->
    <div style="margin-bottom:20px;">
        <label style="display:block;font-weight:bold;margin-bottom:8px;font-size:16px;">Zaaidatum</label>
        <div style="position:relative;">
            <input type="date" name="sow_date" value="<?=htmlspecialchars($formData['sow_date'])?>" required style="width:100%;padding:14px;font-size:18px;border:2px solid #ddd;border-radius:8px;appearance:none;-webkit-appearance:none;-moz-appearance:none;cursor:pointer;background:#fff url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="%23555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>') no-repeat right 14px center;">
        </div>
    </div>

    <div style="margin-bottom:20px;">
        <label style="display:block;font-weight:bold;margin-bottom:8px;font-size:16px;">Verwachte Oogstdatum</label>
        <div style="position:relative;">
            <input type="date" name="expected_harvest_date" value="<?=htmlspecialchars($formData['expected_harvest_date'])?>" required style="width:100%;padding:14px;font-size:18px;border:2px solid #ddd;border-radius:8px;appearance:none;-webkit-appearance:none;-moz-appearance:none;cursor:pointer;background:#fff url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="%23555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>') no-repeat right 14px center;">
        </div>
    </div>

    <!-- Aantal & Type -->
    <div style="display:flex;gap:15px;margin-bottom:20px;">
        <div style="flex:1;">
            <label style="display:block;font-weight:bold;margin-bottom:8px;font-size:16px;">Aantal Trays</label>
            <input type="number" name="tray_count" value="<?=htmlspecialchars((string)$formData['tray_count'])?>" min="1" required style="width:100%;padding:14px;font-size:18px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;">
        </div>
        <div style="flex:1;">
            <label style="display:block;font-weight:bold;margin-bottom:8px;font-size:16px;">Tray Type</label>
            <input type="text" name="tray_type" value="<?=htmlspecialchars($formData['tray_type'])?>" style="width:100%;padding:14px;font-size:18px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;">
        </div>
    </div>

    <!-- Zaad Selectie -->
    <div style="margin-bottom:20px;">
        <label style="display:block;font-weight:bold;margin-bottom:8px;font-size:16px;">Zaad Inventaris</label>
        <select name="inventory_id" id="seedSelect" required onchange="updateUnit()" style="width:100%;padding:14px;font-size:18px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;background:#fff;">
            <option value="">Kies zaad...</option>
            <?php foreach($inventoryItems as $item): ?>
            <option value="<?=$item['id']?>" data-unit="<?=$item['unit']?>"><?=$item['item_name']?> (<?=$item['quantity']?> <?=$item['unit']?>)</option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Hoeveelheid met Grote +/- Knoppen -->
    <div style="margin-bottom:20px;">
        <label style="display:block;font-weight:bold;margin-bottom:8px;font-size:16px;">Zaadhoeveelheid</label>
        <div style="display:flex;align-items:center;gap:10px;">
            <button type="button" onclick="adjustAmount(-1)" style="width:50px;height:50px;font-size:24px;font-weight:bold;background:#f0f0f0;border:2px solid #ddd;border-radius:8px;cursor:pointer;color:#333;">-</button>
            <input type="number" step="0.01" name="seed_amount" id="seedAmount" value="<?=htmlspecialchars((string)$formData['seed_amount'])?>" required style="flex:1;padding:14px;font-size:18px;border:2px solid #ddd;border-radius:8px;text-align:center;font-weight:bold;">
            <button type="button" onclick="adjustAmount(1)" style="width:50px;height:50px;font-size:24px;font-weight:bold;background:#f0f0f0;border:2px solid #ddd;border-radius:8px;cursor:pointer;color:#333;">+</button>
            <span id="unitDisplay" style="min-width:80px;padding:14px;font-size:18px;font-weight:bold;background:#f9f9f9;border:2px solid #eee;border-radius:8px;text-align:center;">eenheid</span>
        </div>
    </div>

    <!-- Status -->
    <div style="margin-bottom:25px;">
        <label style="display:block;font-weight:bold;margin-bottom:8px;font-size:16px;">Status</label>
        <select name="status" style="width:100%;padding:14px;font-size:18px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;background:#fff;">
            <option value="Gepland">Gepland</option>
            <option value="Groeiend" selected>Groeiend</option>
            <option value="Oogstklaar">Oogstklaar</option>
            <option value="Geoogst">Geoogst</option>
        </select>
    </div>

    <!-- Submit -->
    <button type="submit" style="width:100%;padding:18px;font-size:20px;font-weight:bold;color:#fff;background-color:#28a745;border:none;border-radius:8px;cursor:pointer;box-shadow:0 4px 6px rgba(0,0,0,0.1);transition:background 0.2s;">💾 Opslaan</button>
    <a href="grow_batches.php" style="display:block;text-align:center;margin-top:15px;padding:12px;color:#666;text-decoration:none;font-size:16px;">Annuleren</a>
</form>
</div>
</div>

<script>
// Eenheid updaten
function updateUnit() {
    var s = document.getElementById('seedSelect');
    var u = document.getElementById('unitDisplay');
    if(s && s.selectedIndex > 0) {
        var opt = s.options[s.selectedIndex];
        u.textContent = opt.getAttribute('data-unit') || 'eenheid';
    } else if(u) {
        u.textContent = 'eenheid';
    }
}

// +/- Knoppen logica
function adjustAmount(change) {
    var input = document.getElementById('seedAmount');
    if(!input) return;
    var val = parseFloat(input.value) || 0;
    var step = parseFloat(input.step) || 1;
    var newVal = val + (change * step);
    if(newVal < 0) newVal = 0;
    input.value = newVal.toFixed(2);
}

// Init
updateUnit();
</script>
<?php include '../app/includes/footer.php'; ?>
