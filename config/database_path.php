<?php
/**
 * Database path - OS-onafhankelijk.
 * Productie vs testbank wordt EXPLICIET gekozen via de
 * omgevingsvariabele ERP_ENV, niet via het besturingssysteem.
 *
 *   ERP_ENV niet gezet of "production" -> Live (productie, de veilige default)
 *   ERP_ENV = "development"            -> Development (testbank)
 *
 * Pad is relatief aan deze map, dus identiek op Windows en Linux.
 */
$env = strtolower((string)(getenv('ERP_ENV') ?: 'production'));

$db = ($env === 'development')
    ? 'MicrogreensERP_Development.sqlite'
    : 'MicrogreensERP_Live.sqlite';

return __DIR__ . '/../database/' . $db;