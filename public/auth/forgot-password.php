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
$success = '';
$resetLink = '';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'];
$publicBase = dirname(dirname($scriptName));
$appUrl = $scheme . '://' . $host . BASE_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthService::checkCsrf($_POST['csrf_token'] ?? '');

    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $token = $auth->initiatePasswordReset($email);

        if ($token) {
            $resetLink = $appUrl . '/auth/reset-password.php?token=' . urlencode($token);
            $success = 'A password reset link has been generated.';
        } else {
            $success = 'If an account with that email exists, a reset link has been generated.';
        }
    }
}

$csrfToken = AuthService::generateCsrfToken();
include __DIR__ . '/../../app/Views/auth/forgot-password.php';
