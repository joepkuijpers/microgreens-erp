<?php
$breadcrumbs = $breadcrumbs ?? [];

if (empty($breadcrumbs)) {
    return;
}
?>

<nav class="breadcrumbs" aria-label="Breadcrumb">
    <?php foreach ($breadcrumbs as $index => $item): ?>
        <?php if ($index > 0): ?>
            <span class="breadcrumb-separator">›</span>
        <?php endif; ?>

        <?php if (!empty($item['href'])): ?>
            <a href="<?= htmlspecialchars($item['href']) ?>">
                <?= htmlspecialchars($item['label']) ?>
            </a>
        <?php else: ?>
            <span><?= htmlspecialchars($item['label']) ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
