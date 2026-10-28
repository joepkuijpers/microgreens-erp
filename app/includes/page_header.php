<?php
$pageIcon = $pageIcon ?? '🌱';
$pageTitle = $pageTitle ?? 'Microgreens ERP';
$pageDescription = $pageDescription ?? '';
$pageActions = $pageActions ?? [];
?>

<div class="page-header">

    <div class="page-header-left">

        <h1>
            <?= htmlspecialchars($pageIcon) ?>
            <?= htmlspecialchars($pageTitle) ?>
        </h1>

        <?php if (!empty($pageDescription)): ?>
            <p><?= htmlspecialchars($pageDescription) ?></p>
        <?php endif; ?>

    </div>

    <div class="page-header-right">
        <?php include __DIR__ . '/page_actions.php'; ?>
    </div>

</div>
