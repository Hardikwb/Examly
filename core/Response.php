<?php
declare(strict_types=1);

final class Response
{
    public static function redirect(string $location): void
    {
        header('Location: ' . appUrl($location));
        exit;
    }

    public static function notFound(string $message = 'The requested page could not be found.'): void
    {
        http_response_code(404);
        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        exit;
    }
}
