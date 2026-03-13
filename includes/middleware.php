<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';

/**
 * Middleware helper - call at top of any protected page
 * Returns current user or redirects to login
 */
function requireAuth(?string $requiredRole = null): array
{
    $token = $_COOKIE['auth_token'] ?? '';

    if (empty($token)) {
        header('Location: /index.php?msg=Please+login+to+continue');
        exit;
    }

    $auth = new Auth();
    $user = $auth->validateSession($token);

    if (!$user) {
        setcookie('auth_token', '', time() - 3600, '/', '', false, true);
        header('Location: /index.php?msg=Session+expired+please+login');
        exit;
    }

    if ($requiredRole && $user['role'] !== $requiredRole) {
        header('Location: /dashboard/' . $user['role'] . '.php');
        exit;
    }

    return $user;
}

/**
 * Get current user without forcing redirect (for API endpoints)
 */
function getCurrentUser(): ?array
{
    $token = $_COOKIE['auth_token'] ?? '';
    if (empty($token))
        return null;
    $auth = new Auth();
    return $auth->validateSession($token);
}

/**
 * JSON response helper
 */
function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Redirect with flash message
 */
function redirectWithMsg(string $url, string $msg, string $type = 'success'): void
{
    header("Location: {$url}?msg=" . urlencode($msg) . "&type={$type}");
    exit;
}
