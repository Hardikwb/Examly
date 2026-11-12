<?php
require dirname(__DIR__) . '/bootstrap.php';
requireRole('student');
$studentId = (int)AuthService::user()['id'];
$attemptId = (int)($_GET['id'] ?? 0);
$attempt = (new Attempt())->findOwned($attemptId, $studentId);
if (!$attempt) Response::notFound();
if ($attempt['status'] === 'in_progress') redirectTo('student/exam.php?attempt=' . $attemptId);
$pageTitle = 'Attempt Result';
$dashboardMode = true;
$released = $attempt['status'] === 'evaluated' && (!$attempt['result_publish_at'] || new DateTimeImmutable($attempt['result_publish_at']) <= new DateTimeImmutable());
$details = $released ? (new Result())->resultDetails($attemptId) : [];
$correct = 0;
$incorrect = 0;
$unattempted = 0;
foreach ($details as $question) {
    if ($question['question_type'] === 'descriptive') {
        if (trim((string)($question['answer_text'] ?? '')) === '') $unattempted++;
        continue;
    }
    $correctIds = array_map(fn($o) => (int)$o['id'], array_filter($question['options'], fn($o) => (bool)$o['is_correct']));
    $selectedIds = array_map(fn($o) => (int)$o['id'], array_filter($question['options'], fn($o) => (bool)$o['selected']));
    if (!$selectedIds) $unattempted++;
    elseif ($correctIds === $selectedIds) $correct++;
    else $incorrect++;
}
$percentage = $released && ((float)$attempt['total_marks'] > 0) ? max(0, round((float)$attempt['obtained_marks'] / (float)$attempt['total_marks'] * 100, 1)) : 0;
$passed = $released && (float)$attempt['obtained_marks'] >= (float)$attempt['passing_marks'];
require APP_ROOT . '/components/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Examination result</h1>
        <p><?= e($attempt['title']) ?> · submitted <?= dateTime($attempt['submitted_at']) ?></p>
    </div><a class="button button-outline button-small" href="<?= e(appUrl('student/results.php')) ?>">← My results</a>
</div>
<?php if (!$released): ?><section class="panel">
        <div class="panel-body">
            <div class="empty-state"><strong><?= $attempt['status'] === 'evaluated' ? 'Your result is not published yet' : 'Your attempt has been submitted' ?></strong><?php if ($attempt['status'] !== 'evaluated'): ?>Your descriptive answers are awaiting teacher evaluation.<?php else: ?>Your teacher will release this result at <?= dateTime($attempt['result_publish_at']) ?>.<?php endif; ?></div>
        </div>
    </section>
<?php else: ?><section class="panel">
        <div class="panel-body">
            <div class="exam-score-banner">
                <div><span class="badge <?= $passed ? 'badge-green' : 'badge-red' ?>"><?= $passed ? 'Passed' : 'Not passed' ?></span>
                    <h2 style="margin-top:10px"><?= e($attempt['title']) ?></h2>
                    <p class="muted">Your result is available.</p>
                </div>
                <div class="exam-score-number"><?= e(money($attempt['obtained_marks'])) ?> / <?= e(money($attempt['total_marks'])) ?><div class="text-small muted" style="text-align:right"><?= e(number_format($percentage, 1)) ?>%</div>
                </div>
            </div>
            <div class="dashboard-grid" style="margin-top:17px">
                <article class="metric-card"><span class="metric-icon teal">✓</span>
                    <div class="metric-copy"><strong><?= $correct ?></strong><span>Correct answers</span></div>
                </article>
                <article class="metric-card"><span class="metric-icon red">×</span>
                    <div class="metric-copy"><strong><?= $incorrect ?></strong><span>Incorrect answers</span></div>
                </article>
                <article class="metric-card"><span class="metric-icon amber">○</span>
                    <div class="metric-copy"><strong><?= $unattempted ?></strong><span>Unattempted</span></div>
                </article>
                <article class="metric-card"><span class="metric-icon purple">◉</span>
                    <div class="metric-copy"><strong><?= e(money($attempt['passing_marks'])) ?></strong><span>Passing marks</span></div>
                </article>
            </div>
        </div>
    </section>
    <section class="panel">
        <div class="panel-header">
            <h2>Answer review</h2><span class="badge badge-green">Published</span>
        </div>
        <div class="panel-body">
            <div class="activity-list"><?php foreach ($details as $index => $question): $chosen = array_values(array_filter($question['options'], fn($o) => $o['selected']));
                                            $correctOpts = array_values(array_filter($question['options'], fn($o) => $o['is_correct'])); ?><article class="question-editor">
                        <div class="exam-question-meta"><strong>Question <?= $index + 1 ?></strong><span><?= $question['marks_awarded'] === null ? '—' : e(money($question['marks_awarded'])) . ' / ' . e(money($question['marks'])) ?></span></div>
                        <p><?= nl2br(e($question['question_text'])) ?></p><?php if ($question['question_type'] === 'descriptive'): ?><div class="notice-card"><strong>Your response</strong><br><?= nl2br(e($question['answer_text'] ?? 'Unanswered')) ?></div><?php else: ?><p><strong>Your answer:</strong> <?= e(implode(', ', array_column($chosen, 'option_text')) ?: 'Unanswered') ?></p>
                            <p class="muted text-small"><strong>Correct answer:</strong> <?= e(implode(', ', array_column($correctOpts, 'option_text'))) ?></p><?php endif; ?>
                    </article><?php endforeach; ?></div>
        </div>
    </section><?php endif; ?>
<?php require APP_ROOT . '/components/footer.php'; ?>