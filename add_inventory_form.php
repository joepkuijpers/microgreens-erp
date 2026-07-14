<?php
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
    <h1><?= htmlspecialchars(__('add_inventory')) ?></h1>

    <div class="card">
        <form method="post" action="add_inventory.php">

            <label><?= htmlspecialchars(__('item_name')) ?></label><br>
            <input type="text" name="item_name" required><br><br>

            <label><?= htmlspecialchars(__('category')) ?></label><br>
            <input
                type="text"
                name="category"
                placeholder="<?= htmlspecialchars(__('category_placeholder')) ?>"
            ><br><br>

            <label><?= htmlspecialchars(__('quantity')) ?></label><br>
            <input type="number" step="0.01" name="quantity" required><br><br>

            <label><?= htmlspecialchars(__('unit')) ?></label><br>
            <input
                type="text"
                name="unit"
                placeholder="<?= htmlspecialchars(__('unit_placeholder')) ?>"
                required
            ><br><br>

            <label><?= htmlspecialchars(__('unit_cost')) ?></label><br>
            <input type="number" step="0.01" name="unit_cost" required><br><br>

            <button type="submit" class="btn">
                <?= htmlspecialchars(__('save')) ?>
            </button>

            <a href="list_inventory.php" class="btn">
                <?= htmlspecialchars(__('back')) ?>
            </a>

        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>