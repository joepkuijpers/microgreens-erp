<?php
$werk   = "C:/Users/joepk/Downloads/microgreens-erp/database/MicrogreensERP_Live.sqlite";
$lokaal = "C:/Users/joepk/Downloads/microgreens-erp/database/ERP-backup";
$kdrive = "C:/Users/joepk/kDrive Joep Kuijpers/GoeiGroen/ERP_backup";
$gpg    = "C:\\Program Files\\GnuPG\\bin\\gpg.exe";
$bewaarL = 2;   // lokaal bewaren: laatste 48 = 24u bij 30-min ritme
$passFile = __DIR__ . "/backup_pass.txt";
$pass = is_file($passFile) ? trim(file_get_contents($passFile)) : "";
if (!$pass) { echo "ERP_BACKUP_PASS ontbreekt\n"; exit(1); }

@mkdir($lokaal, 0777, true);
@mkdir($kdrive, 0777, true);

$st   = date("Ymd_His");
$snap = "$lokaal/snap_$st.sqlite";
$gnaam = "ERP_$st.sqlite.gpg";

$pdo = new PDO("sqlite:$werk");
$pdo->exec("VACUUM INTO " . $pdo->quote($snap));

$cmd = "\"$gpg\" --batch --yes --pinentry-mode loopback --passphrase-fd 0 --symmetric --cipher-algo AES256 --output \"$lokaal/$gnaam\" \"$snap\"";
$p = proc_open($cmd, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
fwrite($pipes[0], $pass); fclose($pipes[0]);
$err = stream_get_contents($pipes[2]); fclose($pipes[2]);
stream_get_contents($pipes[1]); fclose($pipes[1]);
$exit = proc_close($p);
unlink($snap);
if ($exit) { echo "GPG-fout: $err\n"; exit(1); }
// Lokaal opruimen: bewaar laatste $bewaarL (kDrive houdt ALLES)
$z = glob("$lokaal/ERP_*.sqlite.gpg") ?: [];
usort($z, fn($a,$b) => filemtime($b) <=> filemtime($a));
foreach (array_slice($z, $bewaarL) as $oud) @unlink($oud);
if (copy("$lokaal/$gnaam", "$kdrive/$gnaam")) {
    echo "OK: $gnaam versleuteld -> kDrive + lokaal\n";
} else {
    echo "kDrive-kopie mislukt\n"; exit(1);
}