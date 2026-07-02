<?php
$pageIcon = $pageIcon ?? '🌱';
$pageTitle = $pageTitle ?? 'Microgreens ERP';
$pageDescription = $pageDescription ?? '';
$pageActions = $pageActions ?? [];
?>

<div class="page-header">
    <div>
        <h1><?= htmlspecialchars($pageIcon . ' ' . $pageTitle) ?></h1>

        <?php if (!empty($pageDescription)): ?>
            <p><?= htmlspecialchars($pageDescription) ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($pageActions)): ?>
        <div class="page-actions">
            <?php foreach ($pageActions as $action): ?>
                <a class="button" href="<?= htmlspecialchars($action['href']) ?>">
                    <?= htmlspecialchars($action['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
