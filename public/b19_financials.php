<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
session_start();

$dbPath = __DIR__ . "/../database/MicrogreensERP_Live.sqlite";
if (!file_exists($dbPath)) { die("Database niet gevonden"); }

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("DB Fout: " . $e->getMessage()); }

$message = "";
$error = "";

// --- VERWERKING FORMULIER ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST["transaction_type"] ?? "";
    $category = $_POST["category"] ?? "";
    $amount = floatval($_POST["amount"] ?? 0);
    $desc = $_POST["description"] ?? "";
    $date = $_POST["transaction_date"] ?? date("Y-m-d");
    $entity_id = $_POST["related_entity_id"] ?? null;
    $entity_type = $_POST["related_entity_type"] ?? null;

    if ($type && $category && $amount > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO financial_transactions (transaction_type, category, amount, description, transaction_date, related_entity_id, related_entity_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$type, $category, $amount, $desc, $date, $entity_id, $entity_type, $_SESSION["user"] ?? "admin"]);
            $message = "Transactie succesvol toegevoegd!";
        } catch (Exception $e) {
            $error = "Fout bij opslaan: " . $e->getMessage();
        }
    } else {
        $error = "Vul alle verplichte velden in (Type, Categorie, Bedrag).";
    }
}

// --- OPHALEN DATA ---
// Totaal Inkomsten
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM financial_transactions WHERE transaction_type = 'INCOME'");
$total_income = $stmt->fetchColumn();

// Totaal Kosten
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM financial_transactions WHERE transaction_type = 'EXPENSE'");
$total_expense = $stmt->fetchColumn();

// Winst
$profit = $total_income - $total_expense;

// Recentste transacties
$stmt = $pdo->query("SELECT * FROM financial_transactions ORDER BY transaction_date DESC LIMIT 20");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>B19 - Financials</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>input[type="date"] { width: 100%; padding: 10px; cursor: pointer; box-sizing: border-box; } 
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h1, h2 { color: #2c3e50; }
        .kpi-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .kpi-box { flex: 1; padding: 20px; text-align: center; border-radius: 8px; color: white; }
        .bg-income { background: #27ae60; }
        .bg-expense { background: #c0392b; }
        .bg-profit { background: #2980b9; }
        .kpi-value { font-size: 2em; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #ecf0f1; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background: #2c3e50; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #34495e; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .nav-link { display: inline-block; margin-bottom: 20px; color: #2c3e50; text-decoration: none; font-weight: bold; }
        .nav-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div style="padding:20px;"><a href="dashboard.php" style="background:#2c3e50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;font-weight:bold;display:inline-block;">← Terug naar Dashboard</a></div>
<div class="container">
    <a href="dashboard.php" class="nav-link">← Terug naar Dashboard</a>
    <h1>💰 B19 - Financials & Kostenbeheer</h1>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- KPIs -->
    <div class="kpi-row">
        <div class="kpi-box bg-income">
            <div>Inkomsten</div>
            <div class="kpi-value">€ <?= number_format($total_income, 2) ?></div>
        </div>
        <div class="kpi-box bg-expense">
            <div>Kosten</div>
            <div class="kpi-value">€ <?= number_format($total_expense, 2) ?></div>
        </div>
        <div class="kpi-box bg-profit">
            <div>Winst/Verlies</div>
            <div class="kpi-value">€ <?= number_format($profit, 2) ?></div>
        </div>
    </div>

    <!-- Formulier -->
    <div class="card">
        <h2>Nieuwe Transactie Toevoegen</h2>
        <form method="POST" style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);">
    <div style="margin-bottom:15px;">
        <label style="display:block;margin-bottom:5px;font-weight:bold;">Type Transactie</label>
        <select name="transaction_type" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;">
            <option value="INCOME">📈 Inkomst (Verkoop)</option>
            <option value="EXPENSE">📉 Uitgave (Kosten)</option>
        </select>
    </div>
    <div style="margin-bottom:15px;">
        <label style="display:block;margin-bottom:5px;font-weight:bold;">Categorie</label>
        <select name="category" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;">
            <option value="SALES">Verkoop</option>
            <option value="SEED">Zaad / Input</option>
            <option value="SUPPLIER">Leverancier</option>
            <option value="ENERGY">Energie</option>
            <option value="WATER">Water</option>
            <option value="PACKAGING">Verpakking</option>
            <option value="OTHER">Overig</option>
        </select>
    </div>
    <div style="margin-bottom:15px;">
        <label style="display:block;margin-bottom:5px;font-weight:bold;">Bedrag (€)</label>
        <input type="number" step="0.01" name="amount" required placeholder="0.00" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;">
    </div>
    <div style="margin-bottom:15px;">
        <label style="display:block;margin-bottom:5px;font-weight:bold;">Datum</label>
        <input type="date" name="transaction_date" value="<?php echo date("Y-m-d"); ?>" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;">
    </div>
    <div style="margin-bottom:15px;">
        <label style="display:block;margin-bottom:5px;font-weight:bold;">Omschrijving</label>
        <textarea name="description" rows="3" placeholder="Bijv. Aankoop zaden Broccoli" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;"></textarea>
    </div>
    <button type="submit" style="background:#2c3e50;color:white;padding:12px 20px;border:none;border-radius:4px;cursor:pointer;font-size:16px;width:100%;">💾 Opslaan Transactie</button>
</form>
    </div>

    <!-- Overzicht -->
    <div class="card">
        <h2>Recente Transacties</h2>
        <table>
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Type</th>
                    <th>Categorie</th>
                    <th>Omschrijving</th>
                    <th>Bedrag</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t[transaction_date]) ?></td>
                    <td>
                        <span style="color: <?= $t[transaction_type] == 'INCOME' ? 'green' : 'red' ?>; font-weight:bold;">
                            <?= $t[transaction_type] == 'INCOME' ? '📈 IN' : '📉 UIT' ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($t[category]) ?></td>
                    <td><?= htmlspecialchars($t[description]) ?></td>
                    <td>€ <?= number_format($t[amount], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
