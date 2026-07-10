<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Services\AuthService;

$env = [];
$envPath = __DIR__ . '/../../config/env.php';
if (file_exists($envPath)) {
    $env = require $envPath;
}

session_init($env);

$auth = new AuthService();
$error = '';
$email = $_POST['email'] ?? '';
$surface = $_GET['surface'] ?? $_POST['surface'] ?? 'guest';

$surfaceMap = [
    'guest' => BASE_URL . '/guest/index.php?route=home',
    'staff' => BASE_URL . '/staff/index.php?route=room-status-board',
    'owner' => BASE_URL . '/owner/index.php?route=dashboard',
    'admin' => BASE_URL . '/admin/index.php?route=dashboard',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthService::checkCsrf($_POST['csrf_token'] ?? '');

    try {
        $user = $auth->login($_POST['email'] ?? '', $_POST['password'] ?? '');

        $roleSurfaceMap = [
            'admin' => 'admin',
            'owner' => 'owner',
            'front_office' => 'staff',
            'housekeeping' => 'staff',
            'maintenance' => 'staff',
            'fnb' => 'staff',
            'manager' => 'staff',
            'guest' => 'guest',
        ];
        $targetSurface = $roleSurfaceMap[$user['role']] ?? 'guest';
        $redirect = $surfaceMap[$targetSurface];

        header('Location: ' . $redirect);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$csrfToken = AuthService::generateCsrfToken();
include __DIR__ . '/../../app/Views/auth/login.php';
