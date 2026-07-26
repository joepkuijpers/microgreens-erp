<?php
$pageActions = $pageActions ?? [];

if (empty($pageActions)) {
    return;
}
?>

<div class="page-actions">
    <?php foreach ($pageActions as $action): ?>
        <a href="<?= htmlspecialchars($action['href']) ?>" class="button">
            <?= htmlspecialchars($action['icon'] ?? '') ?>
            <?= htmlspecialchars($action['label']) ?>
        </a>
    <?php endforeach; ?>
</div>
