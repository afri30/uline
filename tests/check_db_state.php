<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Env.php';
\App\Core\Env::load();
require_once __DIR__ . '/../app/Core/Database.php';

$db = \App\Core\Database::getConnection();

echo "=== USERS ===\n";
$users = $db->query("SELECT id, name, username, role, status FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "ID: {$u['id']} | {$u['username']} ({$u['name']}) | Role: {$u['role']} | Status: {$u['status']}\n";
}

echo "\n=== TEACHERS ===\n";
$teachers = $db->query("SELECT t.id, t.user_id, u.name, t.nip FROM teachers t JOIN users u ON t.user_id = u.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($teachers as $t) {
    echo "Teacher ID: {$t['id']} | User ID: {$t['user_id']} | Name: {$t['name']} | NIP: {$t['nip']}\n";
}

echo "\n=== STUDENTS ===\n";
$students = $db->query("SELECT s.id, s.user_id, u.name, s.nis, c.name as class_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN classes c ON s.class_id = c.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($students as $s) {
    echo "Student ID: {$s['id']} | User ID: {$s['user_id']} | Name: {$s['name']} | Class: {$s['class_name']}\n";
}

echo "\n=== EXAMS ===\n";
$exams = $db->query("SELECT e.id, e.teacher_id, u.name as teacher_name, e.subject_id, s.name as subject_name, e.class_id, c.name as class_name, e.title, e.duration, e.start_at, e.end_at, e.status FROM exams e JOIN teachers t ON e.teacher_id = t.id JOIN users u ON t.user_id = u.id JOIN subjects s ON e.subject_id = s.id JOIN classes c ON e.class_id = c.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($exams as $e) {
    echo "Exam ID: {$e['id']} | Title: {$e['title']} | Teacher: {$e['teacher_name']} (TID: {$e['teacher_id']}) | Subject: {$e['subject_name']} | Class: {$e['class_name']} | Start: {$e['start_at']} | End: {$e['end_at']} | Status: {$e['status']}\n";
}

echo "\n=== EXAM CONFIGS ===\n";
$configs = $db->query("SELECT id, title, duration, token, is_active FROM exam_configs")->fetchAll(PDO::FETCH_ASSOC);
foreach ($configs as $cfg) {
    echo "Config ID: {$cfg['id']} | Title: {$cfg['title']} | Duration: {$cfg['duration']} | Token: [CONFIDENTIAL: {$cfg['token']}] | Active: {$cfg['is_active']}\n";
}

echo "\n=== QUESTIONS ===\n";
$questions = $db->query("SELECT q.id, q.teacher_id, u.name as teacher_name, q.subject_id, s.name as subject_name, q.question_type, q.difficulty, q.score FROM questions q JOIN teachers t ON q.teacher_id = t.id JOIN users u ON t.user_id = u.id JOIN subjects s ON q.subject_id = s.id ORDER BY q.id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($questions as $q) {
    echo "QID: {$q['id']} | Teacher: {$q['teacher_name']} (TID: {$q['teacher_id']}) | Mapel: {$q['subject_name']} | Type: {$q['question_type']} | Diff: {$q['difficulty']} | Score: {$q['score']}\n";
}


