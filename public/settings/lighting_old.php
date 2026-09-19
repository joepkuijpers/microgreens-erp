<?php
require_once '../../app/includes/auth.php';
auth_require_login();
$assetPrefix = '../';
$navPrefix = '../';

$pageIcon = '💡';
$pageTitle = 'Verlichting';
$pageDescription = 'Beheer lichtschema’s, luxgrenzen en toekomstige lampsturing.';

$pageActions = [
    ['href' => 'index.php', 'icon' => '←', 'label' => 'Terug']
];

$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => '../dashboard.php'],
    ['label' => 'Instellingen', 'href' => 'index.php'],
    ['label' => 'Verlichting']
];

include '../../app/includes/layout_start.php';
include '../../app/includes/breadcrumbs.php';
include '../../app/includes/page_header.php';
?>

<div class="card">
    <h2>Verlichtingsinstellingen</h2>

    <table>
        <tr><th>Onderdeel</th><th>Status</th></tr>
        <tr><td>Lichtschema</td><td>Nog niet geconfigureerd</td></tr>
        <tr><td>Daglengte</td><td>Nog niet geconfigureerd</td></tr>
        <tr><td>Luxregeling</td><td>Voorbereid via sensorinstellingen</td></tr>
        <tr><td>Automatische lampsturing</td><td>Nog niet gekoppeld</td></tr>
    </table>
</div>

<?php include '../../app/includes/layout_end.php'; ?>
