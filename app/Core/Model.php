<?php

namespace App\Core;

use PDO;
use Exception;

abstract class Model
{
    protected ?PDO $db = null;
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];

    public function __construct()
    {
        // Lazy connection or safe attempt
    }

    /**
     * Get active database connection (lazy loaded)
     *
     * @return PDO
     */
    protected function getDb(): PDO
    {
        if ($this->db === null) {
            $this->db = Database::getConnection();
        }
        return $this->db;
    }

    /**
     * Get all records
     *
     * @param string $orderBy
     * @param string $direction
     * @return array
     */
    public function findAll(string $orderBy = 'id', string $direction = 'DESC'): array
    {
        $stmt = $this->getDb()->prepare("SELECT * FROM {$this->table} ORDER BY {$orderBy} {$direction}");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Alias for findAll()
     */
    public function all(string $orderBy = 'id', string $direction = 'DESC'): array
    {
        return $this->findAll($orderBy, $direction);
    }

    /**
     * Find record by ID
     *
     * @param int|string $id
     * @return array|false
     */
    public function findById($id)
    {
        $stmt = $this->getDb()->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Alias for findById()
     */
    public function find($id)
    {
        return $this->findById($id);
    }

    /**
     * Find record by column value
     *
     * @param string $column
     * @param mixed $value
     * @return array|false
     */
    public function findWhere(string $column, $value)
    {
        $stmt = $this->getDb()->prepare("SELECT * FROM {$this->table} WHERE {$column} = :val LIMIT 1");
        $stmt->execute([':val' => $value]);
        return $stmt->fetch();
    }

    /**
     * Get multiple records by column value
     *
     * @param string $column
     * @param mixed $value
     * @param string $orderBy
     * @param string $direction
     * @return array
     */
    public function findAllWhere(string $column, $value, string $orderBy = 'id', string $direction = 'DESC'): array
    {
        $stmt = $this->getDb()->prepare("SELECT * FROM {$this->table} WHERE {$column} = :val ORDER BY {$orderBy} {$direction}");
        $stmt->execute([':val' => $value]);
        return $stmt->fetchAll();
    }

    /**
     * Create a new record
     *
     * @param array $data
     * @return int Last insert ID
     * @throws Exception
     */
    public function create(array $data): int
    {
        $filtered = $this->filterFillable($data);
        if (empty($filtered)) {
            throw new Exception("No valid fillable fields provided for table {$this->table}");
        }

        $fields = array_keys($filtered);
        $columns = implode('`, `', $fields);
        $placeholders = ':' . implode(', :', $fields);

        $sql = "INSERT INTO `{$this->table}` (`{$columns}`) VALUES ({$placeholders})";
        $stmt = $this->getDb()->prepare($sql);

        $params = [];
        foreach ($filtered as $key => $val) {
            $params[':' . $key] = $val;
        }

        $stmt->execute($params);
        return (int)$this->getDb()->lastInsertId();
    }

    /**
     * Update a record by primary key
     *
     * @param int|string $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data): bool
    {
        $filtered = $this->filterFillable($data);
        if (empty($filtered)) {
            return false;
        }

        $fields = [];
        $params = [':pk' => $id];

        foreach ($filtered as $key => $val) {
            $fields[] = "`{$key}` = :{$key}";
            $params[':' . $key] = $val;
        }

        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $fields) . " WHERE `{$this->primaryKey}` = :pk";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a record by primary key
     *
     * @param int|string $id
     * @return bool
     */
    public function delete($id): bool
    {
        $stmt = $this->getDb()->prepare("DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Execute custom query with parameters
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count records in table
     *
     * @param string|null $where
     * @param array $params
     * @return int
     */
    public function count(?string $where = null, array $params = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}`";
        if ($where) {
            $sql .= " WHERE " . $where;
        }
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch();
        return (int)($res['total'] ?? 0);
    }

    /**
     * Begin database transaction
     */
    public function beginTransaction(): bool
    {
        return $this->getDb()->beginTransaction();
    }

    /**
     * Commit database transaction
     */
    public function commit(): bool
    {
        return $this->getDb()->commit();
    }

    /**
     * Rollback database transaction
     */
    public function rollBack(): bool
    {
        return $this->getDb()->rollBack();
    }

    /**
     * Filter data array by $fillable property
     *
     * @param array $data
     * @return array
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }
}

class_alias(Model::class, 'App\\Core\\BaseModel');

