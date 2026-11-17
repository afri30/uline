-- ==============================================================================
-- AI Online Examination & Proctoring System - Database Schema
-- Database Name: uline_db
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `uline_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `uline_db`;

-- Drop existing tables in reverse order of foreign keys
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `answers`;
DROP TABLE IF EXISTS `exam_sessions`;
DROP TABLE IF EXISTS `exam_questions`;
DROP TABLE IF EXISTS `exams`;
DROP TABLE IF EXISTS `questions`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `teachers`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------------------
-- 1. Table: users
-- ------------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('SUPER_ADMIN', 'GURU', 'PESERTA') NOT NULL DEFAULT 'PESERTA',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Table: classes
-- ------------------------------------------------------------------------------
CREATE TABLE `classes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `grade` VARCHAR(10) NOT NULL,
    `academic_year` VARCHAR(20) NOT NULL DEFAULT '2026/2027',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_classes_grade` (`grade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Table: teachers
-- ------------------------------------------------------------------------------
CREATE TABLE `teachers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `nip` VARCHAR(50) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `gender` ENUM('L', 'P') DEFAULT 'L',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. Table: students
-- ------------------------------------------------------------------------------
CREATE TABLE `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `class_id` INT DEFAULT NULL,
    `nis` VARCHAR(30) NOT NULL UNIQUE,
    `nisn` VARCHAR(30) DEFAULT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `gender` ENUM('L', 'P') DEFAULT 'L',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_students_class` FOREIGN KEY (`class_id`) 
        REFERENCES `classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_students_class` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. Table: subjects
-- ------------------------------------------------------------------------------
CREATE TABLE `subjects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(30) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `teacher_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_subjects_teacher` FOREIGN KEY (`teacher_id`) 
        REFERENCES `teachers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_subjects_teacher` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. Table: questions (Bank Soal)
