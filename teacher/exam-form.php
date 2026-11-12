<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('teacher');
$teacherId = (int)AuthService::user()['id'];
$examId = (int)($_GET['id'] ?? $_POST['exam_id'] ?? 0);
$model = new Exam();
$existing = $examId ? $model->find($examId) : null;
if ($examId && (!$existing || !$model->belongsToTeacher($examId, $teacherId))) Response::notFound();
$assigned = Database::connection()->prepare('SELECT c.id,c.class_name,c.section,s.subject_name FROM class_teachers ct JOIN classes c ON c.id=ct.class_id JOIN subjects s ON s.id=ct.subject_id WHERE ct.teacher_id=? ORDER BY c.class_name,s.subject_name');
$assigned->execute([$teacherId]);
$classes = $assigned->fetchAll();
$pageTitle = $existing ? 'Edit Exam' : 'Create Exam';
$dashboardMode = true;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    postOnly();
    $validator = (new Validator())
        ->required($_POST, 'title', 'Title')->length($_POST, 'title', 'Title', 3, 180)
        ->required($_POST, 'class_id', 'Class')
        ->numericRange($_POST, 'duration_minutes', 'Duration', 1, 600)
        ->numericRange($_POST, 'total_marks', 'Total marks', 1, 100000)
        ->numericRange($_POST, 'passing_marks', 'Passing marks', 0, 100000)
        ->numericRange($_POST, 'max_attempts', 'Maximum attempts', 1, 10);
    $startRaw = (string)($_POST['start_time'] ?? '');
    $endRaw = (string)($_POST['end_time'] ?? '');
    $start = $startRaw !== '' ? strtotime($startRaw) : false;
    $end = $endRaw !== '' ? strtotime($endRaw) : false;
    if ($start === false || $end === false || $end <= $start) $validator->add('end_time', 'End time must be after start time.');
    if ((float)($_POST['passing_marks'] ?? 0) > (float)($_POST['total_marks'] ?? 0)) $validator->add('passing_marks', 'Passing marks cannot exceed total marks.');
    $classId = (int)($_POST['class_id'] ?? 0);
    $validClass = false;
    foreach ($classes as $class) if ((int)$class['id'] === $classId) $validClass = true;
    if (!$validClass) $validator->add('class_id', 'Choose a class assigned to you.');
    $resultRaw = trim((string)($_POST['result_publish_at'] ?? ''));
    $publishAt = $resultRaw !== '' ? strtotime($resultRaw) : null;
    if ($resultRaw !== '' && $publishAt === false) $validator->add('result_publish_at', 'Enter a valid result publication date.');
    if ($existing && (new Database())->count('exam_attempts', ['exam_id' => $examId]) > 0 && (int)$existing['class_id'] !== $classId) $validator->add('class_id', 'The class cannot change after students have started an attempt.');
    $status = (string)($_POST['status'] ?? 'draft');
    if (!in_array($status, ['draft', 'scheduled', 'published', 'closed'], true)) $status = 'draft';
    $errors = $validator->errors();

    if (!$errors) {
        $data = [
            'title' => trim((string)$_POST['title']),
            'description' => trim((string)($_POST['description'] ?? '')) ?: null,
            'teacher_id' => $teacherId,
            'class_id' => $classId,
            'start_time' => date('Y-m-d H:i:s', (int)$start),
            'end_time' => date('Y-m-d H:i:s', (int)$end),
            'duration_minutes' => (int)$_POST['duration_minutes'],
            'total_marks' => (float)$_POST['total_marks'],
            'passing_marks' => (float)$_POST['passing_marks'],
            'max_attempts' => (int)$_POST['max_attempts'],
            'shuffle_questions' => isset($_POST['shuffle_questions']) ? 1 : 0,
            'shuffle_options' => isset($_POST['shuffle_options']) ? 1 : 0,
            'status' => $existing ? $status : 'draft',
            'result_publish_at' => $publishAt ? date('Y-m-d H:i:s', $publishAt) : null,
        ];
        $questionCount = $existing ? (new Database())->count('questions', ['exam_id' => $examId]) : 0;
        if ($existing && $questionCount > 0) {
            $sum = Database::connection()->prepare('SELECT COALESCE(SUM(marks),0) FROM questions WHERE exam_id=?');
            $sum->execute([$examId]);
            $data['total_marks'] = (float)$sum->fetchColumn();
        }
        if ($data['status'] === 'published' && $questionCount === 0) $errors[] = 'Add at least one question before publishing.';
        if ($data['status'] === 'published' && $data['total_marks'] <= 0) $errors[] = 'Published exams need questions worth more than zero marks.';
        if ($data['status'] === 'published' && $data['passing_marks'] > $data['total_marks']) $errors[] = 'Passing marks cannot exceed the total marks available in the questions.';
        if (!$errors) {
            try {
                if ($existing) {
                    $wasPublished = $existing['status'] === 'published';
                    $model->update($examId, $data);
                    audit('exam_updated', 'exams', $examId);
                    if ($data['status'] === 'published' && !$wasPublished) {
                        $students = Database::connection()->prepare('SELECT student_id FROM class_students WHERE class_id=?');
                        $students->execute([$classId]);
                        foreach ($students->fetchAll(PDO::FETCH_COLUMN) as $studentId) notifyUser((int)$studentId, 'New examination available', $data['title'] . ' is now available in your exam list.');
                    }
                    Session::flash('success', 'Examination settings saved.');
                    redirectTo('teacher/exams.php');
                }
                $newId = $model->create($data);
                audit('exam_created', 'exams', $newId);
                Session::flash('success', 'Exam draft created. Add questions, then publish it when it is ready.');
                redirectTo('teacher/questions.php?exam=' . $newId);
            } catch (Throwable $e) {
                error_log('Exam save failed: ' . $e->getMessage());
                $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Exam settings could not be saved. Check the class and schedule.';
            }
        }
    }
}

