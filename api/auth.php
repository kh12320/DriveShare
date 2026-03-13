<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$auth = new Auth();

switch ($action) {

    // ──────────────────────────────────────────────────────────
    case 'register':
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm = trim($_POST['confirm'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name) || empty($email) || empty($password) || empty($role)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }
        if ($password !== $confirm) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit;
        }
        if (strlen($password) < 8) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }

        $result = $auth->register($name, $email, $password, $role, $phone);
        if ($result['success']) {
            $loginResult = $auth->login($email, $password);
            if ($loginResult['success']) {
                setcookie('auth_token', $loginResult['token'], time() + SESSION_LIFETIME, '/', '', false, true);
                $result['redirect'] = $loginResult['user']['role'] === 'owner'
                    ? '/dashboard/owner.php'
                    : '/dashboard/customer.php';
            }
        }
        ob_end_clean();
        echo json_encode($result);
        break;

    // ──────────────────────────────────────────────────────────
    case 'login':
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
            exit;
        }

        $result = $auth->login($email, $password);
        if ($result['success']) {
            setcookie('auth_token', $result['token'], time() + SESSION_LIFETIME, '/', '', false, true);
            $result['redirect'] = $result['user']['role'] === 'owner'
                ? '/dashboard/owner.php'
                : '/dashboard/customer.php';
        }
        ob_end_clean();
        echo json_encode($result);
        break;

    // ──────────────────────────────────────────────────────────
    case 'forgot_password':
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }

        // Build the redirect URL pointing to our reset page
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        $redirectTo = $scheme . '://' . $host . '/reset-password.php';

        $result = $auth->forgotPassword($email, $redirectTo);
        ob_end_clean();
        echo json_encode($result);
        break;

    // ──────────────────────────────────────────────────────────
    case 'reset_password':
        $accessToken = trim($_POST['access_token'] ?? '');
        $newPassword = trim($_POST['password'] ?? '');
        $confirm = trim($_POST['confirm'] ?? '');

        if (empty($accessToken) || empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request. Please use the link from your email.']);
            exit;
        }
        if ($newPassword !== $confirm) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit;
        }
        if (strlen($newPassword) < 8) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
            exit;
        }

        $result = $auth->resetPassword($accessToken, $newPassword);
        ob_end_clean();
        echo json_encode($result);
        break;

    // ──────────────────────────────────────────────────────────
    case 'logout':
        $token = $_COOKIE['auth_token'] ?? '';
        if ($token) {
            $auth->logout($token);
            setcookie('auth_token', '', time() - 3600, '/', '', false, true);
        }
        ob_end_clean();
        echo json_encode(['success' => true, 'redirect' => '/index.php']);
        break;

    // ──────────────────────────────────────────────────────────
    default:
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
