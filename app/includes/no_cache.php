<?php
// B15-fix: voorkom verouderde HTML/PHP in de browser (open punt Cyclus 5/8)
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}
