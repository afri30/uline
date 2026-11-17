<?php

namespace App\Models;

use App\Core\Model;

class Teacher extends Model
{
    protected string $table = 'teachers';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id',
        'nip',
        'phone',
        'gender'
    ];

    /**
     * Find teacher profile by user_id
     *
     * @param int $userId
     * @return array|false
     */
    public function findByUserId(int $userId)
    {
        return $this->findWhere('user_id', $userId);
    }

    /**
     * Get all teachers with user accounts
     *
     * @return array
     */
    public function getAllWithUsers(): array
    {
        $sql = "
            SELECT t.*, u.name, u.email, u.username, u.status
            FROM `{$this->table}` t
            JOIN `users` u ON t.user_id = u.id
            ORDER BY u.name ASC
        ";
        return $this->query($sql);
    }
}
