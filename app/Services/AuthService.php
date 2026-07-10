<?php
namespace App\Services;

use PDO;
use Exception;

class AuthService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function login(string $email, string $password): array
    {
        if (empty($email) || empty($password)) {
            throw new Exception('Email and password are required.');
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || empty($user['password_hash'])) {
            throw new Exception('Invalid email or password.');
        }

        if (!self::verifyPassword($password, $user['password_hash'])) {
            throw new Exception('Invalid email or password.');
        }

        $this->db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?")->execute([$user['id']]);

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['property_id'] = $user['property_id'] ? (int)$user['property_id'] : 1;
        $_SESSION['_last_activity'] = time();
        $_SESSION['_created_at'] = time();

        return $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    public static function isAuthenticated(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function getCurrentUser(): ?array
    {
        if (!self::isAuthenticated()) {
            return null;
        }
        return [
            'id' => (int)$_SESSION['user_id'],
            'role' => $_SESSION['role'] ?? null,
            'first_name' => $_SESSION['first_name'] ?? null,
            'last_name' => $_SESSION['last_name'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'property_id' => (int)($_SESSION['property_id'] ?? 0),
        ];
    }

    public static function requireAuth(array $allowedRoles = []): void
    {
        if (!self::isAuthenticated()) {
            $surface = $_GET['surface'] ?? determineSurfaceFromReferer();
            $loginUrl = BASE_URL . '/auth/login.php?surface=' . urlencode($surface);
            header('Location: ' . $loginUrl);
            exit;
        }

        if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles, true)) {
            http_response_code(403);
            $surface = $_SESSION['role'] ?? 'guest';
            $surfaceMap = [
                'admin' => 'admin',
                'owner' => 'owner',
                'front_office' => 'staff',
                'housekeeping' => 'staff',
                'maintenance' => 'staff',
                'fnb' => 'staff',
                'manager' => 'staff',
                'guest' => 'guest',
            ];
            $target = $surfaceMap[$surface] ?? 'guest';
            header('Location: ' . BASE_URL . '/' . $target . '/index.php');
            exit;
        }
    }

    public static function checkCsrf(string $token): void
    {
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            die('CSRF validation failed.');
        }
    }

    public static function verifyCsrfToken(string $token): bool
    {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function initiatePasswordReset(string $email): string
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();

        if (!$userId) {
            return '';
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $this->db->prepare("
            INSERT INTO password_reset_tokens (user_id, token, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $token, $expiresAt]);

        return $token;
    }

    public function validateResetToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT prt.*, u.email, u.first_name, u.last_name, u.role
            FROM password_reset_tokens prt
            JOIN users u ON prt.user_id = u.id
            WHERE prt.token = ?
              AND prt.used_at IS NULL
              AND prt.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $resetData = $this->validateResetToken($token);
        if (!$resetData) {
            throw new Exception('Invalid or expired reset token.');
        }

        $hash = self::hashPassword($newPassword);
        $userId = (int)$resetData['user_id'];

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);

            $stmt = $this->db->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE token = ?");
            $stmt->execute([$token]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updatePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !self::verifyPassword($currentPassword, $hash)) {
            throw new Exception('Current password is incorrect.');
        }

        $newHash = self::hashPassword($newPassword);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$newHash, $userId]);
    }

    public function getAllowedSurfacesForRole(string $role): array
    {
        $map = [
            'admin' => ['admin'],
            'owner' => ['owner'],
            'front_office' => ['staff'],
            'housekeeping' => ['staff'],
            'maintenance' => ['staff'],
            'fnb' => ['staff'],
            'manager' => ['staff'],
            'guest' => ['guest'],
        ];
        return $map[$role] ?? ['guest'];
    }

    public function getDefaultRouteForRole(string $role): string
    {
        $map = [
            'admin' => BASE_URL . '/admin/index.php?route=dashboard',
            'owner' => BASE_URL . '/owner/index.php?route=dashboard',
            'front_office' => BASE_URL . '/staff/index.php?route=room-status-board',
            'housekeeping' => BASE_URL . '/staff/index.php?route=room-status-board',
            'maintenance' => BASE_URL . '/staff/index.php?route=maintenance-queue',
            'fnb' => BASE_URL . '/staff/index.php?route=fnb-orders',
            'manager' => BASE_URL . '/staff/index.php?route=room-status-board',
            'guest' => BASE_URL . '/guest/index.php?route=home',
        ];
        return $map[$role] ?? BASE_URL . '/guest/index.php?route=home';
    }
}

function determineSurfaceFromReferer(): string
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (str_contains($referer, '/staff/')) return 'staff';
    if (str_contains($referer, '/owner/')) return 'owner';
    if (str_contains($referer, '/admin/')) return 'admin';
    return 'guest';
}
