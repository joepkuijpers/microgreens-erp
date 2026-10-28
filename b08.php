<?php
error_reporting(E_ALL);
ini_set("display_errors", 0);
$dbPath = "/var/www/html/database/MicrogreensERP_Live.sqlite";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_GET["action"])) {
    header("Content-Type: application/json");
    try {
        $db = new PDO("sqlite:$dbPath");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->beginTransaction();

        if ($_GET["action"] === "list_harvested") {
            $stmt = $db->query("SELECT id, batch_code, crop, harvest_date FROM production_batches WHERE status = 'HARVESTED' ORDER BY harvest_date DESC");
            echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } elseif ($_GET["action"] === "freeze_dry") {
            $input = json_decode(file_get_contents("php://input"), true);
            if (!isset($input["batch_id"], $input["dry_weight"], $input["operator"])) {
                throw new Exception("Ontbrekende velden");
            }
            $db->prepare("UPDATE production_batches SET status = 'FREEZE_DRIED' WHERE id = ?")->execute([$input["batch_id"]]);
            $db->prepare("INSERT INTO freeze_dry_logs (batch_id, process_date, dry_weight_grams, operator, notes) VALUES (?, datetime('now'), ?, ?, ?)")->execute([$input["batch_id"], $input["dry_weight"], $input["operator"], $input["notes"] ?? ""]);
            $db->commit();
            echo json_encode(["success" => true]);
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(400);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8"><title>B08 Vriesdrogen</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
async function loadBatches(){
    const r=await fetch("?action=list_harvested");
    const j=await r.json();
    const l=document.getElementById("list");
    if(j.success&&j.data.length){
        l.innerHTML=j.data.map(b=>`<div class="p-3 border cursor-pointer hover:bg-blue-50" onclick="sel(${b.id},'${b.batch_code}','${b.crop}')"><b>${b.batch_code}</b> ${b.crop}</div>`).join("");
    }else{l.innerHTML="<p class='text-gray-500'>Geen batches.</p>";}
}
function sel(id,code,crop){
    document.getElementById("id").value=id;
    document.getElementById("disp").value=code+" ("+crop+")";
    document.getElementById("list").classList.add("hidden");
    document.getElementById("form").classList.remove("hidden");
}
async function sub(e){
    e.preventDefault();
    const p={
        batch_id:document.getElementById("id").value,
        dry_weight:parseFloat(document.getElementById("w").value),
        operator:document.getElementById("op").value,
        notes:document.getElementById("nt").value
    };
    const r=await fetch("?action=freeze_dry",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(p)});
    const j=await r.json();
    if(j.success){alert("Succes!");location.reload();}else{alert("Fout: "+j.error);}
}
</script>
</head>
<body class="bg-gray-50 p-6">
<div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
<h1 class="text-2xl font-bold text-blue-700 mb-4">B08: Vriesdrogen</h1>
<div id="list" class="mb-4 space-y-2"><p>Laden...</p></div>
<div id="form" class="hidden space-y-4">
<input type="hidden" id="id">
<input type="text" id="disp" disabled class="w-full border p-2 bg-gray-100">
<input type="number" id="w" placeholder="Droog gewicht (g)" class="w-full border p-2" step="0.1">
<input type="text" id="op" placeholder="Operator" class="w-full border p-2">
<textarea id="nt" placeholder="Notities" class="w-full border p-2"></textarea>
<button onclick="sub(event)" class="bg-blue-600 text-white px-4 py-2 rounded">Start Proces</button>
<button onclick="loadBatches();document.getElementById('form').classList.add('hidden');document.getElementById('list').classList.remove('hidden')" class="bg-gray-300 px-4 py-2 rounded">Annuleer</button>
</div>
</div>
<script>loadBatches();</script>
</body>
</html>
