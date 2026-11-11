<?php
declare(strict_types=1);

final class User
{
    private PDO $pdo;
    public function __construct() { $this->pdo = Database::connection(); }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, role, status, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function listByRole(string $role, string $search = '', int $limit = 200, int $offset = 0): array
    {
        $sql = 'SELECT u.id, u.name, u.email, u.status, u.created_at FROM users u WHERE u.role = :role';
        $params = ['role' => $role];
        if ($search !== '') { $sql .= ' AND (u.name LIKE :query_name OR u.email LIKE :query_email)'; $params['query_name'] = '%' . $search . '%'; $params['query_email'] = '%' . $search . '%'; }
        $sql .= ' ORDER BY u.name LIMIT ' . max(1,min(200,$limit)) . ' OFFSET ' . max(0,$offset);
        $stmt = $this->pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }

    public function countByRole(string $role,string $search=''): int
    {
        $sql='SELECT COUNT(*) FROM users WHERE role=:role';$params=['role'=>$role];
        if($search!==''){$sql.=' AND (name LIKE :query_name OR email LIKE :query_email)';$params['query_name']='%'.$search.'%';$params['query_email']='%'.$search.'%';}
        $stmt=$this->pdo->prepare($sql);$stmt->execute($params);return (int)$stmt->fetchColumn();
    }

    public function create(string $name, string $email, string $password, string $role): int
    {
        return (new Database())->insert('users', ['name' => $name, 'email' => strtolower($email), 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role, 'status' => 'active']);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET status = :status WHERE id = :id AND role <> "admin"');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function updateProfile(int $id, string $name, string $email): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
        $stmt->execute(['name' => $name, 'email' => strtolower($email), 'id' => $id]);
    }

    public function updatePassword(int $id, string $password): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :hash, remember_token_hash = NULL WHERE id = :id');
        $stmt->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
    }
}
