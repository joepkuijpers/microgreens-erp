<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inventory_id = (int)($_POST['inventory_id'] ?? 0);
    $type = trim($_POST['type'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if ($inventory_id <= 0 || $type === '' || $amount <= 0) {
        die('Ongeldige invoer.');
    }

    $stmt = $db->prepare("SELECT * FROM inventory WHERE id = :id");
    $stmt->execute([':id' => $inventory_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die('Voorraaditem niet gevonden.');
    }

    $quantity_before = (float)$item['quantity'];

    $positiveTypes = ['INKOOP', 'CORRECTIE_PLUS'];
    $quantity_change = in_array($type, $positiveTypes) ? $amount : -$amount;

    $quantity_after = $quantity_before + $quantity_change;

    if ($quantity_after < 0) {
        die('Fout: voorraad kan niet onder 0 komen.');
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
    SELECT id, item_name, quantity, unit
    FROM inventory
    ORDER BY item_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
    <h1>➕ Voorraadmutatie toevoegen</h1>

    <p>
        <a class="btn" href="list_inventory.php">← Terug naar voorraad</a>
        <a class="btn" href="inventory_transactions.php">📋 Mutaties</a>
    </p>

    <div class="card">
        <form method="post">
            <label>Voorraaditem</label><br>
            <select name="inventory_id" required>
                <option value="">-- Kies voorraaditem --</option>
                <?php foreach ($items as $item): ?>
                    <option value="<?= htmlspecialchars($item['id']) ?>">
                        <?= htmlspecialchars($item['item_name']) ?>
                        (<?= number_format((float)$item['quantity'], 2, ',', '.') ?>
                        <?= htmlspecialchars($item['unit']) ?>)
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label>Type mutatie</label><br>
            <select name="type" required>
                <option value="">-- Kies type --</option>
                <option value="INKOOP">Inkoop / voorraad erbij</option>
                <option value="VERBRUIK">Verbruik / voorraad eraf</option>
                <option value="VERLIES">Verlies / weggegooid</option>
                <option value="CORRECTIE_PLUS">Correctie plus</option>
                <option value="CORRECTIE_MIN">Correctie min</option>
            </select><br><br>

            <label>Aantal</label><br>
            <input type="number" step="0.01" name="amount" required><br><br>

            <label>Opmerking</label><br>
            <input type="text" name="note" placeholder="Bijv. inkoop, telling, verlies, test"><br><br>

            <button type="submit" class="btn">Mutatie opslaan</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
