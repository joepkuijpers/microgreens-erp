<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = $_POST['name'];

    $stmt = $db->prepare("
        INSERT INTO suppliers (name)
        VALUES (?)
    ");

    $stmt->execute([$name]);

    echo '<p>' . htmlspecialchars(__('supplier_saved')) . '</p>';
}
?>

<h1><?= htmlspecialchars(__('new_supplier')) ?></h1>

<form method="post">
    <?= htmlspecialchars(__('name')) ?>:<br>
    <input type="text" name="name"><br><br>

    <input type="submit" value="<?= htmlspecialchars(__('save')) ?>">
</form>

<br>
<a href="index.php"><?= htmlspecialchars(__('menu')) ?></a>