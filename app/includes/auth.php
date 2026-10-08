<?php

function auth_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');

    $isHttps = (
        !empty($_SERVER['HTTPS']) &&
        strtolower((string)$_SERVER['HTTPS']) !== 'off'
    );

    session_name('microgreens_erp');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_start();
}

function auth_current_user(): ?array
{
    auth_start_session();

    $user = $_SESSION['auth_user'] ?? null;

    if (!is_array($user)) {
        return null;
    }

    $requiredKeys = [
        'id',
        'username',
        'display_name',
        'role',
    ];

    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $user)) {
            return null;
        }
    }

    $lastActivity = (int)(
        $_SESSION['auth_last_activity'] ?? 0
    );

    if (
        $lastActivity > 0 &&
        time() - $lastActivity > 28800
    ) {
        auth_logout_user();
        return null;
    }

    $_SESSION['auth_last_activity'] = time();

    return $user;
}

function auth_is_logged_in(): bool
{
    return auth_current_user() !== null;
}

function auth_login_user(array $user): void
{
    auth_start_session();
    session_regenerate_id(true);

    $_SESSION['auth_user'] = [
        'id' => (int)$user['id'],
        'username' => (string)$user['username'],
        'display_name' => (string)$user['display_name'],
        'role' => (string)$user['role'],
    ];

    $_SESSION['auth_last_activity'] = time();
    $_SESSION['auth_login_time'] = time();
}

function auth_logout_user(): void
{
    auth_start_session();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $parameters = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $parameters['path'],
                'domain' => $parameters['domain'],
                'secure' => $parameters['secure'],
                'httponly' => $parameters['httponly'],
                'samesite' => 'Strict',
            ]
        );
    }

    session_destroy();
}

function auth_csrf_token(): string
{
    auth_start_session();

    if (
        !isset($_SESSION['auth_csrf_token']) ||
        !is_string($_SESSION['auth_csrf_token'])
    ) {
        $_SESSION['auth_csrf_token'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['auth_csrf_token'];
}

function auth_verify_csrf(?string $token): bool
{
    auth_start_session();

    $storedToken = $_SESSION['auth_csrf_token'] ?? null;

    return (
        is_string($storedToken) &&
        is_string($token) &&
        $token !== '' &&
        hash_equals($storedToken, $token)
    );
}

function auth_safe_redirect_target(
    ?string $target,
    string $fallback = 'dashboard.php'
): string {
    if (
        $target === null ||
        $target === '' ||
        str_contains($target, "\r") ||
        str_contains($target, "\n") ||
        str_contains($target, '://') ||
        str_starts_with($target, '//')
    ) {
        return $fallback;
    }

    return $target;
}

function auth_attempt_login(
    PDO $db,
    string $username,
    string $password
): bool {
    $username = trim($username);

    $stmt = $db->prepare("
        SELECT
            id,
            username,
            display_name,
            password_hash,
            role,
            is_active,
            failed_login_attempts,
            locked_until
        FROM erp_users
        WHERE username = :username
        LIMIT 1
    ");
    $stmt->execute([
        ':username' => $username,
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        password_verify(
            $password,
            password_hash(
                'invalid-login-placeholder',
                PASSWORD_DEFAULT
            )
        );

        return false;
    }

    if ((int)$user['is_active'] !== 1) {
        return false;
    }

    if (!empty($user['locked_until'])) {
        $lockedUntil = strtotime(
            (string)$user['locked_until'] . ' UTC'
        );

        if (
            $lockedUntil !== false &&
            $lockedUntil > time()
        ) {
            return false;
        }
    }

    if (
        !password_verify(
            $password,
            (string)$user['password_hash']
        )
    ) {
        $attempts = (
            (int)$user['failed_login_attempts']
        ) + 1;

        $lockedUntil = $attempts >= 5
            ? gmdate(
                'Y-m-d H:i:s',
                time() + 900
            )
            : null;

        $failureStmt = $db->prepare("
            UPDATE erp_users
            SET
                failed_login_attempts = :attempts,
                locked_until = :locked_until,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $failureStmt->execute([
            ':attempts' => $attempts,
            ':locked_until' => $lockedUntil,
            ':id' => (int)$user['id'],
        ]);

        return false;
    }

    $passwordHash = (string)$user['password_hash'];

    if (
        password_needs_rehash(
            $passwordHash,
            PASSWORD_DEFAULT
        )
    ) {
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );
    }

    $successStmt = $db->prepare("
        UPDATE erp_users
        SET
            password_hash = :password_hash,
            failed_login_attempts = 0,
            locked_until = NULL,
            last_login_at = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    $successStmt->execute([
        ':password_hash' => $passwordHash,
        ':id' => (int)$user['id'],
    ]);

    auth_login_user($user);

    return true;
}

function auth_require_login(): void
{
    if (auth_is_logged_in()) {
        return;
    }

    $requestUri = (string)(
        $_SERVER['REQUEST_URI'] ??
        '/microgreens/PHP/'
    );

    header(
        'Location: /microgreens/PHP/login.php?next=' .
        urlencode($requestUri)
    );
    exit;
}
