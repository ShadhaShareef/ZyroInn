<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Services\AuthService;

$env = [];
$envPath = __DIR__ . '/../../config/env.php';
if (file_exists($envPath)) {
    $env = require $envPath;
}

session_init($env);

AuthService::logout();

$surface = $_GET['surface'] ?? 'guest';
$redirects = [
    'guest' => BASE_URL . '/guest/index.php?route=home',
    'staff' => BASE_URL . '/auth/login.php?surface=staff',
    'owner' => BASE_URL . '/auth/login.php?surface=owner',
    'admin' => BASE_URL . '/auth/login.php?surface=admin',
];
header('Location: ' . ($redirects[$surface] ?? $redirects['guest']));
exit;
