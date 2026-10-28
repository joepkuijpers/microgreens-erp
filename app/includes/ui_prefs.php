<?php
// Decoratieve emoji uit tekst halen; status-symbolen (✓ ⚠ ❌) blijven altijd.
function emoji_strip($t){
    return preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $t);
}
function emoji($t){
    if (!empty($_SESSION['emoji_ui'])) return $t;
    return str_replace('  ', ' ', emoji_strip($t));
}
