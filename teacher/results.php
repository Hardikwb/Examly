<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('teacher');
$pageTitle = 'View Results';
$dashboardMode = true;
$rows = (new Result())->forTeacher((int)AuthService::user()['id']);
require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1>View Results</h1>
        <p>Review attempts, scores and descriptive answers awaiting grading.</p>
    </div>
</div>
<section class="panel">
    <div class="panel-header">
        <h2>Student attempts</h2><span class="badge badge-blue"><?= count($rows) ?> attempts</span>
    </div>
    <div class="panel-body flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Exam</th>
                        <th>Class</th>
                        <th>Attempt</th>
                        <th>Score</th>
                        <th>Release</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($rows as $row): $published = $row['status'] === 'evaluated' && (!$row['result_publish_at'] || new DateTimeImmutable($row['result_publish_at']) <= new DateTimeImmutable());
                            $needsGrading = in_array($row['status'], ['submitted', 'auto_submitted'], true); ?><tr>
                            <td><strong><?= e($row['student_name']) ?></strong><br><small class="muted"><?= e($row['student_email']) ?></small></td>
                            <td><?= e($row['title']) ?></td>
                            <td><?= e($row['class_name']) ?></td>
                            <td><span class="badge badge-<?= $needsGrading ? 'amber' : ($row['status'] === 'evaluated' ? 'green' : 'blue') ?>"><?= e(ucwords(str_replace('_', ' ', $row['status']))) ?></span><br><small class="muted"><?= dateTime($row['submitted_at'], 'M j, Y g:i A') ?></small></td>
                            <td><?= $row['obtained_marks'] === null ? '—' : e(money($row['obtained_marks'])) . ' / ' . e(money($row['total_marks'])) ?></td>
                            <td><?= $published ? '<span class="badge badge-green">Published</span>' : dateTime($row['result_publish_at'], 'M j, Y g:i A') ?></td>
                            <td>
                                <div class="table-actions"><?php if ($needsGrading): ?><a class="button button-primary button-small" href="<?= e(appUrl('teacher/grade.php?attempt=' . (int)$row['id'])) ?>">Grade</a><?php else: ?><a class="button button-outline button-small" href="<?= e(appUrl('teacher/grade.php?attempt=' . (int)$row['id'])) ?>">Details</a><?php endif; ?></div>
                            </td>
                        </tr><?php endforeach; ?><?php if (!$rows): ?><tr>
                            <td colspan="7">
                                <div class="empty-state">No student attempts have been submitted yet.</div>
                            </td>
                        </tr><?php endif; ?></tbody>
            </table>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>