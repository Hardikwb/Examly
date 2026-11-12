<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('teacher');
$teacherId = (int)AuthService::user()['id'];
$examId = (int)($_GET['exam'] ?? $_POST['exam_id'] ?? 0);
$exam = (new Exam())->find($examId);
if (!$exam || !((int)$exam['teacher_id'] === $teacherId)) Response::notFound();
$pageTitle = 'Manage Questions';
$dashboardMode = true;
$questionModel = new Question();
$editingId = (int)($_GET['edit'] ?? 0);
$editing = null;
$errors = [];
$hasAttempts = (new Database())->count('exam_attempts', ['exam_id' => $examId]) > 0;
if ($editingId) {
    foreach ($questionModel->forExamWithKeys($examId) as $q) if ((int)$q['id'] === $editingId) $editing = $q;
    if (!$editing) Response::notFound('Question not found.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    postOnly();
    $action = (string)($_POST['action'] ?? 'save');
    $postQuestionId = (int)($_POST['question_id'] ?? 0);
    if ($hasAttempts) flashAndRedirect('error', 'Questions are locked because students have already started an attempt.', 'teacher/questions.php?exam=' . $examId);
    if ($action === 'delete') {
        $questionModel->delete($postQuestionId);
        (new Exam())->recalculateTotal($examId);
        audit('question_deleted', 'questions', $postQuestionId);
        Session::flash('success', 'Question removed.');
        redirectTo('teacher/questions.php?exam=' . $examId);
    }
    $type = is_string($_POST['question_type'] ?? null) ? (string)$_POST['question_type'] : 'single_choice';
    if (!in_array($type, ['single_choice', 'multiple_choice', 'true_false', 'descriptive'], true)) $type = 'single_choice';
    $options = is_array($_POST['option_text'] ?? null) ? array_map(fn($value) => is_scalar($value) ? (string)$value : '', array_values(array_slice($_POST['option_text'], 0, 8))) : [];
    $correct = is_array($_POST['correct'] ?? null) ? array_values(array_map('strval', array_filter($_POST['correct'], fn($value) => is_scalar($value)))) : [];
    $v = (new Validator())->required($_POST, 'question_text', 'Question text')->length($_POST, 'question_text', 'Question text', 3, 4000)->numericRange($_POST, 'marks', 'Marks', 0, 100000)->numericRange($_POST, 'negative_marks', 'Negative marks', 0, 100000);
    $validOptions = array_values(array_filter($options, fn($o) => trim($o) !== ''));
    if ($type !== 'descriptive' && count($validOptions) < 2) $v->add('option_text', 'Add at least two answer options.');
    if ($type !== 'descriptive' && !$correct) $v->add('correct', 'Select at least one correct answer.');
    if ($type === 'single_choice' && count($correct) !== 1) $v->add('correct', 'Single choice questions need exactly one correct answer.');
    if ($type === 'true_false' && count($validOptions) !== 2) $v->add('option_text', 'True/false questions need two options.');
    if ($type === 'true_false' && count($correct) !== 1) $v->add('correct', 'True/false questions need exactly one correct answer.');
    $errors = $v->errors();
    if (!$errors) {
        $position = $editing['position'] ?? ((int)Database::connection()->query('SELECT COALESCE(MAX(position),0)+1 FROM questions WHERE exam_id=' . (int)$examId)->fetchColumn());
        $data = ['exam_id' => $examId, 'question_text' => trim((string)$_POST['question_text']), 'question_type' => $type, 'marks' => (float)$_POST['marks'], 'negative_marks' => (float)($_POST['negative_marks'] ?? 0), 'position' => $position];
        try {
            if ($editing) {
                $questionModel->update($editingId, $data, $options, $correct);
                audit('question_updated', 'questions', $editingId);
                Session::flash('success', 'Question updated.');
            } else {
                $qid = $questionModel->create($examId, $data, $options, $correct);
                audit('question_created', 'questions', $qid);
                Session::flash('success', 'Question added.');
            }
            (new Exam())->recalculateTotal($examId);
            redirectTo('teacher/questions.php?exam=' . $examId);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $errors[] = 'Question could not be saved. Check that each option and correct answer is valid.';
        }
    }
}
$questions = $questionModel->forExam($examId);
$pageTitle = 'Manage Questions';
require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Manage Questions</h1>
        <p><?= e($exam['title']) ?> · <?= count($questions) ?> questions · <?= e(money($exam['total_marks'])) ?> total marks</p>
    </div>
    <div class="page-heading-actions"><a class="button button-outline button-small" href="<?= e(appUrl('teacher/exams.php')) ?>">← Exams</a><?php if (!$hasAttempts): ?><a class="button button-primary button-small" href="<?= e(appUrl('teacher/questions.php?exam=' . $examId)) ?>#question-form">＋ Add question</a><?php endif; ?></div>
</div>
<?php if ($hasAttempts): ?><div class="notice-card" style="margin-bottom:16px">Students have started this examination. Questions are locked to preserve attempt integrity.</div><?php endif; ?>
<?php if (!$hasAttempts): ?><section class="panel" id="question-form">
        <div class="panel-header">
            <h2><?= $editing ? 'Edit question' : 'Add a question' ?></h2><span class="badge badge-blue">Single choice · Multiple choice · True/false · Descriptive</span>
        </div>
        <div class="panel-body"><?php if ($errors): ?><ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?><form method="post" class="form-stack"><?= CSRF::field() ?><input type="hidden" name="exam_id" value="<?= $examId ?>"><input type="hidden" name="action" value="save"><?php if ($editing): ?><input type="hidden" name="question_id" value="<?= $editingId ?>"><?php endif; ?><div class="field"><label for="question_text">Question text</label><textarea id="question_text" name="question_text" required minlength="3" maxlength="4000"><?= old('question_text', $editing['question_text'] ?? '') ?></textarea></div>
                <div class="form-grid">
                    <div class="field"><label for="question_type">Question type</label><select id="question_type" name="question_type"><?php foreach (['single_choice' => 'Single-correct MCQ', 'multiple_choice' => 'Multiple-correct MCQ', 'true_false' => 'True / false', 'descriptive' => 'Descriptive (manual grading)'] as $value => $label): ?><option value="<?= e($value) ?>" <?= selected($_POST['question_type'] ?? ($editing['question_type'] ?? 'single_choice'), $value) ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                    <div class="field"><label for="marks">Marks</label><input id="marks" type="number" name="marks" min="0" max="100000" step="0.25" required value="<?= old('marks', $editing['marks'] ?? 1) ?>"></div>
                    <div class="field"><label for="negative_marks">Negative marks</label><input id="negative_marks" type="number" name="negative_marks" min="0" max="100000" step="0.25" required value="<?= old('negative_marks', $editing['negative_marks'] ?? 0) ?>"></div>
                </div><?php $editOpts = $editing['options'] ?? [];
                        for ($i = 0; $i < 4; $i++): $opt = $editOpts[$i] ?? null;
                            $isCorrect = $opt && (bool)$opt['is_correct']; ?><div class="option-editor-row"><span><?= chr(65 + $i) ?>.</span><input type="text" name="option_text[]" maxlength="1000" placeholder="Answer option <?= $i + 1 ?>" value="<?= isset($_POST['option_text'][$i]) ? e($_POST['option_text'][$i]) : e($opt['option_text'] ?? '') ?>"><label><input type="checkbox" name="correct[]" value="<?= $i + 1 ?>" <?= checked(in_array((string)($i + 1), array_map('strval', $_POST['correct'] ?? []), true) || (!isset($_POST['correct']) && $isCorrect)) ?>> Correct answer</label></div><?php endfor; ?><p class="field-help">For descriptive questions, leave the answer option fields blank. For single-choice, select one correct answer. For multiple-choice, select all correct answers.</p>
                <div class="form-actions"><a class="button button-outline" href="<?= e(appUrl('teacher/questions.php?exam=' . $examId)) ?>">Cancel</a><button class="button button-primary"><?= $editing ? 'Save changes' : 'Add question' ?></button></div>
            </form>
        </div>
    </section><?php endif; ?>
<section class="panel">
    <div class="panel-header">
        <h2>Question bank for this exam</h2><span class="badge badge-blue"><?= count($questions) ?> questions</span>
    </div>
    <div class="panel-body">
        <div class="activity-list"><?php foreach ($questions as $index => $question): ?><article class="question-editor">
                    <div class="exam-question-meta"><strong>Question <?= $index + 1 ?> · <?= e(ucwords(str_replace('_', ' ', $question['question_type']))) ?></strong><span><?= e(money($question['marks'])) ?> marks<?= (float)$question['negative_marks'] ? ' · −' . e(money($question['negative_marks'])) : '' ?></span></div>
                    <p><?= nl2br(e($question['question_text'])) ?></p><?php if ($question['options']): ?><ol type="A"><?php foreach ($question['options'] as $option): ?><li><?= e($option['option_text']) ?></li><?php endforeach; ?></ol><?php endif; ?><?php if (!$hasAttempts): ?><div class="table-actions"><a class="button button-outline button-small" href="<?= e(appUrl('teacher/questions.php?exam=' . $examId . '&edit=' . (int)$question['id'] . '#question-form')) ?>">Edit</a>
                            <form method="post"><?= CSRF::field() ?><input type="hidden" name="exam_id" value="<?= $examId ?>"><input type="hidden" name="question_id" value="<?= (int)$question['id'] ?>"><input type="hidden" name="action" value="delete"><button class="button button-outline button-small" type="submit">Delete</button></form>
                        </div><?php endif; ?>
                </article><?php endforeach; ?><?php if (!$questions): ?><div class="empty-state"><strong>No questions added yet</strong>Add at least one question before publishing.</div><?php endif; ?></div>
    </div>
</section>
<?php require APP_ROOT . '/components/footer.php'; ?>