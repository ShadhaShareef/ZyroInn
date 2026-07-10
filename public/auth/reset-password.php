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
$valid = false;
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (empty($token)) {
    $error = 'No reset token provided.';
} else {
    $resetData = $auth->validateResetToken($token);
    $valid = (bool)$resetData;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthService::checkCsrf($_POST['csrf_token'] ?? '');

    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $auth->resetPassword($token, $password);
            $success = 'Your password has been updated successfully.';
            $valid = false;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$csrfToken = AuthService::generateCsrfToken();
include __DIR__ . '/../../app/Views/auth/reset-password.php';
