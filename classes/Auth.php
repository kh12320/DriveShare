<?php
require_once __DIR__ . '/../config/database.php';

class Auth
{
    private string $authUrl;
    private string $anonKey;
    private string $serviceKey;

    public function __construct()
    {
        $this->authUrl = rtrim(SUPABASE_URL, '/') . '/auth/v1';
        $this->anonKey = SUPABASE_ANON_KEY;
        $this->serviceKey = SUPABASE_SERVICE_KEY;
    }

    /* ─────────────────────────────────────────────────────────
       REGISTER — Supabase Auth + mirror in our users table
    ───────────────────────────────────────────────────────── */
    public function register(string $name, string $email, string $password, string $role, string $phone = ''): array
    {
        // 1. Create user in Supabase Auth
        $res = $this->authPost('/signup', [
            'email' => $email,
            'password' => $password,
            'data' => ['name' => $name, 'role' => $role, 'phone' => $phone],
        ]);

        if (!empty($res['error'])) {
            $msg = $res['error']['message'] ?? 'Registration failed.';
            // Friendlier messages
            if (str_contains($msg, 'already registered'))
                $msg = 'An account with this email already exists.';
            return ['success' => false, 'message' => $msg];
        }
        if (!empty($res['error_description'])) {
            return ['success' => false, 'message' => $res['error_description']];
        }

        $supabaseUid = $res['user']['id'] ?? Database::uuid();

        // 2. Mirror in our users table for role / profile queries
        try {
            $existing = Database::select('users', ['email' => $email], 'id');
            if (empty($existing)) {
                Database::insert('users', [
                    'id' => $supabaseUid,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_BCRYPT), // fallback
                    'name' => $name,
                    'role' => $role,
                    'phone' => $phone,
                ]);
            }
        } catch (RuntimeException $ignored) {
        }

        return ['success' => true, 'message' => 'Account created!'];
    }

    /* ─────────────────────────────────────────────────────────
       LOGIN — Supabase Auth first, bcrypt fallback for old users
    ───────────────────────────────────────────────────────── */
    public function login(string $email, string $password): array
    {
        $res = $this->authPost('/token?grant_type=password', [
            'email' => $email,
            'password' => $password,
        ]);
        $token = $res['access_token'] ?? null;

        if ($token) {
            // ✅ Supabase Auth verified — load our user row
            $users = Database::select('users', ['email' => $email], '*');
            if (empty($users)) {
                // Sync any missing user into our table automatically
                $meta = $res['user']['user_metadata'] ?? [];
                try {
                    Database::insert('users', [
                        'id' => $res['user']['id'],
                        'email' => $email,
                        'password_hash' => '',
                        'name' => $meta['name'] ?? explode('@', $email)[0],
                        'role' => $meta['role'] ?? 'customer',
                        'phone' => $meta['phone'] ?? '',
                    ]);
                } catch (RuntimeException $ignored) {
                }
                $users = Database::select('users', ['email' => $email], '*');
            }
            $user = $users[0] ?? null;
            if (!$user) {
                return ['success' => false, 'message' => 'Profile not found. Please re-register.'];
            }

            // Replace the giant Supabase JWT with a local session token to fit in VARCHAR(255)
            $token = bin2hex(random_bytes(32));
        } else {
            // ⬅ Fallback: verify against our table's bcrypt hash (legacy users)
            $users = Database::select('users', ['email' => $email], '*');
            if (empty($users) || !password_verify($password, $users[0]['password_hash'] ?? '')) {
                $errMsg = $res['error_description']
                    ?? ($res['error']['message'] ?? 'Invalid email or password.');
                return ['success' => false, 'message' => $errMsg];
            }
            $user = $users[0];
            $token = bin2hex(random_bytes(32));
        }

        // Store session
        $expires = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        try {
            Database::insert('sessions', [
                'id' => Database::uuid(),
                'user_id' => $user['id'],
                'token' => $token,
                'expires_at' => $expires,
            ]);
        } catch (RuntimeException $ignored) {
        }

        return ['success' => true, 'token' => $token, 'user' => $user];
    }

    /* ─────────────────────────────────────────────────────────
       FORGOT PASSWORD — Supabase sends the reset email
    ───────────────────────────────────────────────────────── */
    public function forgotPassword(string $email, string $redirectTo): array
    {
        // Supabase sends a magic link email automatically
        $this->authPost('/recover?redirect_to=' . urlencode($redirectTo), [
            'email' => $email,
        ]);

        // Always return success — prevents email enumeration attacks
        return [
            'success' => true,
            'message' => 'If that email is registered, a password reset link has been sent. Check your inbox (and spam folder).',
        ];
    }

    /* ─────────────────────────────────────────────────────────
       RESET PASSWORD — called with the Supabase access_token
       from the reset email redirect
    ───────────────────────────────────────────────────────── */
    public function resetPassword(string $accessToken, string $newPassword): array
    {
        $ch = curl_init($this->authUrl . '/user');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode(['password' => $newPassword]),
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->anonKey,
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $res = json_decode($body, true) ?? [];

        if ($code < 200 || $code >= 300) {
            $msg = $res['error']['message'] ?? $res['msg'] ?? 'Password reset failed. The link may have expired.';
            return ['success' => false, 'message' => $msg];
        }

        // Also sync the hash into our users table (for the fallback path)
        $userEmail = $res['email'] ?? null;
        if ($userEmail) {
            try {
                Database::update(
                    'users',
                    ['password_hash' => password_hash($newPassword, PASSWORD_BCRYPT)],
                    ['email' => $userEmail]
                );
            } catch (RuntimeException $ignored) {
            }
        }

        return ['success' => true, 'message' => 'Password updated successfully. You can now sign in.'];
    }

    /* ─────────────────────────────────────────────────────────
       VALIDATE SESSION
    ───────────────────────────────────────────────────────── */
    public function validateSession(string $token): ?array
    {
        $sessions = Database::select('sessions', ['token' => $token], '*');
        if (empty($sessions))
            return null;

        $session = $sessions[0];
        if (strtotime($session['expires_at']) < time()) {
            Database::delete('sessions', ['token' => $token]);
            return null;
        }

        $users = Database::select('users', ['id' => $session['user_id']], '*');
        return $users[0] ?? null;
    }

    /* ─────────────────────────────────────────────────────────
       LOGOUT
    ───────────────────────────────────────────────────────── */
    public function logout(string $token): void
    {
        Database::delete('sessions', ['token' => $token]);
    }

    /* ─────────────────────────────────────────────────────────
       INTERNAL HELPER
    ───────────────────────────────────────────────────────── */
    private function authPost(string $path, array $data): array
    {
        $ch = curl_init($this->authUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->anonKey,
                'Content-Type: application/json',
            ],
        ]);
        $body = curl_exec($ch);
        return json_decode($body, true) ?? [];
    }
}
