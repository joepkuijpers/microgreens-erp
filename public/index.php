<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Microgreens ERP Dashboard</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f9; }
        h1 { color: #2c3e50; }
        .card { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background: #ecf0f1; }
        .badge { padding: 4px 8px; border-radius: 4px; color: white; font-size: 0.8em; font-weight: bold; }
        .bg-completed { background: #27ae60; }
        .bg-running { background: #f39c12; }
    </style>
</head>
<body>
    <h1>Microgreens Dashboard</h1>
    <div class="card">
        <h2>Batches</h2>
        <table>
            <tr><th>Code</th><th>Status</th><th>Gewicht</th></tr>
            <?php
            $db = new PDO("sqlite:../database/MicrogreensERP_Development.sqlite");
            $stmt = $db->query("SELECT batch_code, status, actual_quantity, quantity_unit FROM production_batches ORDER BY created_at DESC LIMIT 5");
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr><td>{$row['batch_code']}</td><td>{$row['status']}</td><td>{$row['actual_quantity']} {$row['quantity_unit']}</td></tr>";
            }
            ?>
        </table>
    </div>
    <div class="card">
        <h2>Freeze-Dry Processen</h2>
        <table>
            <tr><th>Cycle</th><th>Machine</th><th>Status</th><th>Yield</th></tr>
            <?php
            $stmt = $db->query("SELECT cycle_code, machine_id, status, ROUND((final_weight_g/input_weight_g)*100, 1) as yield FROM freeze_dry_processes ORDER BY started_at DESC LIMIT 5");
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $class = ($row['status'] == 'COMPLETED') ? 'bg-completed' : 'bg-running';
                echo "<tr><td>{$row['cycle_code']}</td><td>{$row['machine_id']}</td><td><span class='badge {$class}'>{$row['status']}</span></td><td>{$row['yield']}%</td></tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>
