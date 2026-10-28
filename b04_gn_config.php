<?php
require_once __DIR__ . '/app/includes/header.php';
$pageTitle = __('gn_configuration');
$message = ""; $messageType = "";

// ACTIE: NIEUW GN PROFIEL
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'add_gn') {
    $name = trim($_POST['name'] ?? '');
    $l = (float)($_POST['length'] ?? 0);
    $w = (float)($_POST['width'] ?? 0);
    $h = (float)($_POST['height'] ?? 15);
    
    if ($name && $l > 0 && $w > 0) {
        $surface = ($l * $w) / 10000; // cm² naar m²
        $volume = ($l * $w * $h) / 1000; // cm³ naar liters
        try {
            $db->prepare("INSERT INTO gn_profiles (name, length_cm, width_cm, height_cm, surface_m2, volume_l) VALUES (?, ?, ?, ?, ?, ?)")
               ->execute([$name, $l, $w, $h, $surface, $volume]);
            $message = __('gn_added') . " ($name)"; $messageType = "success";
        } catch (Exception $e) {
            $message = __('error_gn_exists'); $messageType = "error";
        }
    } else { $message = __('error_fill_fields'); $messageType = "error"; }
}

// ACTIE: GN KOPPELEN AAN TRAY
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'link_tray') {
    $tray_id = (int)($_POST['tray_id'] ?? 0);
    $gn_id = (int)($_POST['gn_id'] ?? 0);
    if ($tray_id && $gn_id) {
        try {
            $db->prepare("UPDATE trays SET gn_profile_id = ? WHERE id = ?")->execute([$gn_id, $tray_id]);
            $message = __('tray_gn_linked'); $messageType = "success";
        } catch (Exception $e) { $message = $e->getMessage(); $messageType = "error"; }
    }
}

$data = [];
try {
    $data['profiles'] = $db->query("SELECT * FROM gn_profiles ORDER BY surface_m2 DESC")->fetchAll();
    $data['trays'] = $db->query("SELECT t.*, g.name as gn_name FROM trays t LEFT JOIN gn_profiles g ON t.gn_profile_id = g.id ORDER BY t.tray_code")->fetchAll();
} catch (Exception $e) { $message = $e->getMessage(); }
?>

<div style="max-width:1200px; margin:0 auto;">
    <?php if($message): ?>
        <div style="background:<?php echo $messageType==='error'?'#f8d7da':'#d4edda'; ?>; color:<?php echo $messageType==='error'?'#721c24':'#155724'; ?>; padding:15px; border-radius:5px; margin-bottom:20px;"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- GN PROFIELEN -->
    <div style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-bottom:30px;">
        <h2 style="margin-top:0;">📏 <?php echo __('gn_profiles'); ?></h2>
        <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
            <thead>
                <tr style="background:#f4f4f9; text-align:left;">
                    <th style="padding:10px; border-bottom:2px solid #ddd;">Naam</th>
                    <th style="padding:10px; border-bottom:2px solid #ddd;">Lengte (cm)</th>
                    <th style="padding:10px; border-bottom:2px solid #ddd;">Breedte (cm)</th>
                    <th style="padding:10px; border-bottom:2px solid #ddd;">Hoogte (cm)</th>
                    <th style="padding:10px; border-bottom:2px solid #ddd;">Oppervlak (m²)</th>
                    <th style="padding:10px; border-bottom:2px solid #ddd;">Volume (L)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data['profiles'] as $p): ?>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo $p['length_cm']; ?></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo $p['width_cm']; ?></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo $p['height_cm']; ?></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo number_format($p['surface_m2'], 3); ?></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo number_format($p['volume_l'], 1); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Nieuw Profiel -->
        <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; background:#f8f9fa; padding:15px; border-radius:5px;">
            <input type="hidden" name="action" value="add_gn">
            <input type="text" name="name" placeholder="Naam (bijv. GN 2/3)" required style="flex:1; min-width:150px; padding:8px; border:1px solid #ddd; border-radius:4px;">
            <input type="number" step="0.1" name="length" placeholder="Lengte (cm)" required style="width:100px; padding:8px; border:1px solid #ddd; border-radius:4px;">
            <input type="number" step="0.1" name="width" placeholder="Breedte (cm)" required style="width:100px; padding:8px; border:1px solid #ddd; border-radius:4px;">
            <input type="number" step="0.1" name="height" placeholder="Hoogte (cm)" value="15" style="width:80px; padding:8px; border:1px solid #ddd; border-radius:4px;">
            <button type="submit" style="background:#27ae60; color:#fff; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;">➕ <?php echo __('add_gn'); ?></button>
        </form>
    </div>

    <!-- TRAYS KOPPELEN -->
    <div style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1);">
        <h2 style="margin-top:0;">🔗 <?php echo __('link_gn_to_trays'); ?></h2>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f4f4f9; text-align:left;">
                    <th style="padding:10px; border-bottom:2px solid #ddd;">Tray Code</th>
                    <th style="padding:10px; border-bottom:2px solid #ddd;">Huidige GN</th>
                    <th style="padding:10px; border-bottom:2px solid #ddd;">Koppel GN</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data['trays'] as $t): ?>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong><?php echo htmlspecialchars($t['tray_code']); ?></strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo $t['gn_name'] ?? '<span style="color:#999;">Geen</span>'; ?></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;">
                        <form method="POST" style="display:flex; gap:5px;">
                            <input type="hidden" name="action" value="link_tray">
                            <input type="hidden" name="tray_id" value="<?php echo $t['id']; ?>">
                            <select name="gn_id" style="flex:1; padding:5px; border:1px solid #ddd; border-radius:3px;">
                                <option value="">-- Kies --</option>
                                <?php foreach($data['profiles'] as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo $t['gn_profile_id']==$p['id']?'selected':''; ?>><?php echo htmlspecialchars($p['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" style="background:#3498db; color:#fff; border:none; padding:5px 10px; border-radius:3px; cursor:pointer;">💾</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="next-step-bar">
    <a href="b04_tray_management.php">← <?php echo __('tray_management'); ?></a>
    <a href="b05_seed_planning.php"><?php echo __('module_b05'); ?> →</a>
</div>

<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
