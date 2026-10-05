<?php
// Veiligheidscheck
if (!isset($db)) {
    die("Fout: Database connectie (\$db) is niet beschikbaar. Controleer index.php");
}
?>

<div class="card">
    <h2>👥 <?php echo htmlspecialchars($module_title); ?></h2>
    
    <p>Module: Personeel</p>

    <h3>Laatste gegevens</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Naam</th>
                <th>Rol</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            try {
                // Probeer data op te halen
                $stmt = $db->query("SELECT * FROM staff_members ORDER BY rowid DESC LIMIT 5");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['name'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['role'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['status'] ?? 'Actief') . "</td>";
                    echo "</tr>";
                }
            } catch (PDOException $e) {
                echo "<tr><td colspan='4' style='color:red; padding:10px;'>";
                echo "⚠️ Tabel 'staff_members' bestaat niet of is leeg.<br>";
                echo "<small>Fout: " . htmlspecialchars($e->getMessage()) . "</small>";
                echo "</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
