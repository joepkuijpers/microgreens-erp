<?php
require_once '../app/includes/auth.php';
auth_require_login();
include '../app/db_connect.php';
require_once '../app/includes/language.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_inventory_form.php');
    exit;
}

$item_name = trim($_POST['item_name'] ?? '');
$supplier_id_raw = trim((string)($_POST['supplier_id'] ?? ''));
$category = trim($_POST['category'] ?? '');
$quantity = (float)($_POST['quantity'] ?? 0);
$unit = trim($_POST['unit'] ?? '');
$unit_cost = (float)($_POST['unit_cost'] ?? 0);
$registration_time_seconds = (float)($_POST['registration_time_seconds'] ?? 0);
$existing_inventory_id = !empty($_POST['existing_inventory_id']) ? (int)$_POST['existing_inventory_id'] : null;

$operator_id = $_SESSION['user_id'] ?? null;
$created_at = date('Y-m-d H:i:s');

// Validatie (Sad Path Preventie)
if (
    $item_name === '' ||
    !ctype_digit($supplier_id_raw) ||
    (int)$supplier_id_raw <= 0 ||
    $quantity <= 0 ||
    $unit === '' ||
    $unit_cost < 0
) {
    die(__('invalid_inventory_input'));
}

$supplier_id = (int)$supplier_id_raw;

// Leverancier check
$supplierCheck = $db->prepare("SELECT COUNT(*) FROM suppliers WHERE id = :supplier_id");
$supplierCheck->execute([':supplier_id' => $supplier_id]);

if ((int)$supplierCheck->fetchColumn() !== 1) {
    die(__('invalid_inventory_input'));
}

try {
    $db->beginTransaction();

    // Sad Path Afhandeling: Check of dit item al bestaat bij deze leverancier als er geen ID gekozen was
    if (!$existing_inventory_id) {
        $checkStmt = $db->prepare("SELECT id, quantity FROM inventory WHERE LOWER(item_name) = LOWER(:item_name) AND supplier_id = :supplier_id LIMIT 1");
        $checkStmt->execute([
            ':item_name' => $item_name,
            ':supplier_id' => $supplier_id
        ]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $existing_inventory_id = (int)$existing['id'];
        }
    }

    if ($existing_inventory_id) {
        // SCENARIO A: Bestaande voorraad ophogen (No Double Entry)
        $fetchCurrent = $db->prepare("SELECT quantity FROM inventory WHERE id = :id");
        $fetchCurrent->execute([':id' => $existing_inventory_id]);
        $quantity_before = (float)$fetchCurrent->fetchColumn();
        $quantity_after = $quantity_before + $quantity;

        $updateStmt = $db->prepare("
            UPDATE inventory 
            SET quantity = :quantity_after, 
                unit_cost = :unit_cost,
                updated_at = :updated_at,
                registration_time_seconds = COALESCE(registration_time_seconds, 0) + :reg_time
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':quantity_after' => $quantity_after,
            ':unit_cost' => $unit_cost,
            ':updated_at' => $created_at,
            ':reg_time' => $registration_time_seconds,
            ':id' => $existing_inventory_id
        ]);

        $inventory_id = $existing_inventory_id;
        $note = __('inventory_replenished');
    } else {
        // SCENARIO B: Nieuw artikel aanmaken
        $quantity_before = 0;
        $quantity_after = $quantity;

        $stmt = $db->prepare("
            INSERT INTO inventory
            (item_name, supplier_id, category, quantity, unit, unit_cost, created_at, operator_id, registration_time_seconds)
            VALUES
            (:item_name, :supplier_id, :category, :quantity, :unit, :unit_cost, :created_at, :operator_id, :reg_time)
        ");

        $stmt->execute([
            ':item_name' => $item_name,
            ':supplier_id' => $supplier_id,
            ':category' => $category,
            ':quantity' => $quantity,
            ':unit' => $unit,
            ':unit_cost' => $unit_cost,
            ':created_at' => $created_at,
            ':operator_id' => $operator_id,
            ':reg_time' => $registration_time_seconds
        ]);

        $inventory_id = (int)$db->lastInsertId();
        $note = __('new_inventory_item_created');
    }

    // Mutatie-log wegschrijven (Traceability Audit Trail)
    $log = $db->prepare("
        INSERT INTO inventory_transactions
        (inventory_id, type, quantity_change, quantity_before, quantity_after, unit, note, reference_type, reference_id)
        VALUES
        (:inventory_id, :type, :quantity_change, :quantity_before, :quantity_after, :unit, :note, :reference_type, :reference_id)
    ");

    $log->execute([
        ':inventory_id' => $inventory_id,
        ':type' => 'TOEVOEGING',
        ':quantity_change' => $quantity,
        ':quantity_before' => $quantity_before,
        ':quantity_after' => $quantity_after,
        ':unit' => $unit,
        ':note' => $note,
        ':reference_type' => 'inventory',
        ':reference_id' => $inventory_id
    ]);

    $db->commit();

} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $exception;
}

header('Location: list_inventory.php');
exit;