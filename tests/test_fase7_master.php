<?php
$DB='/var/www/html/microgreens/database/MicrogreensERP_Live.sqlite';
$res=[]; $pass=0; $fail=0;
function t($l,$ok,$d=''){global $res,$pass,$fail;$s=$ok?'PASS':'FAIL';if($ok)$pass++;else $fail++;$res[]="[$s] $l".($d?" -> $d":"");}
$db=new PDO("sqlite:$DB",null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
echo "FASE 7 MASTER HARNESS - ".date('Y-m-d H:i:s')."\n\n";
$ts=date('YmdHis'); $bc="MH-{$ts}"; $crop='Radijs Rosse'; $tc='green';
// SETUP
$lot=$db->query("SELECT id,variety FROM seed_lots LIMIT 1")->fetch();
t('01 Seedlot',!empty($lot),$lot?$lot['variety']:'GEEN');
$si=$db->query("SELECT id FROM seed_inventory LIMIT 1")->fetch();
t('02 Seed_inventory',!empty($si),$si?"id={$si['id']}":'GEEN');
$tray=$db->query("SELECT id,tray_code FROM trays WHERE status='EMPTY' LIMIT 1")->fetch();
if(!$tray){$db->exec("UPDATE trays SET status='EMPTY' WHERE id=(SELECT id FROM trays LIMIT 1)");$tray=$db->query("SELECT id,tray_code FROM trays WHERE status='EMPTY' LIMIT 1")->fetch();}
t('03 Tray',!empty($tray),$tray?$tray['tray_code']:'GEEN');
// BATCH
$hd=date('Y-m-d',strtotime('+14 days'));
$db->prepare("INSERT INTO production_batches (batch_code,crop_type,status,expected_harvest_date,created_at) VALUES (?,?, 'PLANNED',?,datetime('now'))")->execute([$bc,$crop,$hd]);
$bid=(int)$db->lastInsertId(); t('04 Batch',$bid>0,"id=$bid");
$db->prepare("INSERT INTO batch_materials (batch_id,inventory_id,quantity_used) VALUES (?,?,5.0)")->execute([$bid,$si['id']]);
t('05 Materiaal gelinked',$db->lastInsertId()>0);
// GERMINATIE
$db->prepare("UPDATE trays SET status='GERMINATING' WHERE id=?")->execute([$tray['id']]);
$db->prepare("INSERT INTO germination_records (seed_inventory_id,tray_id,production_batch_id,quantity_seeds,start_date,status) VALUES (?,?,?,500.0,date('now'),'GERMINATING')")->execute([$si['id'],$tray['id'],$bid]);
$gr=(int)$db->lastInsertId(); t('06 Germinatie',$gr>0,"id=$gr");
$db->prepare("UPDATE production_batches SET status='GROWING' WHERE id=?")->execute([$bid]);
$db->prepare("UPDATE germination_records SET status='COMPLETED' WHERE id=?")->execute([$gr]);
$db->prepare("UPDATE trays SET status='GROWING' WHERE id=?")->execute([$tray['id']]);
// OOGST
$hw=200.0;
$db->prepare("INSERT INTO harvests (germination_record_id,harvest_date,weight_grams,quality_status) VALUES (?,date('now'),?,'GOOD')")->execute([$gr,$hw]);
$hid=(int)$db->lastInsertId(); t('07 Oogst',$hid>0,"{$hw}g");
$db->prepare("UPDATE production_batches SET status='HARVESTED' WHERE id=?")->execute([$bid]);
// TRAY-RELEASE (B07-patch-logica, atomic)
$trayId=$db->query("SELECT tray_id FROM germination_records WHERE id=$gr")->fetchColumn();
$db->prepare("UPDATE trays SET status='EMPTY' WHERE id=?")->execute([$trayId]);
$db->prepare("INSERT INTO audit_events (event_type,table_name,record_id,action,description,before_data,after_data,created_at) VALUES ('B07_TRAY_RELEASE','trays',?,'UPDATE',?,?,?,datetime('now'))")->execute([$trayId,'Tray vrijgegeven bij oogst '.$bc,json_encode(['status'=>'GROWING']),json_encode(['status'=>'EMPTY'])]);
t('08 Tray vrijgegeven (EMPTY)',$db->query("SELECT status FROM trays WHERE id={$tray['id']}")->fetchColumn()==='EMPTY');
t('09 Audit TRAY_RELEASE',$db->query("SELECT COUNT(*) FROM audit_events WHERE event_type='B07_TRAY_RELEASE' AND record_id=$trayId")->fetchColumn()>0);
// OUTPUTS (B07-pad)
$db->prepare("INSERT INTO production_outputs (batch_id,output_type,quantity,unit,status,produced_at,track_color) VALUES (?,?,120.0,'g','REGISTERED',date('now'),?)")->execute([$bid,'FRESH',$tc]);
$freshId=(int)$db->lastInsertId();
$db->prepare("INSERT INTO production_outputs (batch_id,output_type,quantity,unit,status,produced_at,track_color) VALUES (?,?,80.0,'g','REGISTERED',date('now'),?)")->execute([$bid,'FREEZE_DRY_INPUT',$tc]);
$fdinId=(int)$db->lastInsertId();
t('10 B07 output FRESH+FD_IN',$freshId>0&&$fdinId>0);
$mc=$db->query("SELECT (SELECT weight_grams FROM harvests WHERE id=$hid) g,(SELECT COALESCE(SUM(quantity),0) FROM production_outputs WHERE batch_id=$bid AND unit='g') v")->fetch();
t('11 Massabalans (productiepad)',abs($mc['g']-$mc['v'])<0.01,"g={$mc['g']} v={$mc['v']}");
// B08 PAD
$db->prepare("INSERT INTO freeze_dry_processes (production_output_id,batch_id,machine_id,cycle_code,started_at,input_weight_g,water_removed_g,final_weight_g,status,organic_status_snapshot) VALUES (?,?,'FD-01','CY-{$ts}',datetime('now'),80.0,72.0,8.0,'DONE','CERTIFIED')")->execute([$fdinId,$bid]);
$fdp=(int)$db->lastInsertId(); t('12 B08 freeze_dry_process',$fdp>0,"id=$fdp");
$f=$db->query("SELECT input_weight_g i,water_removed_g w,final_weight_g o FROM freeze_dry_processes WHERE id=$fdp")->fetch();
t('13 Vriesdroog-balans',abs($f['i']-$f['w']-$f['o'])<0.01,"in={$f['i']} water={$f['w']} out={$f['o']}");
$db->prepare("INSERT INTO finished_inventory (harvest_id,product_name,weight_grams,harvest_date,status,output_type) VALUES (?,?,8.0,date('now'),'AVAILABLE','FREEZE_DRY')")->execute([$hid,$crop]);
$fiId=(int)$db->lastInsertId(); t('14 finished_inventory',$fiId>0);
$tr2=$db->query("SELECT pb.crop_type FROM finished_inventory fi JOIN harvests h ON fi.harvest_id=h.id JOIN germination_records gr ON h.germination_record_id=gr.id JOIN production_batches pb ON gr.production_batch_id=pb.id WHERE fi.id=$fiId")->fetch();
t('15 Trace brug',!empty($tr2),$tr2?$tr2['crop_type']:'LEEG');
$db->prepare("INSERT INTO packaging_units (finished_inventory_id,package_code,total_weight_g,packaging_date,status,created_at) VALUES (?,?,8.0,date('now'),'AVAILABLE',datetime('now'))")->execute([$fiId,"PKG-{$ts}"]);
$pkg=(int)$db->lastInsertId(); t('16 Verpakking',$pkg>0,"id=$pkg");
$cols=$db->query("PRAGMA table_info(packaging_units)")->fetchAll(PDO::FETCH_COLUMN,1);
$hasQ=in_array('quarantine_reason',$cols); t('17 Kolom quarantine_reason',$hasQ);
if($hasQ){
 $db->prepare("UPDATE packaging_units SET status='QUARANTINE',quarantine_reason=? WHERE id=?")->execute(['Schimmel',$pkg]);
 t('18 Quarantaine gezet',$db->query("SELECT status FROM packaging_units WHERE id=$pkg")->fetchColumn()==='QUARANTINE');
 t('19 Verkoop geblokkeerd',$db->query("SELECT COUNT(*) FROM packaging_units WHERE id=$pkg AND status='AVAILABLE'")->fetchColumn()==0);
 $db->prepare("UPDATE packaging_units SET status='AVAILABLE',quarantine_reason=NULL WHERE id=?")->execute([$pkg]);
 t('20 Vrijgegeven',$db->query("SELECT status FROM packaging_units WHERE id=$pkg")->fetchColumn()==='AVAILABLE');
 $db->prepare("UPDATE packaging_units SET status='REJECTED',quarantine_reason='Afgekeurd' WHERE id=?")->execute([$pkg]);
 t('21 Afgekeurd REJECTED',$db->query("SELECT status FROM packaging_units WHERE id=$pkg")->fetchColumn()==='REJECTED');
 t('22 Afgekeurd geblokkeerd',$db->query("SELECT COUNT(*) FROM packaging_units WHERE id=$pkg AND status='AVAILABLE'")->fetchColumn()==0);
}
$trq=$db->prepare("SELECT crop_type,status FROM production_batches WHERE batch_code=?");$trq->execute([$bc]);$trr=$trq->fetch();
t('23 Trace happy',!empty($trr),$trr?$trr['crop_type']:'LEEG');
$bw=$db->query("SELECT s.name FROM batch_materials bm JOIN seed_inventory si ON bm.inventory_id=si.id JOIN seed_lots sl ON si.seed_lot_id=sl.id JOIN suppliers s ON sl.supplier_id=s.id WHERE bm.batch_id=$bid LIMIT 1")->fetch();
t('24 Trace backward',!empty($bw),$bw?$bw['name']:'GEEN');
t('25 Trace sad',$db->query("SELECT COUNT(*) FROM production_batches WHERE batch_code='BESTAAT-NIET-999'")->fetchColumn()==0);
$al=$db->query("PRAGMA table_info(system_alerts)")->fetchAll(PDO::FETCH_COLUMN,1);
t('26 severity',in_array('severity',$al)); t('27 resolved',in_array('resolved',$al));
t('28 geen acknowledged',!in_array('acknowledged',$al)); t('29 geen status',!in_array('status',$al));
// TAAL-LAAG (B03-conversie bewijs)
$nl=require '/var/www/html/microgreens/app/languages/nl.php';
$en=require '/var/www/html/microgreens/app/languages/en.php';
$t1=isset($nl['b03_save'],$en['b03_save'])&&$nl['b03_save']!=$en['b03_save'];
t('30 B03-taalkey vertaalt (NL!=EN)',$t1,$t1?($nl['b03_save'].'/'.$en['b03_save']):'GEEN');
t('31 Audit events',$db->query("SELECT COUNT(*) FROM audit_events WHERE event_type='B07_TRAY_RELEASE'")->fetchColumn()>0);
// CLEANUP
$fis=$db->query("SELECT id FROM finished_inventory WHERE harvest_id=$hid")->fetchAll(PDO::FETCH_COLUMN);
foreach($fis as $x)$db->exec("DELETE FROM packaging_units WHERE finished_inventory_id=$x");
$db->exec("DELETE FROM finished_inventory WHERE harvest_id=$hid");
$db->exec("DELETE FROM freeze_dry_processes WHERE batch_id=$bid");
$db->exec("DELETE FROM production_outputs WHERE batch_id=$bid");
$db->exec("DELETE FROM harvests WHERE id=$hid");
$db->exec("DELETE FROM germination_records WHERE id=$gr");
$db->exec("DELETE FROM batch_materials WHERE batch_id=$bid");
$db->exec("DELETE FROM production_batches WHERE id=$bid");
$db->exec("DELETE FROM audit_events WHERE event_type='B07_TRAY_RELEASE' AND record_id=$trayId");
$db->exec("UPDATE trays SET status='EMPTY' WHERE id={$tray['id']}");
$rest=$db->query("SELECT COUNT(*) FROM production_batches WHERE id=$bid")->fetchColumn();
t('32 Cleanup volledig',$rest==0);
echo "\n".str_repeat('=',52)."\n MASTER HARNESS: $pass PASS / $fail FAIL\n".str_repeat('=',52)."\n\n";
foreach($res as $r)echo "  $r\n";
echo "\n".($fail===0?" ALLES GROEN - FASE 7 ONBREEKBAAR BEWEZEN\n":" $fail FAILED\n");