-- ------------------------------------------------------------------------------
CREATE TABLE `questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `teacher_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `question_text` TEXT NOT NULL,
    `question_type` ENUM('multiple_choice', 'true_false', 'essay') NOT NULL DEFAULT 'multiple_choice',
    `option_a` TEXT DEFAULT NULL,
    `option_b` TEXT DEFAULT NULL,
    `option_c` TEXT DEFAULT NULL,
    `option_d` TEXT DEFAULT NULL,
    `correct_answer` VARCHAR(255) NOT NULL,
    `score` INT NOT NULL DEFAULT 10,
    `difficulty` ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'medium',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_questions_teacher` FOREIGN KEY (`teacher_id`) 
        REFERENCES `teachers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_questions_subject` FOREIGN KEY (`subject_id`) 
        REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_questions_teacher` (`teacher_id`),
    INDEX `idx_questions_subject` (`subject_id`),
    INDEX `idx_questions_type` (`question_type`),
    INDEX `idx_questions_difficulty` (`difficulty`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. Table: exams (Manajemen Ujian Guru)
-- ------------------------------------------------------------------------------
CREATE TABLE `exams` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `teacher_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `class_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `duration` INT NOT NULL DEFAULT 60 COMMENT 'Durasi pengerjaan dalam menit',
    `start_at` DATETIME NOT NULL,
    `end_at` DATETIME NOT NULL,
    `random_questions` TINYINT(1) NOT NULL DEFAULT 0,
    `random_options` TINYINT(1) NOT NULL DEFAULT 0,
    `ai_proctoring` TINYINT(1) NOT NULL DEFAULT 0,
    `dual_camera` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('draft', 'published', 'active', 'finished') NOT NULL DEFAULT 'draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_exams_teacher` FOREIGN KEY (`teacher_id`) 
        REFERENCES `teachers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_exams_subject` FOREIGN KEY (`subject_id`) 
        REFERENCES `subjects` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_exams_class` FOREIGN KEY (`class_id`) 
        REFERENCES `classes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_exams_teacher` (`teacher_id`),
    INDEX `idx_exams_subject` (`subject_id`),
    INDEX `idx_exams_class` (`class_id`),
    INDEX `idx_exams_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 8. Table: exam_questions (Relasi Ujian dan Butir Soal)
-- ------------------------------------------------------------------------------
CREATE TABLE `exam_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `exam_id` INT NOT NULL,
    `question_id` INT NOT NULL,
    `question_order` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_eq_exam` FOREIGN KEY (`exam_id`) 
        REFERENCES `exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_eq_question` FOREIGN KEY (`question_id`) 
        REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `uq_exam_question` (`exam_id`, `question_id`),
    INDEX `idx_eq_exam` (`exam_id`),
    INDEX `idx_eq_question` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 9. Table: exam_sessions (Sesi Pengerjaan Ujian Siswa)
-- ------------------------------------------------------------------------------
CREATE TABLE `exam_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `exam_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `started_at` DATETIME NOT NULL,
    `finished_at` DATETIME DEFAULT NULL,
    `status` ENUM('started', 'finished', 'expired') NOT NULL DEFAULT 'started',
    `remaining_seconds` INT NOT NULL DEFAULT 0,
    `last_activity_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_es_exam` FOREIGN KEY (`exam_id`) 
        REFERENCES `exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_es_student` FOREIGN KEY (`student_id`) 
        REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_es_exam` (`exam_id`),
    INDEX `idx_es_student` (`student_id`),
    INDEX `idx_es_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 10. Table: answers (Jawaban Peserta Ujian)
-- ------------------------------------------------------------------------------
CREATE TABLE `answers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `exam_session_id` INT NOT NULL,
    `question_id` INT NOT NULL,
    `answer` TEXT DEFAULT NULL,
    `score` INT NOT NULL DEFAULT 0,
    `answered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ans_session` FOREIGN KEY (`exam_session_id`) 
        REFERENCES `exam_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ans_question` FOREIGN KEY (`question_id`) 
        REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `uq_session_question` (`exam_session_id`, `question_id`),
    INDEX `idx_ans_session` (`exam_session_id`),
    INDEX `idx_ans_question` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 11. Table: camera_events (Pencatatan Event Kamera & WebRTC Proctoring)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `camera_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `exam_session_id` INT NOT NULL,
    `camera_type` VARCHAR(50) NOT NULL COMMENT 'camera_1 (Face), camera_2 (Desk/Env)',
    `event_type` VARCHAR(100) NOT NULL COMMENT 'connected, disconnected, permission_denied, stream_error, device_changed',
    `timestamp` DATETIME NOT NULL,
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ce_session` FOREIGN KEY (`exam_session_id`) 
        REFERENCES `exam_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_ce_session` (`exam_session_id`),
    INDEX `idx_ce_type` (`camera_type`),
    INDEX `idx_ce_event` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 12. Table: proctor_events (AI Proctoring Event Detection & Risk Scoring)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `proctor_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `exam_session_id` INT NOT NULL,
    `camera_type` VARCHAR(50) NOT NULL DEFAULT 'camera_1',
    `event_type` VARCHAR(100) NOT NULL COMMENT 'FACE_NOT_DETECTED, MULTIPLE_FACES, CAMERA_DISCONNECTED, LOOKING_AWAY, SUSPICIOUS_ACTIVITY, TAB_SWITCHED',
    `confidence` FLOAT NOT NULL DEFAULT 1.0 COMMENT 'Confidence score 0.0 - 1.0',
    `severity` ENUM('INFO', 'WARNING', 'HIGH') NOT NULL DEFAULT 'INFO',
    `timestamp` DATETIME NOT NULL,
    `evidence_path` VARCHAR(255) DEFAULT NULL,
    `metadata` JSON DEFAULT NULL,
    `reviewed` TINYINT(1) NOT NULL DEFAULT 0,
    `review_status` ENUM('pending', 'reviewed', 'confirmed', 'dismissed') NOT NULL DEFAULT 'pending',
    `review_note` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pe_session` FOREIGN KEY (`exam_session_id`) 
        REFERENCES `exam_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_pe_session` (`exam_session_id`),
    INDEX `idx_pe_type` (`event_type`),
    INDEX `idx_pe_severity` (`severity`),
    INDEX `idx_pe_status` (`review_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 13. Table: audit_logs (Pencatatan Audit Trail Aktivitas Penting Sistem)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL COMMENT 'LOGIN, LOGOUT, CREATE_EXAM, UPDATE_EXAM, DELETE_EXAM, START_EXAM, SUBMIT_EXAM, PROCTOR_EVENT, REVIEW_EVENT',
    `description` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_al_user` (`user_id`),
    INDEX `idx_al_action` (`action`),
    INDEX `idx_al_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 14. Table: exam_configs (Pengaturan & Konfigurasi Ujian Online Global)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_configs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL DEFAULT 'Konfigurasi Utama Ujian Online',
    `duration` INT NOT NULL DEFAULT 60 COMMENT 'Durasi pengerjaan dalam menit',
    `random_questions` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Acak Soal, 0 = Urut',
    `random_options` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Acak Opsi Jawaban, 0 = Urut',
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `token` VARCHAR(50) NOT NULL DEFAULT 'ULINE2026',
    `token_secret` VARCHAR(255) DEFAULT NULL COMMENT 'Kredensial rahasia / salt token',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- SEED DATA TESTING (15 Users, 4 Teachers, 10 Students, 5 Subjects)
-- Password for all seed users: password123
-- Hash generated via password_hash('password123', PASSWORD_BCRYPT)
-- ==============================================================================

-- 1. Insert Users (Total: 15 Pengguna)
INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `status`) VALUES
(1, 'Super Administrator', 'admin', 'admin@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'SUPER_ADMIN', 'active'),
(2, 'Budi Santoso, M.Pd.', 'guru1', 'guru.budi@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'GURU', 'active'),
(3, 'Siti Nurhaliza, S.Kom.', 'guru_siti', 'guru.siti@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'GURU', 'active'),
(4, 'Ahmad Pratama', 'siswa1', 'ahmad@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'PESERTA', 'active'),
(5, 'Dewi Lestari', 'siswa_dewi', 'dewi@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'PESERTA', 'active'),
(6, 'Rizky Ramadhan (Non-Aktif)', 'siswa_nonaktif', 'nonaktif@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'PESERTA', 'inactive'),
(7, 'Hendra Wijaya, S.Pd.', 'guru_hendra', 'guru.hendra@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'GURU', 'active'),
(8, 'Rina Kusumawati, M.Si.', 'guru_rina', 'guru.rina@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'GURU', 'active'),
(9, 'Bayu Saputra', 'siswa_bayu', 'bayu@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'PESERTA', 'active'),
(10, 'Citra Kirana', 'siswa_citra', 'citra@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'PESERTA', 'active'),
(11, 'Doni Wahyudi', 'siswa_doni', 'doni@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'PESERTA', 'active'),
(12, 'Eka Putri', 'siswa_eka', 'eka@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'PESERTA', 'active'),
(13, 'Fajar Nugraha', 'siswa_fajar', 'fajar@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'PESERTA', 'active'),
(14, 'Gita Savitri', 'siswa_gita', 'gita@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'PESERTA', 'active'),
(15, 'Hadi Gunawan', 'siswa_hadi', 'hadi@example.com', '$2y$10$XkXf4GOEehGsOTk2vXtj1OjNz7HHGquQWkx2NxH.C3smN.TKpdsBq', 'PESERTA', 'active');

-- 2. Insert Classes
INSERT INTO `classes` (`id`, `name`, `grade`, `academic_year`) VALUES
(1, 'XII MIPA 1', '12', '2026/2027'),
(2, 'XII MIPA 2', '12', '2026/2027'),
(3, 'XI IPS 1', '11', '2026/2027');

-- 3. Insert Teachers (Total: 4 Guru)
INSERT INTO `teachers` (`id`, `user_id`, `nip`, `phone`, `gender`) VALUES
(1, 2, '198507122010011005', '081234567890', 'L'),
(2, 3, '199003252015022003', '081234567891', 'P'),
(3, 7, '198804152012011008', '081234567892', 'L'),
(4, 8, '199209102018022004', '081234567893', 'P');

-- 4. Insert Students (Total: 10 Siswa)
INSERT INTO `students` (`id`, `user_id`, `class_id`, `nis`, `nisn`, `phone`, `gender`) VALUES
(1, 4, 1, '2026001', '0058912341', '085711223344', 'L'),
(2, 5, 1, '2026002', '0058912342', '085711223345', 'P'),
(3, 6, 2, '2026003', '0058912343', '085711223346', 'L'),
(4, 9, 1, '2026004', '0058912344', '085711223347', 'L'),
(5, 10, 2, '2026005', '0058912345', '085711223348', 'P'),
(6, 11, 3, '2026006', '0058912346', '085711223349', 'L'),
(7, 12, 3, '2026007', '0058912347', '085711223350', 'P'),
(8, 13, 1, '2026008', '0058912348', '085711223351', 'L'),
(9, 14, 2, '2026009', '0058912349', '085711223352', 'P'),
(10, 15, 3, '20260010', '0058912350', '085711223353', 'L');

-- 5. Insert Subjects (Total: 5 Mata Pelajaran)
INSERT INTO `subjects` (`id`, `code`, `name`, `description`, `teacher_id`) VALUES
(1, 'MAT-12', 'Matematika Peminatan', 'Materi Kalkulus, Trigonometri Lanjut, dan Statistika Ujian', 1),
(2, 'TIK-12', 'Informatika & Kecerdasan Buatan', 'Pengenalan Algoritma, Pemrograman, dan AI Ethics', 2),
(3, 'BIG-12', 'Bahasa Inggris Lanjut', 'Reading Comprehension, Structure & TOEFL Preparation', 1),
(4, 'FIS-12', 'Fisika Terapan & Mekanika', 'Kinematika, Dinamika Partikel, Gelombang, dan Termodinamika', 3),
(5, 'BIO-12', 'Biologi Molekuler & Genetika', 'Struktur DNA/RNA, Sintesis Protein, Hereditas, dan Bioteknologi Modern', 4);

-- 6. Insert Seed Questions
INSERT INTO `questions` (`id`, `teacher_id`, `subject_id`, `question_text`, `question_type`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `score`, `difficulty`) VALUES
(1, 1, 1, 'Berapakah turunan pertama dari f(x) = 3x^4 - 5x^2 + 7?', 'multiple_choice', '12x^3 - 10x', '12x^3 - 5x', '7x^3 - 10x', '12x^4 - 10x^2', 'A', 10, 'easy'),
(2, 1, 1, 'Jika sin(A) = 3/5 pada kuadran I, maka nilai dari cos(A) adalah...', 'multiple_choice', '4/5', '3/4', '5/4', '1/5', 'A', 10, 'medium'),
(3, 1, 1, 'Integral tak tentu dari ∫(6x^2 + 4x) dx adalah 2x^3 + 2x^2 + C.', 'true_false', NULL, NULL, NULL, NULL, 'TRUE', 10, 'easy'),
(4, 1, 1, 'Jelaskan langkah-langkah dalam menentukan titik stasioner dan jenisnya pada suatu kurva fungsi kontinu!', 'essay', NULL, NULL, NULL, NULL, 'Langkah: 1) Cari f\'(x)=0, 2) Tentukan nilai x pembuat nol, 3) Gunakan uji turunan kedua f\'\'(x).', 20, 'hard'),
(5, 1, 3, 'Choose the correct sentence in Past Perfect Tense:', 'multiple_choice', 'She had finished her exam before the proctor warned.', 'She has finished her exam before the proctor warned.', 'She was finishing her exam before the proctor warned.', 'She finished her exam before the proctor had warned.', 'A', 10, 'medium'),
(6, 2, 2, 'Manakah yang BUKAN merupakan cabang utama dari Artificial Intelligence?', 'multiple_choice', 'Machine Learning', 'Computer Vision', 'Quantum Hydrodynamics', 'Natural Language Processing', 'C', 10, 'easy'),
(7, 3, 4, 'Hukum Newton II menyatakan bahwa percepatan sebuah benda berbanding lurus dengan resultan gaya yang bekerja dan berbanding terbalik dengan massanya (F = m.a).', 'true_false', NULL, NULL, NULL, NULL, 'TRUE', 10, 'easy'),
(8, 4, 5, 'Molekul yang bertindak sebagai pembawa pesan kode genetik dari DNA di inti sel ke ribosom untuk proses sintesis protein adalah...', 'multiple_choice', 'mRNA (Messenger RNA)', 'tRNA (Transfer RNA)', 'rRNA (Ribosomal RNA)', 'DNA Helikase', 'A', 10, 'medium');

-- 7. Insert Seed Exams
INSERT INTO `exams` (`id`, `teacher_id`, `subject_id`, `class_id`, `title`, `description`, `duration`, `start_at`, `end_at`, `random_questions`, `random_options`, `ai_proctoring`, `dual_camera`, `status`) VALUES
(1, 1, 1, 1, 'Penilaian Harian 1: Kalkulus & Trigonometri', 'Ujian evaluasi bab kalkulus turunan, integral, dan identitas trigonometri.', 90, '2026-09-10 08:00:00', '2026-09-10 12:00:00', 1, 1, 1, 0, 'published'),
(2, 1, 3, 1, 'Quiz 1: English Grammar & Reading Comprehension', 'Short assessment on past tenses and vocabulary in context.', 45, '2026-09-12 09:00:00', '2026-09-12 11:00:00', 0, 1, 0, 0, 'draft');

-- 8. Insert Seed Exam Questions
INSERT INTO `exam_questions` (`id`, `exam_id`, `question_id`, `question_order`) VALUES
(1, 1, 1, 1),
(2, 1, 2, 2),
(3, 1, 3, 3),
(4, 2, 5, 1);

-- 9. Insert Seed Exam Configuration
INSERT INTO `exam_configs` (`id`, `title`, `duration`, `random_questions`, `random_options`, `start_time`, `end_time`, `token`, `token_secret`, `is_active`) VALUES
(1, 'Konfigurasi Utama Ujian Online', 60, 1, 1, '2026-10-20 08:00:00', '2026-10-20 12:00:00', 'ULINE2026', 'exam_sec_f98c21a084eb73', 1);


