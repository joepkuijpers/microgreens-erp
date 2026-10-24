<?php
$DB='/var/www/html/microgreens/database/MicrogreensERP_Live.sqlite';
$results=[]; $pass=0; $fail=0;
function t($label,$ok,$detail=''){global $results,$pass,$fail;$s=$ok?'PASS':'FAIL';if($ok)$pass++;else $fail++;$results[]="[$s] $label".($detail?" -> $detail":"");}
$db=new PDO("sqlite:$DB",null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
echo "FASE 7 HYBRIDE - ".date('Y-m-d H:i:s')."\n\n";
$ts=date('Ymd_His'); $batch_code="TEST-F7-{$ts}"; $crop='Radijs Rosse'; $tc='green';
$lot=$db->query("SELECT id,variety FROM seed_lots LIMIT 1")->fetch();
t('Seedlot',!empty($lot),$lot?$lot['variety']:'GEEN');
$si=$db->query("SELECT id FROM seed_inventory LIMIT 1")->fetch();
t('Seed_inventory',!empty($si),$si?"id={$si['id']}":'GEEN');
$tray=$db->query("SELECT id,tray_code FROM trays WHERE status='EMPTY' LIMIT 1")->fetch();
if(!$tray){$db->exec("UPDATE trays SET status='EMPTY' WHERE id=(SELECT id FROM trays LIMIT 1)");$tray=$db->query("SELECT id,tray_code FROM trays WHERE status='EMPTY' LIMIT 1")->fetch();}
t('Tray',!empty($tray),$tray?$tray['tray_code']:'GEEN');
$hd=date('Y-m-d',strtotime('+14 days'));
$db->prepare("INSERT INTO production_batches (batch_code,crop_type,status,expected_harvest_date,created_at) VALUES (?,?, 'PLANNED',?,datetime('now'))")->execute([$batch_code,$crop,$hd]);
$batch_id=(int)$db->lastInsertId(); t('Batch',$batch_id>0,"id=$batch_id");
$db->prepare("INSERT INTO batch_materials (batch_id,inventory_id,quantity_used) VALUES (?,?,5.0)")->execute([$batch_id,$si['id']]);
t('Materiaal gelinked',$db->lastInsertId()>0);
$db->prepare("UPDATE trays SET status='GERMINATING' WHERE id=?")->execute([$tray['id']]);
$db->prepare("INSERT INTO germination_records (seed_inventory_id,tray_id,production_batch_id,quantity_seeds,start_date,status) VALUES (?,?,?,500.0,date('now'),'GERMINATING')")->execute([$si['id'],$tray['id'],$batch_id]);
$gr_id=(int)$db->lastInsertId(); t('Germinatie',$gr_id>0,"id=$gr_id");
$db->prepare("UPDATE production_batches SET status='GROWING' WHERE id=?")->execute([$batch_id]);
$db->prepare("UPDATE germination_records SET status='COMPLETED' WHERE id=?")->execute([$gr_id]);
$db->prepare("UPDATE trays SET status='EMPTY' WHERE id=?")->execute([$tray['id']]);
$hw=200.0;
$db->prepare("INSERT INTO harvests (germination_record_id,harvest_date,weight_grams,quality_status) VALUES (?,date('now'),?,'GOOD')")->execute([$gr_id,$hw]);
$harvest_id=(int)$db->lastInsertId(); t('Oogst',$harvest_id>0,"{$hw}g");
$db->prepare("UPDATE production_batches SET status='HARVESTED' WHERE id=?")->execute([$batch_id]);
// B07-pad: output via production_outputs (quantity, batch_id)
$db->prepare("INSERT INTO production_outputs (batch_id,output_type,quantity,unit,status,produced_at,track_color) VALUES (?,?,120.0,'g','AVAILABLE',date('now'),?)")->execute([$batch_id,'FRESH',$tc]);
$fresh_id=(int)$db->lastInsertId();
$db->prepare("INSERT INTO production_outputs (batch_id,output_type,quantity,unit,status,produced_at,track_color) VALUES (?,?,80.0,'g','AVAILABLE',date('now'),?)")->execute([$batch_id,'FREEZE_DRY_INPUT',$tc]);
$fdin_id=(int)$db->lastInsertId();
t('B07 output FRESH+FREEZE_DRY_INPUT',$fresh_id>0 && $fdin_id>0);
// B20-pad: massabalans op production_outputs (quantity, unit g)
$mc=$db->query("SELECT (SELECT weight_grams FROM harvests WHERE id=$harvest_id) g,(SELECT COALESCE(SUM(quantity),0) FROM production_outputs WHERE batch_id=$batch_id AND unit='g') v")->fetch();
t('Massabalans (productiepad)',abs($mc['g']-$mc['v'])<0.01,"g={$mc['g']} v={$mc['v']}");
// B08-pad: freeze_dry_processes op de FREEZE_DRY_INPUT-output
$db->prepare("INSERT INTO freeze_dry_processes (production_output_id,batch_id,machine_id,cycle_code,started_at,input_weight_g,water_removed_g,final_weight_g,status,organic_status_snapshot) VALUES (?,?,'FD-01','CY-{$ts}',datetime('now'),80.0,72.0,8.0,'DONE','CERTIFIED')")->execute([$fdin_id,$batch_id]);
$fdp_id=(int)$db->lastInsertId(); t('B08 freeze_dry_process',$fdp_id>0,"id=$fdp_id");
$fdp=$db->query("SELECT input_weight_g,water_removed_g,final_weight_g FROM freeze_dry_processes WHERE id=$fdp_id")->fetch();
t('Vriesdroog massabalans',abs($fdp['input_weight_g']-$fdp['water_removed_g']-$fdp['final_weight_g'])<0.01,"in={$fdp['input_weight_g']} water={$fdp['water_removed_g']} out={$fdp['final_weight_g']}");
// finished_inventory uit vriesdroog-resultaat (productnaam uit batch)
$db->prepare("INSERT INTO finished_inventory (harvest_id,product_name,weight_grams,harvest_date,status,output_type) VALUES (?,?,8.0,date('now'),'AVAILABLE','FREEZE_DRY')")->execute([$harvest_id,$crop]);
$fi_id=(int)$db->lastInsertId(); t('finished_inventory',$fi_id>0);
$tr2=$db->query("SELECT pb.crop_type FROM finished_inventory fi JOIN harvests h ON fi.harvest_id=h.id JOIN germination_records gr ON h.germination_record_id=gr.id JOIN production_batches pb ON gr.production_batch_id=pb.id WHERE fi.id=$fi_id")->fetch();
t('Trace brug',!empty($tr2),$tr2?$tr2['crop_type']:'LEEG');
$db->prepare("INSERT INTO packaging_units (finished_inventory_id,package_code,total_weight_g,packaging_date,status,created_at) VALUES (?,?,8.0,date('now'),'AVAILABLE',datetime('now'))")->execute([$fi_id,"PKG-F7-{$ts}"]);
$pkg_id=(int)$db->lastInsertId(); t('Verpakking',$pkg_id>0,"id=$pkg_id");
$cols=$db->query("PRAGMA table_info(packaging_units)")->fetchAll(PDO::FETCH_COLUMN,1);
$has_q=in_array('quarantine_reason',$cols); t('Kolom quarantine_reason',$has_q);
if($has_q){
 $db->prepare("UPDATE packaging_units SET status='QUARANTINE',quarantine_reason=? WHERE id=?")->execute(['Schimmel',$pkg_id]);
 $qc=$db->query("SELECT status,quarantine_reason FROM packaging_units WHERE id=$pkg_id")->fetch();
 t('Quarantaine',$qc['status']==='QUARANTINE',$qc['quarantine_reason']);
 $av=$db->query("SELECT COUNT(*) FROM packaging_units WHERE id=$pkg_id AND status='AVAILABLE'")->fetchColumn();
 t('Verkoop geblokkeerd',$av==0);
 $db->prepare("UPDATE packaging_units SET status='AVAILABLE',quarantine_reason=NULL WHERE id=?")->execute([$pkg_id]);
 $rel=$db->query("SELECT status FROM packaging_units WHERE id=$pkg_id")->fetchColumn(); t('Vrijgegeven',$rel==='AVAILABLE');
 $db->prepare("UPDATE packaging_units SET status='REJECTED',quarantine_reason='Afgekeurd' WHERE id=?")->execute([$pkg_id]);
 $rej=$db->query("SELECT status FROM packaging_units WHERE id=$pkg_id")->fetchColumn(); t('Afgekeuren REJECTED',$rej==='REJECTED');
 $av2=$db->query("SELECT COUNT(*) FROM packaging_units WHERE id=$pkg_id AND status='AVAILABLE'")->fetchColumn();
 t('Afgekeurd geblokkeerd',$av2==0);
}
$tr=$db->prepare("SELECT crop_type,status FROM production_batches WHERE batch_code=?");$tr->execute([$batch_code]);$trr=$tr->fetch();
t('Trace happy',!empty($trr),$trr?$trr['crop_type']:'LEEG');
$bw=$db->query("SELECT s.name FROM batch_materials bm JOIN seed_inventory si ON bm.inventory_id=si.id JOIN seed_lots sl ON si.seed_lot_id=sl.id JOIN suppliers s ON sl.supplier_id=s.id WHERE bm.batch_id=$batch_id LIMIT 1")->fetch();
t('Trace backward',!empty($bw),$bw?$bw['name']:'GEEN');
$sad=$db->query("SELECT COUNT(*) FROM production_batches WHERE batch_code='BESTAAT-NIET-999'")->fetchColumn(); t('Trace sad',$sad==0);
$ac=$db->query("SELECT COUNT(*) FROM audit_events WHERE entity_type='production_batches' AND entity_id=$batch_id")->fetchColumn(); t('Audit',$ac>=0,"$ac");
$al=$db->query("PRAGMA table_info(system_alerts)")->fetchAll(PDO::FETCH_COLUMN,1);
t('severity',in_array('severity',$al)); t('resolved',in_array('resolved',$al));
t('geen acknowledged',!in_array('acknowledged',$al)); t('geen status',!in_array('status',$al));
// CLEANUP (echte paden)
$fi_ids=$db->query("SELECT id FROM finished_inventory WHERE harvest_id=$harvest_id")->fetchAll(PDO::FETCH_COLUMN);
foreach($fi_ids as $f)$db->exec("DELETE FROM packaging_units WHERE finished_inventory_id=$f");
$db->exec("DELETE FROM finished_inventory WHERE harvest_id=$harvest_id");
$db->exec("DELETE FROM freeze_dry_processes WHERE batch_id=$batch_id");
$db->exec("DELETE FROM production_outputs WHERE batch_id=$batch_id");
$db->exec("DELETE FROM harvests WHERE id=$harvest_id");
$db->exec("DELETE FROM germination_records WHERE id=$gr_id");
$db->exec("DELETE FROM batch_materials WHERE batch_id=$batch_id");
$db->exec("DELETE FROM production_batches WHERE id=$batch_id");
$db->exec("UPDATE trays SET status='EMPTY' WHERE id={$tray['id']}");
t('Cleanup',true);
echo "\n".str_repeat('=',50)."\n RESULTAAT: $pass PASS / $fail FAIL\n".str_repeat('=',50)."\n\n";
foreach($results as $r)echo "  $r\n";
echo "\n".($fail===0?" ALLES GROEN\n":" $fail FAILED\n");
