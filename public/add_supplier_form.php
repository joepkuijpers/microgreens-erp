<?php
require_once '../app/includes/language.php';

include '../app/includes/header.php';
include '../app/includes/sidebar.php';

$error = $_GET['error'] ?? '';
?>

<div class="main">
    <h1><?= htmlspecialchars(__('new_supplier')) ?></h1>

    <?php if ($error === 'name_required'): ?>
        <div class="card">
            <p><?= htmlspecialchars(__('name_required')) ?></p>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="post" action="add_supplier.php">
            <label for="name"><?= htmlspecialchars(__('name')) ?></label><br>
            <input id="name" type="text" name="name" required autofocus><br><br>

            <button type="submit" class="btn">
                <?= htmlspecialchars(__('save')) ?>
            </button>

            <a href="list_suppliers.php" class="btn">
                <?= htmlspecialchars(__('cancel')) ?>
            </a>
        </form>
    </div>
</div>

<?php include '../app/includes/footer.php'; ?>
