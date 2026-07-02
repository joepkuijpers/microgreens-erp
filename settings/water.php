<?php
$assetPrefix = '../';
$navPrefix = '../';

$pageIcon = '💧';
$pageTitle = 'Waterbeheer';
$pageDescription = 'Beheer irrigatie, pompen en waterreservoirs.';

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
    ['label'=>'Water']
];

include '../includes/layout_start.php';
include '../includes/breadcrumbs.php';
include '../includes/page_header.php';
?>

<div class="card">

<h2>Waterbeheer</h2>

<table>

<tr>
    <th>Onderdeel</th>
    <th>Status</th>
</tr>

<tr>
    <td>Automatische irrigatie</td>
    <td>Nog niet gekoppeld</td>
</tr>

<tr>
    <td>Waterreservoir</td>
    <td>Nog niet gekoppeld</td>
</tr>

<tr>
    <td>Pomp</td>
    <td>Nog niet gekoppeld</td>
</tr>

<tr>
    <td>Waterniveau sensor</td>
    <td>Toekomstige uitbreiding</td>
</tr>

</table>

</div>

<?php include '../includes/layout_end.php'; ?>