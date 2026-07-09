<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $batch_id = $_POST['batch_id'];
    $inventory_id = $_POST['inventory_id'];
    $quantity_used = $_POST['quantity_used'];

    $stmt = $db->prepare("
        INSERT INTO batch_materials
        (batch_id, inventory_id, quantity_used)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$batch_id, $inventory_id, $quantity_used]);

    $stmt = $db->prepare("
        UPDATE inventory
        SET quantity = quantity - ?
        WHERE id = ?
    ");
    $stmt->execute([$quantity_used, $inventory_id]);

    echo "<p>Materiaalverbruik opgeslagen en voorraad bijgewerkt!</p>";
}

$batches = $db->query("SELECT id, crop FROM grow_batches ORDER BY id DESC");
$inventory = $db->query("SELECT id, item_name, quantity, unit FROM inventory ORDER BY item_name");
?>

<h1>Materiaalverbruik registreren</h1>

<form method="post">

Teelt:<br>
<select name="batch_id">
<?php foreach ($batches as $b) { echo "<option value='".$b['id']."'>".$b['id']." - ".$b['crop']."</option>"; } ?>
</select><br><br>

Voorraaditem:<br>
<select name="inventory_id">
<?php foreach ($inventory as $i) { echo "<option value='".$i['id']."'>".$i['item_name']." - ".$i['quantity']." ".$i['unit']."</option>"; } ?>
</select><br><br>

Hoeveel gebruikt:<br>
<input type="number" step="0.01" name="quantity_used"><br><br>

<input type="submit" value="Opslaan">
</form>

<br>
<a href="index.php">Menu</a>
