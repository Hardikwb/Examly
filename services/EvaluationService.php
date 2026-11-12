<?php

declare(strict_types=1);

final class EvaluationService
{
    public static function finalizeManual(int $attemptId, array $scores): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT status FROM exam_attempts WHERE id=? FOR UPDATE');
            $lock->execute([$attemptId]);
            $status = $lock->fetchColumn();
            if (!in_array($status, ['submitted', 'auto_submitted'], true)) throw new RuntimeException('This attempt is already finalized.');

            $marksQuery = $pdo->prepare('SELECT q.marks FROM questions q JOIN exam_attempts a ON a.exam_id=q.exam_id WHERE a.id=? AND q.id=? AND q.question_type="descriptive"');
            foreach ($scores as $questionId => $score) {
                if (!is_numeric($score) || (float)$score < 0) throw new InvalidArgumentException('Manual marks must be zero or higher.');
                $marksQuery->execute([$attemptId, (int)$questionId]);
                $maximum = $marksQuery->fetchColumn();
                if ($maximum === false || (float)$score > (float)$maximum) throw new InvalidArgumentException('Manual marks exceed the question value.');
            }

            (new Result())->scoreDescriptive($attemptId, $scores);
            $sumStmt = $pdo->prepare('SELECT COALESCE(SUM(sa.marks_awarded),0) FROM student_answers sa WHERE sa.attempt_id=?');
            $sumStmt->execute([$attemptId]);
            $sum = (float)$sumStmt->fetchColumn();
            $pending = $pdo->prepare('SELECT COUNT(*) FROM questions q LEFT JOIN student_answers sa ON sa.question_id=q.id AND sa.attempt_id=? WHERE q.exam_id=(SELECT exam_id FROM exam_attempts WHERE id=?) AND q.question_type="descriptive" AND sa.marks_awarded IS NULL');
            $pending->execute([$attemptId, $attemptId]);
            $completed = (int)$pending->fetchColumn() === 0;
            if ($completed) {
                $update = $pdo->prepare('UPDATE exam_attempts SET obtained_marks=?,status="evaluated" WHERE id=?');
                $update->execute([$sum, $attemptId]);
            }
            $info = $pdo->prepare('SELECT a.student_id,e.title,e.result_publish_at FROM exam_attempts a JOIN exams e ON e.id=a.exam_id WHERE a.id=?');
            $info->execute([$attemptId]);
            $exam = $info->fetch();
            $pdo->commit();
            audit('manual_evaluation_saved', 'exam_attempts', $attemptId);
            if ($completed && $exam) {
                try {
                    $delayed = $exam['result_publish_at'] && new DateTimeImmutable($exam['result_publish_at']) > new DateTimeImmutable();
                    $message = $delayed ? 'Your evaluation is complete. Your result will appear at ' . dateTime($exam['result_publish_at']) . '.' : 'Your result is ready to view.';
                    notifyUser((int)$exam['student_id'], 'Evaluation complete', $message);
                } catch (Throwable $notificationError) {
                    error_log('Result notification failed: ' . $notificationError->getMessage());
                }
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
