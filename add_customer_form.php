<?php
include 'db_connect.php';

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

    echo '<p>' . htmlspecialchars(__('customer_saved')) . '</p>';
}
?>

<h1><?= htmlspecialchars(__('new_customer')) ?></h1>

<form method="post">

<?= htmlspecialchars(__('name')) ?>:<br>
<input type="text" name="name"><br><br>

<?= htmlspecialchars(__('email')) ?>:<br>
<input type="email" name="email"><br><br>

<?= htmlspecialchars(__('phone')) ?>:<br>
<input type="text" name="phone"><br><br>

<?= htmlspecialchars(__('notes')) ?>:<br>
<input type="text" name="notes"><br><br>

<input type="submit" value="<?= htmlspecialchars(__('save')) ?>">

</form>

<br>
<a href="index.php"><?= htmlspecialchars(__('menu')) ?></a>