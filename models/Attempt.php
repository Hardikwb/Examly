<?php
declare(strict_types=1);

final class Attempt
{
    private PDO $pdo;
    public function __construct() { $this->pdo = Database::connection(); }
    public function forStudent(int $studentId): array { $s=$this->pdo->prepare('SELECT a.*,e.title,e.total_marks,e.passing_marks,e.result_publish_at,e.status exam_status FROM exam_attempts a JOIN exams e ON e.id=a.exam_id WHERE a.student_id=? ORDER BY COALESCE(a.submitted_at,a.started_at) DESC'); $s->execute([$studentId]); return $s->fetchAll(); }
    public function findOwned(int $attemptId,int $studentId): ?array { $s=$this->pdo->prepare('SELECT a.*,e.title,e.description,e.start_time exam_start,e.end_time exam_end,e.duration_minutes,e.total_marks,e.passing_marks,e.status exam_status,e.result_publish_at,e.shuffle_options,e.shuffle_questions FROM exam_attempts a JOIN exams e ON e.id=a.exam_id WHERE a.id=? AND a.student_id=?'); $s->execute([$attemptId,$studentId]);return $s->fetch()?:null; }
    public function currentForExam(int $examId,int $studentId): ?array { $s=$this->pdo->prepare('SELECT * FROM exam_attempts WHERE exam_id=? AND student_id=? AND status="in_progress" ORDER BY attempt_number DESC LIMIT 1');$s->execute([$examId,$studentId]);return $s->fetch()?:null; }
    public function byId(int $id): ?array { $s=$this->pdo->prepare('SELECT * FROM exam_attempts WHERE id=?');$s->execute([$id]);return $s->fetch()?:null; }
    public function initializeQuestionOrder(array $attempt): array
    {
        $saved=json_decode((string)($attempt['question_order_json']??''),true);
        if(is_array($saved)&&!empty($saved['questions']))return $saved;
        $questions=(new Question())->forExam((int)$attempt['exam_id']);$ids=array_map(fn($q)=>(int)$q['id'],$questions);
        if(!empty($attempt['shuffle_questions']))shuffle($ids);
        $options=[];
        foreach($questions as $question){$optionIds=array_map(fn($option)=>(int)$option['id'],$question['options']);if(!empty($attempt['shuffle_options']))shuffle($optionIds);$options[(string)$question['id']]=$optionIds;}
        $order=['questions'=>$ids,'options'=>$options];
        $stmt=$this->pdo->prepare('UPDATE exam_attempts SET question_order_json=? WHERE id=? AND student_id=? AND question_order_json IS NULL');
        $stmt->execute([json_encode($order,JSON_THROW_ON_ERROR),(int)$attempt['id'],(int)$attempt['student_id']]);
        $savedStmt=$this->pdo->prepare('SELECT question_order_json FROM exam_attempts WHERE id=? AND student_id=?');$savedStmt->execute([(int)$attempt['id'],(int)$attempt['student_id']]);
        return json_decode((string)$savedStmt->fetchColumn(),true) ?: $order;
    }
    public function saveAnswer(int $attemptId,int $studentId,int $questionId,string $text,array $optionIds,bool $review): void
    {
        $db=new Database();$db->beginTransaction();try{$lock=$this->pdo->prepare('SELECT a.status,a.expires_at,e.end_time,e.status exam_status FROM exam_attempts a JOIN exams e ON e.id=a.exam_id WHERE a.id=? AND a.student_id=? FOR UPDATE');$lock->execute([$attemptId,$studentId]);$state=$lock->fetch();if(!$state||$state['status']!=='in_progress'||$state['exam_status']!=='published'||new DateTimeImmutable()>new DateTimeImmutable($state['end_time'])||new DateTimeImmutable()>new DateTimeImmutable($state['expires_at']))throw new RuntimeException('This examination is no longer accepting answers.');$db->connection()->prepare('INSERT INTO student_answers(attempt_id,question_id,answer_text,is_marked_review) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE answer_text=VALUES(answer_text),is_marked_review=VALUES(is_marked_review)')->execute([$attemptId,$questionId,$text===''?null:$text,$review?1:0]);$s=$db->connection()->prepare('SELECT id FROM student_answers WHERE attempt_id=? AND question_id=?');$s->execute([$attemptId,$questionId]);$answerId=(int)$s->fetchColumn();$del=$db->connection()->prepare('DELETE sao FROM student_answer_options sao JOIN question_options qo ON qo.id=sao.option_id WHERE sao.student_answer_id=? AND qo.question_id=?');$del->execute([$answerId,$questionId]);$add=$db->connection()->prepare('INSERT IGNORE INTO student_answer_options(student_answer_id,option_id) SELECT ?,id FROM question_options WHERE question_id=? AND id=?');foreach(array_unique(array_map('intval',$optionIds)) as $optionId)$add->execute([$answerId,$questionId,$optionId]);$db->connection()->prepare('UPDATE exam_attempts SET last_question_id=? WHERE id=? AND student_id=?')->execute([$questionId,$attemptId,$studentId]);$db->commit();}catch(Throwable $e){$db->rollback();throw $e;}
    }
    public function updateLastQuestion(int $attemptId,int $studentId,int $questionId): void { $s=$this->pdo->prepare('UPDATE exam_attempts SET last_question_id=? WHERE id=? AND student_id=? AND status="in_progress"');$s->execute([$questionId,$attemptId,$studentId]); }
    public function savedForAttempt(int $attemptId): array { $s=$this->pdo->prepare('SELECT sa.id,sa.question_id,sa.answer_text,sa.is_marked_review, sao.option_id FROM student_answers sa LEFT JOIN student_answer_options sao ON sao.student_answer_id=sa.id WHERE sa.attempt_id=?');$s->execute([$attemptId]);$map=[];foreach($s->fetchAll() as $row){$id=(int)$row['question_id'];$map[$id]??=['answer_text'=>$row['answer_text'],'is_marked_review'=>(bool)$row['is_marked_review'],'option_ids'=>[]];if($row['option_id']!==null)$map[$id]['option_ids'][]=(int)$row['option_id'];}return $map; }
    public function countForStudent(int $studentId): int { return (new Database())->count('exam_attempts',['student_id'=>$studentId]); }
    public function latestResults(int $studentId,int $limit=5): array { $s=$this->pdo->prepare('SELECT a.*,e.title,e.total_marks,e.passing_marks,e.result_publish_at FROM exam_attempts a JOIN exams e ON e.id=a.exam_id WHERE a.student_id=? AND a.status="evaluated" AND (e.result_publish_at IS NULL OR e.result_publish_at<=NOW()) ORDER BY a.submitted_at DESC LIMIT ' . max(1,$limit));$s->execute([$studentId]);return $s->fetchAll(); }
}
