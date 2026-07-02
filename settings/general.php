<?php
$assetPrefix = '../';
$navPrefix = '../';

$pageIcon = '⚙';
$pageTitle = 'Algemene instellingen';
$pageDescription = 'Beheer algemene ERP-instellingen zoals bedrijfsgegevens, taal, tijdzone en valuta.';

$pageActions = [
    ['href' => 'index.php', 'icon' => '←', 'label' => 'Terug naar instellingen']
];

$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => '../dashboard.php'],
    ['label' => 'Instellingen', 'href' => 'index.php'],
    ['label' => 'Algemeen']
];

include '../includes/layout_start.php';
include '../includes/breadcrumbs.php';
include '../includes/page_header.php';
?>

<div class="card">
    <h2>Bedrijfsinstellingen</h2>
    <p>Deze pagina is voorbereid voor algemene ERP-instellingen.</p>

    <table>
        <tr>
            <th>Instelling</th>
            <th>Waarde</th>
        </tr>
        <tr>
            <td>Bedrijfsnaam</td>
            <td>Microgreens ERP</td>
        </tr>
        <tr>
            <td>Taal</td>
            <td>Nederlands</td>
        </tr>
        <tr>
            <td>Tijdzone</td>
            <td>Europe/Amsterdam</td>
        </tr>
        <tr>
            <td>Valuta</td>
            <td>EUR</td>
        </tr>
    </table>
</div>

<?php include '../includes/layout_end.php'; ?>
