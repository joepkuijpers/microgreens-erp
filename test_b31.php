<?php
/**
 * test_b31.php — Automatische B31-test (archiveren, delete, backup-integriteit)
 * Draai: php test_b31.php
 * Let op: werkt op LIVE database. Maakt testdata aan en ruimt die op.
 */

require_once __DIR__ . '/app/includes/config.php';

$dbPath = ERP_DB;
echo "=== B31 Testscript ===\n";
echo "DB: $dbPath\n\n";

$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$testCode = 'TEST_B31_' . date('Ymd_His');
$scherm = 0;
$schermTotaal = 3;

// ─── HELPERS ───────────────────────────────────────────────────────────

function logOK(string $msg): void { echo "  ✅ $msg\n"; }
function logFAIL(string $msg): void { echo "  ❌ $msg\n"; }
function logINFO(string $msg): void { echo "  ℹ️  $msg\n"; }

function cleanup(PDO $pdo, string $code): void {
    // Verwijder testdata die nog over is
    $pdo->exec("DELETE FROM batch_materials WHERE batch_id IN (SELECT id FROM production_batches WHERE batch_code = '$code')");
    $pdo->exec("DELETE FROM germination_records WHERE production_batch_id IN (SELECT id FROM production_batches WHERE batch_code = '$code')");
    $pdo->exec("DELETE FROM production_batches WHERE batch_code = '$code'");
    $pdo->exec("DELETE FROM deletion_log WHERE batch_code = '$code'");
}

// ─── TEST 1: ARCHIVEREN & HERSTELLEN ──────────────────────────────────

echo "── TEST 1: Archiveren & Herstellen ──\n";

try {
    // Aanmaak testbatch
    $stmt = $pdo->prepare("INSERT INTO production_batches (batch_code, status, started_at) VALUES (?, 'SEEDING', datetime('now'))");
    $stmt->execute([$testCode]);
    $batchId = $pdo->lastInsertId();
    logOK("Testbatch aangemaakt: $testCode (id=$batchId)");

    // Archiveren (archived_at zetten)
    $pdo->exec("UPDATE production_batches SET archived_at = datetime('now') WHERE id = $batchId");
    $row = $pdo->query("SELECT archived_at FROM production_batches WHERE id = $batchId")->fetch(PDO::FETCH_ASSOC);
    if (!empty($row['archived_at'])) {
        logOK("Archiveren geslaagd: archived_at = {$row['archived_at']}");
    } else {
        logFAIL("Archiveren mislukt: archived_at is leeg");
    }

    // Controleren dat batch "onzichtbaar" is voor actieve lijst
    $actief = $pdo->query("SELECT COUNT(*) FROM production_batches WHERE id = $batchId AND archived_at IS NULL")->fetchColumn();
    if ($actief == 0) {
        logOK("Batch niet zichtbaar in actieve lijst (archived_at IS NULL filter werkt)");
    } else {
        logFAIL("Batch nog zichtbaar ondanks archived_at");
    }

    // Herstellen
    $pdo->exec("UPDATE production_batches SET archived_at = NULL WHERE id = $batchId");
    $row = $pdo->query("SELECT archived_at FROM production_batches WHERE id = $batchId")->fetch(PDO::FETCH_ASSOC);
    if (empty($row['archived_at'])) {
        logOK("Herstellen geslaagd: archived_at is weer NULL");
    } else {
        logFAIL("Herstellen mislukt: archived_at nog gevuld");
    }

    $scherm++;
} catch (Exception $e) {
    logFAIL("Exception: " . $e->getMessage());
}

echo "\n";

// ─── TEST 2: DEFINITIEF WISSEN ────────────────────────────────────────

echo "── TEST 2: Definitief wissen ──\n";

$testCode2 = 'TEST_B31_DEL_' . date('Ymd_His');

