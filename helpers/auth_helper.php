<?php

declare(strict_types=1);

function requireLogin(): void
{
    if (!AuthService::refreshUser()) {
        Session::flash('info', 'Please sign in to continue.');
        redirectTo('login.php');
    }
}

function requireRole(string ...$roles): void
{
    requireLogin();
    if (!in_array(AuthService::user()['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('You are not permitted to access this page.');
    }
}

function dashboardPath(?string $role = null): string
{
    return match ($role ?? AuthService::user()['role'] ?? '') {
        'admin' => 'admin/index.php',
        'teacher' => 'teacher/index.php',
        'student' => 'student/index.php',
        default => 'login.php',
    };
}
