cat > db_connect.php <<'PHP'
<?php
$db = new PDO('sqlite:/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
PHP