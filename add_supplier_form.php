<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = $_POST['name'];

    $stmt = $db->prepare("
        INSERT INTO suppliers (name)
        VALUES (?)
    ");

    $stmt->execute([$name]);

    echo '<p>' . htmlspecialchars(t('supplier_saved')) . '</p>';
}
?>

<h1><?= htmlspecialchars(t('new_supplier')) ?></h1>

<form method="post">
    <?= htmlspecialchars(t('name')) ?>:<br>
    <input type="text" name="name"><br><br>

    <input type="submit" value="<?= htmlspecialchars(t('save')) ?>">
</form>

<br>
<a href="index.php"><?= htmlspecialchars(t('menu')) ?></a>