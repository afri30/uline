<?php

namespace App\Models;

use App\Core\Model;

class Student extends Model
{
    protected string $table = 'students';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id',
        'class_id',
        'nis',
        'nisn',
        'phone',
        'gender'
    ];

    /**
     * Find student profile by user_id
     *
     * @param int $userId
     * @return array|false
     */
    public function findByUserId(int $userId)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE user_id = :uid LIMIT 1";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Find student profile with class details by user_id
     *
     * @param int $userId
     * @return array|false
     */
    public function findByUserIdWithClass(int $userId)
    {
        $sql = "
            SELECT s.*, c.name as class_name, c.grade, c.academic_year
            FROM `{$this->table}` s
            LEFT JOIN `classes` c ON s.class_id = c.id
            WHERE s.user_id = :uid
            LIMIT 1
        ";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Get all students with user and class details
     *
     * @return array
     */
    public function getAllWithDetails(): array
    {
        $sql = "
            SELECT s.*, u.name, u.email, u.username, u.status, c.name as class_name, c.grade
            FROM `{$this->table}` s
            JOIN `users` u ON s.user_id = u.id
            LEFT JOIN `classes` c ON s.class_id = c.id
            ORDER BY u.name ASC
        ";
        return $this->query($sql);
    }
}
