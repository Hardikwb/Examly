<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('student');
$pageTitle = 'My Results';
$dashboardMode = true;
$studentId = (int)AuthService::user()['id'];
$attempts = (new Attempt())->forStudent($studentId);
$publishedCount = 0;
require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1>My Results</h1>
        <p>Scores are shown after the teacher publishes an evaluated attempt.</p>
    </div><a class="button button-outline button-small" href="<?= e(appUrl('student/history.php')) ?>">Exam history</a>
</div>
<section class="panel">
    <div class="panel-header">
        <h2>Results and evaluations</h2>
    </div>
    <div class="panel-body flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Examination</th>
                        <th>Submitted</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Outcome</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody><?php foreach ($attempts as $attempt): $released = $attempt['status'] === 'evaluated' && (!$attempt['result_publish_at'] || new DateTimeImmutable($attempt['result_publish_at']) <= new DateTimeImmutable());
                            if ($released) $publishedCount++;
                            $percentage = $released && $attempt['total_marks'] ? (float)$attempt['obtained_marks'] / (float)$attempt['total_marks'] * 100 : 0;
                            $passed = $released && (float)$attempt['obtained_marks'] >= (float)$attempt['passing_marks']; ?><tr>
                            <td><strong><?= e($attempt['title']) ?></strong><br><small class="muted">Attempt <?= (int)$attempt['attempt_number'] ?></small></td>
                            <td><?= dateTime($attempt['submitted_at'] ?? $attempt['started_at']) ?></td>
                            <td><?= $released ? e(money($attempt['obtained_marks'])) . ' / ' . e(money($attempt['total_marks'])) : '—' ?></td>
                            <td><?= $released ? number_format($percentage, 1) . '%' : '—' ?></td>
                            <td><?php if ($released): ?><span class="badge <?= $passed ? 'badge-green' : 'badge-red' ?>"><?= $passed ? 'Passed' : 'Not passed' ?></span><?php elseif ($attempt['status'] === 'in_progress'): ?><span class="badge badge-amber">In progress</span><?php elseif ($attempt['status'] !== 'evaluated'): ?><span class="badge badge-amber">Awaiting evaluation</span><?php else: ?><span class="badge badge-blue">Not published</span><?php endif; ?></td>
                            <td><?php if ($released): ?><a class="button button-outline button-small" href="<?= e(appUrl('student/attempt-result.php?id=' . (int)$attempt['id'])) ?>">View result</a><?php elseif ($attempt['status'] === 'in_progress'): ?><a class="button button-primary button-small" href="<?= e(appUrl('student/exam.php?attempt=' . (int)$attempt['id'])) ?>">Continue</a><?php else: ?><span class="muted text-small">—</span><?php endif; ?></td>
                        </tr><?php endforeach; ?><?php if (!$attempts): ?><tr>
                            <td colspan="6">
                                <div class="empty-state">You haven’t taken an exam yet.</div>
                            </td>
                        </tr><?php endif; ?></tbody>
            </table>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>