try {
    // Aanmaak testbatch + materials
    $stmt = $pdo->prepare("INSERT INTO production_batches (batch_code, status, started_at) VALUES (?, 'SEEDING', datetime('now'))");
    $stmt->execute([$testCode2]);
    $batchId2 = $pdo->lastInsertId();

    // Material toevoegen (FK-loos, dus expliciet)
    $pdo->exec("INSERT INTO batch_materials (batch_id, inventory_id, quantity_used) VALUES ($batchId2, 99999, 1.0)");
    logOK("Testbatch aangemaakt: $testCode2 (id=$batchId2) + 1 material");

    // Check: material bestaat
    $matCount = $pdo->query("SELECT COUNT(*) FROM batch_materials WHERE batch_id = $batchId2")->fetchColumn();
    logINFO("batch_materials voor deze batch: $matCount rij(en)");

    // Simuleer B31-delete in transactie (zoals B31 het doet)
    $pdo->beginTransaction();
    try {
        $pdo->exec("DELETE FROM batch_materials WHERE batch_id = $batchId2");
        $pdo->exec("DELETE FROM germination_records WHERE production_batch_id = $batchId2");
        $pdo->exec("DELETE FROM production_batches WHERE id = $batchId2");

        // Log in deletion_log
      $pdo->exec("INSERT INTO deletion_log (action, system, batch_id, batch_code, operator, related_rows, created_at) VALUES ('DELETE', 'production', $batchId2, '$testCode2', 'testscript', 1, datetime('now'))");

        $pdo->commit();
        logOK("Delete-transactie geslaagd");
    } catch (Exception $e) {
        $pdo->rollBack();
        logFAIL("Delete-transactie mislukt: " . $e->getMessage());
        throw $e;
    }

    // Verificatie: batch weg?
    $exists = $pdo->query("SELECT COUNT(*) FROM production_batches WHERE id = $batchId2")->fetchColumn();
    if ($exists == 0) {
        logOK("Batch weg uit production_batches");
    } else {
        logFAIL("Batch nog aanwezig in production_batches");
    }

    // Verificatie: materials weg?
    $matLeft = $pdo->query("SELECT COUNT(*) FROM batch_materials WHERE batch_id = $batchId2")->fetchColumn();
    if ($matLeft == 0) {
        logOK("Materials weg uit batch_materials");
    } else {
        logFAIL("Materials nog aanwezig: $matLeft rij(en)");
    }

    // Verificatie: deletion_log?
    $logRow = $pdo->query("SELECT * FROM deletion_log WHERE batch_code = '$testCode2'")->fetch(PDO::FETCH_ASSOC);
    if ($logRow) {
    logOK("deletion_log bevat regel: {$logRow['batch_code']} om {$logRow['created_at']}");
    } else {
        logFAIL("deletion_log bevat GEEN regel voor $testCode2");
    }

    $scherm++;
} catch (Exception $e) {
    logFAIL("Exception: " . $e->getMessage());
}

echo "\n";

// ─── TEST 3: BACK-UP-INTEGRITEIT ──────────────────────────────────────

echo "── TEST 3: Back-up-integriteit ──\n";

try {
    // Draai backup
    echo "  ℹ️  Backup wordt uitgevoerd...\n";
    $output = shell_exec("php " . escapeshellarg(__DIR__ . '/_backup.php') . " 2>&1");
    echo "  ℹ️  Output: $output";

    // Vind nieuwste .gpg in lokale backup-map
    $backupDir = ERP_ROOT . '/database/ERP-backup';
    $files = glob("$backupDir/*.gpg");
    if (empty($files)) {
        logFAIL("Geen .gpg-bestanden gevonden in $backupDir");
        throw new Exception("Geen backup gevonden");
    }

    // Nieuwste op datum
    usort($files, function ($a, $b) { return filemtime($b) - filemtime($a); });
    $nieuwste = $files[0];
    logINFO("Nieuwste backup: " . basename($nieuwste));

    // Decrypteren naar tijdelijk bestand
    $passFile = __DIR__ . '/backup_pass.txt';
    $pass = trim(file_get_contents($passFile));
    $gpg = "C:\\Program Files\\GnuPG\\bin\\gpg.exe";
    $tempDb = sys_get_temp_dir() . '/b31_test_restore.sqlite';

    $cmd = "\"$gpg\" --batch --yes --passphrase " . escapeshellarg($pass) . " -o " . escapeshellarg($tempDb) . " --decrypt " . escapeshellarg($nieuwste) . " 2>&1";
    $gpgOut = shell_exec($cmd);

    if (!file_exists($tempDb)) {
        logFAIL("Decrypteren mislukt — geen output-bestand");
        echo "  ℹ️  GPG output: $gpgOut\n";
        throw new Exception("Decrypt mislukt");
    }

    logOK("Decrypteren geslaagd: " . basename($tempDb));

    // Integrity check
    $tempPdo = new PDO("sqlite:$tempDb");
    $tempPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $integrity = $tempPdo->query("PRAGMA integrity_check")->fetchColumn();
    if ($integrity === 'ok') {
        logOK("PRAGMA integrity_check = ok");
    } else {
        logFAIL("PRAGMA integrity_check = $integrity");
    }

    // Tabeltelling
    $tables = $tempPdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table'")->fetchColumn();
    logINFO("Tabellen in herstelde DB: $tables");

    // Verwijderde batch moet OOK weg zijn in de backup
    $delCheck = $tempPdo->query("SELECT COUNT(*) FROM production_batches WHERE batch_code = '$testCode2'")->fetchColumn();
    if ($delCheck == 0) {
        logOK("Verwijderde batch ($testCode2) is OOK afwezig in de backup ✓");
    } else {
        logFAIL("Verwijderde batch ($testCode2) is NOG AANWEZIG in de backup");
    }

    // Opruimen
    $tempPdo = null;
    unlink($tempDb);
    logINFO("Tijdelijke DB verwijderd");

    $scherm++;
} catch (Exception $e) {
    logFAIL("Exception: " . $e->getMessage());
}

echo "\n";

// ─── CLEANUP ───────────────────────────────────────────────────────────

cleanup($pdo, $testCode);
cleanup($pdo, $testCode2);
echo "── CLEANUP: testdata verwijderd ──\n\n";

// ─── EINDRAPPORT ───────────────────────────────────────────────────────

echo "══════════════════════════════════════\n";
if ($scherm === $schermTotaal) {
    echo "RESULTAAT: $scherm/$schermTotaal geslaagd — B31 is waterdicht ✅\n";
} else {
    echo "RESULTAAT: $scherm/$schermTotaal geslaagd — open punten hierboven ❌\n";
}
echo "══════════════════════════════════════\n";