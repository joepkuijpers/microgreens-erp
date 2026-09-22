<?php
if (!isset($db)) { die("Database connectie niet beschikbaar."); }
$target_table = "maintenance_logs";
$module_emoji = "🛠️";
?>
<div class="card">
    <h2><?php echo $module_emoji; ?> <?php echo htmlspecialchars($module_title); ?></h2>
    <div style="margin:15px 0; padding:15px; background:#f0f4f8; border-radius:6px;">
        <p><strong>Verwachte Tabel:</strong> <code><?php echo $target_table; ?></code></p>
    </div>
    <h3>Laatste gegevens</h3>
    <table>
        <thead><tr><th>ID</th><th>Waarde</th><th>Datum</th><th>Details</th></tr></thead>
        <tbody>
            <?php
            try {
                $stmt = $db->query("SELECT * FROM " . $target_table . " ORDER BY rowid DESC LIMIT 5");
                $found = false;
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $found = true;
                    $k = array_keys($row);
                    $c1 = htmlspecialchars($row[$k[0]] ?? '-');
                    $c2 = htmlspecialchars($row[$k[1]] ?? '-');
                    $c3 = htmlspecialchars($row[$k[2]] ?? '-');
                    $c4 = htmlspecialchars($row[$k[3]] ?? '-');
                    echo "<tr><td><strong>{$c1}</strong></td><td><span class='badge bg-running'>{$c2}</span></td><td>{$c3}</td><td>{$c4}</td></tr>";
                }
                if (!$found) {
                    echo "<tr><td colspan='4' style='text-align:center;color:#7f8c8d;'>Geen data in <strong>{$target_table}</strong></td></tr>";
                }
            } catch (PDOException $e) {
                echo "<tr><td colspan='4' style='color:red;padding:10px;'>⚠️ Tabel '<strong>{$target_table}</strong>' bestaat niet.<br><small>" . htmlspecialchars($e->getMessage()) . "</small></td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
