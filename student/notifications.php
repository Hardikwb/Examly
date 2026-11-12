<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('student');
$userId = (int)AuthService::user()['id'];
$pageTitle = 'Notifications';
$dashboardMode = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    postOnly();
    $id = (int)($_POST['notification_id'] ?? 0);
    NotificationService::markRead($id, $userId);
    Session::flash('success', 'Notification marked as read.');
    redirectTo('student/notifications.php');
}
$stmt = Database::connection()->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();
require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Notifications</h1>
        <p>Exam and account updates for you.</p>
    </div>
</div>
<section class="panel">
    <div class="panel-header">
        <h2>Recent updates</h2>
    </div>
    <div class="panel-body">
        <div class="activity-list"><?php foreach ($notifications as $notification): ?><article class="activity-item"><span class="activity-symbol"><?= $notification['is_read'] ? '✓' : '●' ?></span>
                    <div style="flex:1"><strong><?= e($notification['title']) ?></strong>
                        <p style="margin:3px 0;color:var(--ink-700)"><?= nl2br(e($notification['message'])) ?></p><small><?= dateTime($notification['created_at']) ?></small>
                    </div><?php if (!$notification['is_read']): ?><form method="post"><?= CSRF::field() ?><input type="hidden" name="notification_id" value="<?= (int)$notification['id'] ?>"><button class="button button-outline button-small">Mark read</button></form><?php endif; ?>
                </article><?php endforeach; ?><?php if (!$notifications): ?><div class="empty-state"><strong>No notifications yet</strong>New exam announcements and updates will appear here.</div><?php endif; ?></div>
    </div>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>