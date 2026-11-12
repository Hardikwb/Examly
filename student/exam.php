<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('student');
$studentId = (int)AuthService::user()['id'];
$attemptId = (int)($_GET['attempt'] ?? $_POST['attempt_id'] ?? 0);
$attemptModel = new Attempt();
$attempt = $attemptModel->findOwned($attemptId, $studentId);
if (!$attempt) Response::notFound('This attempt does not exist.');
$pageTitle = $attempt['title'];
$dashboardMode = false;
$examMode = true;
if ($attempt['status'] !== 'in_progress') redirectTo('student/attempt-result.php?id=' . $attemptId);
if ($_SERVER['REQUEST_METHOD'] === 'POST') postOnly();
if (ExamService::expired($attempt)) {
    ExamService::submit($attemptId, $studentId, true);
    Session::flash('info', 'Your examination time has ended. The attempt was submitted.');
    redirectTo('student/attempt-result.php?id=' . $attemptId);
}

$questions = (new Question())->forExam((int)$attempt['exam_id']);
if (!$questions) {
    Session::flash('error', 'This examination has no questions yet. Contact your teacher.');
    redirectTo('student/exams.php');
}
$order = $attemptModel->initializeQuestionOrder($attempt);
$byId = [];
foreach ($questions as $question) $byId[(int)$question['id']] = $question;
$ordered = [];
foreach ($order['questions'] as $id) if (isset($byId[(int)$id])) $ordered[] = $byId[(int)$id];
if (!$ordered) $ordered = $questions;
$saved = $attemptModel->savedForAttempt($attemptId);
$questionId = (int)($_POST['question_id'] ?? $attempt['last_question_id'] ?? $ordered[0]['id']);
if (!isset($byId[$questionId])) $questionId = (int)$ordered[0]['id'];
$action = (string)($_POST['action'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'submit') {
            ExamService::saveAndNavigate($attempt, $questionId, $_POST, 'save');
            ExamService::submit($attemptId, $studentId, false);
            Session::flash('success', 'Your examination has been submitted.');
            redirectTo('student/attempt-result.php?id=' . $attemptId);
        }
        if ($action === 'review' && !isset($_POST['is_marked_review'])) $_POST['is_marked_review'] = '1';
        $nextId = ExamService::saveAndNavigate($attempt, $questionId, $_POST, $action);
        $attemptModel->updateLastQuestion($attemptId, $studentId, $nextId);
        redirectTo('student/exam.php?attempt=' . $attemptId);
    } catch (Throwable $e) {
        error_log('Exam navigation: ' . $e->getMessage());
        Session::flash('error', 'Your response could not be saved. Please try again.');
        redirectTo('student/exam.php?attempt=' . $attemptId);
    }
}

$current = $byId[$questionId];
foreach ($current['options'] as &$option) $option['selected'] = in_array((int)$option['id'], $saved[$questionId]['option_ids'] ?? [], true);
unset($option);
$optionOrder = $order['options'][(string)$questionId] ?? [];
if ($optionOrder) {
    $rank = array_flip(array_map('intval', $optionOrder));
    usort($current['options'], fn($a, $b) => ($rank[(int)$a['id']] ?? PHP_INT_MAX) <=> ($rank[(int)$b['id']] ?? PHP_INT_MAX));
}
$orderedIds = array_map(fn($q) => (int)$q['id'], $ordered);
$position = array_search($questionId, $orderedIds, true);
$position = $position === false ? 0 : $position;
$savedAnswer = $saved[$questionId] ?? ['answer_text' => '', 'is_marked_review' => false, 'option_ids' => []];
$seconds = max(0, (new DateTimeImmutable($attempt['expires_at']))->getTimestamp() - time());
$remaining = sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
require APP_ROOT . '/components/header.php';
?>
<section class="exam-shell">
    <header class="exam-topline">
        <h1><?= e($attempt['title']) ?></h1>
        <div class="exam-timer"><span aria-hidden="true">◷</span><?= e($remaining) ?></div>
    </header>
    <div class="exam-layout">
        <form method="post" class="exam-form">
            <?= CSRF::field() ?>
            <input type="hidden" name="attempt_id" value="<?= $attemptId ?>">
            <input type="hidden" name="question_id" value="<?= $questionId ?>">
            <input type="hidden" name="action" value="goto">
            <aside class="exam-nav">
                <h2>Question navigation</h2>
                <div class="question-nav-grid">
                    <?php foreach ($ordered as $index => $q): $qid = (int)$q['id'];
                        $entry = $saved[$qid] ?? null;
                        $answered = $entry && (trim((string)$entry['answer_text']) !== '' || $entry['option_ids']);
                        $class = $qid === $questionId ? 'current' : ($entry['is_marked_review'] ? 'review' : ($answered ? 'answered' : '')); ?>
                        <button class="question-nav-item <?= e($class) ?>" type="submit" name="target_question" value="<?= $qid ?>" aria-label="Go to question <?= $index + 1 ?>"><?= $index + 1 ?></button>
                    <?php endforeach; ?>
                </div>
                <div class="exam-legend"><span><i class="legend-square"></i>Current</span><span><i class="legend-square answered"></i>Answered</span><span><i class="legend-square review"></i>Marked for review</span></div>
            </aside>
            <section class="exam-question-area">
                <div class="exam-question-meta"><strong>Question <?= $position + 1 ?> of <?= count($ordered) ?></strong><span><?= e(money($current['marks'])) ?> marks</span></div>
                <div class="exam-question-text"><?= nl2br(e($current['question_text'])) ?></div>
                <div class="exam-options">
                    <?php if ($current['question_type'] === 'descriptive'): ?>
                        <label class="field"><span class="field-label">Your response</span><textarea name="answer_text" rows="7" maxlength="10000" placeholder="Write your answer here..."><?= e($savedAnswer['answer_text'] ?? '') ?></textarea></label>
                        <?php else: foreach ($current['options'] as $option): $multiple = $current['question_type'] === 'multiple_choice'; ?>
                            <label class="exam-option"><input type="<?= $multiple ? 'checkbox' : 'radio' ?>" name="options[]" value="<?= (int)$option['id'] ?>" <?= checked($option['selected']) ?>><?= e($option['option_text']) ?></label>
                    <?php endforeach;
                    endif; ?>
                </div>
                <label class="check-field" style="margin-top:15px"><input type="checkbox" name="is_marked_review" value="1" <?= checked($savedAnswer['is_marked_review']) ?>> Mark for review</label>
                <div class="exam-actions">
                    <button class="button button-outline" type="submit" name="action" value="previous" <?= $position === 0 ? 'disabled' : '' ?>>← Previous</button>
                    <div class="button-row">
                        <button class="button button-soft button-small" type="submit" name="action" value="save">Save answer</button>
                        <button class="button button-soft button-small" type="submit" name="action" value="review">Save &amp; mark</button>
                        <?php if ($position < count($ordered) - 1): ?><button class="button button-primary" type="submit" name="action" value="next">Next →</button><?php else: ?><button class="button button-primary" type="submit" name="action" value="next">Finish questions →</button><?php endif; ?>
                    </div>
                </div>
                <div class="form-actions"><button class="button exam-submit" type="submit" name="action" value="submit">Submit Examination</button></div>
            </section>
        </form>
    </div>
</section>
<p class="muted text-small" style="margin:10px 0 0">Remaining time is calculated when this page loads and checked by the server on every submission or navigation.</p>
<?php require APP_ROOT . '/components/footer.php'; ?>