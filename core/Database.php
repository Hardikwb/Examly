<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    private const COLUMNS = [
        'users' => ['id','name','email','password_hash','role','status','remember_token_hash','created_at','updated_at'],
        'password_resets' => ['id','user_id','token_hash','expires_at','created_at'],
        'classes' => ['id','class_name','section','academic_year','created_at'],
        'subjects' => ['id','subject_name','description','created_at'],
        'class_students' => ['id','class_id','student_id','enrolled_at'],
        'class_teachers' => ['id','class_id','teacher_id','subject_id','assigned_at'],
        'exams' => ['id','title','description','teacher_id','class_id','start_time','end_time','duration_minutes','total_marks','passing_marks','max_attempts','shuffle_questions','shuffle_options','status','result_publish_at','created_at','updated_at'],
        'questions' => ['id','exam_id','question_text','question_type','marks','negative_marks','position','created_at'],
        'question_options' => ['id','question_id','option_text','is_correct','position'],
        'exam_attempts' => ['id','exam_id','student_id','attempt_number','started_at','expires_at','submitted_at','status','obtained_marks','last_question_id','question_order_json','created_at'],
        'student_answers' => ['id','attempt_id','question_id','answer_text','marks_awarded','is_marked_review','updated_at'],
        'student_answer_options' => ['id','student_answer_id','option_id'],
        'notifications' => ['id','user_id','title','message','is_read','created_at'],
        'audit_logs' => ['id','user_id','action','entity_type','entity_id','created_at'],
    ];

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $config = require APP_ROOT . '/config/database.php';
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $config['host'], $config['port'], $config['name'], $config['charset']);
            self::$connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$connection;
    }

    private static function identifier(string $table, string $column): string
    {
        if (!isset(self::COLUMNS[$table]) || !in_array($column, self::COLUMNS[$table], true)) {
            throw new InvalidArgumentException('Unknown database identifier.');
        }
        return '`' . $column . '`';
    }

    private static function table(string $table): string
    {
        if (!isset(self::COLUMNS[$table])) {
            throw new InvalidArgumentException('Unknown database table.');
        }
        return '`' . $table . '`';
    }

    private function conditions(array $where, array &$params): string
    {
        $parts = [];
        foreach ($where as $column => $value) {
            $quoted = self::identifier($this->currentTable, (string) $column);
            if ($value === null) {
                $parts[] = "$quoted IS NULL";
            } else {
                $key = ':w' . count($params);
                $parts[] = "$quoted = $key";
                $params[$key] = $value;
            }
        }
        return $parts ? ' WHERE ' . implode(' AND ', $parts) : '';
    }

    private string $currentTable = '';

    public function insert(string $table, array $data): int
    {
        self::table($table);
        if (!$data) throw new InvalidArgumentException('Insert data cannot be empty.');
        $columns = [];
        $holders = [];
        $params = [];
        foreach ($data as $column => $value) {
            $columns[] = self::identifier($table, (string) $column);
            $key = ':v' . count($params);
            $holders[] = $key;
            $params[$key] = $value;
        }
        $stmt = self::connection()->prepare('INSERT INTO ' . self::table($table) . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $holders) . ')');
        $stmt->execute($params);
        return (int) self::connection()->lastInsertId();
    }

    public function select(string $table, array $columns = ['*'], array $where = [], string $orderBy = 'id', string $direction = 'ASC', ?int $limit = null, int $offset = 0): array
    {
        self::table($table);
        $select = $columns === ['*'] ? '*' : implode(',', array_map(fn($column) => self::identifier($table, (string) $column), $columns));
        $this->currentTable = $table;
        $params = [];
        $sql = 'SELECT ' . $select . ' FROM ' . self::table($table) . $this->conditions($where, $params);
        if ($orderBy !== '') $sql .= ' ORDER BY ' . self::identifier($table, $orderBy) . (strtoupper($direction) === 'DESC' ? ' DESC' : ' ASC');
        if ($limit !== null) $sql .= ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findOne(string $table, array $where): ?array
    {
        return $this->select($table, ['*'], $where, '', 'ASC', 1)[0] ?? null;
    }

    public function findById(string $table, int $id): ?array
    {
        return $this->findOne($table, ['id' => $id]);
    }

    public function update(string $table, array $data, array $where): int
    {
        self::table($table);
        if (!$data || !$where) throw new InvalidArgumentException('Update data and conditions are required.');
        $params = [];
        $sets = [];
        foreach ($data as $column => $value) {
            $quoted = self::identifier($table, (string) $column);
            $key = ':s' . count($params);
            $sets[] = "$quoted = $key";
            $params[$key] = $value;
        }
        $this->currentTable = $table;
        $sql = 'UPDATE ' . self::table($table) . ' SET ' . implode(',', $sets) . $this->conditions($where, $params);
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function delete(string $table, array $where): int
    {
        self::table($table);
        if (!$where) throw new InvalidArgumentException('Delete conditions are required.');
        $this->currentTable = $table;
        $params = [];
        $stmt = self::connection()->prepare('DELETE FROM ' . self::table($table) . $this->conditions($where, $params));
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function count(string $table, array $where = []): int
    {
        self::table($table);
        $this->currentTable = $table;
        $params = [];
        $stmt = self::connection()->prepare('SELECT COUNT(*) FROM ' . self::table($table) . $this->conditions($where, $params));
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function exists(string $table, array $where): bool
    {
        return $this->count($table, $where) > 0;
    }

    public function beginTransaction(): bool { return self::connection()->beginTransaction(); }
    public function commit(): bool { return self::connection()->commit(); }
    public function rollback(): bool { return self::connection()->inTransaction() ? self::connection()->rollBack() : false; }
}
