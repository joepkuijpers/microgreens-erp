<?php
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
    <h1><?= htmlspecialchars(t('add_inventory')) ?></h1>

    <div class="card">
        <form method="post" action="add_inventory.php">

            <label><?= htmlspecialchars(t('item_name')) ?></label><br>
            <input type="text" name="item_name" required><br><br>

            <label><?= htmlspecialchars(t('category')) ?></label><br>
            <input
                type="text"
                name="category"
                placeholder="<?= htmlspecialchars(t('category_placeholder')) ?>"
            ><br><br>

            <label><?= htmlspecialchars(t('quantity')) ?></label><br>
            <input type="number" step="0.01" name="quantity" required><br><br>

            <label><?= htmlspecialchars(t('unit')) ?></label><br>
            <input
                type="text"
                name="unit"
                placeholder="<?= htmlspecialchars(t('unit_placeholder')) ?>"
                required
            ><br><br>

            <label><?= htmlspecialchars(t('unit_cost')) ?></label><br>
            <input type="number" step="0.01" name="unit_cost" required><br><br>

            <button type="submit" class="btn">
                <?= htmlspecialchars(t('save')) ?>
            </button>

            <a href="list_inventory.php" class="btn">
                <?= htmlspecialchars(t('back')) ?>
            </a>

        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>