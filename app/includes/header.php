<?php
// GEEN CACHE: toon altijd de actuele pagina (belangrijk voor taalwissel)
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// FOUTEN ONDERTKUIPT (productie)
ini_set('display_errors', 0);
error_reporting(0);

// SESSIE STARTEN
if (session_status() === PHP_SESSION_NONE) {
 require_once __DIR__ . '/no_cache.php';
 session_start();
require_once __DIR__ . '/ui_prefs.php';
if (!isset($_SESSION['emoji_ui'])) $_SESSION['emoji_ui'] = 0;
}

// TAAL WISSEL LOGICA (moet allereerst, vóór elke output)
if (isset($_GET['lang']) && in_array($_GET['lang'], ['nl', 'en', 'de', 'fr', 'es', 'it'], true)) {
 $_SESSION['erp_language'] = $_GET['lang'];
 // behoud alle parameters BEHALVE lang (dus mode=planned blijft), en lang weg (geen lus)
 $q = $_GET;
 unset($q['lang']);
 $qs = http_build_query($q);
 $cleanUrl = strtok($_SERVER["REQUEST_URI"], '?') . ($qs !== '' ? '?' . $qs : '');
 header("Location: " . $cleanUrl);
 exit;
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
 $stmt = $db->prepare("SELECT severity, message FROM system_alerts WHERE resolved = 0 ORDER BY created_at DESC LIMIT 1");
 $stmt->execute();
 $alert = $stmt->fetch();
 if ($alert) {
 $wdStatus = $alert['status'];
 $wdMsg = htmlspecialchars($alert['message']);
 }
} catch (Exception $e) {}

