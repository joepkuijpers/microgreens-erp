<?php
include '../app/db_connect.php';
include '../app/includes/language.php';
require_once '../app/includes/auth.php';

auth_start_session();

if (auth_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = '';
$next = auth_safe_redirect_target(
    (string)($_GET['next'] ?? $_POST['next'] ?? ''),
    'dashboard.php'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(
        (string)($_POST['username'] ?? '')
    );
    $password = (string)($_POST['password'] ?? '');
    $csrfToken = (string)(
        $_POST['csrf_token'] ?? ''
    );

    if (
        !auth_verify_csrf($csrfToken) ||
        $username === '' ||
        $password === ''
    ) {
        $error = __('invalid_login');
    } elseif (
        auth_attempt_login(
            $db,
            $username,
            $password
        )
    ) {
        header(
            'Location: ' .
            auth_safe_redirect_target(
                $next,
                'dashboard.php'
            )
        );
        exit;
    } else {
        $error = __('invalid_login');
    }
}

include '../app/includes/header.php';
?>

<div class="main">
    <h1>🔐 <?= htmlspecialchars(__('erp_login')) ?></h1>

    <div class="card">
        <p>
            <?= htmlspecialchars(__('login_explanation')) ?>
        </p>

        <?php if ($error !== ''): ?>
            <p>
                <strong>
                    <?= htmlspecialchars($error) ?>
                </strong>
            </p>
        <?php endif; ?>

        <form method="post" action="login.php">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(auth_csrf_token()) ?>"
            >

            <input
                type="hidden"
                name="next"
                value="<?= htmlspecialchars($next) ?>"
            >

            <label for="username">
                <?= htmlspecialchars(__('username')) ?>
            </label><br>

            <input
                id="username"
                type="text"
                name="username"
                value="<?= htmlspecialchars($username) ?>"
                autocomplete="username"
                maxlength="64"
                required
                autofocus
            ><br><br>

            <label for="password">
                <?= htmlspecialchars(__('password')) ?>
            </label><br>

            <input
                id="password"
                type="password"
                name="password"
                autocomplete="current-password"
                required
            ><br><br>

            <button type="submit" class="btn">
                <?= htmlspecialchars(__('log_in')) ?>
            </button>
        </form>
    </div>
</div>

<?php include '../app/includes/footer.php'; ?>
