<?php

declare(strict_types=1);

final class Exam
{
    private PDO $pdo;
    public function __construct()
    {
        $this->pdo = Database::connection();
    }
    public function find(int $id): ?array
    {
        $s = $this->pdo->prepare('SELECT e.*, c.class_name, c.section, u.name AS teacher_name FROM exams e JOIN classes c ON c.id=e.class_id JOIN users u ON u.id=e.teacher_id WHERE e.id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }
    public function forTeacher(int $teacherId): array
    {
        $s = $this->pdo->prepare('SELECT e.*,c.class_name,c.section,(SELECT COUNT(*) FROM questions q WHERE q.exam_id=e.id) question_count FROM exams e JOIN classes c ON c.id=e.class_id WHERE e.teacher_id=? ORDER BY e.start_time DESC');
        $s->execute([$teacherId]);
        return $s->fetchAll();
    }
    public function forClass(int $classId): array
    {
        $s = $this->pdo->prepare('SELECT e.*,u.name AS teacher_name,(SELECT COUNT(*) FROM questions q WHERE q.exam_id=e.id) question_count FROM exams e JOIN users u ON u.id=e.teacher_id WHERE e.class_id=? ORDER BY e.start_time DESC');
        $s->execute([$classId]);
        return $s->fetchAll();
    }
    public function create(array $data): int
    {
        return (new Database())->insert('exams', $data);
    }
    public function update(int $id, array $data): void
    {
        (new Database())->update('exams', $data, ['id' => $id]);
    }
    public function belongsToTeacher(int $examId, int $teacherId): bool
    {
        return (new Database())->exists('exams', ['id' => $examId, 'teacher_id' => $teacherId]);
    }
    public function recalculateTotal(int $examId): void
    {
        $s = $this->pdo->prepare('UPDATE exams SET total_marks=(SELECT COALESCE(SUM(marks),0) FROM questions WHERE exam_id=:exam) WHERE id=:id');
        $s->execute(['exam' => $examId, 'id' => $examId]);
    }
    public function studentSchedule(int $studentId): array
    {
        $s = $this->pdo->prepare('SELECT e.*,u.name teacher_name,c.class_name,c.section,(SELECT COUNT(*) FROM exam_attempts a WHERE a.exam_id=e.id AND a.student_id=:student) attempts_used,(SELECT COUNT(*) FROM questions q WHERE q.exam_id=e.id) question_count FROM class_students cs JOIN exams e ON e.class_id=cs.class_id JOIN users u ON u.id=e.teacher_id JOIN classes c ON c.id=e.class_id WHERE cs.student_id=:student2 AND e.status IN ("published","scheduled") ORDER BY e.start_time');
        $s->execute(['student' => $studentId, 'student2' => $studentId]);
        return $s->fetchAll();
    }
    public function adminRecent(int $limit = 8): array
    {
        return $this->pdo->query('SELECT e.*,u.name teacher_name,c.class_name FROM exams e JOIN users u ON u.id=e.teacher_id JOIN classes c ON c.id=e.class_id ORDER BY e.created_at DESC LIMIT ' . max(1, $limit))->fetchAll();
    }
}
