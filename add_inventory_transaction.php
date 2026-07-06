<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inventory_id = (int)($_POST['inventory_id'] ?? 0);
    $type = trim($_POST['type'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if ($inventory_id <= 0 || $type === '' || $amount <= 0) {
        die(t('invalid_inventory_input'));
    }

    $stmt = $db->prepare("
        SELECT
            id,
            item_name,
            quantity,
            unit
        FROM inventory
        WHERE id = :id
    ");
    $stmt->execute([':id' => $inventory_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die(t('inventory_item_not_found'));
    }

    $quantity_before = (float)$item['quantity'];

    $positiveTypes = ['INKOOP', 'CORRECTIE_PLUS'];
    $quantity_change = in_array($type, $positiveTypes, true) ? $amount : -$amount;

    $quantity_after = $quantity_before + $quantity_change;

    if ($quantity_after < 0) {
        die(t('inventory_cannot_be_negative'));
    }

    $update = $db->prepare("
        UPDATE inventory
        SET quantity = :quantity
        WHERE id = :id
    ");

    $update->execute([
        ':quantity' => $quantity_after,
        ':id' => $inventory_id
    ]);

    $log = $db->prepare("
        INSERT INTO inventory_transactions
        (inventory_id, type, quantity_change, quantity_before, quantity_after, unit, note, reference_type, reference_id)
        VALUES
        (:inventory_id, :type, :quantity_change, :quantity_before, :quantity_after, :unit, :note, :reference_type, :reference_id)
    ");

    $log->execute([
        ':inventory_id' => $inventory_id,
        ':type' => $type,
        ':quantity_change' => $quantity_change,
        ':quantity_before' => $quantity_before,
        ':quantity_after' => $quantity_after,
        ':unit' => $item['unit'],
        ':note' => $note,
        ':reference_type' => 'manual',
        ':reference_id' => null
    ]);

    header('Location: inventory_transactions.php');
    exit;
}

$items = $db->query("
    SELECT
        id,
        item_name,
        quantity,
        unit
    FROM inventory
    ORDER BY item_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
    <h1>➕ <?= htmlspecialchars(t('add_inventory_transaction')) ?></h1>

    <p>
        <a class="btn" href="list_inventory.php">
            ← <?= htmlspecialchars(t('back_to_inventory')) ?>
        </a>

        <a class="btn" href="inventory_transactions.php">
            📋 <?= htmlspecialchars(t('transactions')) ?>
        </a>
    </p>

    <div class="card">
        <form method="post">

            <label><?= htmlspecialchars(t('inventory_item')) ?></label><br>
            <select name="inventory_id" required>
                <option value="">
                    -- <?= htmlspecialchars(t('choose_inventory_item')) ?> --
                </option>

                <?php foreach ($items as $item): ?>
                    <option value="<?= htmlspecialchars((string)$item['id']) ?>">
                        <?= htmlspecialchars((string)$item['item_name']) ?>
                        (<?= number_format((float)$item['quantity'], 2, ',', '.') ?>
                        <?= htmlspecialchars((string)($item['unit'] ?? '')) ?>)
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label><?= htmlspecialchars(t('transaction_type')) ?></label><br>
            <select name="type" required>
                <option value="">
                    -- <?= htmlspecialchars(t('choose_type')) ?> --
                </option>

                <option value="INKOOP">Inkoop / voorraad erbij</option>
                <option value="VERBRUIK">Verbruik / voorraad eraf</option>
                <option value="VERLIES">Verlies / weggegooid</option>
                <option value="CORRECTIE_PLUS">Correctie plus</option>
                <option value="CORRECTIE_MIN">Correctie min</option>
            </select><br><br>

            <label><?= htmlspecialchars(t('quantity')) ?></label><br>
            <input
                type="number"
                step="0.01"
                name="amount"
                required
            ><br><br>

            <label><?= htmlspecialchars(t('notes')) ?></label><br>
            <input
                type="text"
                name="note"
                placeholder="<?= htmlspecialchars(t('inventory_note_placeholder')) ?>"
            ><br><br>

            <button type="submit" class="btn">
                <?= htmlspecialchars(t('save_transaction')) ?>
            </button>

        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>