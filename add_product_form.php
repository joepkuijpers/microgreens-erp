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

    echo '<p>' . htmlspecialchars(t('product_saved')) . '</p>';
}
?>

<h1><?= htmlspecialchars(t('new_product')) ?></h1>

<form method="post">

<?= htmlspecialchars(t('name')) ?>:<br>
<input type="text" name="name"><br><br>

<?= htmlspecialchars(t('category')) ?>:<br>
<input type="text" name="category"><br><br>

<?= htmlspecialchars(t('unit')) ?>:<br>
<input type="text" name="unit" value="bakje"><br><br>

<?= htmlspecialchars(t('sale_price')) ?>:<br>
<input type="number" step="0.01" name="sale_price"><br><br>

<?= htmlspecialchars(t('notes')) ?>:<br>
<input type="text" name="notes"><br><br>

<input type="submit" value="<?= htmlspecialchars(t('save')) ?>">

</form>

<br>
<a href="index.php"><?= htmlspecialchars(t('menu')) ?></a>