<?php
// FOUTEN TONEN (voor debug)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// SESSIE STARTEN (veilig checken of die al loopt)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// TAAL WISSEL LOGICA (MOET ALLEREERST GEBEUREN, VOOR ELKE OUTPUT!)
if (isset($_GET['lang']) && in_array($_GET['lang'], ['nl', 'en', 'de', 'fr', 'es', 'it'])) {
    $_SESSION['erp_language'] = $_GET['lang'];
    // URL schoonmaken en redirecten
    $cleanUrl = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: " . $cleanUrl);
    exit; // BELANGRIJK: stop direct na redirect!
}

// TAAL BEPALEN
$languageCode = $_SESSION['erp_language'] ?? 'nl';

// TAAL BESTAND LADEN
if (file_exists(__DIR__ . '/language.php')) {
    require_once __DIR__ . '/language.php';
} else {
    function __($k){ return $k; }
}

// DATABASE CONNECTIE
$dbPath = __DIR__ . '/../../database/MicrogreensERP_Live.sqlite';
if (!file_exists($dbPath)) { die("DB niet gevonden: " . $dbPath); }
try {
    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }

// WATCHDOG DATA
$wdStatus = 'ok'; 
$wdMsg = '';
try {
    $stmt = $db->prepare("SELECT status, message FROM system_alerts WHERE acknowledged = 0 ORDER BY created_at DESC LIMIT 1");
    $stmt->execute(); 
    $alert = $stmt->fetch();
    if ($alert) { 
        $wdStatus = $alert['status']; 
        $wdMsg = htmlspecialchars($alert['message']); 
    }
} catch (Exception $e) {}

