<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_GET["lang"])) { $_SESSION["lang"] = $_GET["lang"]; }
$lang = $_SESSION["lang"] ?? "nl";
if (!in_array($lang, ["nl", "en"])) { $lang = "nl"; }
$f = __DIR__ . "/" . $lang . ".json";
$t = file_exists($f) ? json_decode(file_get_contents($f), true) : [];
function __($k) { global $t; return $t[$k] ?? $k; }
function lang_url($l) { $u = preg_replace("/[?&]lang=[a-z]{2}/", "", $_SERVER["PHP_SELF"]); return $u . (strpos($u,"?")?"&":"?") . "lang=" . $l; }
?>
