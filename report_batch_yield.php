<?php
require 'config/database.php';

$result = $db->query("
SELECT
    g.id,
    g.crop,
    g.tray_count,
    COALESCE(SUM(h.weight_grams),0) as total_harvest
FROM grow_batches g
LEFT JOIN harvests h ON g.id = h.batch_id
GROUP BY g.id
ORDER BY g.id
");

echo "<h1>Opbrengst per batch</h1>";

foreach($result as $row){

    $perTray = 0;

    if($row['tray_count'] > 0){
        $perTray = $row['total_harvest'] / $row['tray_count'];
    }

    echo "Batch " . $row['id'] .
         " | " . $row['crop'] .
         " | Oogst: " . $row['total_harvest'] . " g" .
         " | Per tray: " . round($perTray,1) . " g" .
         "<br>";
}
?>
