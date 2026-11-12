<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('student');
$pageTitle = 'Available Exams';
$dashboardMode = true;
$studentId = (int)AuthService::user()['id'];
$schedule = (new Exam())->studentSchedule($studentId);
$attemptModel = new Attempt();
$now = new DateTimeImmutable();
require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Available Exams</h1>
        <p>View exams for your classes and continue any attempt in progress.</p>
    </div>
</div>
<section class="panel">
    <div class="panel-header">
        <h2>Exam schedule</h2><span class="badge badge-blue"><?= count($schedule) ?> exams</span>
    </div>
    <div class="panel-body flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Examination</th>
                        <th>Teacher</th>
                        <th>Schedule</th>
                        <th>Duration</th>
                        <th>Attempts</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody><?php foreach ($schedule as $exam): $current = $attemptModel->currentForExam((int)$exam['id'], $studentId);
                            $hasRemaining = (int)$exam['attempts_used'] < (int)$exam['max_attempts'];
                            $isOpen = $exam['status'] === 'published' && $now >= new DateTimeImmutable($exam['start_time']) && $now <= new DateTimeImmutable($exam['end_time']); ?><tr>
                            <td><strong><?= e($exam['title']) ?></strong><br><small class="muted"><?= e($exam['class_name']) ?> <?= e($exam['section']) ?> · <?= (int)$exam['question_count'] ?> questions</small></td>
                            <td><?= e($exam['teacher_name']) ?></td>
                            <td><?= dateTime($exam['start_time'], 'M j, Y g:i A') ?><br><small class="muted">until <?= dateTime($exam['end_time'], 'M j, g:i A') ?></small></td>
                            <td><?= (int)$exam['duration_minutes'] ?> minutes</td>
                            <td><?= (int)$exam['attempts_used'] ?> / <?= (int)$exam['max_attempts'] ?></td>
                            <td><?php if ($current): ?><span class="badge badge-amber">In progress</span><?php elseif ($isOpen): ?><span class="badge badge-green">Available</span><?php elseif ($exam['status'] === 'scheduled' || new DateTimeImmutable($exam['start_time']) > $now): ?><span class="badge badge-blue">Upcoming</span><?php elseif (!$hasRemaining): ?><span class="badge badge-red">Attempts used</span><?php else: ?><span class="badge">Closed</span><?php endif; ?></td>
                            <td><?php if ($current): ?><a class="button button-primary button-small" href="<?= e(appUrl('student/exam.php?attempt=' . (int)$current['id'])) ?>">Continue</a><?php elseif ($isOpen && $hasRemaining): ?><form method="post" action="<?= e(appUrl('student/start.php')) ?>"><?= CSRF::field() ?><input type="hidden" name="exam_id" value="<?= (int)$exam['id'] ?>"><button class="button button-primary button-small">Start exam</button></form><?php else: ?><span class="muted text-small">—</span><?php endif; ?></td>
                        </tr><?php endforeach; ?><?php if (!$schedule): ?><tr>
                            <td colspan="7">
                                <div class="empty-state"><strong>No examinations scheduled</strong>Ask your administrator to enroll you in a class.</div>
                            </td>
                        </tr><?php endif; ?></tbody>
            </table>
        </div>
    </div>
</section>
<p class="muted text-small">Your attempt timer is based on server time. It is refreshed when you navigate or save a response.</p>
<?php require APP_ROOT . '/components/footer.php'; ?>