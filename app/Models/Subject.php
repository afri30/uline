<?php

namespace App\Models;

use App\Core\Model;

class Subject extends Model
{
    protected string $table = 'subjects';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'code',
        'name',
        'description',
        'teacher_id'
    ];

    /**
     * Get all subjects with teacher details
     *
     * @return array
     */
    public function getAllWithTeachers(): array
    {
        $sql = "
            SELECT s.*, t.nip, u.name as teacher_name
            FROM `{$this->table}` s
            LEFT JOIN `teachers` t ON s.teacher_id = t.id
            LEFT JOIN `users` u ON t.user_id = u.id
            ORDER BY s.name ASC
        ";
        return $this->query($sql);
    }

    /**
     * Get subjects taught by specific teacher_id
     *
     * @param int $teacherId
     * @return array
     */
    public function getByTeacherId(int $teacherId): array
    {
        $sql = "
            SELECT * FROM `{$this->table}`
            WHERE `teacher_id` = :tid
            ORDER BY `name` ASC
        ";
        return $this->query($sql, [':tid' => $teacherId]);
    }
}