// MODULES
$wf_steps = ['b01_batch.php'=>1,'b02_germination.php'=>2,'b07_harvest.php'=>3,'b09_packaging.php'=>4,'b09_sale.php'=>5,'b21_logistics.php'=>6,'b25_customer_feedback.php'=>7];
$modules = [
['id'=>'DASH','file'=>'index.php','key'=>'dashboard_home'],
['id'=>'B01','file'=>'b01_batch.php','key'=>'module_b01'],
['id'=>'B02','file'=>'b02_germination.php','key'=>'module_b02'],
['id'=>'B03','file'=>'b03_growth_chamber.php','key'=>'module_b03'],
['id'=>'B04','file'=>'b04_lighting.php','key'=>'module_b04'],
['id'=>'B06','file'=>'b06_climate_monitoring.php','key'=>'module_b06'],
['id'=>'B07','file'=>'b07_harvest.php','key'=>'module_b07'],
['id'=>'B08','file'=>'b08_freeze_dry.php','key'=>'module_b08'],
['id'=>'B09','file'=>'b09_sale.php','key'=>'module_b09'],
['id'=>'B10','file'=>'b10_sensor_sync.php','key'=>'module_b10'],
['id'=>'B11','file'=>'b09_packaging.php','key'=>'module_b11'],
['id'=>'B12','file'=>'b12_cleaning.php','key'=>'module_b12'],
['id'=>'B13','file'=>'b13_energy.php','key'=>'module_b13'],
['id'=>'B14','file'=>'b14_water_irrigation.php','key'=>'module_b14'],
['id'=>'B15','file'=>'b15_substrate.php','key'=>'module_b15'],
['id'=>'B16','file'=>'b16_task_scheduler.php','key'=>'module_b16'],
['id'=>'B17','file'=>'b17_staff.php','key'=>'module_b17'],
['id'=>'B18','file'=>'b18_suppliers.php','key'=>'module_b18'],
['id'=>'B19','file'=>'b19_financials.php','key'=>'module_b19'],
['id'=>'B20','file'=>'b20_waste.php','key'=>'module_b20'],
['id'=>'B21','file'=>'b21_logistics.php','key'=>'module_b21'],
['id'=>'B22','file'=>'b22_maintenance.php','key'=>'module_b22'],
['id'=>'B23','file'=>'b23_sops.php','key'=>'module_b23'],
['id'=>'B24','file'=>'b24_sales_analytics.php','key'=>'module_b24'],
['id'=>'B25','file'=>'b25_customer_feedback.php','key'=>'module_b25'],
['id'=>'B26','file'=>'b26_system_settings.php','key'=>'module_b26'],
['id'=>'B27','file'=>'b27_api_integrations.php','key'=>'module_b27'],
['id'=>'B28','file'=>'b28_watchdog.php','key'=>'module_b28'],
['id'=>'B29','file'=>'b29_quarantine.php','key'=>'module_b29'],
];
$group_map=['index.php'=>'home','b01_batch.php'=>'prod','b02_germination.php'=>'prod','b07_harvest.php'=>'prod','b08_freeze_dry.php'=>'prod','b09_packaging.php'=>'prod','b20_waste.php'=>'prod','b29_quarantine.php'=>'prod','b03_growth_chamber.php'=>'teelt','b06_climate_monitoring.php'=>'teelt','b04_lighting.php'=>'teelt','b10_sensor_sync.php'=>'teelt','b14_water_irrigation.php'=>'teelt','b15_substrate.php'=>'teelt','b12_cleaning.php'=>'kwal','b22_maintenance.php'=>'kwal','b23_sops.php'=>'kwal','b09_sale.php'=>'verkoop','b19_financials.php'=>'verkoop','b24_sales_analytics.php'=>'verkoop','b25_customer_feedback.php'=>'verkoop','b21_logistics.php'=>'log','b16_task_scheduler.php'=>'log','b17_staff.php'=>'log','b13_energy.php'=>'bedrijf','b18_suppliers.php'=>'bedrijf','b26_system_settings.php'=>'bedrijf','b27_api_integrations.php'=>'bedrijf','b28_watchdog.php'=>'watch'];
$group_meta=['prod'=>[emoji('📦 '),'Productie / Production'],'teelt'=>[emoji('🌱 '),'Teelt & Klimaat / Cultivation'],'kwal'=>[emoji('🧼 '),'Kwaliteit & Hygiene / Quality'],'verkoop'=>[emoji('💰 '),'Verkoop & Financien / Sales'],'log'=>[emoji('🚚 '),'Logistiek & Planning / Logistics'],'bedrijf'=>[emoji('🏢 '),'Bedrijf & Systeem / System'],'watch'=>[emoji('🔔 '),'Monitoring / Watchdog']];
$pageTitle = $pageTitle ?? 'ERP';
?> <!DOCTYPE html><html lang="<?php echo htmlspecialchars($languageCode); ?>"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?php echo htmlspecialchars($pageTitle); ?></title> <style>
*{box-sizing:border-box;}
body{font-family:sans-serif;margin:0;display:flex;height:100vh;background:#f4f4f9}
.sidebar{width:250px;background:#2c3e50;color:#ecf0f1;overflow-y:auto;flex-shrink:0}
.sidebar-header{padding:20px;background:#1a252f;text-align:center;font-weight:bold;border-bottom:1px solid #34495e}
.nav-links{list-style:none;padding:0;margin:0}
.nav-links li{border-bottom:1px solid #34495e}
.nav-links a{display:block;padding:12px 20px;color:#bdc3c7;text-decoration:none}
.nav-links a:hover,.nav-links a.active{background:#34495e;color:#fff;border-left:4px solid #27ae60}
.nav-group{border-bottom:1px solid #34495e}.nav-group summary{display:block;padding:12px 20px;color:#ecf0f1;cursor:pointer;font-weight:bold;list-style:none}.nav-group summary::-webkit-details-marker{display:none}.nav-group summary:hover{background:#34495e}.grp-n{opacity:.55;font-size:11px;font-weight:normal}.nav-sub{list-style:none;padding:0;margin:0;background:#26333f}.nav-sub a{padding:9px 20px 9px 34px;font-size:13px}
.main-content{flex-grow:1;display:flex;flex-direction:column;overflow:hidden}
.top-header{background:#fff;padding:15px 30px;box-shadow:0 2px 5px rgba(0,0,0,0.05);display:flex;justify-content:space-between;align-items:center}
.page-title{font-size:1.5rem;color:#2c3e50;margin:0}
.watchdog-indicator{padding:8px 15px;border-radius:20px;font-weight:bold;text-decoration:none;margin-left:20px}
.wd-ok{background:#d4edda;color:#155724}.wd-warning{background:#fff3cd;color:#856404}.wd-critical{background:#f8d7da;color:#721c24}
.content-area{padding:30px;overflow-y:auto;flex-grow:1}
.lang-switch a{margin:0 5px;text-decoration:none;color:#2980b9;font-weight:bold}
.lang-switch a.active{color:#2c3e50;text-decoration:underline;}
.next-step-bar{background:#e8f4fd;padding:15px 30px;margin-top:30px;border-top:2px solid #3498db;display:flex;justify-content:space-between;align-items:center;width:100%;flex-wrap:wrap;gap:10px;}
.sidebar{resize:horizontal;min-width:130px;max-width:420px;overflow:auto}
.nav-links a{white-space:normal;word-break:break-word;line-height:1.3}
.sidebar-resize-handle{position:absolute;top:0;right:0;width:6px;height:100%;cursor:col-resize;z-index:20}
@media(max-width:768px){body{flex-direction:column}.sidebar{width:100%;max-width:100%;resize:none;border-bottom:2px solid #34495e}.nav-links{display:flex;overflow-x:auto;flex-wrap:nowrap}.nav-links li{border-bottom:none;border-right:1px solid #34495e;flex-shrink:0}.nav-links a{white-space:nowrap;padding:10px 14px}.content-area{padding:14px}.top-header{padding:12px 16px;flex-wrap:wrap;gap:8px}}
@media(max-width:480px){.content-area{padding:10px}}
table{display:block;overflow-x:auto;max-width:100%}
input,select,textarea,button{max-width:100%}

.next-step-bar a{background:#3498db;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;font-weight:bold;display:inline-block;}
.next-step-bar a:hover{background:#2980b9;}
input[type="date"]{cursor:pointer;}
input[type="date"]::-webkit-calendar-picker-indicator{opacity:.6;}
@media(max-width:1023px){.sidebar{position:fixed;top:0;left:0;height:100vh;width:280px;transform:translateX(-100%);transition:transform .25s ease;z-index:1000;resize:none;max-width:85vw}.nav-toggle{display:inline-flex!important}.sidebar-resize-handle{display:none}body.nav-open .sidebar{transform:translateX(0)}body.nav-open::after{content:"";position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999}}.nav-toggle{display:none;align-items:center;justify-content:center;width:42px;height:42px;border:none;background:#2c3e50;color:#fff;font-size:22px;border-radius:8px;cursor:pointer;margin-right:12px}.sidebar-header{display:flex;flex-direction:column;align-items:center;gap:8px}.brand-logo{width:90%;max-width:160px;height:auto;background:#fff;border-radius:10px;padding:8px}</style> <script>
(function(){
 function bind(el){
 if(el.dataset.dpBound) return;
 el.dataset.dpBound="1";
 el.addEventListener("click", function(){
 if(typeof this.showPicker==="function"){ try{ this.showPicker(); }catch(e){} }
 });
 }
 function bindAll(){ document.querySelectorAll('input[type="date"]').forEach(bind); }
 if(document.readyState==="loading"){ document.addEventListener("DOMContentLoaded", bindAll); }
 else { bindAll(); }
})();
</script> <?php require_once __DIR__ . "/registration_timer.php"; ?>
<style>
.flash { padding: 12px 16px; margin: 12px 0; border-radius: 6px; border-left: 4px solid; font-weight: 600; }
.flash-success { background: #e7f6ec; color: #1a7f37; border-color: #1a7f37; }
.flash-warning { background: #fff4e5; color: #b45309; border-color: #b45309; }
.flash-error { background: #fdecec; color: #b42318; border-color: #b42318; }
.flash-info { background: #eaf2fb; color: #1d4ed8; border-color: #1d4ed8; }
@media(max-width:1023px){.sidebar{position:fixed;top:0;left:0;height:100vh;width:280px;transform:translateX(-100%);transition:transform .25s ease;z-index:1000;resize:none;max-width:85vw}.nav-toggle{display:inline-flex!important}.sidebar-resize-handle{display:none}body.nav-open .sidebar{transform:translateX(0)}body.nav-open::after{content:"";position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999}}.nav-toggle{display:none;align-items:center;justify-content:center;width:42px;height:42px;border:none;background:#2c3e50;color:#fff;font-size:22px;border-radius:8px;cursor:pointer;margin-right:12px}.sidebar-header{display:flex;flex-direction:column;align-items:center;gap:8px}.brand-logo{width:90%;max-width:160px;height:auto;background:#fff;border-radius:10px;padding:8px}</style>
<?php require_once __DIR__ . "/active_time_heartbeat.php"; ?></head><body> <nav class="sidebar"><div class="sidebar-header"> Microgreens ERP</div><div class="sidebar-resize-handle" id="sidebarHandle" title="Sleep om breedte te wijzigen"></div><ul class="nav-links">
<?php $cur=basename($_SERVER["PHP_SELF"]); $byGroup=[]; foreach($modules as $m){$g=$group_map[$m["file"]]??"other"; $byGroup[$g][]=$m;} ?>
<?php if(isset($byGroup["home"])): foreach($byGroup["home"] as $m): ?>
<li><a href="<?php echo $m["file"]; ?>" class="<?php echo $cur===$m["file"]?"active":""; ?>"><?php echo __($m["key"]); ?></a></li>
<?php endforeach; endif; ?>
<?php foreach($group_meta as $gid=>$gm): if(empty($byGroup[$gid])) continue; $open=in_array($cur,array_column($byGroup[$gid],"file"))?"open":""; ?>
<li class="nav-group"><details <?php echo $open; ?>><summary><span><?php echo $gm[0]; ?></span> <?php echo $gm[1]; ?> <span class="grp-n">(<?php echo count($byGroup[$gid]); ?>)</span></summary><ul class="nav-sub">
<?php foreach($byGroup[$gid] as $m): ?>
<li><a href="<?php echo $m["file"]; ?>" class="<?php echo $cur===$m["file"]?"active":""; ?>"><?php echo __($m["key"]); ?><?php if(isset($wf_steps[$m["file"]])) echo ' <span style="opacity:.6;font-size:11px">'.(int)$wf_steps[$m["file"]].'/7</span>'; ?></a></li>
<?php endforeach; ?>
</ul></details></li><?php endforeach; ?>
</ul></nav> <div class="main-content"><header class="top-header"> <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1> <div style="display:flex;align-items:center"> <?php if($wdStatus==='critical'): ?><a href="b28_watchdog.php" class="watchdog-indicator wd-critical"> Kritiek</a> <?php elseif($wdStatus==='warning'): ?><a href="b28_watchdog.php" class="watchdog-indicator wd-warning"> Waarschuwing</a> <?php else: ?><a href="b28_watchdog.php" class="watchdog-indicator wd-ok"> OK</a><?php endif; ?> <div class="lang-switch"><a href="?lang=nl" class="<?php echo $languageCode==='nl'?'active':''; ?>">NL</a>|<a href="?lang=en" class="<?php echo $languageCode==='en'?'active':''; ?>">EN</a></div> </div></header><main class="content-area">