require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <p><?= $existing ? 'Update the schedule and settings for this exam.' : 'Start with a draft. Add questions before publishing.' ?></p>
    </div><a class="button button-outline button-small" href="<?= e(appUrl('teacher/exams.php')) ?>">← Back to exams</a>
</div>
<?php if (!$classes): ?>
    <div class="notice-card">No classes are assigned to your account. Ask an administrator to assign you a class and subject before creating an exam.</div>
<?php else: ?>
    <section class="panel">
        <div class="panel-body">
            <?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?>
            <form method="post" class="form-stack">
                <?= CSRF::field() ?>
                <?php if ($existing): ?><input type="hidden" name="exam_id" value="<?= (int)$examId ?>"><?php endif; ?>
                <div class="form-grid">
                    <div class="field"><label for="title">Exam title</label><input id="title" name="title" required minlength="3" maxlength="180" value="<?= old('title', $existing['title'] ?? '') ?>"></div>
                    <div class="field"><label for="class_id">Class and subject</label><select id="class_id" name="class_id" required>
                            <option value="">Choose assigned class</option><?php foreach ($classes as $class): ?><option value="<?= (int)$class['id'] ?>" <?= selected($_POST['class_id'] ?? ($existing['class_id'] ?? ''), $class['id']) ?>><?= e($class['class_name']) ?> <?= e($class['section']) ?> · <?= e($class['subject_name']) ?></option><?php endforeach; ?>
                        </select></div>
                </div>
                <div class="field"><label for="description">Instructions and description</label><textarea id="description" name="description" maxlength="4000"><?= old('description', $existing['description'] ?? '') ?></textarea></div>
                <div class="form-grid">
                    <div class="field"><label for="start_time">Starts</label><input id="start_time" name="start_time" type="datetime-local" required value="<?= e(date('Y-m-d\TH:i', strtotime($_POST['start_time'] ?? ($existing['start_time'] ?? '+1 day')))) ?>"></div>
                    <div class="field"><label for="end_time">Ends</label><input id="end_time" name="end_time" type="datetime-local" required value="<?= e(date('Y-m-d\TH:i', strtotime($_POST['end_time'] ?? ($existing['end_time'] ?? '+2 days')))) ?>"></div>
                </div>
                <div class="form-grid">
                    <div class="field"><label for="duration_minutes">Duration (minutes)</label><input id="duration_minutes" name="duration_minutes" type="number" required min="1" max="600" value="<?= old('duration_minutes', $existing['duration_minutes'] ?? 60) ?>"></div>
                    <div class="field"><label for="max_attempts">Maximum attempts</label><input id="max_attempts" name="max_attempts" type="number" required min="1" max="10" value="<?= old('max_attempts', $existing['max_attempts'] ?? 1) ?>"></div>
                    <div class="field"><label for="total_marks">Total marks</label><input id="total_marks" name="total_marks" type="number" required min="1" max="100000" step="0.25" value="<?= old('total_marks', $existing['total_marks'] ?? 10) ?>"><span class="field-help">The total recalculates from question marks once questions are added.</span></div>
                    <div class="field"><label for="passing_marks">Passing marks</label><input id="passing_marks" name="passing_marks" type="number" required min="0" max="100000" step="0.25" value="<?= old('passing_marks', $existing['passing_marks'] ?? 5) ?>"></div>
                </div>
                <div class="form-grid">
                    <div class="field"><label for="result_publish_at">Publish results at (optional)</label><input id="result_publish_at" name="result_publish_at" type="datetime-local" value="<?= e(!empty($existing['result_publish_at']) ? date('Y-m-d\TH:i', strtotime($existing['result_publish_at'])) : '') ?>"><span class="field-help">Leave blank to publish immediately after evaluation.</span></div>
                    <?php if ($existing): ?><div class="field"><label for="status">Exam status</label><select id="status" name="status"><?php foreach (['draft', 'scheduled', 'published', 'closed'] as $value): ?><option value="<?= $value ?>" <?= selected($_POST['status'] ?? $existing['status'], $value) ?>><?= ucfirst($value) ?></option><?php endforeach; ?></select><span class="field-help">Publish only after adding questions.</span></div><?php else: ?><div class="field"><label>Status</label><input value="Draft" disabled><span class="field-help">Publish from Manage Exams after adding questions.</span></div><?php endif; ?>
                </div>
                <div class="button-row"><label class="check-field"><input type="checkbox" name="shuffle_questions" value="1" <?= checked($_POST['shuffle_questions'] ?? ($existing['shuffle_questions'] ?? false)) ?>> Shuffle questions</label><label class="check-field"><input type="checkbox" name="shuffle_options" value="1" <?= checked($_POST['shuffle_options'] ?? ($existing['shuffle_options'] ?? false)) ?>> Shuffle answer options</label></div>
                <div class="form-actions"><a class="button button-outline" href="<?= e(appUrl('teacher/exams.php')) ?>">Cancel</a><button class="button button-primary">Save exam</button></div>
            </form>
        </div>
    </section>
<?php endif; ?>
<?php require APP_ROOT . '/components/footer.php'; ?>