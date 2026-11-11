<?php
declare(strict_types=1);

final class Subject
{
    public function all(): array { return Database::connection()->query('SELECT * FROM subjects ORDER BY subject_name')->fetchAll(); }
    public function create(array $data): int { return (new Database())->insert('subjects', $data); }
    public function update(int $id, array $data): void { (new Database())->update('subjects', $data, ['id'=>$id]); }
}
