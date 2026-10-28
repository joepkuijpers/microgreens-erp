<?php
$db_path = __DIR__ . "/MicrogreensERP_Live.sqlite";
if (!isset($message)) { $message = ""; }

try {
    $pdo = new PDO("sqlite:" . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE IF NOT EXISTS bxx_germination_tests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        seed_batch_code TEXT,
        crop_type TEXT,
        sample_size INTEGER,
        sprouted_count INTEGER,
        status TEXT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["seed_batch_code"])) {
        $batch = trim($_POST["seed_batch_code"]);
        $crop = trim($_POST["crop_type"]);
        $sample = (int)$_POST["sample_size"];
        $sprouted = (int)$_POST["sprouted_count"];
        $notes = trim($_POST["notes"]);

        if ($sample > 0) {
            $rate = ($sprouted / $sample) * 100;
            $status = ($rate >= 85) ? "Goedgekuurd" : "Afgekuurd / Bijsturen";

            $stmt = $pdo->prepare("INSERT INTO bxx_germination_tests (seed_batch_code, crop_type, sample_size, sprouted_count, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$batch, $crop, $sample, $sprouted, $status, $notes]);

            $message = "Kiemtest succesvol opgeslagen! Kiemkracht: " . number_format($rate, 1) . "% (" . $status . ")";
        }
    }

    $tests = $pdo->query("SELECT * FROM bxx_germination_tests ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = "Database Fout: " . $e->getMessage();
    $tests = [];
}
?>