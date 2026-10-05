<?php
$dbPath = '/var/www/html/database/MicrogreensERP_Live.sqlite';
if (!file_exists($dbPath)) { http_response_code(500); echo json_encode(['error' => 'DB niet gevonden']); exit; }
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("PRAGMA foreign_keys = ON");
} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['error' => 'DB fout: ' . $e->getMessage()]); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    try {
        if ($_GET['action'] === 'readiness') {
            $stmt = $db->prepare("SELECT id, batch_code, crop, started_at, julianday('now') - julianday(started_at) as dag, status FROM production_batches WHERE status = 'GROWING' AND (julianday('now') - julianday(started_at)) >= 14 ORDER BY started_at ASC");
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } elseif ($_GET['action'] === 'harvest') {
            if (!isset($input['batch_id'], $input['weight_grams'], $input['operator'])) throw new Exception('Ontbrekende velden');
            $batchId = (int)$input['batch_id']; $weight = (float)$input['weight_grams'];
            $operator = preg_replace('/[^a-zA-Z0-9_]/', '', $input['operator']); $notes = $input['notes'] ?? '';
            if ($weight <= 0) throw new Exception('Gewicht > 0');
            $db->beginTransaction();
            $db->prepare("UPDATE production_batches SET status = 'harvested' WHERE id = ?")->execute([$batchId]);
            $db->prepare("INSERT INTO harvest_logs (batch_id, harvest_date, weight_grams, operator, notes) VALUES (?, datetime('now'), ?, ?, ?)")->execute([$batchId, $weight, $operator, $notes]);
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Oogst geregistreerd']);
        } else { throw new Exception('Ongeldige actie'); }
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(400); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B07: Oogst</title><script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">
<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-2xl font-bold text-green-700 mb-4">B07: Oogst Registratie</h1>
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <div class="flex justify-between items-center mb-4"><h2 class="text-xl font-semibold">Klaar voor Oogst</h2><button onclick="loadReadiness()" class="bg-blue-600 text-white px-4 py-2 rounded">🔄 Ververs</button></div>
        <div id="readiness-list" class="space-y-3"><p class="text-gray-500">Laden...</p></div>
    </div>
    <div id="harvest-form-section" class="bg-white p-6 rounded-lg shadow hidden">
        <h2 class="text-xl font-semibold mb-4 text-green-700">Oogst Registreren</h2>
        <form onsubmit="submitHarvest(event)">
            <input type="hidden" id="batch_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="block text-sm font-medium">Batch</label><input type="text" id="display_batch_code" disabled class="w-full bg-gray-100 border rounded p-2"></div>
                <div><label class="block text-sm font-medium">Gewas</label><input type="text" id="display_crop" disabled class="w-full bg-gray-100 border rounded p-2"></div>
            </div>
            <div class="mb-4"><label class="block text-sm font-medium">Gewicht (gram)</label><input type="number" step="0.1" id="weight_grams" required class="w-full border rounded p-2"></div>
            <div class="mb-4"><label class="block text-sm font-medium">Operator</label><input type="text" id="operator" required class="w-full border rounded p-2"></div>
            <div class="mb-6"><label class="block text-sm font-medium">Notities</label><textarea id="notes" class="w-full border rounded p-2"></textarea></div>
            <div class="flex gap-3"><button type="submit" class="bg-green-600 text-white px-6 py-2 rounded">Bevestig</button><button type="button" onclick="cancelHarvest()" class="bg-gray-300 px-6 py-2 rounded">Annuleer</button></div>
        </form>
    </div>
</div>
<script>
async function loadReadiness() {
    const el = document.getElementById('readiness-list');
    try {
        const r = await fetch('b07.php?action=readiness', {method:'POST'});
        const d = await r.json();
        if(d.success && d.data.length) {
            el.innerHTML = d.data.map(b => `<div class="border p-4 rounded flex justify-between items-center"><div><p class="font-bold">${b.batch_code}</p><p class="text-sm text-gray-600">${b.crop} | DAG: ${Math.round(b.dag)}</p></div><button onclick="prepareHarvest(${b.id},'${b.batch_code}','${b.crop}')" class="bg-green-600 text-white px-4 py-2 rounded">Oogsten</button></div>`).join('');
        } else { el.innerHTML = '<p class="text-gray-500">Geen batches klaar.</p>'; }
    } catch(e) { el.innerHTML = `<p class="text-red-500">Fout: ${e.message}</p>`; }
}
function prepareHarvest(id, bn, ct) {
    document.getElementById('batch_id').value = id;
    document.getElementById('display_batch_code').value = bn;
    document.getElementById('display_crop').value = ct;
    document.getElementById('harvest-form-section').classList.remove('hidden');
}
function cancelHarvest() { document.getElementById('harvest-form-section').classList.add('hidden'); document.querySelector('form').reset(); }
async function submitHarvest(e) {
    e.preventDefault();
    try {
        const r = await fetch('b07.php?action=harvest', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({batch_id: parseInt(document.getElementById('batch_id').value), weight_grams: parseFloat(document.getElementById('weight_grams').value), operator: document.getElementById('operator').value, notes: document.getElementById('notes').value})
        });
        const d = await r.json();
        if(d.success) { alert('OK'); cancelHarvest(); loadReadiness(); } else { alert('Fout: '+d.error); }
    } catch(e) { alert('Netwerkfout'); }
}
loadReadiness();
</script>
</body>
</html>
