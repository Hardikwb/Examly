<?php
declare(strict_types=1);

define('APP_NAME', 'Examly');
define('APP_ROOT', dirname(__DIR__));
$configuredUrl = getenv('APP_URL') ?: '';
if ($configuredUrl === '' && !empty($_SERVER['DOCUMENT_ROOT'])) {
    $projectRoot = realpath(APP_ROOT);
    $documentRoot = realpath((string) $_SERVER['DOCUMENT_ROOT']);
    if ($projectRoot && $documentRoot && str_starts_with($projectRoot, $documentRoot)) {
        $relativeRoot = str_replace('\\', '/', substr($projectRoot, strlen($documentRoot)));
        $configuredUrl = '/' . trim($relativeRoot, '/');
    }
}
define('APP_URL', rtrim((string) $configuredUrl, '/'));
define('SESSION_TIMEOUT', 60 * 60 * 8);
define('REMEMBER_COOKIE', 'examly_remember');
date_default_timezone_set((string) (getenv('APP_TIMEZONE') ?: 'Asia/Kolkata'));

function appUrl(string $path = ''): string
{
    $path = ltrim($path, '/');
    return APP_URL . ($path === '' ? '/' : '/' . $path);
}
