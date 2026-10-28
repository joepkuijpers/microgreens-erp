<?php
require_once '../app/includes/auth.php';
auth_require_login();
require_once '../app/db_connect.php';
require_once '../app/includes/language.php';

$suppliers = $db->query("
    SELECT id, name
    FROM suppliers
    ORDER BY name COLLATE NOCASE ASC
")->fetchAll(PDO::FETCH_ASSOC);

include '../app/includes/header.php';
include '../app/includes/sidebar.php';
?>

<div class="main">
    <h1><?= htmlspecialchars(__('add_inventory')) ?></h1>

    <div class="card">
        <form method="post" action="add_inventory.php">

            <label><?= htmlspecialchars(__('item_name')) ?></label><br>
            <input type="text" name="item_name" required><br><br>

            <label for="supplier_id">
                <?= htmlspecialchars(__('supplier')) ?>
            </label><br>

            <select id="supplier_id" name="supplier_id" required>
                <option value="">
                    <?= htmlspecialchars(__('select_supplier')) ?>
                </option>

                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= htmlspecialchars((string)$supplier['id']) ?>">
                        <?= htmlspecialchars((string)$supplier['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

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

<?php include '../app/includes/footer.php'; ?>
