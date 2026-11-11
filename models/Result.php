<?php
declare(strict_types=1);

final class Result
{
    private PDO $pdo;
    public function __construct() { $this->pdo=Database::connection(); }
    public function forTeacher(int $teacherId): array { $s=$this->pdo->prepare('SELECT a.*,e.title,e.total_marks,e.passing_marks,e.result_publish_at,u.name student_name,u.email student_email,c.class_name FROM exam_attempts a JOIN exams e ON e.id=a.exam_id JOIN users u ON u.id=a.student_id JOIN classes c ON c.id=e.class_id WHERE e.teacher_id=? ORDER BY a.submitted_at DESC');$s->execute([$teacherId]);return $s->fetchAll(); }
    public function forAdmin(): array { return $this->pdo->query('SELECT a.*,e.title,e.total_marks,e.passing_marks,e.result_publish_at,u.name student_name,u.email student_email,c.class_name,t.name teacher_name FROM exam_attempts a JOIN exams e ON e.id=a.exam_id JOIN users u ON u.id=a.student_id JOIN users t ON t.id=e.teacher_id JOIN classes c ON c.id=e.class_id ORDER BY a.submitted_at DESC')->fetchAll(); }
    public function resultDetails(int $attemptId): array { $s=$this->pdo->prepare('SELECT q.id,q.question_text,q.question_type,q.marks,q.negative_marks,q.position,sa.id answer_id,sa.answer_text,sa.marks_awarded FROM questions q LEFT JOIN student_answers sa ON sa.question_id=q.id AND sa.attempt_id=? WHERE q.exam_id=(SELECT exam_id FROM exam_attempts WHERE id=?) ORDER BY q.position');$s->execute([$attemptId,$attemptId]);$rows=$s->fetchAll();$opt=$this->pdo->prepare('SELECT qo.id,qo.option_text,qo.is_correct,EXISTS(SELECT 1 FROM student_answer_options sao WHERE sao.option_id=qo.id AND sao.student_answer_id=?) selected FROM question_options qo WHERE qo.question_id=? ORDER BY qo.position');foreach($rows as &$row){$opt->execute([$row['answer_id']??0,$row['id']]);$row['options']=$opt->fetchAll();}return $rows; }
    public function scoreDescriptive(int $attemptId,array $scores): void { $s=$this->pdo->prepare('UPDATE student_answers sa JOIN questions q ON q.id=sa.question_id SET sa.marks_awarded=? WHERE sa.attempt_id=? AND sa.question_id=? AND q.question_type="descriptive"');foreach($scores as $questionId=>$marks)$s->execute([(float)$marks,$attemptId,(int)$questionId]); }
}
