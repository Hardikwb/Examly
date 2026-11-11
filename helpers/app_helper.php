<?php

declare(strict_types=1);

function e(mixed $value): string
{
    if (!is_scalar($value) && $value !== null) return '';
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(mixed $amount): string
{
    return number_format((float) $amount, 2);
}
function dateTime(mixed $value, string $format = 'M j, Y · g:i A'): string
{
    if (!$value) return '—';
    try {
        return (new DateTimeImmutable((string) $value))->format($format);
    } catch (Throwable) {
        return e($value);
    }
}

function redirectTo(string $path): void
{
    Response::redirect($path);
}
function postOnly(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
    CSRF::verify();
}

function audit(string $action, ?string $entityType = null, ?int $entityId = null): void
{
    try {
        (new Database())->insert('audit_logs', [
            'user_id' => AuthService::user()['id'] ?? null,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    } catch (Throwable $e) {
        error_log('Audit log could not be saved: ' . $e->getMessage());
    }
}

function notifyUser(int $userId, string $title, string $message): void
{
    (new Database())->insert('notifications', ['user_id' => $userId, 'title' => $title, 'message' => $message]);
}

function old(string $field, mixed $default = ''): string
{
    $value = $_POST[$field] ?? $default;
    return e(is_scalar($value) ? $value : $default);
}
function selected(mixed $value, mixed $current): string
{
    return (string) $value === (string) $current ? ' selected' : '';
}
function checked(mixed $value): string
{
    return $value ? ' checked' : '';
}

function optionList(array $items, string $valueKey, string $labelKey, mixed $current = null): string
{
    $html = '';
    foreach ($items as $item) $html .= '<option value="' . e($item[$valueKey]) . '"' . selected($item[$valueKey], $current) . '>' . e($item[$labelKey]) . '</option>';
    return $html;
}

function userInitials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $first = preg_split('//u', $parts[0] ?? 'E', -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $second = preg_split('//u', $parts[1] ?? '', -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return strtoupper(($first[0] ?? 'E') . ($second[0] ?? ''));
}

function flashAndRedirect(string $type, string $message, string $path): void
{
    Session::flash($type, $message);
    redirectTo($path);
}
