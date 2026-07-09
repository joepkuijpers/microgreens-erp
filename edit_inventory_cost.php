<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $db->prepare("
        UPDATE inventory
        SET unit_cost = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['unit_cost'],
        $_POST['inventory_id']
    ]);

    echo "<p>Kostprijs opgeslagen!</p>";
}

$items = $db->query("
    SELECT id, item_name, quantity, unit
    FROM inventory
    ORDER BY item_name
");
?>

<h1>Kostprijs voorraad aanpassen</h1>

<form method="post">

Voorraaditem:<br>
<select name="inventory_id">
<?php foreach($items as $i) {
    echo "<option value='".$i['id']."'>".$i['item_name']." - ".$i['quantity']." ".$i['unit']."</option>";
} ?>
</select><br><br>

Kostprijs per eenheid:<br>
<input type="number" step="0.01" name="unit_cost"><br><br>

<input type="submit" value="Opslaan">
</form>

<br>
<a href="index.php">Menu</a>
