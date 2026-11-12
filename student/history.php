<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('student');
$pageTitle = 'Exam History';
$dashboardMode = true;
$attempts = (new Attempt())->forStudent((int)AuthService::user()['id']);
require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Exam History</h1>
        <p>Past and in-progress examinations linked to your account.</p>
    </div>
</div>
<section class="panel">
    <div class="panel-header">
        <h2>Attempts</h2><span class="badge badge-blue"><?= count($attempts) ?> attempts</span>
    </div>
    <div class="panel-body flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Examination</th>
                        <th>Attempt</th>
                        <th>Started</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody><?php foreach ($attempts as $attempt): ?><tr>
                            <td><strong><?= e($attempt['title']) ?></strong></td>
                            <td><?= (int)$attempt['attempt_number'] ?></td>
                            <td><?= dateTime($attempt['started_at']) ?></td>
                            <td><?= dateTime($attempt['submitted_at']) ?></td>
                            <td><span class="badge badge-<?= $attempt['status'] === 'evaluated' ? 'green' : ($attempt['status'] === 'in_progress' ? 'amber' : 'blue') ?>"><?= e(ucwords(str_replace('_', ' ', $attempt['status']))) ?></span></td>
                            <td><?php if ($attempt['status'] === 'in_progress'): ?><a class="button button-primary button-small" href="<?= e(appUrl('student/exam.php?attempt=' . (int)$attempt['id'])) ?>">Continue</a><?php elseif ($attempt['status'] === 'evaluated'): ?><a class="button button-outline button-small" href="<?= e(appUrl('student/attempt-result.php?id=' . (int)$attempt['id'])) ?>">Result</a><?php else: ?><span class="muted text-small">Awaiting review</span><?php endif; ?></td>
                        </tr><?php endforeach; ?><?php if (!$attempts): ?><tr>
                            <td colspan="6">
                                <div class="empty-state">Your examination history will appear here.</div>
                            </td>
                        </tr><?php endif; ?></tbody>
            </table>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>