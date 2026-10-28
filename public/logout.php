<?php
require_once '../app/includes/auth.php';

auth_start_session();

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !auth_verify_csrf(
        (string)($_POST['csrf_token'] ?? '')
    )
) {
    header('Location: index.php');
    exit;
}

auth_logout_user();

header('Location: login.php?logged_out=1');
exit;
