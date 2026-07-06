<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $stmt = $db->prepare("
        INSERT INTO customers
        (name, email, phone, notes)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['notes']
    ]);

    echo '<p>' . htmlspecialchars(t('customer_saved')) . '</p>';
}
?>

<h1><?= htmlspecialchars(t('new_customer')) ?></h1>

<form method="post">

<?= htmlspecialchars(t('name')) ?>:<br>
<input type="text" name="name"><br><br>

<?= htmlspecialchars(t('email')) ?>:<br>
<input type="email" name="email"><br><br>

<?= htmlspecialchars(t('phone')) ?>:<br>
<input type="text" name="phone"><br><br>

<?= htmlspecialchars(t('notes')) ?>:<br>
<input type="text" name="notes"><br><br>

<input type="submit" value="<?= htmlspecialchars(t('save')) ?>">

</form>

<br>
<a href="index.php"><?= htmlspecialchars(t('menu')) ?></a>