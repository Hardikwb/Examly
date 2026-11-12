<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('teacher');
$pageTitle = 'Dashboard';
$dashboardMode = true;
$teacherId = (int)AuthService::user()['id'];
$pdo = Database::connection();
$db = new Database();
$examCount = $db->count('exams', ['teacher_id' => $teacherId]);
$q = $pdo->prepare('SELECT COUNT(*) FROM questions q JOIN exams e ON e.id=q.exam_id WHERE e.teacher_id=?');
$q->execute([$teacherId]);
$questionCount = (int)$q->fetchColumn();
$s = $pdo->prepare('SELECT COUNT(DISTINCT a.student_id) FROM exam_attempts a JOIN exams e ON e.id=a.exam_id WHERE e.teacher_id=?');
$s->execute([$teacherId]);
$studentCount = (int)$s->fetchColumn();
$upcoming = (new Exam())->forTeacher($teacherId);
$upcoming = array_values(array_filter($upcoming, fn($e) => $e['status'] !== 'closed' && new DateTimeImmutable($e['start_time']) > new DateTimeImmutable()));
$recent = (new Exam())->forTeacher($teacherId);
$recent = array_slice($recent, 0, 5);
$results = (new Result())->forTeacher($teacherId);
$evaluated = array_values(array_filter($results, fn($r) => $r['status'] === 'evaluated' && $r['obtained_marks'] !== null));
$average = $evaluated ? array_sum(array_map(fn($r) => (float)$r['obtained_marks'] / (max(1, (float)$r['total_marks'])) * 100, $evaluated)) / count($evaluated) : 0;
$average = max(0, min(100, $average));
require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Teacher Dashboard</h1>
        <p>Welcome back, <?= e(AuthService::user()['name']) ?>. Here’s your assessment overview.</p>
    </div>
    <div class="page-heading-actions"><a class="button button-primary button-small" href="<?= e(appUrl('teacher/exam-form.php')) ?>">＋ Create exam</a></div>
</div>
<section class="dashboard-grid">
    <article class="metric-card"><span class="metric-icon">▣</span>
        <div class="metric-copy"><strong><?= $examCount ?></strong><span>Total examinations</span></div>
    </article>
    <article class="metric-card"><span class="metric-icon teal">☷</span>
        <div class="metric-copy"><strong><?= $questionCount ?></strong><span>Total questions</span></div>
    </article>
    <article class="metric-card"><span class="metric-icon purple">♙</span>
        <div class="metric-copy"><strong><?= $studentCount ?></strong><span>Students attempted</span></div>
    </article>
    <article class="metric-card"><span class="metric-icon amber">◉</span>
        <div class="metric-copy"><strong><?= count($upcoming) ?></strong><span>Upcoming examinations</span></div>
    </article>
</section>
<section class="content-columns">
    <div>
        <article class="panel">
            <div class="panel-header">
                <h2>Your recent exams</h2><a href="<?= e(appUrl('teacher/exams.php')) ?>">View all →</a>
            </div>
            <div class="panel-body">
                <div class="activity-list"><?php foreach ($recent as $exam): ?><div class="exam-row"><span class="exam-row-icon">▣</span>
                            <div class="exam-row-main"><strong><?= e($exam['title']) ?></strong><small><?= e($exam['class_name']) ?> <?= e($exam['section']) ?> · <?= dateTime($exam['start_time'], 'M j, Y · g:i A') ?></small></div><span class="badge <?= $exam['status'] === 'published' ? 'badge-green' : ($exam['status'] === 'draft' ? 'badge-amber' : 'badge-blue') ?>"><?= e(ucfirst($exam['status'])) ?></span>
                        </div><?php endforeach; ?><?php if (!$recent): ?><div class="empty-state">Create your first exam to get started.</div><?php endif; ?></div>
            </div>
        </article>
        <article class="panel">
            <div class="panel-header">
                <h2>Upcoming examinations</h2><a href="<?= e(appUrl('teacher/exams.php')) ?>">Manage →</a>
            </div>
            <div class="panel-body">
                <div class="exam-list"><?php foreach (array_slice($upcoming, 0, 4) as $exam): ?><div class="exam-row"><span class="exam-row-icon">◷</span>
                            <div class="exam-row-main"><strong><?= e($exam['title']) ?></strong><small><?= dateTime($exam['start_time']) ?> · <?= e($exam['class_name']) ?></small></div><span class="badge badge-blue"><?= e(ucfirst($exam['status'])) ?></span>
                        </div><?php endforeach; ?><?php if (!$upcoming): ?><div class="empty-state">No upcoming examinations.</div><?php endif; ?></div>
            </div>
        </article>
    </div>
    <aside>
        <article class="panel">
            <div class="panel-header">
                <h2>Performance overview</h2>
            </div>
            <div class="panel-body">
                <div class="performance-flex">
                    <div class="score-ring" style="--score:<?= (int)round($average) ?>"><strong><?= (int)round($average) ?>%</strong><small>average</small></div>
                    <div class="legend"><span><i></i>Average published scores</span><span><i></i><?= count($evaluated) ?> evaluated attempts</span></div>
                </div>
            </div>
        </article>
        <article class="panel">
            <div class="panel-header">
                <h2>Quick actions</h2>
            </div>
            <div class="panel-body">
                <div class="quick-actions"><a class="quick-action primary" href="<?= e(appUrl('teacher/exam-form.php')) ?>">＋ Create examination <span>→</span></a><a class="quick-action" href="<?= e(appUrl('teacher/exams.php')) ?>">☷ Manage questions <span>→</span></a><a class="quick-action" href="<?= e(appUrl('teacher/results.php')) ?>">◉ Review results <span>→</span></a><a class="quick-action" href="<?= e(appUrl('teacher/classes.php')) ?>">▦ View my classes <span>→</span></a></div>
            </div>
        </article>
    </aside>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>