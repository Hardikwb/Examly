<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('teacher');
$pageTitle = 'Manage Exams';
$dashboardMode = true;
$teacherId = (int)AuthService::user()['id'];
$model = new Exam();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    postOnly();
    $id = (int)($_POST['exam_id'] ?? 0);
    $exam = $model->find($id);
    if (!$exam || !$model->belongsToTeacher($id, $teacherId)) Response::notFound();
    $status = (string)($_POST['status'] ?? '');
    if (in_array($status, ['draft', 'scheduled', 'published', 'closed'], true)) {
        $questions = (new Database())->count('questions', ['exam_id' => $id]);
        if ($status === 'published' && $questions === 0) flashAndRedirect('error', 'Add at least one question before publishing this exam.', 'teacher/questions.php?exam=' . $id);
        if ($status === 'published' && (float)$exam['total_marks'] <= 0) flashAndRedirect('error', 'Published exams need questions worth more than zero marks.', 'teacher/questions.php?exam=' . $id);
        if ($status === 'published' && (float)$exam['passing_marks'] > (float)$exam['total_marks']) flashAndRedirect('error', 'Passing marks cannot exceed total marks. Edit exam settings before publishing.', 'teacher/exams.php');
        $wasPublished = $exam['status'] === 'published';
        $model->update($id, ['status' => $status]);
        audit('exam_status_' . $status, 'exams', $id);
        if ($status === 'published' && !$wasPublished) {
            $students = Database::connection()->prepare('SELECT student_id FROM class_students WHERE class_id=?');
            $students->execute([$exam['class_id']]);
            foreach ($students->fetchAll(PDO::FETCH_COLUMN) as $studentId) notifyUser((int)$studentId, 'New examination available', $exam['title'] . ' is now available in your exam list.');
        }
        Session::flash('success', 'Exam status updated.');
    }
    redirectTo('teacher/exams.php');
}

$exams = $model->forTeacher($teacherId);
require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Manage Exams</h1>
        <p>Create, schedule and publish your examinations.</p>
    </div><a class="button button-primary button-small" href="<?= e(appUrl('teacher/exam-form.php')) ?>">＋ Create exam</a>
</div>
<section class="panel">
    <div class="panel-header">
        <h2>Your examinations</h2><span class="badge badge-blue"><?= count($exams) ?> exams</span>
    </div>
    <div class="panel-body flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Exam</th>
                        <th>Class</th>
                        <th>Questions</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exams as $exam): ?>
                        <tr>
                            <td><strong><?= e($exam['title']) ?></strong><br><small class="muted"><?= e(money($exam['total_marks'])) ?> marks · <?= (int)$exam['duration_minutes'] ?> minutes</small></td>
                            <td><?= e($exam['class_name']) ?> <?= e($exam['section']) ?></td>
                            <td><?= (int)$exam['question_count'] ?></td>
                            <td><?= dateTime($exam['start_time'], 'M j, Y g:i A') ?><br><small class="muted">to <?= dateTime($exam['end_time'], 'M j, g:i A') ?></small></td>
                            <td><span class="badge badge-<?= $exam['status'] === 'published' ? 'green' : ($exam['status'] === 'closed' ? 'red' : ($exam['status'] === 'draft' ? 'amber' : 'blue')) ?>"><?= e(ucfirst($exam['status'])) ?></span></td>
                            <td>
                                <div class="table-actions"><a class="button button-outline button-small" href="<?= e(appUrl('teacher/questions.php?exam=' . (int)$exam['id'])) ?>">Questions</a><a class="button button-outline button-small" href="<?= e(appUrl('teacher/exam-form.php?id=' . (int)$exam['id'])) ?>">Edit</a>
                                    <form method="post" class="table-actions"><?= CSRF::field() ?><input type="hidden" name="exam_id" value="<?= (int)$exam['id'] ?>"><select name="status" aria-label="Set status for <?= e($exam['title']) ?>"><?php foreach (['draft', 'scheduled', 'published', 'closed'] as $s): ?><option value="<?= $s ?>" <?= selected($s, $exam['status']) ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select><button class="button button-primary button-small">Save</button></form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$exams): ?><tr>
                            <td colspan="6">
                                <div class="empty-state"><strong>No exams yet</strong>Create an exam draft and add questions to publish it.</div>
                            </td>
                        </tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>