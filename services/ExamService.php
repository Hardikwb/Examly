<?php

declare(strict_types=1);

final class ExamService
{
    public static function canManage(array $exam, int $userId, string $role): bool
    {
        return $role === 'admin' || (int)$exam['teacher_id'] === $userId;
    }

    public static function start(int $examId, int $studentId): int
    {
        $exam = (new Exam())->find($examId);
        if (!$exam || $exam['status'] !== 'published') throw new RuntimeException('This examination is not available.');
        $now = new DateTimeImmutable();
        if ($now < new DateTimeImmutable($exam['start_time']) || $now > new DateTimeImmutable($exam['end_time'])) throw new RuntimeException('This examination is outside its scheduled time.');
        $enrolled = (new Database())->exists('class_students', ['class_id' => (int)$exam['class_id'], 'student_id' => $studentId]);
        if (!$enrolled) throw new RuntimeException('You are not enrolled in this examination class.');
        $attempts = new Attempt();
        $current = $attempts->currentForExam($examId, $studentId);
        if ($current) return (int)$current['id'];
        $count = (new Database())->count('exam_attempts', ['exam_id' => $examId, 'student_id' => $studentId]);
        if ($count >= (int)$exam['max_attempts']) throw new RuntimeException('You have used all attempts for this examination.');
        $started = $now;
        $expires = $started->modify('+' . (int)$exam['duration_minutes'] . ' minutes');
        $end = new DateTimeImmutable($exam['end_time']);
        if ($expires > $end) $expires = $end;
        return (new Database())->insert('exam_attempts', ['exam_id' => $examId, 'student_id' => $studentId, 'attempt_number' => $count + 1, 'started_at' => $started->format('Y-m-d H:i:s'), 'expires_at' => $expires->format('Y-m-d H:i:s'), 'status' => 'in_progress', 'last_question_id' => null]);
    }

    public static function saveAndNavigate(array $attempt, int $questionId, array $post, string $action): int
    {
        $questions = (new Question())->forExam((int)$attempt['exam_id']);
        $questionIds = array_map(fn($q) => (int)$q['id'], $questions);
        $stored = json_decode((string)($attempt['question_order_json'] ?? ''), true);
        if (is_array($stored) && !empty($stored['questions'])) $questionIds = array_values(array_filter(array_map('intval', $stored['questions']), fn($id) => in_array($id, array_map(fn($q) => (int)$q['id'], $questions), true)));
        if (!in_array($questionId, $questionIds, true)) throw new RuntimeException('The requested question does not belong to this examination.');
        $postedText = $post['answer_text'] ?? '';
        $text = is_string($postedText) ? trim($postedText) : '';
        if (strlen($text) > 40000) throw new RuntimeException('This response is too long.');
        $optionIds = $post['options'] ?? [];
        if (!is_array($optionIds)) $optionIds = [];
        $optionIds = array_values(array_filter($optionIds, fn($value) => is_scalar($value) && filter_var((string)$value, FILTER_VALIDATE_INT) !== false));
        $review = !empty($post['is_marked_review']);
        (new Attempt())->saveAnswer((int)$attempt['id'], (int)$attempt['student_id'], $questionId, $text, $optionIds, $review);
        $current = array_search($questionId, $questionIds, true);
        $targetRaw = $post['target_question'] ?? null;
        $target = is_scalar($targetRaw) ? filter_var((string)$targetRaw, FILTER_VALIDATE_INT) : false;
        return match ($action) {
            'previous' => $questionIds[max(0, $current - 1)],
            'next' => $questionIds[min(count($questionIds) - 1, $current + 1)],
            'goto' => $target !== false && in_array((int)$target, $questionIds, true) ? (int)$target : $questionId,
            default => $questionId,
        };
    }

    public static function expired(array $attempt): bool
    {
        return ($attempt['exam_status'] ?? 'published') !== 'published' || new DateTimeImmutable() >= new DateTimeImmutable($attempt['expires_at']) || new DateTimeImmutable() > new DateTimeImmutable($attempt['exam_end']);
    }

