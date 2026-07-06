<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $db->prepare("
        INSERT INTO expenses
        (expense_date, description, amount)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $_POST['expense_date'],
        $_POST['description'],
        $_POST['amount']
    ]);

    echo '<p>' . htmlspecialchars(t('expense_saved')) . '</p>';
}
?>

<h1><?= htmlspecialchars(t('new_expense')) ?></h1>

<form method="post">

<?= htmlspecialchars(t('date')) ?>:<br>
<input type="date" name="expense_date"><br><br>

<?= htmlspecialchars(t('description')) ?>:<br>
<input type="text" name="description"><br><br>

<?= htmlspecialchars(t('amount')) ?>:<br>
<input type="number" step="0.01" name="amount"><br><br>

<input type="submit" value="<?= htmlspecialchars(t('save')) ?>">

</form>

<br>
<a href="index.php"><?= htmlspecialchars(t('menu')) ?></a>