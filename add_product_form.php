<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $stmt = $db->prepare("
        INSERT INTO products
        (name, category, unit, sale_price, notes)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['name'],
        $_POST['category'],
        $_POST['unit'],
        $_POST['sale_price'],
        $_POST['notes']
    ]);

    echo '<p>' . htmlspecialchars(__('product_saved')) . '</p>';
}
?>

<h1><?= htmlspecialchars(__('new_product')) ?></h1>

<form method="post">

<?= htmlspecialchars(__('name')) ?>:<br>
<input type="text" name="name"><br><br>

<?= htmlspecialchars(__('category')) ?>:<br>
<input type="text" name="category"><br><br>

<?= htmlspecialchars(__('unit')) ?>:<br>
<input type="text" name="unit" value="bakje"><br><br>

<?= htmlspecialchars(__('sale_price')) ?>:<br>
<input type="number" step="0.01" name="sale_price"><br><br>

<?= htmlspecialchars(__('notes')) ?>:<br>
<input type="text" name="notes"><br><br>

<input type="submit" value="<?= htmlspecialchars(__('save')) ?>">

</form>

<br>
<a href="index.php"><?= htmlspecialchars(__('menu')) ?></a>