    public static function submit(int $attemptId, int $studentId, bool $automatic = false): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $s = $pdo->prepare('SELECT a.*,e.total_marks,e.end_time exam_end,e.status exam_status,e.result_publish_at FROM exam_attempts a JOIN exams e ON e.id=a.exam_id WHERE a.id=? AND a.student_id=? FOR UPDATE');
            $s->execute([$attemptId, $studentId]);
            $attempt = $s->fetch();
            if (!$attempt || $attempt['status'] !== 'in_progress') {
                $pdo->rollBack();
                return;
            }
            if ($attempt['exam_status'] !== 'published' || new DateTimeImmutable() > new DateTimeImmutable($attempt['expires_at']) || new DateTimeImmutable() > new DateTimeImmutable($attempt['exam_end'])) $automatic = true;
            $q = $pdo->prepare('SELECT q.id,q.question_type,q.marks,q.negative_marks,sa.id answer_id FROM questions q LEFT JOIN student_answers sa ON sa.question_id=q.id AND sa.attempt_id=? WHERE q.exam_id=? ORDER BY q.position');
            $q->execute([$attemptId, $attempt['exam_id']]);
            $questions = $q->fetchAll();
            $sum = 0.0;
            $manual = false;
            foreach ($questions as $question) {
                $answerId = $question['answer_id'] ? (int)$question['answer_id'] : 0;
                if ($question['question_type'] === 'descriptive') {
                    if (!$answerId) {
                        $create = $pdo->prepare('INSERT INTO student_answers(attempt_id,question_id,answer_text,marks_awarded) VALUES(?,?,NULL,0)');
                        $create->execute([$attemptId, $question['id']]);
                    } else {
                        $text = $pdo->prepare('SELECT answer_text FROM student_answers WHERE id=?');
                        $text->execute([$answerId]);
                        $manualAnswer = trim((string)$text->fetchColumn());
                        if ($manualAnswer !== '') $manual = true;
                        else {
                            $pdo->prepare('UPDATE student_answers SET marks_awarded=0 WHERE id=?')->execute([$answerId]);
                        }
                    }
                    continue;
                }
                $correct = $pdo->prepare('SELECT id FROM question_options WHERE question_id=? AND is_correct=1 ORDER BY id');
                $correct->execute([$question['id']]);
                $correctIds = array_map('intval', $correct->fetchAll(PDO::FETCH_COLUMN));
                $selected = $pdo->prepare('SELECT sao.option_id FROM student_answer_options sao WHERE sao.student_answer_id=? ORDER BY sao.option_id');
                $selected->execute([$answerId]);
                $selectedIds = array_map('intval', $selected->fetchAll(PDO::FETCH_COLUMN));
                $isCorrect = $correctIds !== [] && $correctIds === $selectedIds;
                $award = $isCorrect ? (float)$question['marks'] : ($selectedIds !== [] ? -(float)$question['negative_marks'] : 0.0);
                $sum += $award;
                if ($answerId) {
                    $upd = $pdo->prepare('UPDATE student_answers SET marks_awarded=? WHERE id=?');
                    $upd->execute([$award, $answerId]);
                }
            }
            $finalStatus = $manual ? ($automatic ? 'auto_submitted' : 'submitted') : 'evaluated';
            $update = $pdo->prepare('UPDATE exam_attempts SET status=?,submitted_at=NOW(),obtained_marks=? WHERE id=?');
            $update->execute([$finalStatus, round($sum, 2), $attemptId]);
            $pdo->commit();
            audit($automatic ? 'exam_auto_submitted' : 'exam_submitted', 'exam_attempts', $attemptId);
            $ready = $finalStatus === 'evaluated' && (!$attempt['result_publish_at'] || new DateTimeImmutable($attempt['result_publish_at']) <= new DateTimeImmutable());
            $message = $finalStatus !== 'evaluated' ? 'Your attempt was received and is awaiting evaluation.' : ($ready ? 'Your result is ready to view.' : 'Your exam has been evaluated. The result will be released at ' . dateTime($attempt['result_publish_at']) . '.');
            try {
                notifyUser($studentId, $ready ? 'Exam result available' : 'Exam submitted', $message);
            } catch (Throwable $notificationError) {
                error_log('Exam notification failed: ' . $notificationError->getMessage());
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