// MODULES
$modules = [
['id'=>'DASH','file'=>'index.php','key'=>'dashboard_home'],
['id'=>'B01','file'=>'b01_seed_inventory.php','key'=>'module_b01'],
['id'=>'B02','file'=>'b02_germination.php','key'=>'module_b02'],
['id'=>'B03','file'=>'b03_growth_stage.php','key'=>'module_b03'],
['id'=>'B04','file'=>'b04_tray_management.php','key'=>'module_b04'],
['id'=>'B05','file'=>'b05_seed_planning.php','key'=>'module_b05'],
['id'=>'B06','file'=>'b06_production_actions.php','key'=>'module_b06'],
['id'=>'B07','file'=>'b07_harvest.php','key'=>'module_b07'],
['id'=>'B08','file'=>'b08_freeze_dry.php','key'=>'module_b08'],
['id'=>'B09','file'=>'b09_packaging.php','key'=>'module_b09'],
['id'=>'B10','file'=>'b10_iot_climate.php','key'=>'module_b10'],
['id'=>'B11','file'=>'b11_packaging.php','key'=>'module_b11'],
['id'=>'B12','file'=>'b12_cleaning.php','key'=>'module_b12'],
['id'=>'B13','file'=>'b13_energy.php','key'=>'module_b13'],
['id'=>'B14','file'=>'b14_water_usage.php','key'=>'module_b14'],
['id'=>'B15','file'=>'b15_substrate.php','key'=>'module_b15'],
['id'=>'B16','file'=>'b16_task_scheduler.php','key'=>'module_b16'],
['id'=>'B17','file'=>'b17_staff.php','key'=>'module_b17'],
['id'=>'B18','file'=>'b18_supplier.php','key'=>'module_b18'],
['id'=>'B19','file'=>'b19_financials.php','key'=>'module_b19'],
['id'=>'B20','file'=>'b20_waste.php','key'=>'module_b20'],
['id'=>'B21','file'=>'b21_logistics.php','key'=>'module_b21'],
['id'=>'B22','file'=>'b22_maintenance.php','key'=>'module_b22'],
['id'=>'B23','file'=>'b23_sops.php','key'=>'module_b23'],
['id'=>'B24','file'=>'b24_sales_analytics.php','key'=>'module_b24'],
['id'=>'B25','file'=>'b25_customer_feedback.php','key'=>'module_b25'],
['id'=>'B26','file'=>'b26_system_settings.php','key'=>'module_b26'],
['id'=>'B27','file'=>'b27_api_integrations.php','key'=>'module_b27'],
['id'=>'B28','file'=>'b28_watchdog_alerts.php','key'=>'module_b28'],
];
$pageTitle = $pageTitle ?? 'ERP';
?>
<!DOCTYPE html><html lang="<?php echo htmlspecialchars($languageCode); ?>"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle); ?></title>
<style>
*{box-sizing:border-box;}
body{font-family:sans-serif;margin:0;display:flex;height:100vh;background:#f4f4f9}
.sidebar{width:250px;background:#2c3e50;color:#ecf0f1;overflow-y:auto;flex-shrink:0}
.sidebar-header{padding:20px;background:#1a252f;text-align:center;font-weight:bold;border-bottom:1px solid #34495e}
.nav-links{list-style:none;padding:0;margin:0}
.nav-links li{border-bottom:1px solid #34495e}
.nav-links a{display:block;padding:12px 20px;color:#bdc3c7;text-decoration:none}
.nav-links a:hover,.nav-links a.active{background:#34495e;color:#fff;border-left:4px solid #27ae60}
.main-content{flex-grow:1;display:flex;flex-direction:column;overflow:hidden}
.top-header{background:#fff;padding:15px 30px;box-shadow:0 2px 5px rgba(0,0,0,0.05);display:flex;justify-content:space-between;align-items:center}
.page-title{font-size:1.5rem;color:#2c3e50;margin:0}
.watchdog-indicator{padding:8px 15px;border-radius:20px;font-weight:bold;text-decoration:none;margin-left:20px}
.wd-ok{background:#d4edda;color:#155724}.wd-warning{background:#fff3cd;color:#856404}.wd-critical{background:#f8d7da;color:#721c24}
.content-area{padding:30px;overflow-y:auto;flex-grow:1}
.lang-switch a{margin:0 5px;text-decoration:none;color:#2980b9;font-weight:bold}
.lang-switch a.active{color:#2c3e50;text-decoration:underline;}
.next-step-bar{background:#e8f4fd;padding:15px 30px;margin-top:30px;border-top:2px solid #3498db;display:flex;justify-content:space-between;align-items:center;width:100%;flex-wrap:wrap;gap:10px;}
.next-step-bar a{background:#3498db;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;font-weight:bold;display:inline-block;}
.next-step-bar a:hover{background:#2980b9;}
</style></head><body>
<nav class="sidebar"><div class="sidebar-header">🌱 Microgreens ERP</div><ul class="nav-links">
<?php foreach($modules as $m): $active=(basename($_SERVER['PHP_SELF'])===$m['file'])?'active':''; ?>
<li><a href="<?php echo $m['file']; ?>" class="<?php echo $active; ?>"><?php echo $m['id']; ?> - <?php echo __($m['key']); ?></a></li>
<?php endforeach; ?>
</ul></nav>
<div class="main-content"><header class="top-header">
<h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
<div style="display:flex;align-items:center">
<?php if($wdStatus==='critical'): ?><a href="b28_watchdog_alerts.php" class="watchdog-indicator wd-critical">🔴 Kritiek</a>
<?php elseif($wdStatus==='warning'): ?><a href="b28_watchdog_alerts.php" class="watchdog-indicator wd-warning">⚠ Waarschuwing</a>
<?php else: ?><a href="b28_watchdog_alerts.php" class="watchdog-indicator wd-ok">✓ OK</a><?php endif; ?>
<div class="lang-switch"><a href="?lang=nl" class="<?php echo $languageCode==='nl'?'active':''; ?>">NL</a>|<a href="?lang=en" class="<?php echo $languageCode==='en'?'active':''; ?>">EN</a></div>
</div></header><main class="content-area">
