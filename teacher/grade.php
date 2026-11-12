<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('teacher');
$teacherId = (int)AuthService::user()['id'];
$attemptId = (int)($_GET['attempt'] ?? $_POST['attempt_id'] ?? 0);
$stmt = Database::connection()->prepare('SELECT a.*,e.title,e.total_marks,e.passing_marks,e.teacher_id,e.result_publish_at,u.name student_name FROM exam_attempts a JOIN exams e ON e.id=a.exam_id JOIN users u ON u.id=a.student_id WHERE a.id=? AND e.teacher_id=?');
$stmt->execute([$attemptId, $teacherId]);
$attempt = $stmt->fetch();
if (!$attempt) Response::notFound();
$pageTitle = 'Review Attempt';
$dashboardMode = true;
$details = (new Result())->resultDetails($attemptId);
$descriptive = array_values(array_filter($details, fn($q) => $q['question_type'] === 'descriptive'));
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    postOnly();
    $scores = is_array($_POST['scores'] ?? null) ? $_POST['scores'] : [];
    $valid = [];
    foreach ($descriptive as $question) {
        $id = (int)$question['id'];
        if (array_key_exists((string)$id, $scores)) {
            $score = $scores[$id];
            if (!is_numeric($score) || (float)$score < 0 || (float)$score > (float)$question['marks']) $errors[] = 'Marks for question ' . ($question['position']) . ' must be between 0 and ' . money($question['marks']) . '.';
            else $valid[$id] = (float)$score;
        }
    }
    if (!$errors) {
        EvaluationService::finalizeManual($attemptId, $valid);
        Session::flash('success', 'Manual evaluation saved. Any completed result can now be released according to the exam settings.');
        redirectTo('teacher/results.php');
    }
}
require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Review Attempt</h1>
        <p><?= e($attempt['student_name']) ?> · <?= e($attempt['title']) ?> · Submitted <?= dateTime($attempt['submitted_at']) ?></p>
    </div><a class="button button-outline button-small" href="<?= e(appUrl('teacher/results.php')) ?>">← Results</a>
</div>
<?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?><section class="panel">
    <div class="panel-header">
        <h2>Attempt review</h2><span class="badge badge-<?= $attempt['status'] === 'evaluated' ? 'green' : 'amber' ?>"><?= e(ucwords(str_replace('_', ' ', $attempt['status']))) ?></span>
    </div>
    <div class="panel-body">
        <div class="exam-score-banner">
            <div>
                <h2>Current score</h2>
                <p class="muted">Result release: <?= dateTime($attempt['result_publish_at']) ?></p>
            </div>
            <div class="exam-score-number"><?= $attempt['obtained_marks'] === null ? 'Pending' : e(money($attempt['obtained_marks'])) . ' / ' . e(money($attempt['total_marks'])) ?></div>
        </div>
        <form method="post" class="form-stack" style="margin-top:18px"><?= CSRF::field() ?><input type="hidden" name="attempt_id" value="<?= $attemptId ?>">
            <div class="activity-list"><?php foreach ($details as $index => $question): ?><article class="question-editor">
                        <div class="exam-question-meta"><strong>Question <?= $index + 1 ?> · <?= e(ucwords(str_replace('_', ' ', $question['question_type']))) ?></strong><span><?= e(money($question['marks'])) ?> marks</span></div>
                        <p><?= nl2br(e($question['question_text'])) ?></p><?php if ($question['question_type'] === 'descriptive'): ?><div class="notice-card"><strong>Student response</strong><br><?= nl2br(e($question['answer_text'] ?? 'No response')) ?></div>
                            <div class="field"><label for="score-<?= (int)$question['id'] ?>">Marks awarded (0–<?= e(money($question['marks'])) ?>)</label><input id="score-<?= (int)$question['id'] ?>" name="scores[<?= (int)$question['id'] ?>]" type="number" min="0" max="<?= e($question['marks']) ?>" step="0.25" value="<?= e($question['marks_awarded'] ?? '') ?>" <?= $attempt['status'] === 'evaluated' ? 'disabled' : '' ?>></div><?php else: ?><p class="muted text-small">Response: <?php $chosen = array_values(array_filter($question['options'], fn($o) => $o['selected']));
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            echo e($question['answer_text'] ?? (implode(', ', array_column($chosen, 'option_text')) ?: 'Unanswered')); ?> · Score <?= $question['marks_awarded'] === null ? '—' : e(money($question['marks_awarded'])) ?></p><?php endif; ?>
                    </article><?php endforeach; ?></div><?php if ($descriptive && in_array($attempt['status'], ['submitted', 'auto_submitted'], true)): ?><div class="form-actions"><button class="button button-primary">Save manual evaluation</button></div><?php elseif (!$descriptive): ?><p class="muted text-small">This attempt contains only automatically evaluated objective questions.</p><?php elseif ($attempt['status'] === 'evaluated'): ?><p class="muted text-small">This attempt has been fully evaluated.</p><?php endif; ?>
        </form>
    </div>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>