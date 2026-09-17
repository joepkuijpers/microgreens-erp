<div class="card">

<h2>💶 <?= __('financial') ?></h2>

<table>
    <tr>
        <td><?= __('revenue') ?></td>
        <td>€<?= number_format($omzet, 2) ?></td>
    </tr>

    <tr>
        <td><?= __('expenses') ?></td>
        <td>€<?= number_format($kosten_bedrag, 2) ?></td>
    </tr>

    <tr>
        <td><?= __('profit') ?></td>
        <td>€<?= number_format($winst, 2) ?></td>
    </tr>
</table>

</div>