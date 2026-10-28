<?php
require_once '../app/includes/auth.php';
auth_require_login();
include '../app/db_connect.php';
require_once '../app/includes/language.php';

// Ophalen van actieve leveranciers
$suppliersStmt = $db->query("SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name ASC");
$suppliers = $suppliersStmt->fetchAll(PDO::FETCH_ASSOC);

// Ophalen van bestaande artikelen ter ondersteuning van autocomplete/selectie
$inventoryStmt = $db->query("SELECT id, item_name, supplier_id, category, unit, unit_cost FROM inventory WHERE is_active = 1 ORDER BY item_name ASC");
$existingItems = $inventoryStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title><?= __('add_inventory_item') ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"], select { width: 100%; max-width: 400px; padding: 8px; box-sizing: border-box; }
        .btn { padding: 10px 15px; background-color: #28a745; color: white; border: none; cursor: pointer; }
        .btn:hover { background-color: #218838; }
        .info-box { background-color: #e9ecef; padding: 10px; max-width: 400px; margin-bottom: 15px; border-left: 4px solid #17a2b8; }
    </style>
</head>
<body>

<h2><?= __('add_inventory_item') ?></h2>

<div class="info-box">
    <small>Kies een bestaand artikel om snel de voorraad aan te vullen, of vul de gegevens in voor een nieuw artikel.</small>
</div>

<form action="add_inventory.php" method="POST" id="inventoryForm">
    <!-- Tijdmeting verborgen veld -->
    <input type="hidden" name="registration_time_seconds" id="registration_time_seconds" value="0">
    <input type="hidden" name="existing_inventory_id" id="existing_inventory_id" value="">

    <div class="form-group">
        <label for="existing_item_select">Snelkiezer Bestaand Artikel (Happy Path):</label>
        <select id="existing_item_select" onchange="autofillExistingItem(this.value)">
            <option value="">-- Kies bestaand artikel of voer nieuw in --</option>
            <?php foreach ($existingItems as $item): ?>
                <option value="<?= $item['id'] ?>" 
                        data-name="<?= htmlspecialchars($item['item_name']) ?>"
                        data-supplier="<?= $item['supplier_id'] ?>"
                        data-category="<?= htmlspecialchars($item['category']) ?>"
                        data-unit="<?= htmlspecialchars($item['unit']) ?>"
                        data-cost="<?= $item['unit_cost'] ?>">
                    <?= htmlspecialchars($item['item_name']) ?> (<?= htmlspecialchars($item['category']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <hr style="max-width: 400px; margin: 20px 0; border: 0; border-top: 1px solid #ccc;">

    <div class="form-group">
        <label for="item_name"><?= __('item_name') ?> *</label>
        <input type="text" name="item_name" id="item_name" required autocomplete="off">
    </div>

    <div class="form-group">
        <label for="supplier_id"><?= __('supplier') ?> *</label>
        <select name="supplier_id" id="supplier_id" required>
            <option value=""><?= __('select_supplier') ?></option>
            <?php foreach ($suppliers as $supplier): ?>
                <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="category"><?= __('category') ?></label>
        <input type="text" name="category" id="category" placeholder="bijv. Zaden, Trays, Substraat">
    </div>

    <div class="form-group">
        <label for="quantity"><?= __('quantity') ?> *</label>
        <input type="number" step="0.01" name="quantity" id="quantity" required min="0.01">
    </div>

    <div class="form-group">
        <label for="unit"><?= __('unit') ?> *</label>

        <input type="text" name="unit" id="unit" required placeholder="bijv. gram, stuks, kg">
    </div>

    <div class="form-group">
        <label for="unit_cost"><?= __('unit_cost') ?> (€)</label>
        <input type="number" step="0.0001" name="unit_cost" id="unit_cost" min="0" value="0">
    </div>

    <button type="submit" class="btn"><?= __('save') ?></button>
</form>

<script>
// Tijdmeting via actieve interactie
let startTime = Date.now();
let activeSeconds = 0;
let timerInterval = setInterval(() => {
    activeSeconds++;
    document.getElementById('registration_time_seconds').value = activeSeconds;
}, 1000);

// Autocomplete functionaliteit
function autofillExistingItem(itemId) {
    if (!itemId) {
        document.getElementById('existing_inventory_id').value = '';
        return;
    }

    const select = document.getElementById('existing_item_select');
    const selectedOption = select.options[select.selectedIndex];

    document.getElementById('existing_inventory_id').value = itemId;
    document.getElementById('item_name').value = selectedOption.getAttribute('data-name');
    document.getElementById('supplier_id').value = selectedOption.getAttribute('data-supplier');
    document.getElementById('category').value = selectedOption.getAttribute('data-category');
    document.getElementById('unit').value = selectedOption.getAttribute('data-unit');
    document.getElementById('unit_cost').value = selectedOption.getAttribute('data-cost');

    // Focus op aantal voor snelle invoer (< 15 seconden happy path)
    document.getElementById('quantity').focus();
}
</script>

</body>
</html>