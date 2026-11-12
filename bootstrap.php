<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/CSRF.php';
require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/core/Validator.php';

foreach (glob(__DIR__ . '/models/*.php') ?: [] as $file) require_once $file;
foreach (glob(__DIR__ . '/services/*.php') ?: [] as $file) require_once $file;
require_once __DIR__ . '/helpers/app_helper.php';
require_once __DIR__ . '/helpers/auth_helper.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'none'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; img-src 'self' data:; style-src 'self' 'unsafe-inline'");
set_exception_handler(static function (Throwable $exception): void {
    error_log('Examly application error: ' . $exception->getMessage());
    if (!headers_sent()) http_response_code(500);
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Something went wrong · Examly</title><body style="font-family:Segoe UI,Arial,sans-serif;padding:3rem;color:#13213b"><h1>Something went wrong</h1><p>Please try again. If the problem continues, contact your administrator.</p><p><a href="' . e(appUrl('index.php')) . '">Return to Examly</a></p></body></html>';
});

Session::start();
AuthService::restoreRememberedSession();
