<?php
require_once __DIR__ . '/app/includes/header.php';
$err=''; $ok=false;
$lots = $db->query("SELECT id, variety, organic_status FROM seed_lots ORDER BY variety ASC")->fetchAll(PDO::FETCH_ASSOC);
$protos = $db->query("SELECT id, crop_type, grow_days, temp_c, water_ml, substrate_type, tray_size, seed_weight_g FROM germination_protocols ORDER BY crop_type ASC")->fetchAll(PDO::FETCH_ASSOC);
$batches = $db->query("SELECT id, batch_code FROM production_batches ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $lot=(int)($_POST['seed_lot_id']??0); $proto=(int)($_POST['protocol_id']??0)?:null;
    $batch=(int)($_POST['batch_id']??0)?:null; $date=$_POST['test_date']?:date('Y-m-d');
    $sample=(int)($_POST['sample_size']??0); $sprout=(int)($_POST['sprouted_count']??0);
    if ($lot<=0 || $sample<=0 || $sprout<0 || $sprout>$sample) { $err=__('germ_err'); }
    else {
        $rate = $sample>0 ? round(($sprout/$sample)*100,1) : null;
        $st=$db->prepare("INSERT INTO germination_tests (seed_lot_id,protocol_id,batch_id,test_date,sample_size,sprouted_count,germination_rate,notes,operator) VALUES (?,?,?,?,?,?,?,?,?)");
        $st->execute([$lot,$proto,$batch,$date,$sample,$sprout,$rate,trim($_POST['notes']??''),trim($_POST['operator']??'')]);
        $ok=true;
    }
}
?>
<div style="max-width:640px;margin:24px auto;padding:0 16px;">
  <a href="b01_batch.php" style="text-decoration:none;">&larr; <?= __('germ_back') ?></a>
  <h2 style="margin:16px 0;">🌱 <?= __('germ_title') ?></h2>
  <?php if($err): ?><div style="background:#ffe6e6;padding:12px;border-radius:8px;margin-bottom:12px;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if($ok): ?><div style="background:#e6f7e6;padding:12px;border-radius:8px;margin-bottom:12px;"><?= __('germ_saved') ?> <a href="germination_test.php"><?= __('germ_register') ?></a></div><?php endif; ?>
  <form method="post" style="display:flex;flex-direction:column;gap:12px;">
    <label><?= __('germ_lot') ?>*<select name="seed_lot_id" required style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"><option value="0"><?= __('germ_none') ?></option><?php foreach($lots as $l): ?><option value="<?= (int)$l['id'] ?>"><?= htmlspecialchars($l['variety']) ?> (<?= htmlspecialchars($l['organic_status']) ?>)</option><?php endforeach; ?></select></label>
    <label><?= __('germ_protocol') ?><select name="protocol_id" id="protoSel" onchange="showProto()" style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"><option value="0"><?= __('germ_none') ?></option><?php foreach($protos as $p): ?><option value="<?= (int)$p['id'] ?>" data-d="<?= (int)$p['grow_days'] ?>" data-t="<?= htmlspecialchars((string)$p['temp_c']) ?>" data-w="<?= htmlspecialchars((string)$p['water_ml']) ?>" data-s="<?= htmlspecialchars((string)$p['substrate_type']) ?>" data-y="<?= htmlspecialchars((string)$p['tray_size']) ?>" data-g="<?= htmlspecialchars((string)$p['seed_weight_g']) ?>"><?= htmlspecialchars($p['crop_type']) ?></option><?php endforeach; ?></select></label>
    <div id="protoInfo" style="display:none;background:#eef6ff;padding:10px;border-radius:8px;font-size:14px;"></div>
    <label><?= __('germ_batch') ?><select name="batch_id" style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"><option value="0"><?= __('germ_none') ?></option><?php foreach($batches as $b): ?><option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['batch_code']) ?></option><?php endforeach; ?></select></label>
    <div style="display:flex;gap:12px;">
      <label style="flex:1;"><?= __('germ_date') ?><input type="date" name="test_date" value="<?= date('Y-m-d') ?>" style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"></label>
    </div>
    <div style="display:flex;gap:12px;">
      <label style="flex:1;"><?= __('germ_sample') ?>*<input type="number" name="sample_size" id="sample" oninput="calc()" required style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"></label>
      <label style="flex:1;"><?= __('germ_sprouted') ?>*<input type="number" name="sprouted_count" id="sprout" oninput="calc()" required style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"></label>
      <label style="flex:1;"><?= __('germ_rate') ?><input id="rate" readonly style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;background:#f4f4f4;font-weight:bold;"></label>
    </div>
    <label><?= __('germ_notes') ?><textarea name="notes" rows="2" style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"></textarea></label>
    <label><?= __('germ_operator') ?><input name="operator" style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;"></label>
    <button type="submit" style="padding:14px;font-size:18px;background:#2e7d32;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:bold;"><?= __('germ_save') ?></button>
  </form>
</div>
<script>
function calc(){var s=parseInt(document.getElementById('sample').value)||0;var p=parseInt(document.getElementById('sprout').value)||0;document.getElementById('rate').value=(s>0&&p<=s)?((p/s*100).toFixed(1)+'%'):'';}
function showProto(){var o=document.getElementById('protoSel').selectedOptions[0];var b=document.getElementById('protoInfo');if(o.value=='0'){b.style.display='none';return;}b.style.display='block';b.innerHTML='📋 <?= __('germ_protocol') ?>: '+ (o.dataset.d||'-')+' dagen · '+ (o.dataset.t||'-')+'°C · '+ (o.dataset.w||'-')+' ml · substraat '+(o.dataset.s||'-')+' · bak '+(o.dataset.y||'-')+' · zaadgewicht '+(o.dataset.g||'-')+' g';}
</script>
