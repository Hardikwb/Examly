<?php

declare(strict_types=1);

final class NotificationService
{
    public static function recent(int $userId, int $limit = 6): array
    {
        $s = Database::connection()->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT ' . max(1, $limit));
        $s->execute([$userId]);
        return $s->fetchAll();
    }
    public static function markRead(int $notificationId, int $userId): void
    {
        $s = Database::connection()->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?');
        $s->execute([$notificationId, $userId]);
    }
}
