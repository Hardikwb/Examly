<?php

declare(strict_types=1);

final class AuthService
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 15 * 60;

    public static function user(): ?array
    {
        return Session::get('auth_user');
    }
    public static function check(): bool
    {
        return self::user() !== null;
    }
    public static function refreshUser(): bool
    {
        $sessionUser = self::user();
        if (!$sessionUser) return false;
        $record = (new User())->findById((int)$sessionUser['id']);
        if (!$record || $record['status'] !== 'active') {
            self::logout();
            Session::start();
            return false;
        }
        Session::set('auth_user', ['id' => (int)$record['id'], 'name' => $record['name'], 'email' => $record['email'], 'role' => $record['role']]);
        return true;
    }

    public static function login(string $identity, string $password, string $role, bool $remember = false): array
    {
        $key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'local') . '|' . strtolower(trim($identity)));
        $state = Session::get('login_attempts', []);
        $bucket = $state[$key] ?? ['count' => 0, 'until' => 0];
        if ($bucket['until'] > time()) return ['ok' => false, 'message' => 'Too many sign-in attempts. Try again in a few minutes.'];
        $user = (new User())->findByEmail($identity);
        if (!$user || !password_verify($password, $user['password_hash']) || $user['role'] !== $role || $user['status'] !== 'active') {
            $bucket['count']++;
            if ($bucket['count'] >= self::MAX_ATTEMPTS) {
                $bucket['until'] = time() + self::LOCK_SECONDS;
                $bucket['count'] = 0;
            }
            $state[$key] = $bucket;
            Session::set('login_attempts', $state);
            return ['ok' => false, 'message' => 'The email, password or selected role did not match.'];
        }
        unset($state[$key]);
        Session::set('login_attempts', $state);
        Session::regenerate();
        Session::set('auth_user', ['id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']]);
        if ($remember) self::issueRememberToken((int)$user['id']);
        else self::clearRememberCookie();
        audit('login', 'users', (int)$user['id']);
        return ['ok' => true, 'message' => 'Welcome back, ' . $user['name'] . '.'];
    }

    private static function issueRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        (new Database())->update('users', ['remember_token_hash' => hash('sha256', $token)], ['id' => $userId]);
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie(REMEMBER_COOKIE, $userId . '|' . $token, ['expires' => time() + 60 * 60 * 24 * 30, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
    }

    public static function restoreRememberedSession(): void
    {
        if (self::check() || empty($_COOKIE[REMEMBER_COOKIE])) return;
        $parts = explode('|', (string)$_COOKIE[REMEMBER_COOKIE], 2);
        if (count($parts) !== 2 || !ctype_digit($parts[0])) return;
        $user = (new Database())->findById('users', (int)$parts[0]);
        if (!$user || $user['status'] !== 'active' || !$user['remember_token_hash'] || !hash_equals($user['remember_token_hash'], hash('sha256', $parts[1]))) {
            self::clearRememberCookie();
            return;
        }
        Session::regenerate();
        Session::set('auth_user', ['id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']]);
        self::issueRememberToken((int)$user['id']);
    }

    private static function clearRememberCookie(): void
    {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie(REMEMBER_COOKIE, '', ['expires' => time() - 3600, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
    }

    public static function revokeRememberedSession(): void
    {
        self::clearRememberCookie();
    }

    public static function logout(): void
    {
        $user = self::user();
        try {
            if ($user) {
                (new Database())->update('users', ['remember_token_hash' => null], ['id' => $user['id']]);
                audit('logout', 'users', (int)$user['id']);
            }
        } catch (Throwable $e) {
            error_log('Logout cleanup failed: ' . $e->getMessage());
        } finally {
            self::clearRememberCookie();
            Session::destroy();
        }
    }
}
