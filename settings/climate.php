<?php
$assetPrefix = '../';
$navPrefix = '../';

$pageIcon = '🌍';
$pageTitle = 'Klimaatregeling';
$pageDescription = 'Beheer automatische klimaatregeling voor de teeltruimte.';

$pageActions = [
    [
        'href' => 'index.php',
        'icon' => '←',
        'label' => 'Terug'
    ]
];

$breadcrumbs = [
    ['label'=>'Dashboard','href'=>'../dashboard.php'],
    ['label'=>'Instellingen','href'=>'index.php'],
    ['label'=>'Klimaat']
];

include '../includes/layout_start.php';
include '../includes/breadcrumbs.php';
include '../includes/page_header.php';
?>

<div class="card">

<h2>Automatische klimaatregeling</h2>

<table>

<tr>
    <th>Functie</th>
    <th>Status</th>
</tr>

<tr>
    <td>Automatische regeling</td>
    <td>Uitgeschakeld</td>
</tr>

<tr>
    <td>Ventilatoren</td>
    <td>Nog niet gekoppeld</td>
</tr>

<tr>
    <td>Verwarming</td>
    <td>Nog niet gekoppeld</td>
</tr>

<tr>
    <td>Koeling</td>
    <td>Nog niet gekoppeld</td>
</tr>

<tr>
    <td>Luchtbevochtiger</td>
    <td>Nog niet gekoppeld</td>
</tr>

<tr>
    <td>Ontvochtiger</td>
    <td>Nog niet gekoppeld</td>
</tr>

</table>

</div>

<?php include '../includes/layout_end.php'; ?>