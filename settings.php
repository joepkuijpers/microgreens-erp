<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';
include 'includes/language.php';


$message = "";

$allowedLanguages = [
    'nl' => 'Nederlands',
    'en' => 'English',
    'de' => 'Deutsch',
    'fr' => 'Français',
    'es' => 'Español',
    'it' => 'Italiano'
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $languageCode = $_POST['language_code'] ?? 'nl';

    if (!array_key_exists($languageCode, $allowedLanguages)) {
        $languageCode = 'nl';
    }

    $stmt = $db->prepare("
        UPDATE settings SET
            light_min = ?,
            light_max = ?,
            temp_min = ?,
            temp_max = ?,
            humidity_min = ?,
            humidity_max = ?,
            refresh_seconds = ?,
            language_code = ?
        WHERE id = 1
    ");

    $stmt->execute([
        $_POST['light_min'],
        $_POST['light_max'],
        $_POST['temp_min'],
        $_POST['temp_max'],
        $_POST['humidity_min'],
        $_POST['humidity_max'],
        $_POST['refresh_seconds'],
        $languageCode
    ]);

    $message = "✅ " . __('settings_saved');
}

$settings = $db->query("SELECT * FROM settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
?>

<style>
.settings-page {
    margin-left: 280px;
    padding: 30px;
    min-height: 100vh;
    background: #f8fafc;
}

.settings-card {
    background: white;
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    max-width: 700px;
}

.settings-card input,
.settings-card select {
    width: 100%;
    max-width: 300px;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 16px;
}

.settings-card label {
    font-weight: bold;
}

.save-btn {
    background: #2563eb;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
    font-size: 16px;
}

.success {
    background: #d1fae5;
    color: #065f46;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-weight: bold;
}
</style>

<div class="settings-page">

    <h1>⚙️ <?= __('settings') ?></h1>

    <?php if ($message): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="settings-card">

 <h2>🌞 <?= __('light') ?></h2>           

            <label>Minimum Lux</label><br>
            <input type="number" name="light_min" value="<?= htmlspecialchars($settings['light_min']) ?>"><br><br>

            <label>Maximum Lux</label><br>
            <input type="number" name="light_max" value="<?= htmlspecialchars($settings['light_max']) ?>"><br><br>

            <h2>🌡 <?= __('climate') ?></h2>

            <label>Minimum temperatuur °C</label><br>
            <input type="number" step="0.1" name="temp_min" value="<?= htmlspecialchars($settings['temp_min']) ?>"><br><br>

            <label>Maximum temperatuur °C</label><br>
            <input type="number" step="0.1" name="temp_max" value="<?= htmlspecialchars($settings['temp_max']) ?>"><br><br>

            <label>Minimum luchtvochtigheid %</label><br>
            <input type="number" step="0.1" name="humidity_min" value="<?= htmlspecialchars($settings['humidity_min']) ?>"><br><br>

            <label>Maximum luchtvochtigheid %</label><br>
            <input type="number" step="0.1" name="humidity_max" value="<?= htmlspecialchars($settings['humidity_max']) ?>"><br><br>

            <h2>📈 <?= __('dashboard') ?></h2>

            <label>Verversinterval seconden</label><br>
            <input type="number" name="refresh_seconds" value="<?= htmlspecialchars($settings['refresh_seconds']) ?>"><br><br>

            <h2>🌍 <?= __('language') ?></h2>

            <label><?= __('erp_language') ?></label>
            <select name="language_code">
                <?php foreach ($allowedLanguages as $code => $label): ?>
                    <option value="<?= htmlspecialchars($code) ?>" <?= ($settings['language_code'] ?? 'nl') === $code ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

          <button class="save-btn" type="submit">💾 <?= __('save_settings') ?></button>  

        </div>
    </form>

</div>

<?php include 'includes/footer.php'; ?>