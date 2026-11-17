<?php

namespace App\Models;

use App\Core\Model;

class Kelas extends Model
{
    protected string $table = 'classes';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'name',
        'grade',
        'academic_year'
    ];

    /**
     * Get classes with student count
     *
     * @return array
     */
    public function getAllWithStudentCount(): array
    {
        $sql = "
            SELECT c.*, COUNT(s.id) as total_students
            FROM `{$this->table}` c
            LEFT JOIN `students` s ON c.id = s.class_id
            GROUP BY c.id
            ORDER BY c.grade ASC, c.name ASC
        ";
        return $this->query($sql);
    }
}
