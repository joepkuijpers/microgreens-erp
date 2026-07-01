<?php
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
    <h1>Voorraad toevoegen</h1>

    <div class="card">
        <form method="post" action="add_inventory.php">
            <label>Artikelnaam</label><br>
            <input type="text" name="item_name" required><br><br>

            <label>Categorie</label><br>
            <input type="text" name="category" placeholder="Bijv. Zaden, Verpakking, Substraat"><br><br>

            <label>Hoeveelheid</label><br>
            <input type="number" step="0.01" name="quantity" required><br><br>

            <label>Eenheid</label><br>
            <input type="text" name="unit" placeholder="KG, gram, stuks" required><br><br>

            <label>Kostprijs per eenheid (€)</label><br>
            <input type="number" step="0.01" name="unit_cost" required><br><br>

            <button type="submit" class="btn">Opslaan</button>
            <a href="list_inventory.php" class="btn">Terug</a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>