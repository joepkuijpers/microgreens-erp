<?php
include 'db_connect.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Ongeldig voorraad-ID.');
}

$stmt = $db->prepare("SELECT * FROM inventory WHERE id = :id");
$stmt->execute([':id' => $id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die('Voorraaditem niet gevonden.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = trim($_POST['item_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = (float)($_POST['quantity'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $unit_cost = (float)($_POST['unit_cost'] ?? 0);

    if ($item_name === '' || $quantity < 0 || $unit === '' || $unit_cost < 0) {
        die('Ongeldige invoer.');
    }

    $quantity_before = (float)$item['quantity'];
    $quantity_after = $quantity;
    $quantity_change = $quantity_after - $quantity_before;

    $stmt = $db->prepare("
        UPDATE inventory
        SET item_name = :item_name,
            category = :category,
            quantity = :quantity,
            unit = :unit,
            unit_cost = :unit_cost
        WHERE id = :id
    ");

    $stmt->execute([
        ':item_name' => $item_name,
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

    header('Location: list_inventory.php');
    exit;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
<h1><?= htmlspecialchars(t('edit_inventory')) ?></h1>

<div class="card">
    <form method="post">

        <label><?= htmlspecialchars(t('item_name')) ?></label><br>
        <input
            type="text"
            name="item_name"
            value="<?= htmlspecialchars($item['item_name']) ?>"
            required
        ><br><br>

        <label><?= htmlspecialchars(t('category')) ?></label><br>
        <input
            type="text"
            name="category"
            value="<?= htmlspecialchars($item['category'] ?? '') ?>"
        ><br><br>

        <label><?= htmlspecialchars(t('quantity')) ?></label><br>
        <input
            type="number"
            step="0.01"
            name="quantity"
            value="<?= htmlspecialchars($item['quantity']) ?>"
            required
        ><br><br>

        <label><?= htmlspecialchars(t('unit')) ?></label><br>
        <input
            type="text"
            name="unit"
            value="<?= htmlspecialchars($item['unit'] ?? '') ?>"
            required
        ><br><br>

        <label><?= htmlspecialchars(t('unit_cost')) ?></label><br>
        <input
            type="number"
            step="0.01"
            name="unit_cost"
            value="<?= htmlspecialchars($item['unit_cost']) ?>"
            required
        ><br><br>

        <button type="submit" class="btn">
            <?= htmlspecialchars(t('save')) ?>
        </button>

        <a href="list_inventory.php" class="btn">
            <?= htmlspecialchars(t('back')) ?>
        </a>

    </form>
</div>

<?php include 'includes/footer.php'; ?>