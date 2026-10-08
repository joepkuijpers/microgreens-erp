<?php require_once __DIR__ . '/app/includes/header.php'; $pageTitle = __('module_b01'); ?>
    <h2><?php echo __('seed_inventory_title'); ?></h2>
    <p><?php echo __('seed_inventory_description'); ?></p>
    
    <!-- Placeholder for Seed Inventory Form -->
    <form method="POST">
        <label><?php echo __('seed_variety'); ?>:</label>
        <input type="text" name="variety" required>
        
        <label><?php echo __('quantity_grams'); ?>:</label>
        <input type="number" name="quantity" step="0.01" required>
        
        <button type="submit"><?php echo __('add_inventory'); ?></button>
    </form>
</div>

<div class="next-step-bar">
    <div>
        
    </div>
    <div>
        <a href=b02_germination.php>Volgende: b02_germination.php →</a>
    </div>
</div>
<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
