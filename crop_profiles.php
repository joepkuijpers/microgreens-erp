<?php
include 'includes/header.php';
include 'includes/sidebar.php';
include 'db_connect.php';

$profiles = $db->query("
    SELECT
        crop_name,
        blackout_days,
        grow_days_min,
        grow_days_max,
        light_hours_per_day,
        growth_light_hours,
        temp_min,
        temp_max,
        humidity_min,
        humidity_max,
        seed_grams_per_tray,
        expected_yield_grams_per_tray,
        watering_per_day,
        irrigation_notes,
        notes
    FROM crop_profiles
    ORDER BY crop_name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.profile-page {
    padding: 24px;
}

.profile-header {
    background: linear-gradient(135deg, #166534, #0f766e);
    color: white;
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 24px;
}

.profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 18px;
}

.profile-card {
    background: white;
    border-radius: 18px;
    padding: 20px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
}

.profile-card h3 {
    margin: 0 0 12px 0;
    color: #14532d;
}

.profile-line {
    margin-bottom: 8px;
    color: #374151;
}

.profile-note {
    margin-top: 12px;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.5;
}
</style>

<main class="main">
    <div class="profile-page">
        <div class="profile-header">
            <h1>Teeltprofielen</h1>
            <p>Standaard klimaat-, licht- en irrigatieprofielen per microgreen.</p>
        </div>

        <div class="profile-grid">
            <?php foreach ($profiles as $profile): ?>
                <div class="profile-card">
                    <h3><?= htmlspecialchars($profile['crop_name']) ?></h3>

                    <div class="profile-line">
                        Blackout: <strong><?= htmlspecialchars((string)$profile['blackout_days']) ?> dagen</strong>
                    </div>

                    <div class="profile-line">
                        Teeltduur: <strong><?= htmlspecialchars((string)$profile['grow_days_min']) ?>–<?= htmlspecialchars((string)$profile['grow_days_max']) ?> dagen</strong>
                    </div>

                 
                        Licht groei: <strong><?= htmlspecialchars((string)$profile['growth_light_hours']) ?> uur/dag</strong>
                        
                    <div class="profile-line">
                        Temperatuur: <strong><?= htmlspecialchars((string)$profile['temp_min']) ?>–<?= htmlspecialchars((string)$profile['temp_max']) ?> °C</strong>
                    </div>

                 <div class="profile-line">
    Luchtvochtigheid: <strong><?= htmlspecialchars((string)$profile['humidity_min']) ?>–<?= htmlspecialchars((string)$profile['humidity_max']) ?>%</strong>
</div>

<div class="profile-line">
    Zaad: <strong><?= number_format((float)$profile['seed_grams_per_tray'], 1, ',', '.') ?> g/tray</strong>
</div>

                    <div class="profile-line">
                        Verwachte opbrengst: <strong><?= number_format((float)$profile['expected_yield_grams_per_tray'], 1, ',', '.') ?> g/tray</strong>
                    </div>

                    <div class="profile-line">
                        Watergift: <strong><?= htmlspecialchars((string)$profile['watering_per_day']) ?>x per dag</strong>
                    </div>
                    <div class="profile-note">
                        <strong>Irrigatie:</strong><br>
                        <?= htmlspecialchars($profile['irrigation_notes']) ?>
                    </div>

                    <div class="profile-note">
                        <strong>Notities:</strong><br>
                        <?= htmlspecialchars($profile['notes']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
