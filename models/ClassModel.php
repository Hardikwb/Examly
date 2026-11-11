<?php

declare(strict_types=1);

final class ClassModel
{
    private PDO $pdo;
    public function __construct()
    {
        $this->pdo = Database::connection();
    }
    public function all(): array
    {
        return $this->pdo->query('SELECT c.*, (SELECT COUNT(*) FROM class_students cs WHERE cs.class_id=c.id) student_count FROM classes c ORDER BY c.class_name, c.section')->fetchAll();
    }
    public function find(int $id): ?array
    {
        $s = $this->pdo->prepare('SELECT * FROM classes WHERE id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }
    public function create(array $data): int
    {
        return (new Database())->insert('classes', $data);
    }
    public function update(int $id, array $data): void
    {
        (new Database())->update('classes', $data, ['id' => $id]);
    }
    public function students(int $classId): array
    {
        $s = $this->pdo->prepare('SELECT u.id,u.name,u.email FROM class_students cs JOIN users u ON u.id=cs.student_id WHERE cs.class_id=? ORDER BY u.name');
        $s->execute([$classId]);
        return $s->fetchAll();
    }
    public function teachers(int $classId): array
    {
        $s = $this->pdo->prepare('SELECT u.id,u.name,u.email,s.subject_name,ct.subject_id FROM class_teachers ct JOIN users u ON u.id=ct.teacher_id JOIN subjects s ON s.id=ct.subject_id WHERE ct.class_id=? ORDER BY u.name,s.subject_name');
        $s->execute([$classId]);
        return $s->fetchAll();
    }
    public function enroll(int $classId, int $studentId): void
    {
        (new Database())->insert('class_students', ['class_id' => $classId, 'student_id' => $studentId]);
    }
    public function assignTeacher(int $classId, int $teacherId, int $subjectId): void
    {
        (new Database())->insert('class_teachers', ['class_id' => $classId, 'teacher_id' => $teacherId, 'subject_id' => $subjectId]);
    }
    public function removeStudent(int $classId, int $studentId): void
    {
        (new Database())->delete('class_students', ['class_id' => $classId, 'student_id' => $studentId]);
    }
    public function removeTeacher(int $classId, int $teacherId, int $subjectId): void
    {
        (new Database())->delete('class_teachers', ['class_id' => $classId, 'teacher_id' => $teacherId, 'subject_id' => $subjectId]);
    }
}
