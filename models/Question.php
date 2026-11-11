<?php
declare(strict_types=1);

final class Question
{
    private PDO $pdo;

    public function __construct() { $this->pdo=Database::connection(); }

    public function forExam(int $examId): array
    {
        $stmt=$this->pdo->prepare('SELECT * FROM questions WHERE exam_id=? ORDER BY position,id');
        $stmt->execute([$examId]);
        $questions=$stmt->fetchAll();
        $options=$this->pdo->prepare('SELECT id,question_id,option_text,position FROM question_options WHERE question_id=? ORDER BY position,id');
        foreach($questions as &$question){$options->execute([$question['id']]);$question['options']=$options->fetchAll();}
        unset($question);
        return $questions;
    }

    public function forExamWithKeys(int $examId): array
    {
        $stmt=$this->pdo->prepare('SELECT * FROM questions WHERE exam_id=? ORDER BY position,id');
        $stmt->execute([$examId]);
        $questions=$stmt->fetchAll();
        $options=$this->pdo->prepare('SELECT * FROM question_options WHERE question_id=? ORDER BY position,id');
        foreach($questions as &$question){$options->execute([$question['id']]);$question['options']=$options->fetchAll();}
        unset($question);
        return $questions;
    }

    private function addOptions(int $questionId,string $type,array $options,array $correct): void
    {
        if($type==='descriptive')return;
        $db=new Database();$count=0;$correctCount=0;
        foreach($options as $index=>$text){
            $text=trim((string)$text);
            if($text==='')continue;
            $isCorrect=in_array((string)($index+1),$correct,true)?1:0;
            $db->insert('question_options',['question_id'=>$questionId,'option_text'=>$text,'is_correct'=>$isCorrect,'position'=>$index+1]);
            $count++;$correctCount+=$isCorrect;
        }
        if($count<2||$correctCount<1)throw new InvalidArgumentException('Choice questions need at least two options and a valid correct answer.');
        if(in_array($type,['single_choice','true_false'],true)&&$correctCount!==1)throw new InvalidArgumentException('This question type needs exactly one correct answer.');
        if($type==='true_false'&&$count!==2)throw new InvalidArgumentException('True/false questions need exactly two options.');
    }

    public function create(int $examId,array $data,array $options,array $correct): int
    {
        $db=new Database();$db->beginTransaction();
        try{
            $questionId=$db->insert('questions',['exam_id'=>$examId,'question_text'=>$data['question_text'],'question_type'=>$data['question_type'],'marks'=>$data['marks'],'negative_marks'=>$data['negative_marks'],'position'=>$data['position']]);
            $this->addOptions($questionId,$data['question_type'],$options,$correct);
            $db->commit();
            return $questionId;
        }catch(Throwable $e){$db->rollback();throw $e;}
    }

    public function delete(int $id): void { (new Database())->delete('questions',['id'=>$id]); }

    public function update(int $id,array $data,array $options,array $correct): void
    {
        $db=new Database();$db->beginTransaction();
        try{
            $db->update('questions',$data,['id'=>$id]);
            $db->delete('question_options',['question_id'=>$id]);
            $this->addOptions($id,$data['question_type'],$options,$correct);
            $db->commit();
        }catch(Throwable $e){$db->rollback();throw $e;}
    }
}
