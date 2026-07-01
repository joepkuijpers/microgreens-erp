<?php
include 'db_connect.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Ongeldig voorraad-ID.');
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

    $stmt = $db->prepare("
        UPDATE inventory
        SET item_name = :item_name,
            category = :category,
            quantity = :quantity,
            unit = :unit,
            unit_cost = :unit_cost
        WHERE id = :id
    ");

    $stmt->bindValue(':item_name', $item_name, SQLITE3_TEXT);
    $stmt->bindValue(':category', $category, SQLITE3_TEXT);
    $stmt->bindValue(':quantity', $quantity, SQLITE3_FLOAT);
    $stmt->bindValue(':unit', $unit, SQLITE3_TEXT);
    $stmt->bindValue(':unit_cost', $unit_cost, SQLITE3_FLOAT);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();

    header('Location: list_inventory.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM inventory WHERE id = :id");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$item = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$item) {
    die('Voorraaditem niet gevonden.');
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
    <h1>Voorraad bewerken</h1>

    <div class="card">
        <form method="post">
            <label>Artikelnaam</label><br>
            <input type="text" name="item_name" value="<?= htmlspecialchars($item['item_name']) ?>" required><br><br>

            <label>Categorie</label><br>
            <input type="text" name="category" value="<?= htmlspecialchars($item['category'] ?? '') ?>"><br><br>

            <label>Hoeveelheid</label><br>
            <input type="number" step="0.01" name="quantity" value="<?= htmlspecialchars($item['quantity']) ?>" required><br><br>

            <label>Eenheid</label><br>
            <input type="text" name="unit" value="<?= htmlspecialchars($item['unit'] ?? '') ?>" required><br><br>

            <label>Kostprijs per eenheid (€)</label><br>
            <input type="number" step="0.01" name="unit_cost" value="<?= htmlspecialchars($item['unit_cost']) ?>" required><br><br>

            <button type="submit" class="btn">Opslaan</button>
            <a href="list_inventory.php" class="btn">Terug</a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
