<?php
// bootstrap.php - Application Bootstrap

// Register PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

define('BASE_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'));

function session_init(array $env): void
{
    $lifetime = (int)($env['session_lifetime'] ?? 86400);
    $idleTimeout = (int)($env['session_idle'] ?? 1800);

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $idleRoles = ['admin', 'front_office', 'housekeeping', 'maintenance', 'fnb', 'manager', 'guest', 'owner'];
    $role = $_SESSION['role'] ?? '';

    if (in_array($role, $idleRoles, true)) {
        $now = time();
        $lastActivity = $_SESSION['_last_activity'] ?? 0;

        if ($lastActivity > 0 && ($now - $lastActivity) > $idleTimeout) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }
            session_destroy();
            header('Location: ' . BASE_URL . '/auth/login.php?surface=' . urlencode($role));
            exit;
        }

        $_SESSION['_last_activity'] = $now;
    }

    $_SESSION['_created_at'] = $_SESSION['_created_at'] ?? time();
}

function requireEncryptionKey(array $env): void
{
    if (!empty($env['dev_mode'])) return;
    $key = $env['encryption_key'] ?? '';
    if (strlen($key) < 16) {
        http_response_code(500);
        die("Application boot failed: encryption_key is not configured. Set a 64-hex-char key in config/env.php before running in production mode.");
    }
}

function enforceHttps(array $env): void
{
    if (!empty($env['dev_mode'])) return;
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        $redirect = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirect);
        exit;
    }
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
