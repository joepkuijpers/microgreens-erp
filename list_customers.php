<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$customers = $db->query("
    SELECT
        id,
        name,
        email,
        phone
    FROM customers
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main">
    <h1>👥 Klanten</h1>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Naam</th>
                    <th>E-mail</th>
                    <th>Telefoon</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="4">Nog geen klanten gevonden.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$customer['id']) ?></td>
                        <td><?= htmlspecialchars((string)$customer['name']) ?></td>
                        <td><?= htmlspecialchars((string)($customer['email'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)($customer['phone'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>