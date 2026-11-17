<?php

namespace App\Models;

use App\Core\Model;
use Exception;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'status',
        'phone_number',
        'whatsapp_opt_in',
        'push_opt_in'
    ];

    /**
     * Find user by username or email
     *
     * @param string $identifier
     * @return array|false
     */
    public function findByUsernameOrEmail(string $identifier)
    {
        $stmt = $this->getDb()->prepare("
            SELECT * FROM `{$this->table}` 
            WHERE `username` = :id1 OR `email` = :id2 
            LIMIT 1
        ");
        $stmt->execute([
            ':id1' => $identifier,
            ':id2' => $identifier
        ]);
        return $stmt->fetch();
    }

    /**
     * Find user by phone number across users, students, and teachers tables
     *
     * @param string $phone
     * @return array|false
     */
    public function findByPhone(string $phone)
    {
        $digits = preg_replace('/[^\d]/', '', trim($phone));
        if (strlen($digits) < 8) {
            return false;
        }

        // Build search variations (e.g. 0812..., 62812..., +62812...)
        $variations = [$digits];
        if (str_starts_with($digits, '62')) {
            $variations[] = '0' . substr($digits, 2);
            $variations[] = '+' . $digits;
        } elseif (str_starts_with($digits, '0')) {
            $variations[] = '62' . substr($digits, 1);
            $variations[] = '+62' . substr($digits, 1);
        } else {
            $variations[] = '0' . $digits;
            $variations[] = '62' . $digits;
            $variations[] = '+62' . $digits;
        }

        $last9 = substr($digits, -9);
        $likePattern = '%' . $last9;

        $db = $this->getDb();

        // 1. Check in users table
        $inPlaceholders = implode(',', array_fill(0, count($variations), '?'));
        $stmtUsers = $db->prepare("
            SELECT * FROM `{$this->table}`
            WHERE `phone_number` IN ({$inPlaceholders}) OR `phone_number` LIKE ?
            ORDER BY (`phone_number` IS NOT NULL AND `phone_number` != '') DESC, `id` ASC
            LIMIT 1
        ");
        $params = array_merge($variations, [$likePattern]);
        $stmtUsers->execute($params);
        $user = $stmtUsers->fetch();

        if ($user) {
            return $user;
        }

        // 2. Check in students table
        $stmtStudent = $db->prepare("
            SELECT u.*, s.phone AS student_phone
            FROM `students` s
            JOIN `users` u ON s.user_id = u.id
            WHERE s.phone IN ({$inPlaceholders}) OR s.phone LIKE ?
            LIMIT 1
        ");
        $stmtStudent->execute($params);
        $user = $stmtStudent->fetch();

        if ($user) {
            if (empty($user['phone_number']) && !empty($user['student_phone'])) {
                $db->prepare("UPDATE `users` SET `phone_number` = :p WHERE `id` = :id")
                   ->execute([':p' => $user['student_phone'], ':id' => $user['id']]);
                $user['phone_number'] = $user['student_phone'];
            }
            return $user;
        }

        // 3. Check in teachers table
        $stmtTeacher = $db->prepare("
            SELECT u.*, t.phone AS teacher_phone
            FROM `teachers` t
            JOIN `users` u ON t.user_id = u.id
            WHERE t.phone IN ({$inPlaceholders}) OR t.phone LIKE ?
            LIMIT 1
        ");
        $stmtTeacher->execute($params);
        $user = $stmtTeacher->fetch();

        if ($user) {
            if (empty($user['phone_number']) && !empty($user['teacher_phone'])) {
                $db->prepare("UPDATE `users` SET `phone_number` = :p WHERE `id` = :id")
                   ->execute([':p' => $user['teacher_phone'], ':id' => $user['id']]);
                $user['phone_number'] = $user['teacher_phone'];
            }
            return $user;
        }

        return false;
    }

    /**
     * Prepare user data for active session (load associated role profiles & remove password)
     */
    public function prepareUserForSession(array $user): array
    {
        if ($user['role'] === 'GURU') {
            $teacher = (new Teacher())->findByUserId((int)$user['id']);
            $user['teacher_profile'] = $teacher ?: null;
        } elseif ($user['role'] === 'PESERTA') {
            $student = (new Student())->findByUserIdWithClass((int)$user['id']);
            $user['student_profile'] = $student ?: null;
        }

        unset($user['password']);
        return $user;
    }

    /**
     * Authenticate user with password verification & active status check
     *
     * @param string $identifier
     * @param string $password
     * @return array Returns user data array on success
     * @throws Exception On invalid credentials or inactive status
     */
    public function authenticate(string $identifier, string $password): array
    {
        $user = $this->findByUsernameOrEmail($identifier);

        if (!$user) {
            throw new Exception("Username atau Email tidak ditemukan.");
        }

        // Verify password hash
        if (!password_verify($password, $user['password'])) {
            throw new Exception("Kata sandi (password) yang Anda masukkan salah.");
        }

        // Check active status
        if (($user['status'] ?? '') !== 'active') {
            throw new Exception("Akun Anda saat ini berstatus non-aktif. Silakan hubungi Administrator.");
        }

        // Fetch associated profile data
        if ($user['role'] === 'GURU') {
            $teacher = (new Teacher())->findByUserId((int)$user['id']);
            $user['teacher_profile'] = $teacher ?: null;
        } elseif ($user['role'] === 'PESERTA') {
            $student = (new Student())->findByUserIdWithClass((int)$user['id']);
            $user['student_profile'] = $student ?: null;
        }

        // Remove sensitive password hash from session data
        unset($user['password']);

        return $user;
    }

    /**
     * Create user with hashed password
     *
     * @param array $data
     * @return int
     * @throws Exception
     */
    public function createWithPassword(array $data): int
    {
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        return $this->create($data);
    }

    /**
     * Get target dashboard URL based on user role
     *
     * @param string $role
     * @return string
     */
    public static function getDashboardUrl(string $role): string
    {
        return match ($role) {
            'SUPER_ADMIN' => 'admin/dashboard',
            'GURU' => 'guru/dashboard',
            'PESERTA' => 'peserta/dashboard',
            default => 'login',
        };
    }

    /**
     * Get target profile URL based on user role
     *
     * @param string $role
     * @return string
     */
    public static function getProfileUrl(string $role): string
    {
        return match ($role) {
            'SUPER_ADMIN' => 'admin/profile',
            'GURU' => 'guru/profile',
            'PESERTA' => 'peserta/profile',
            default => 'login',
        };
    }
}
