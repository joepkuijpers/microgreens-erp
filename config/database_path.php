<?php
/**
 * Database path - Universal (Windows & Linux)
 * Windows development -> Development DB
 * Linux production (Raspberry Pi) -> Live DB
 */
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    return 'C:/Users/joepk/Downloads/microgreens-erp/database/MicrogreensERP_Development.sqlite';
}
return '/var/www/html/microgreens/database/MicrogreensERP_Live.sqlite';
