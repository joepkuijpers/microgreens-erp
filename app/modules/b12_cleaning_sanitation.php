<?php
class B12_Cleaning_Engine {
    private $db;
    public function __construct($dbPath) {
        $this->db = new SQLite3($dbPath);
    }
    
    public function getLogs($limit = 20) {
        $sql = "SELECT * FROM cleaning_logs ORDER BY cleaned_at DESC LIMIT :lim";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':lim', $limit, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $out = []; 
        while($r = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $r;
        return $out;
    }

    public function addLog($otype, $oname, $pname, $operator, $method, $notes) {
        $stmt = $this->db->prepare("INSERT INTO cleaning_logs (object_type, object_name, product_name, operator_name, method, notes, is_validated) VALUES (:ot, :on, :pn, :op, :m, :n, 0)");
        $stmt->bindValue(':ot', $otype, SQLITE3_TEXT);
        $stmt->bindValue(':on', $oname, SQLITE3_TEXT);
        $stmt->bindValue(':pn', $pname, SQLITE3_TEXT);
        $stmt->bindValue(':op', $operator, SQLITE3_TEXT);
        $stmt->bindValue(':m', $method, SQLITE3_TEXT);
        $stmt->bindValue(':n', $notes, SQLITE3_TEXT);
        return $stmt->execute();
    }

    // FIX: Geen validated_by / validated_at meer gebruiken, alleen is_validated
    public function validateLog($id) {
        $stmt = $this->db->prepare("UPDATE cleaning_logs SET is_validated=1 WHERE id=:i");
        $stmt->bindValue(':i', $id, SQLITE3_INTEGER);
        return $stmt->execute();
    }
    
    public function getUnvalidatedCount() {
        $res = $this->db->querySingle("SELECT COUNT(*) FROM cleaning_logs WHERE is_validated = 0");
        return $res ? $res : 0;
    }
}
