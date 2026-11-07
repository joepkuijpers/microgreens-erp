<?php
$werk   = "C:/Users/joepk/Downloads/microgreens-erp/database/MicrogreensERP_Live.sqlite";
$lokaal = "C:/Users/joepk/Downloads/microgreens-erp/database/ERP-backup";
$kdrive = "C:/Users/joepk/kDrive Joep Kuijpers/GoeiGroen/ERP_backup";
@mkdir($lokaal, 0777, true);
@mkdir($kdrive, 0777, true);

$st = date("Ymd_His");
$snap = "$lokaal/snap_$st.sqlite";
(new PDO("sqlite:$werk"))->exec("VACUUM INTO '$snap'");

$zipNaam = "ERP_$st.zip";
$zipTijdelijk = "$lokaal/$zipNaam";
$zip = new ZipArchive();
if ($zip->open($zipTijdelijk, ZipArchive::CREATE) === true) {
    $zip->addFile($snap, "snap_$st.sqlite");
    $zip->close();
    copy($zipTijdelijk, "$kdrive/$zipNaam");
    echo "OK: $zipNaam -> kDrive (en lokaal bewaard)\n";
}
unlink($snap);