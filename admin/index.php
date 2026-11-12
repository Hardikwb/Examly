<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('admin');
$pageTitle = 'Dashboard';
$dashboardMode = true;
$db = new Database();
$pdo = Database::connection();
$stats = ['students' => $db->count('users', ['role' => 'student', 'status' => 'active']), 'teachers' => $db->count('users', ['role' => 'teacher', 'status' => 'active']), 'classes' => $db->count('classes'), 'exams' => $db->count('exams')];
$activities = $pdo->query('SELECT al.*,u.name user_name FROM audit_logs al LEFT JOIN users u ON u.id=al.user_id ORDER BY al.created_at DESC LIMIT 6')->fetchAll();
$recent = (new Exam())->adminRecent(5);
require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Admin Dashboard</h1>
        <p>Here’s what is happening across your learning space.</p>
    </div>
    <div class="page-heading-actions"><a class="button button-primary button-small" href="<?= e(appUrl('admin/users.php?role=student')) ?>">＋ Add student</a><a class="button button-outline button-small" href="<?= e(appUrl('admin/classes.php')) ?>">Manage classes</a></div>
</div>
<section class="dashboard-grid">
    <article class="metric-card"><span class="metric-icon">♙</span>
        <div class="metric-copy"><strong><?= number_format($stats['students']) ?></strong><span>Total students</span></div>
    </article>
    <article class="metric-card"><span class="metric-icon teal">♟</span>
        <div class="metric-copy"><strong><?= number_format($stats['teachers']) ?></strong><span>Total teachers</span></div>
    </article>
    <article class="metric-card"><span class="metric-icon purple">▦</span>
        <div class="metric-copy"><strong><?= number_format($stats['classes']) ?></strong><span>Total classes</span></div>
    </article>
    <article class="metric-card"><span class="metric-icon amber">▣</span>
        <div class="metric-copy"><strong><?= number_format($stats['exams']) ?></strong><span>Total exams</span></div>
    </article>
</section>
<section class="content-columns">
    <div>
        <article class="panel">
            <div class="panel-header">
                <h2>Recent examinations</h2><a href="<?= e(appUrl('admin/exams.php')) ?>">View all →</a>
            </div>
            <div class="panel-body">
                <div class="activity-list">
                    <?php foreach ($recent as $exam): ?><div class="exam-row"><span class="exam-row-icon">▣</span>
                            <div class="exam-row-main"><strong><?= e($exam['title']) ?></strong><small><?= e($exam['class_name']) ?> · <?= e($exam['teacher_name']) ?> · <?= dateTime($exam['start_time'], 'M j, Y') ?></small></div><span class="badge <?= $exam['status'] === 'published' ? 'badge-green' : ($exam['status'] === 'draft' ? 'badge-amber' : 'badge-blue') ?>"><?= e(ucfirst($exam['status'])) ?></span>
                        </div><?php endforeach; ?>
                    <?php if (!$recent): ?><div class="empty-state">No examinations have been created yet.</div><?php endif; ?>
                </div>
            </div>
        </article>
        <article class="panel">
            <div class="panel-header">
                <h2>Recent activities</h2><a href="<?= e(appUrl('admin/reports.php#activity')) ?>">View report →</a>
            </div>
            <div class="panel-body">
                <div class="activity-list">
                    <?php foreach ($activities as $activity): ?><div class="activity-item"><span class="activity-symbol">◉</span>
                            <div><strong><?= e(ucwords(str_replace('_', ' ', $activity['action']))) ?><?= $activity['user_name'] ? ' · ' . e($activity['user_name']) : '' ?></strong><small><?= e($activity['entity_type'] ?? 'system') ?><?= $activity['entity_id'] ? ' #' . (int)$activity['entity_id'] : '' ?> · <?= dateTime($activity['created_at']) ?></small></div>
                        </div><?php endforeach; ?>
                    <?php if (!$activities): ?><div class="empty-state">Activity will appear here as your team uses Examly.</div><?php endif; ?>
                </div>
            </div>
        </article>
    </div>
    <aside>
        <article class="panel">
            <div class="panel-header">
                <h2>Quick actions</h2>
            </div>
            <div class="panel-body">
                <div class="quick-actions"><a class="quick-action primary" href="<?= e(appUrl('admin/users.php?role=student')) ?>">＋ Create student account <span>→</span></a><a class="quick-action" href="<?= e(appUrl('admin/users.php?role=teacher')) ?>">♟ Add a teacher <span>→</span></a><a class="quick-action" href="<?= e(appUrl('admin/classes.php')) ?>">▦ Create a class <span>→</span></a><a class="quick-action" href="<?= e(appUrl('admin/subjects.php')) ?>">▤ Manage subjects <span>→</span></a><a class="quick-action" href="<?= e(appUrl('admin/reports.php')) ?>">▥ View reports <span>→</span></a></div>
            </div>
        </article>
        <article class="panel">
            <div class="panel-header">
                <h2>System overview</h2>
            </div>
            <div class="panel-body">
                <div class="notice-card">Examly is ready. Add users to classes, assign teachers and schedule examinations from the admin workspace.</div>
                <p class="stat-caption">The statistics above are live counts from your database.</p>
            </div>
        </article>
    </aside>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>