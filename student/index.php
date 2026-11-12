<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('student');
$pageTitle = 'Dashboard';
$dashboardMode = true;
$studentId = (int)AuthService::user()['id'];
$schedule = (new Exam())->studentSchedule($studentId);
$attempts = new Attempt();
$recent = $attempts->latestResults($studentId, 4);
$allAttempts = $attempts->forStudent($studentId);
$published = array_values(array_filter($allAttempts, fn($a) => $a['status'] === 'evaluated' && (!$a['result_publish_at'] || new DateTimeImmutable($a['result_publish_at']) <= new DateTimeImmutable())));
$average = $published ? array_sum(array_map(fn($a) => (float)$a['obtained_marks'] / max(1, (float)$a['total_marks']) * 100, $published)) / count($published) : 0;
$activeCount = 0;
$upcoming = [];
$available = [];
$now = new DateTimeImmutable();
foreach ($schedule as $exam) {
    $current = $attempts->currentForExam((int)$exam['id'], $studentId);
    $hasRemaining = (int)$exam['attempts_used'] < (int)$exam['max_attempts'];
    $canStart = $exam['status'] === 'published' && $now >= new DateTimeImmutable($exam['start_time']) && $now <= new DateTimeImmutable($exam['end_time']) && $hasRemaining;
    if ($current) $activeCount++;
    if ($exam['status'] === 'scheduled' || new DateTimeImmutable($exam['start_time']) > $now) $upcoming[] = $exam;
    if ($current || $canStart) $available[] = $exam;
}
$average = max(0, min(100, $average));
require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Student Dashboard</h1>
        <p>Your examinations and recent results at a glance.</p>
    </div><a class="button button-outline button-small" href="<?= e(appUrl('student/notifications.php')) ?>">Notifications</a>
</div>
<div class="welcome-banner"><span class="welcome-trophy">★</span>
    <div>
        <h2>Welcome back, <?= e(explode(' ', AuthService::user()['name'])[0]) ?>!</h2>
        <p>Keep going. You’re doing great. Check your upcoming assessments below.</p>
    </div>
</div>
<section class="dashboard-grid">
    <article class="metric-card"><span class="metric-icon">▣</span>
        <div class="metric-copy"><strong><?= count($schedule) ?></strong><span>Examinations</span></div>
    </article>
    <article class="metric-card"><span class="metric-icon teal">◷</span>
        <div class="metric-copy"><strong><?= $activeCount ?></strong><span>In progress</span></div>
    </article>
    <article class="metric-card"><span class="metric-icon purple">◉</span>
        <div class="metric-copy"><strong><?= count($published) ?></strong><span>Published results</span></div>
    </article>
    <article class="metric-card"><span class="metric-icon amber">★</span>
        <div class="metric-copy"><strong><?= (int)round($average) ?>%</strong><span>Overall performance</span></div>
    </article>
</section>
<section class="content-columns">
    <div>
        <article class="panel">
            <div class="panel-header">
                <h2>Available examinations</h2><a href="<?= e(appUrl('student/exams.php')) ?>">View all →</a>
            </div>
            <div class="panel-body">
                <div class="activity-list"><?php foreach (array_slice($available, 0, 5) as $exam): $current = $attempts->currentForExam((int)$exam['id'], $studentId); ?><div class="exam-row"><span class="exam-row-icon">▣</span>
                            <div class="exam-row-main"><strong><?= e($exam['title']) ?></strong><small><?= dateTime($exam['start_time'], 'M j, Y · g:i A') ?> · <?= (int)$exam['duration_minutes'] ?> min</small></div><?php if ($current): ?><a class="button button-primary button-small" href="<?= e(appUrl('student/exam.php?attempt=' . (int)$current['id'])) ?>">Continue</a><?php else: ?><a class="button button-primary button-small" href="<?= e(appUrl('student/exams.php')) ?>">Start</a><?php endif; ?>
                        </div><?php endforeach; ?><?php if (!$available): ?><div class="empty-state"><strong>No exams available right now</strong>Your enrolled class exams will appear here.</div><?php endif; ?></div>
            </div>
        </article>
        <article class="panel">
            <div class="panel-header">
                <h2>Upcoming examinations</h2><a href="<?= e(appUrl('student/exams.php')) ?>">View schedule →</a>
            </div>
            <div class="panel-body">
                <div class="activity-list"><?php foreach (array_slice($upcoming, 0, 4) as $exam): ?><div class="exam-row"><span class="exam-row-icon">◷</span>
                            <div class="exam-row-main"><strong><?= e($exam['title']) ?></strong><small><?= dateTime($exam['start_time']) ?> · <?= e($exam['teacher_name']) ?></small></div><span class="badge badge-blue"><?= e(ucfirst($exam['status'])) ?></span>
                        </div><?php endforeach; ?><?php if (!$upcoming): ?><div class="empty-state">No upcoming examinations.</div><?php endif; ?></div>
            </div>
        </article>
    </div>
    <aside>
        <article class="panel">
            <div class="panel-header">
                <h2>Your performance</h2><a href="<?= e(appUrl('student/results.php')) ?>">Results →</a>
            </div>
            <div class="panel-body">
                <div class="performance-flex">
                    <div class="score-ring" style="--score:<?= (int)round($average) ?>"><strong><?= (int)round($average) ?>%</strong><small>overall</small></div>
                    <div class="legend"><span><i></i>Published score average</span><span><i></i><?= count($published) ?> results included</span></div>
                </div>
            </div>
        </article>
        <article class="panel">
            <div class="panel-header">
                <h2>Recent results</h2><a href="<?= e(appUrl('student/results.php')) ?>">View all →</a>
            </div>
            <div class="panel-body">
                <div class="activity-list"><?php foreach ($recent as $result): $pass = (float)$result['obtained_marks'] >= (float)$result['passing_marks']; ?><div class="exam-row"><span class="exam-row-icon <?= $pass ? '' : 'metric-icon red' ?>"><?= $pass ? '✓' : '!' ?></span>
                            <div class="exam-row-main"><strong><?= e($result['title']) ?></strong><small><?= e(money($result['obtained_marks'])) ?> / <?= e(money($result['total_marks'])) ?> · <?= dateTime($result['submitted_at'], 'M j, Y') ?></small></div><span class="badge <?= $pass ? 'badge-green' : 'badge-red' ?>"><?= $pass ? 'Passed' : 'Not passed' ?></span>
                        </div><?php endforeach; ?><?php if (!$recent): ?><div class="empty-state">Your released results will appear here.</div><?php endif; ?></div>
            </div>
        </article>
    </aside>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>