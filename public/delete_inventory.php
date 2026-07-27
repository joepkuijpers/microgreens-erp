<?php
require_once '../app/db_connect.php';
require_once '../app/includes/language.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list_inventory.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    die(__('invalid_inventory_id'));
}

$itemQuery = $db->prepare("
    SELECT id, quantity, unit
    FROM inventory
    WHERE id = :id
      AND is_active = 1
");

$itemQuery->execute([
    ':id' => $id,
]);

$item = $itemQuery->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die(__('invalid_inventory_id'));
}

try {
    $db->beginTransaction();

    $archive = $db->prepare("
        UPDATE inventory
        SET is_active = 0
        WHERE id = :id
          AND is_active = 1
    ");

    $archive->execute([
        ':id' => $id,
    ]);

    if ($archive->rowCount() !== 1) {
        throw new RuntimeException(
            'Inventory item could not be archived.'
        );
    }

    $log = $db->prepare("
        INSERT INTO inventory_transactions
        (
            inventory_id,
            type,
            quantity_change,
            quantity_before,
            quantity_after,
            unit,
            note,
            reference_type,
            reference_id
        )
        VALUES
        (
            :inventory_id,
            :type,
            :quantity_change,
            :quantity_before,
            :quantity_after,
            :unit,
            :note,
            :reference_type,
            :reference_id
        )
    ");

    $log->execute([
        ':inventory_id' => $id,
        ':type' => 'ARCHIVERING',
        ':quantity_change' => 0,
        ':quantity_before' => (float)$item['quantity'],
        ':quantity_after' => (float)$item['quantity'],
        ':unit' => (string)($item['unit'] ?? ''),
        ':note' => __('inventory_item_archived'),
        ':reference_type' => 'inventory',
        ':reference_id' => $id,
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
