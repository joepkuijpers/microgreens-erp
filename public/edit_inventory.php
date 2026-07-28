<?php
require_once '../app/includes/auth.php';
auth_require_login();
include '../app/db_connect.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Ongeldig voorraad-ID.');
}

$stmt = $db->prepare("
    SELECT id, item_name, supplier_id, category, quantity, unit, unit_cost
    FROM inventory
    WHERE id = :id
");
$stmt->execute([':id' => $id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die('Voorraaditem niet gevonden.');
}

$suppliers = $db->query("
    SELECT id, name
    FROM suppliers
    ORDER BY name COLLATE NOCASE ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = trim($_POST['item_name'] ?? '');
    $supplier_id_raw = trim((string)($_POST['supplier_id'] ?? ''));
    $category = trim($_POST['category'] ?? '');
    $quantity = (float)($_POST['quantity'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $unit_cost = (float)($_POST['unit_cost'] ?? 0);

    if (
        $item_name === '' ||
        !ctype_digit($supplier_id_raw) ||
        (int)$supplier_id_raw <= 0 ||
        $quantity < 0 ||
        $unit === '' ||
        $unit_cost < 0
    ) {
        die('Ongeldige invoer.');
    }

    $supplier_id = (int)$supplier_id_raw;

    $supplierCheck = $db->prepare("
        SELECT COUNT(*)
        FROM suppliers
        WHERE id = :supplier_id
    ");

    $supplierCheck->execute([
        ':supplier_id' => $supplier_id,
    ]);

    if ((int)$supplierCheck->fetchColumn() !== 1) {
        die('Ongeldige leverancier.');
    }

    $quantity_before = (float)$item['quantity'];
    $quantity_after = $quantity;
    $quantity_change = $quantity_after - $quantity_before;

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            UPDATE inventory
            SET item_name = :item_name,
                supplier_id = :supplier_id,
                category = :category,
                quantity = :quantity,
                unit = :unit,
                unit_cost = :unit_cost
            WHERE id = :id
        ");

        $stmt->execute([
            ':item_name' => $item_name,
            ':supplier_id' => $supplier_id,
            ':category' => $category,
            ':quantity' => $quantity,
            ':unit' => $unit,
            ':unit_cost' => $unit_cost,
            ':id' => $id
        ]);

        $log = $db->prepare("
            INSERT INTO inventory_transactions
            (inventory_id, type, quantity_change, quantity_before, quantity_after, unit, note, reference_type, reference_id)
            VALUES
            (:inventory_id, :type, :quantity_change, :quantity_before, :quantity_after, :unit, :note, :reference_type, :reference_id)
        ");

        $log->execute([
            ':inventory_id' => $id,
            ':type' => 'BEWERKING',
            ':quantity_change' => $quantity_change,
            ':quantity_before' => $quantity_before,
            ':quantity_after' => $quantity_after,
            ':unit' => $unit,
            ':note' => 'Voorraaditem bewerkt',
            ':reference_type' => 'inventory',
            ':reference_id' => $id
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
}

include '../app/includes/language.php';
include '../app/includes/header.php';
include '../app/includes/sidebar.php';
?>

<div class="main">
<h1><?= htmlspecialchars(__('edit_inventory')) ?></h1>

<div class="card">
    <form method="post">

        <label><?= htmlspecialchars(__('item_name')) ?></label><br>
        <input
            type="text"
            name="item_name"
            value="<?= htmlspecialchars($item['item_name']) ?>"
            required
        ><br><br>

        <label for="supplier_id">
            <?= htmlspecialchars(__('supplier')) ?>
        </label><br>

        <select id="supplier_id" name="supplier_id" required>
            <option value="">
                <?= htmlspecialchars(__('select_supplier')) ?>
            </option>

            <?php foreach ($suppliers as $supplier): ?>
                <option
                    value="<?= htmlspecialchars((string)$supplier['id']) ?>"
                    <?php if (
                        (string)($item['supplier_id'] ?? '') ===
                        (string)$supplier['id']
                    ): ?>
                        selected
                    <?php endif; ?>
                >
                    <?= htmlspecialchars((string)$supplier['name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label><?= htmlspecialchars(__('category')) ?></label><br>
        <input
            type="text"
            name="category"
            value="<?= htmlspecialchars($item['category'] ?? '') ?>"
        ><br><br>

        <label><?= htmlspecialchars(__('quantity')) ?></label><br>
        <input
            type="number"
            step="0.01"
            name="quantity"
            value="<?= htmlspecialchars($item['quantity']) ?>"
            required
        ><br><br>

        <label><?= htmlspecialchars(__('unit')) ?></label><br>
        <input
            type="text"
            name="unit"
            value="<?= htmlspecialchars($item['unit'] ?? '') ?>"
            required
        ><br><br>

        <label><?= htmlspecialchars(__('unit_cost')) ?></label><br>
        <input
            type="number"
            step="0.01"
            name="unit_cost"
            value="<?= htmlspecialchars($item['unit_cost']) ?>"
            required
        ><br><br>

        <button type="submit" class="btn">
            <?= htmlspecialchars(__('save')) ?>
        </button>

        <a href="list_inventory.php" class="btn">
            <?= htmlspecialchars(__('back')) ?>
        </a>

    </form>
</div>

<?php include '../app/includes/footer.php'; ?>
