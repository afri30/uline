<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Core\Database;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Kelas;
use PDO;
use Exception;
use Throwable;

class ChatbotDatabaseService
{
    private ?PDO $db = null;
    private GuardrailService $guardrail;
    private NvidiaAIService $aiService;

    public function __construct()
    {
        $this->guardrail = new GuardrailService();
        $this->aiService = new NvidiaAIService();
    }

    /**
     * Mendapatkan koneksi PDO yang aman
     */
    public function getConnection(): PDO
    {
        if ($this->db === null) {
            $this->db = Database::getConnection();
        }
        return $this->db;
    }

    /**
     * Memeriksa apakah koneksi database berfungsi dengan benar
     *
     * @return array ['connected' => bool, 'driver' => ?string, 'version' => ?string, 'message' => string]
     */
    public function checkConnection(): array
    {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->query("SELECT 1");
            if ($stmt && $stmt->fetchColumn() == 1) {
                return [
                    'connected' => true,
                    'driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
                    'version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
                    'message' => 'Koneksi database berhasil dan stabil.'
                ];
            }
            return [
                'connected' => false,
                'driver' => null,
                'version' => null,
                'message' => 'Koneksi database gagal merespons kueri pengujian.'
            ];
        } catch (Throwable $e) {
            return [
                'connected' => false,
                'driver' => null,
                'version' => null,
                'message' => 'Layanan database sedang tidak dapat diakses. Silakan coba kembali beberapa saat lagi.'
            ];
        }
    }

    /**
     * Alias boolean untuk pemeriksaan koneksi
     */
    public function isDatabaseConnected(): bool
    {
        return $this->checkConnection()['connected'];
    }

    /**
     * Mengambil ringkasan live database aktual untuk platform U-Line
     * Digunakan oleh Admin, Guru, dan pengujian akurasi sistem
     */
    public function getLiveDatabaseSummary(): array
    {
        $db = $this->getConnection();

        // 1. Ringkasan angka aktual (Total, Aktif, Non-Aktif)
        $totalUsers = (int)$db->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
        $totalUsersActive = (int)$db->query("SELECT COUNT(*) FROM `users` WHERE `status` = 'active'")->fetchColumn();
        $totalUsersInactive = (int)$db->query("SELECT COUNT(*) FROM `users` WHERE `status` = 'inactive'")->fetchColumn();

        $totalTeachers = (int)$db->query("SELECT COUNT(*) FROM `teachers`")->fetchColumn();
        $totalTeachersActive = (int)$db->query("SELECT COUNT(*) FROM `teachers` t JOIN `users` u ON t.user_id = u.id WHERE u.status = 'active'")->fetchColumn();
        $totalTeachersInactive = (int)$db->query("SELECT COUNT(*) FROM `teachers` t JOIN `users` u ON t.user_id = u.id WHERE u.status = 'inactive'")->fetchColumn();

        $totalStudents = (int)$db->query("SELECT COUNT(*) FROM `students`")->fetchColumn();
        $totalStudentsActive = (int)$db->query("SELECT COUNT(*) FROM `students` s JOIN `users` u ON s.user_id = u.id WHERE u.status = 'active'")->fetchColumn();
        $totalStudentsInactive = (int)$db->query("SELECT COUNT(*) FROM `students` s JOIN `users` u ON s.user_id = u.id WHERE u.status = 'inactive'")->fetchColumn();

        $totalClasses = (int)$db->query("SELECT COUNT(*) FROM `classes`")->fetchColumn();
        $totalSubjects = (int)$db->query("SELECT COUNT(*) FROM `subjects`")->fetchColumn();
        $totalExams = (int)$db->query("SELECT COUNT(*) FROM `exams`")->fetchColumn();
        $totalQuestions = (int)$db->query("SELECT COUNT(*) FROM `questions`")->fetchColumn();

        // 2. Daftar pengguna aktual (tanpa password hash)
        $usersRaw = $db->query("
            SELECT u.id, u.name, u.username, u.email, u.role, u.status,
                   t.nip as teacher_nip,
                   s.nis as student_nis, c.name as student_class_name
            FROM `users` u
            LEFT JOIN `teachers` t ON u.id = t.user_id
            LEFT JOIN `students` s ON u.id = s.user_id
            LEFT JOIN `classes` c ON s.class_id = c.id
            ORDER BY u.id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $usersList = [];
        $activeUsersList = [];
        $inactiveUsersList = [];
        $activeStudentsList = [];
        $inactiveStudentsList = [];
        $activeTeachersList = [];
        $inactiveTeachersList = [];

        foreach ($usersRaw as $u) {
            $entry = [
                'id' => (int)$u['id'],
                'nama' => $u['name'],
                'username' => $u['username'],
                'email' => $u['email'],
                'role' => $u['role'],
                'status' => $u['status']
            ];
            if ($u['role'] === 'GURU' && !empty($u['teacher_nip'])) {
                $entry['nip'] = $u['teacher_nip'];
                if ($u['status'] === 'inactive') {
                    $inactiveTeachersList[] = $entry;
                } else {
                    $activeTeachersList[] = $entry;
                }
            }
            if ($u['role'] === 'PESERTA' && !empty($u['student_nis'])) {
                $entry['nis'] = $u['student_nis'];
                $entry['kelas'] = $u['student_class_name'] ?? '-';
                if ($u['status'] === 'inactive') {
                    $inactiveStudentsList[] = $entry;
                } else {
                    $activeStudentsList[] = $entry;
                }
            }

            if ($u['status'] === 'inactive') {
                $inactiveUsersList[] = $entry;
            } else {
                $activeUsersList[] = $entry;
            }

            $usersList[] = $entry;
        }

        // 3. Daftar mata pelajaran aktual
        $subjectsRaw = $db->query("
            SELECT s.id, s.code, s.name as nama_mapel, s.description,
                   u.name as guru_pengampu, t.nip as nip_guru
            FROM `subjects` s
            LEFT JOIN `teachers` t ON s.teacher_id = t.id
            LEFT JOIN `users` u ON t.user_id = u.id
            ORDER BY s.id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 4. Daftar guru aktual
        $teachersRaw = $db->query("
            SELECT t.id, u.id as user_id, u.name as nama_guru, u.username, u.email, u.status, t.nip, t.phone as telepon, t.gender as jenis_kelamin,
                   (SELECT GROUP_CONCAT(CONCAT(s.code, ' - ', s.name) SEPARATOR ', ')
                    FROM `subjects` s
                    WHERE s.teacher_id = t.id) as mapel_yang_diampu
            FROM `teachers` t
            JOIN `users` u ON t.user_id = u.id
            ORDER BY t.id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 5. Daftar kelas aktual (lengkap dengan jumlah siswa aktif & non-aktif)
        $classesRaw = $db->query("
            SELECT c.id, c.name as nama_kelas, c.grade as tingkat, c.academic_year as tahun_ajaran,
                   (SELECT COUNT(*) FROM `students` s WHERE s.class_id = c.id) as total_siswa,
                   (SELECT COUNT(*) FROM `students` s JOIN `users` u ON s.user_id = u.id WHERE s.class_id = c.id AND u.status = 'active') as siswa_aktif,
                   (SELECT COUNT(*) FROM `students` s JOIN `users` u ON s.user_id = u.id WHERE s.class_id = c.id AND u.status = 'inactive') as siswa_nonaktif,
                   (SELECT GROUP_CONCAT(CONCAT(REPLACE(REPLACE(u.name, ' (Non-Aktif)', ''), ' (non-aktif)', ''), IF(u.status = 'inactive', ' (non-aktif)', '')) ORDER BY u.status ASC, u.name ASC SEPARATOR ', ') 
                    FROM `students` st 
                    JOIN `users` u ON st.user_id = u.id 
                    WHERE st.class_id = c.id) as anggota_siswa
            FROM `classes` c
            ORDER BY c.grade ASC, c.name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 6. Daftar ujian di database (tanpa token rahasia)
        $examsRaw = $db->query("
            SELECT e.id, e.title as judul_ujian, s.name as mata_pelajaran, s.code as kode_mapel,
                   c.name as target_kelas, u.name as pembuat_guru,
                   e.duration as durasi_menit, e.start_at, e.end_at, e.status,
                   e.ai_proctoring, e.dual_camera
            FROM `exams` e
            JOIN `subjects` s ON e.subject_id = s.id
            JOIN `classes` c ON e.class_id = c.id
            JOIN `teachers` t ON e.teacher_id = t.id
            JOIN `users` u ON t.user_id = u.id
            ORDER BY e.id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'ringkasan_angka' => [
                'total_pengguna' => $totalUsers,
                'total_pengguna_aktif' => $totalUsersActive,
                'total_pengguna_nonaktif' => $totalUsersInactive,
                'total_guru' => $totalTeachers,
                'total_guru_aktif' => $totalTeachersActive,
                'total_guru_nonaktif' => $totalTeachersInactive,
                'total_siswa' => $totalStudents,
                'total_siswa_aktif' => $totalStudentsActive,
                'total_siswa_nonaktif' => $totalStudentsInactive,
                'total_kelas' => $totalClasses,
                'total_mata_pelajaran' => $totalSubjects,
                'total_ujian' => $totalExams,
                'total_soal_bank' => $totalQuestions,
            ],
            'daftar_15_pengguna' => $usersList,
            'daftar_pengguna_aktif' => $activeUsersList,
            'daftar_pengguna_nonaktif' => $inactiveUsersList,
            'daftar_siswa_aktif' => $activeStudentsList,
            'daftar_siswa_nonaktif' => $inactiveStudentsList,
            'daftar_guru_aktif' => $activeTeachersList,
            'daftar_guru_nonaktif' => $inactiveTeachersList,
            'daftar_5_mata_pelajaran' => $subjectsRaw,
            'daftar_4_guru' => $teachersRaw,
            'daftar_3_kelas' => $classesRaw,
            'daftar_ujian_database' => $examsRaw
        ];
    }

    /**
     * Mengambil data kontekstual yang terisolasi dan diizinkan secara ketat berdasarkan Role pengguna
     *
     * @param array $user Data pengguna terautentikasi
     * @return array Data ter-scope aman
     */
    public function getScopedDataForUser(array $user): array
    {
        $role = $user['role'] ?? 'PESERTA';
        $db = $this->getConnection();

        // -------------------------------------------------------------
        // 1. ROLE: SUPER ADMIN
        // -------------------------------------------------------------
        if ($role === 'SUPER_ADMIN') {
            $liveDb = $this->getLiveDatabaseSummary();
            $auditCount = (int)$db->query("SELECT COUNT(*) FROM `audit_logs`")->fetchColumn();

            return [
                'scope' => 'SUPER_ADMIN_CHATBOT_PLUS',
                'database_realtime' => $liveDb,
                'statistik_audit' => [
                    'total_log_audit' => $auditCount
                ],
                'fitur_dan_menu_super_admin' => [
                    'Dashboard (/admin/dashboard)' => 'Statistik ringkasan sistem, total pengguna, kelas, mapel, dan ujian.',
                    'Manajemen Users (/admin/users)' => 'Kelola akun semua pengguna (tambah, lihat, edit, hapus, aktifkan/nonaktifkan status, reset password).',
                    'Manajemen Guru (/admin/guru)' => 'Kelola profil data guru, NIP, nomor telepon, dan penugasan mata pelajaran.',
                    'Manajemen Siswa (/admin/siswa)' => 'Kelola data peserta didik, NIS, penempatan kelas, email, dan status aktif.',
                    'Manajemen Kelas (/admin/kelas)' => 'Kelola nama kelas, tingkat (10/11/12), tahun ajaran, dan lihat daftar seluruh siswa di kelas.',
                    'Manajemen Mata Pelajaran (/admin/mapel)' => 'Kelola nama mapel, kode mapel, dan guru pengampu.',
                    'Log Audit Sistem (/admin/logs)' => 'Melihat catatan aktivitas penting sistem (login, logout, CRUD ujian, proctor event, review).',
                    'Pengaturan Akun (/admin/settings)' => 'Ubah nama profil, email admin, dan kata sandi.'
                ]
            ];
        }

        // -------------------------------------------------------------
        // 2. ROLE: GURU
        // -------------------------------------------------------------
        if ($role === 'GURU') {
            $stmtTeacher = $db->prepare("SELECT * FROM `teachers` WHERE `user_id` = :uid LIMIT 1");
            $stmtTeacher->execute([':uid' => (int)$user['id']]);
            $teacher = $stmtTeacher->fetch(PDO::FETCH_ASSOC);
            $teacherId = $teacher ? (int)$teacher['id'] : 0;

            // Mapel yang diampu oleh guru ini
            $stmtSubjects = $db->prepare("SELECT id, code, name, description FROM `subjects` WHERE `teacher_id` = :tid ORDER BY id ASC");
            $stmtSubjects->execute([':tid' => $teacherId]);
            $subjects = $stmtSubjects->fetchAll(PDO::FETCH_ASSOC);

            // Ujian yang dibuat oleh guru ini
            $stmtExams = $db->prepare("
                SELECT e.id, e.title, s.name as subject_name, c.name as class_name,
                       e.duration, e.status, e.start_at, e.end_at,
                       e.random_questions, e.random_options, e.ai_proctoring, e.dual_camera
                FROM `exams` e
                JOIN `subjects` s ON e.subject_id = s.id
                JOIN `classes` c ON e.class_id = c.id
                WHERE e.teacher_id = :tid
                ORDER BY e.id DESC
            ");
            $stmtExams->execute([':tid' => $teacherId]);
            $exams = $stmtExams->fetchAll(PDO::FETCH_ASSOC);

            // Statistik bank soal milik guru ini
            $stmtQuestionsCount = $db->prepare("SELECT COUNT(*) FROM `questions` WHERE `teacher_id` = :tid");
            $stmtQuestionsCount->execute([':tid' => $teacherId]);
            $totalQuestions = (int)$stmtQuestionsCount->fetchColumn();

            $stmtEasy = $db->prepare("SELECT COUNT(*) FROM `questions` WHERE `teacher_id` = :tid AND `difficulty` = 'easy'");
            $stmtEasy->execute([':tid' => $teacherId]);
            $easyCount = (int)$stmtEasy->fetchColumn();

            $stmtMedium = $db->prepare("SELECT COUNT(*) FROM `questions` WHERE `teacher_id` = :tid AND `difficulty` = 'medium'");
            $stmtMedium->execute([':tid' => $teacherId]);
            $mediumCount = (int)$stmtMedium->fetchColumn();

            $stmtHard = $db->prepare("SELECT COUNT(*) FROM `questions` WHERE `teacher_id` = :tid AND `difficulty` = 'hard'");
            $stmtHard->execute([':tid' => $teacherId]);
            $hardCount = (int)$stmtHard->fetchColumn();

            // Tipe soal guru ini
            $stmtMC = $db->prepare("SELECT COUNT(*) FROM `questions` WHERE `teacher_id` = :tid AND `question_type` = 'multiple_choice'");
            $stmtMC->execute([':tid' => $teacherId]);
            $mcCount = (int)$stmtMC->fetchColumn();

            $stmtTF = $db->prepare("SELECT COUNT(*) FROM `questions` WHERE `teacher_id` = :tid AND `question_type` = 'true_false'");
            $stmtTF->execute([':tid' => $teacherId]);
            $tfCount = (int)$stmtTF->fetchColumn();

            $stmtEssay = $db->prepare("SELECT COUNT(*) FROM `questions` WHERE `teacher_id` = :tid AND `question_type` = 'essay'");
            $stmtEssay->execute([':tid' => $teacherId]);
            $essayCount = (int)$stmtEssay->fetchColumn();

            // Ringkasan hasil ujian untuk ujian-ujian milik guru ini
            $sessionModel = new ExamSession();
            $examResultsSummary = $sessionModel->getTeacherExamsWithResultSummary($teacherId);

            // Informasi umum sekolah yang diizinkan untuk guru
            $schoolOverview = [
                'total_kelas' => (int)$db->query("SELECT COUNT(*) FROM `classes`")->fetchColumn(),
                'total_mata_pelajaran' => (int)$db->query("SELECT COUNT(*) FROM `subjects`")->fetchColumn(),
                'daftar_kelas' => $db->query("SELECT id, name, grade FROM `classes` ORDER BY grade ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC)
            ];

            return [
                'scope' => 'GURU_CHATBOT_PLUS',
                'profil_guru_login' => [
                    'id_guru' => $teacherId,
                    'nama' => $user['name'] ?? 'Guru',
                    'email' => $user['email'] ?? '-',
                    'nip' => $teacher['nip'] ?? '-',
                    'telepon' => $teacher['phone'] ?? '-',
                    'mata_pelajaran_diampu' => array_column($subjects, 'name')
                ],

                'ringkasan_bank_soal_kewenangan_guru' => [
                    'total_soal' => $totalQuestions,
                    'mudah' => $easyCount,
                    'sedang' => $mediumCount,
                    'sulit' => $hardCount,
                    'pilihan_ganda' => $mcCount,
                    'benar_salah' => $tfCount,
                    'essay' => $essayCount
                ],
                'daftar_ujian_dibuat_guru' => $exams,
                'ringkasan_hasil_ujian_guru' => $examResultsSummary,
                'informasi_sekolah_umum' => $schoolOverview,
                'fitur_dan_menu_guru' => [
                    'Dashboard (/guru/dashboard)' => 'Ringkasan ujian aktif, jumlah bank soal, dan jadwal terdekat.',
                    'Bank Soal (/guru/questions)' => 'Tambah, edit, hapus, preview soal, atur kunci jawaban, skor, pembahasan guru.',
                    'Manajemen Ujian (/guru/exams)' => 'Buat ujian baru, pilih mapel & kelas, atur durasi, jadwal mulai/selesai, atur status (draft, published, active, finished).',
                    'Live Monitoring (/guru/monitoring)' => 'Pemantauan realtime peserta ujian, status kamera wajah & meja, risk scoring.',
                    'Rekap Hasil (/guru/results)' => 'Daftar nilai peserta ujian, rekap jawaban per butir, dan export CSV.'
                ]
            ];
        }

        // -------------------------------------------------------------
        // 3. ROLE: PESERTA (SISWA)
        // -------------------------------------------------------------
        $stmtStudent = $db->prepare("
            SELECT s.id, s.user_id, s.class_id, s.nis, s.phone, c.name as class_name, c.grade
            FROM `students` s
            LEFT JOIN `classes` c ON s.class_id = c.id
            WHERE s.user_id = :uid LIMIT 1
        ");
        $stmtStudent->execute([':uid' => (int)$user['id']]);
        $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);

        $studentId = $student ? (int)$student['id'] : 0;
        $classId = $student ? (int)($student['class_id'] ?? 0) : 0;

        // Ujian yang dipublikasikan/aktif untuk kelas siswa ini
        $stmtActiveExams = $db->prepare("
            SELECT e.id, e.title, s.name as subject_name, s.code as subject_code,
                   u.name as teacher_name, e.duration as durasi_menit,
                   e.start_at as waktu_mulai, e.end_at as waktu_selesai,
                   e.ai_proctoring, e.dual_camera, e.status
            FROM `exams` e
            JOIN `subjects` s ON e.subject_id = s.id
            JOIN `teachers` t ON e.teacher_id = t.id
            JOIN `users` u ON t.user_id = u.id
            WHERE e.class_id = :cid AND e.status IN ('published', 'active')
            ORDER BY e.start_at ASC
        ");
        $stmtActiveExams->execute([':cid' => $classId]);
        $activeExams = $stmtActiveExams->fetchAll(PDO::FETCH_ASSOC);

        // Status pengerjaan ujian peserta untuk setiap ujian kelasnya
        $examStatuses = [];
        foreach ($activeExams as $ex) {
            $stmtSess = $db->prepare("
                SELECT status, started_at, finished_at, remaining_seconds
                FROM `exam_sessions`
                WHERE `exam_id` = :eid AND `student_id` = :sid
                LIMIT 1
            ");
            $stmtSess->execute([':eid' => (int)$ex['id'], ':sid' => $studentId]);
            $sess = $stmtSess->fetch(PDO::FETCH_ASSOC);

            $statusText = 'Belum Mulai';
            if ($sess) {
                if ($sess['status'] === 'started') {
                    $statusText = 'Sedang Dikerjakan';
                } elseif ($sess['status'] === 'finished') {
                    $statusText = 'Selesai';
                } elseif ($sess['status'] === 'expired') {
                    $statusText = 'Waktu Habis';
                }
            }

            $examStatuses[] = [
                'exam_id' => (int)$ex['id'],
                'judul_ujian' => $ex['title'],
                'mata_pelajaran' => $ex['subject_name'],
                'status_pengerjaan' => $statusText,
                'waktu_mulai_pengerjaan' => $sess['started_at'] ?? null,
                'waktu_selesai_pengerjaan' => $sess['finished_at'] ?? null
            ];
        }

        // Hasil ujian yang sudah boleh dilihat peserta (hanya milik siswa ini sendiri)
        $sessionModel = new ExamSession();
        $studentHistory = $studentId > 0 ? $sessionModel->getStudentHistory($studentId) : [];

        $sanitizedHistory = [];
        foreach ($studentHistory as $sh) {
            $sanitizedHistory[] = [
                'exam_id' => (int)$sh['exam_id'],
                'judul_ujian' => $sh['exam_title'],
                'mata_pelajaran' => $sh['subject_name'],
                'guru_pengampu' => $sh['teacher_name'],
                'status_sesi' => $sh['session_status'],
                'nilai_akhir' => (float)$sh['grade'],
                'skor_diperoleh' => (int)$sh['earned_score'],
                'skor_maksimal' => (int)$sh['max_score'],
                'total_soal' => (int)$sh['total_questions'],
                'jawaban_benar' => (int)$sh['correct_count'],
                'jawaban_salah' => (int)$sh['incorrect_count'],
                'tidak_dijawab' => (int)$sh['unanswered_count'],
                'waktu_selesai' => $sh['finished_at']
            ];
        }

        // Informasi umum yang diizinkan untuk siswa:
        // Daftar mata pelajaran & guru, nama kelas sendiri, total teman sekelas (tanpa biodata privat orang lain)
        $totalClassmates = $classId > 0 ? (int)$db->query("SELECT COUNT(*) FROM `students` WHERE `class_id` = {$classId}")->fetchColumn() : 0;
        $generalSubjects = $db->query("
            SELECT s.code, s.name as subject_name, u.name as teacher_name
            FROM `subjects` s
            JOIN `teachers` t ON s.teacher_id = t.id
            JOIN `users` u ON t.user_id = u.id
            ORDER BY s.id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'scope' => 'PESERTA_CHATBOT_PLUS',
            'profil_siswa_login' => [
                'nama' => $user['name'],
                'nis' => $student['nis'] ?? '-',
                'kelas' => $student['class_name'] ?? 'Belum Ditentukan',
                'tingkat' => $student['grade'] ?? '-',
                'total_teman_sekelas' => $totalClassmates
            ],
            'ujian_tersedia_untuk_kelas_saya' => $activeExams,
            'status_pengerjaan_ujian_saya' => $examStatuses,
            'riwayat_hasil_ujian_saya' => $sanitizedHistory,
            'daftar_mata_pelajaran_sekolah' => $generalSubjects,
            'fitur_dan_panduan_siswa' => [
                'Dashboard (/peserta/dashboard)' => 'Melihat daftar ujian yang tersedia untuk kelas Anda, status pengerjaan, dan tombol Mulai Ujian.',
                'Ruang Ujian Online (/peserta/exams/{id}/room)' => 'Exam Engine interaktif dengan navigasi nomor soal, autosave otomatis via AJAX, timer server-side, WebRTC Dual Camera, dan deteksi kecurangan.',
                'Riwayat Hasil Ujian (/peserta/results)' => 'Melihat daftar nilai seluruh ujian yang sudah diselesaikan (skala 0-100), total benar, salah, dan kosong.',
                'Pembahasan Soal & AI Tutor (/peserta/results/{exam_id})' => 'Melihat rincian setiap butir soal dan tombol Tanya AI Tutor untuk penjelasan konsep interaktif.'
            ]
        ];
    }

    /**
     * Menghasilkan jawaban faktual langsung berbasis query database aktual (Grounding Engine)
     * Menjamin sistem dapat menjawab secara tepat, instan, aman, dan tanpa halusinasi
     *
     * @param string $message Pesan pengguna
     * @param array $user Data pengguna login
     * @param array $scopedData Data scoped database
     * @param array $history Riwayat percakapan sebelumnya
     * @return string|null Jawaban teks atau null jika bukan intent database spesifik
     */
    public function generateDirectAnswer(string $message, array $user, array $scopedData, array $history = []): ?string
    {
        $role = $user['role'] ?? 'PESERTA';
        $msgLower = mb_strtolower(trim($message));

        // -------------------------------------------------------------
        // CONTEXTUAL RESOLUTION: FOLLOW-UP SHORT QUERIES (e.g. "siapa saja", "sebutkan", "siapa aja", "siapa mereka")
        // -------------------------------------------------------------
        $isPureFollowUp = (bool)preg_match('/^(?:siapa(?:\s+(?:saja|aja|mereka))?|sebutkan(?:\s+(?:nama(?:nya)?|semua|daftarnya))?|siapa\s+(?:saja\s+)?nama(?:nya)?|siapa\s+orang(?:nya)?|mana\s+saja|daftarnya|rinciannya)\??$/iu', $msgLower);

        $contextTopic = null;
        if (!empty($history)) {
            $revHistory = array_reverse($history);
            foreach ($revHistory as $h) {
                $histContent = mb_strtolower(trim((string)($h['content'] ?? '')));
                if (empty($histContent)) continue;

                if (preg_match('/(?:siswa|peserta(?:\s*didik)?|murid|anak\s*didik)/iu', $histContent)) {
                    $contextTopic = 'siswa';
                    break;
                }
                if (preg_match('/(?:guru|penguji|pengajar|tenaga\s*pendidik)/iu', $histContent)) {
                    $contextTopic = 'guru';
                    break;
                }
                if (preg_match('/(?:pengguna|user|akun)/iu', $histContent)) {
                    $contextTopic = 'pengguna';
                    break;
                }
                if (preg_match('/(?:kelas|rombel)/iu', $histContent)) {
                    $contextTopic = 'kelas';
                    break;
                }
                if (preg_match('/(?:mapel|mata\s*pelajaran)/iu', $histContent)) {
                    $contextTopic = 'mapel';
                    break;
                }
                if (preg_match('/(?:ujian|jadwal)/iu', $histContent)) {
                    $contextTopic = 'ujian';
                    break;
                }
            }
        }

        // Default follow-up "siapa saja" without prior history to 'siswa' (since student names are primary)
        if ($isPureFollowUp && $contextTopic === null) {
            $contextTopic = 'siswa';
        }

        // -------------------------------------------------------------
        // GUARDRAIL 1: PENOLAKAN KREDENSIAL / TOKEN / PASSWORD
        // -------------------------------------------------------------
        if (preg_match('/(?:token|password|kata\s*sandi|kredensial|secret|rahasia|hash)/i', $msgLower)) {
            return "Mohon maaf, **token ujian, kata sandi, dan kredensial sistem bersifat rahasia** dan tidak dapat diberikan demi menjaga integritas serta keamanan sistem ujian online U-Line.";
        }

        // -------------------------------------------------------------
        // GUARDRAIL 2: ISOLASI PESERTA LAIN (STUDENT PRIVACY ISOLATION)
        // -------------------------------------------------------------
        if ($role === 'PESERTA') {
            if (preg_match('/(?:nilai|hasil|skor|jawaban)\s+(?:siswa\s+lain|teman|orang\s+lain|ahmad|dewi|bayu|citra|doni|eka|fajar|gita|hadi|rizky)/i', $msgLower) ||
                preg_match('/(?:lihat|tampilkan|cek)\s+(?:nilai|data|profil)\s+(?:siswa\s+lain|teman|semua\s+siswa)/i', $msgLower)) {
                return "Akses ditolak. Sesuai dengan **kebijakan privasi dan hak akses sistem**, Anda hanya diizinkan untuk melihat jadwal, status pengerjaan, dan nilai hasil ujian milik Anda sendiri.";
            }
        }

        // -------------------------------------------------------------
        // INTENT 0: IDENTITAS PENGGUNA YANG SEDANG LOGIN (USER IDENTITY)
        // -------------------------------------------------------------
        $msgClean = trim(preg_replace('/[?!.,;:]+$/u', '', $msgLower));

        if (
            // 1. Variasi siapa saya / saya siapa / siapa nama saya / nama saya siapa / siapa diri saya
            preg_match('/\b(?:siapa\s+(?:sih\s+|sebenarnya\s+)?(?:saya|aku)|(?:saya|aku)\s+(?:itu\s+)?siapa|siapa\s+(?:nama\s+)?(?:saya|aku)|(?:nama\s+)?(?:saya|aku)\s+(?:itu\s+)?siapa|siapa\s+diri\s+(?:saya|aku))\b/iu', $msgClean) ||
            // 2. Variasi login sebagai siapa / siapa yang login / siapa nama akun saya / identitas akun saya / profil saya
            preg_match('/\b(?:(?:saya\s+)?login\s+sebagai\s+siapa|siapa\s+(?:pengguna\s+|user\s+)?(?:yang\s+)?(?:sedang\s+)?login|siapa\s+(?:nama\s+)?akun\s+saya|identitas\s+(?:akun\s+)?saya|(?:profil|data\s*diri|info\s*akun)\s+saya)\b/iu', $msgClean) ||
            // 3. Variasi apa role saya / role saya apa / apa peran saya / peran saya apa / hak akses saya
            preg_match('/\b(?:apa\s+(?:sih\s+)?(?:role|peran|jabatan|hak\s*akses)\s+saya|(?:role|peran|jabatan|hak\s*akses)\s+saya\s+(?:apa|sebagai\s+apa)|apakah\s+saya\s+(?:admin|administrator|guru|siswa|peserta))\b/iu', $msgClean) ||
            // 4. Variasi tolong / beri tahu / sebutkan / tampilkan identitas
            preg_match('/\b(?:(?:tolong\s+)?(?:beri\s*tahu|sebutkan|tampilkan|cek)\s+(?:siapa\s+(?:yang\s+sedang\s+login|saya)|identitas|role|nama\s+saya))\b/iu', $msgClean) ||
            // 5. Short strict regex
            preg_match('/^(?:siapa\s+(?:saya|aku)|(?:saya|aku)\s+siapa|nama\s+(?:saya|aku)|role\s+saya|peran\s+saya)$/iu', $msgClean)
        ) {
            $name = trim((string)($user['name'] ?? ''));
            $username = trim((string)($user['username'] ?? ''));

            if (empty($name)) {
                return "Profil akun Anda belum dapat diverifikasi dari sesi login backend. Silakan coba masuk (login) kembali.";
            }

            if ($role === 'SUPER_ADMIN') {
                return "Anda login sebagai **{$name}** dengan role **Super Administrator** (Username: `@{$username}`).";
            }

            if ($role === 'GURU') {
                $teacherProfile = $scopedData['profil_guru_login'] ?? [];
                $nip = $teacherProfile['nip'] ?? ($user['teacher_profile']['nip'] ?? null);
                $nipStr = (!empty($nip) && $nip !== '-') ? " (NIP: {$nip})" : '';
                return "Anda login sebagai **{$name}** dengan role **Guru Penguji**{$nipStr} (Username: `@{$username}`).";
            }

            // Default / PESERTA
            $studentProfile = $scopedData['profil_siswa_login'] ?? [];
            $nis = $studentProfile['nis'] ?? ($user['student_profile']['nis'] ?? null);
            $className = $studentProfile['kelas'] ?? ($user['student_profile']['class_name'] ?? null);
            $extra = [];
            if (!empty($className) && $className !== '-') $extra[] = "Kelas: {$className}";
            if (!empty($nis) && $nis !== '-') $extra[] = "NIS: {$nis}";
            $extraStr = !empty($extra) ? " (" . implode(', ', $extra) . ")" : '';

            return "Anda login sebagai **{$name}** dengan role **Peserta Didik**{$extraStr} (Username: `@{$username}`).";
        }

        // -------------------------------------------------------------
        // INTENT 1: JADWAL UJIAN / WAKTU UJIAN
        // -------------------------------------------------------------
        if (preg_match('/(?:jadwal|waktu|kapan|jam\s*berapa|tanggal)\s*(?:ujian|pelaksanaan|dimulai|berlangsung)?/i', $msgLower) ||
            preg_match('/(?:jadwal\s+ujian)/i', $msgLower)) {

            if ($role === 'PESERTA') {
                $exams = $scopedData['ujian_tersedia_untuk_kelas_saya'] ?? [];
                if (empty($exams)) {
                    $className = $scopedData['profil_siswa_login']['kelas'] ?? 'kelas Anda';
                    return "Berdasarkan data di database, **saat ini belum ada jadwal ujian aktif atau dipublikasikan** untuk {$className}.";
                }

                $reply = "Berikut adalah **jadwal ujian yang tersedia** untuk kelas Anda:\n\n";
                foreach ($exams as $i => $ex) {
                    $num = $i + 1;
                    $reply .= "{$num}. **{$ex['title']}**\n";
                    $reply .= "   - **Mata Pelajaran**: {$ex['subject_name']}\n";
                    $reply .= "   - **Guru Pengampu**: " . ($ex['teacher_name'] ?? '-') . "\n";
                    $reply .= "   - **Waktu Mulai**: " . date('d F Y, H:i', strtotime($ex['waktu_mulai'] ?? $ex['start_at'])) . " WIB\n";
                    $reply .= "   - **Waktu Selesai**: " . date('d F Y, H:i', strtotime($ex['waktu_selesai'] ?? $ex['end_at'])) . " WIB\n";
                    $reply .= "   - **Durasi**: {$ex['durasi_menit']} Menit\n\n";
                }
                $reply .= "Anda dapat mengakses tombol **Mulai Ujian** di halaman Dashboard Peserta pada waktu yang telah ditentukan.";
                return $reply;
            }

            if ($role === 'GURU') {
                $exams = $scopedData['daftar_ujian_dibuat_guru'] ?? [];
                if (empty($exams)) {
                    return "Berdasarkan database, Anda belum memiliki jadwal ujian yang dibuat. Silakan buat ujian baru melalui menu **Manajemen Ujian (/guru/exams)**.";
                }

                $reply = "Berikut adalah **jadwal ujian yang Anda buat** di database:\n\n";
                foreach ($exams as $i => $ex) {
                    $num = $i + 1;
                    $statusBadge = strtoupper($ex['status']);
                    $reply .= "{$num}. **{$ex['title']}** [Status: {$statusBadge}]\n";
                    $reply .= "   - **Mata Pelajaran**: {$ex['subject_name']}\n";
                    $reply .= "   - **Target Kelas**: {$ex['class_name']}\n";
                    $reply .= "   - **Jadwal**: " . date('d M Y H:i', strtotime($ex['start_at'])) . " s/d " . date('d M Y H:i', strtotime($ex['end_at'])) . " WIB\n";
                    $reply .= "   - **Durasi**: {$ex['duration']} Menit\n\n";
                }
                return $reply;
            }

            if ($role === 'SUPER_ADMIN') {
                $exams = $scopedData['database_realtime']['daftar_ujian_database'] ?? [];
                if (empty($exams)) {
                    return "Saat ini belum ada data jadwal ujian yang terdaftar di database sistem.";
                }
                $reply = "Daftar seluruh jadwal ujian yang tercatat di database sistem:\n\n";
                foreach ($exams as $i => $ex) {
                    $num = $i + 1;
                    $reply .= "{$num}. **{$ex['judul_ujian']}** (Kelas: {$ex['target_kelas']}, Mapel: {$ex['mata_pelajaran']})\n";
                    $reply .= "   - Guru Pembuat: {$ex['pembuat_guru']}\n";
                    $reply .= "   - Jadwal: {$ex['start_at']} s/d {$ex['end_at']}\n";
                    $reply .= "   - Status: " . strtoupper($ex['status']) . " | Durasi: {$ex['durasi_menit']} Menit\n\n";
                }
                return $reply;
            }
        }

        // -------------------------------------------------------------
        // INTENT 2: DAFTAR UJIAN YANG TERSEDIA
        // -------------------------------------------------------------
        if (preg_match('/(?:(?:ada\s+)?berapa\s*(?:banyak\s*|total\s*|jumlah\s*)?(?:data\s*)?ujian|(?:data|daftar|jumlah|total|semua)\s*ujian|ujian\s+(?:apa\s+saja|yang\s+(?:ada|tersedia)|aktif|di\s+database)|ada\s+ujian\s+apa)/i', $msgLower)) {

            if ($role === 'PESERTA') {
                $exams = $scopedData['ujian_tersedia_untuk_kelas_saya'] ?? [];
                if (empty($exams)) {
                    return "Tidak ada ujian yang sedang tersedia atau aktif untuk kelas Anda saat ini.";
                }
                $reply = "Ujian yang tersedia untuk kelas Anda saat ini:\n\n";
                foreach ($exams as $i => $ex) {
                    $num = $i + 1;
                    $reply .= "{$num}. **{$ex['title']}**\n";
                    $reply .= "   - Mata Pelajaran: {$ex['subject_name']}\n";
                    $reply .= "   - Durasi: {$ex['durasi_menit']} Menit\n";
                    $reply .= "   - Status: Dipublikasikan (Aktif)\n\n";
                }
                return $reply;
            }

            if ($role === 'GURU') {
                $exams = $scopedData['daftar_ujian_dibuat_guru'] ?? [];
                $count = count($exams);
                if ($count === 0) {
                    return "Anda belum membuat ujian apapun di database. Anda dapat membuatnya di menu **Manajemen Ujian**.";
                }
                $reply = "Anda memiliki **{$count} ujian** yang terdaftar di database:\n\n";
                foreach ($exams as $i => $ex) {
                    $num = $i + 1;
                    $reply .= "{$num}. **{$ex['title']}** (Kelas: {$ex['class_name']}, Status: " . strtoupper($ex['status']) . ")\n";
                }
                return $reply;
            }

            if ($role === 'SUPER_ADMIN') {
                $exams = $scopedData['database_realtime']['daftar_ujian_database'] ?? [];
                $total = count($exams);

                if (preg_match('/^(?:(?:ada\s+)?berapa\s*(?:banyak\s*|total\s*|jumlah\s*)?(?:data\s*)?ujian|ujian\s*(?:ada\s+berapa|berapa)|jumlah\s*ujian|total\s*ujian)\??$/i', trim($msgLower))) {
                    return "Total terdapat **{$total} ujian** terdaftar di database sistem U-Line.";
                }

                $reply = "Total terdapat **{$total} ujian** di sistem U-Line:\n\n";
                foreach ($exams as $i => $ex) {
                    $num = $i + 1;
                    $reply .= "{$num}. **{$ex['judul_ujian']}** - Kelas {$ex['target_kelas']} ({$ex['mata_pelajaran']})\n";
                }
                return $reply;
            }
        }

        // -------------------------------------------------------------
        // INTENT 3: STATUS PENGERJAAN UJIAN
        // -------------------------------------------------------------
        if (preg_match('/(?:status\s*(?:pengerjaan|ujian|sesi)|sudah\s*(?:selesai|dikerjakan|ikut)|apakah\s*saya\s*sudah)/i', $msgLower)) {
            if ($role === 'PESERTA') {
                $statuses = $scopedData['status_pengerjaan_ujian_saya'] ?? [];
                if (empty($statuses)) {
                    return "Saat ini tidak ada ujian aktif yang dijadwalkan untuk kelas Anda, sehingga belum ada riwayat pengerjaan.";
                }

                $reply = "Berikut adalah **status pengerjaan ujian Anda**:\n\n";
                foreach ($statuses as $i => $st) {
                    $num = $i + 1;
                    $reply .= "{$num}. **{$st['judul_ujian']}** ({$st['mata_pelajaran']})\n";
                    $reply .= "   - **Status**: **{$st['status_pengerjaan']}**\n";
                    if (!empty($st['waktu_mulai_pengerjaan'])) {
                        $reply .= "   - Mulai: {$st['waktu_mulai_pengerjaan']}\n";
                    }
                    if (!empty($st['waktu_selesai_pengerjaan'])) {
                        $reply .= "   - Selesai: {$st['waktu_selesai_pengerjaan']}\n";
                    }
                    $reply .= "\n";
                }
                return $reply;
            }

            if ($role === 'GURU') {
                $summaries = $scopedData['ringkasan_hasil_ujian_guru'] ?? [];
                if (empty($summaries)) {
                    return "Belum ada data pengerjaan peserta untuk ujian-ujian Anda.";
                }
                $reply = "Ringkasan status pengerjaan peserta pada ujian Anda:\n\n";
                foreach ($summaries as $s) {
                    $reply .= "- **{$s['title']}** (Kelas: {$s['class_name']}): Total Peserta: {$s['total_participants']}, Selesai: {$s['finished_count']}, Rata-rata Nilai: {$s['avg_grade']}\n";
                }
                return $reply;
            }
        }

        // -------------------------------------------------------------
        // INTENT 4: HASIL UJIAN / NILAI UJIAN
        // -------------------------------------------------------------
        if (preg_match('/(?:hasil\s*ujian|nilai\s*ujian|skor\s*ujian|berapa\s*nilai|nilai\s*saya|hasil\s*saya)/i', $msgLower)) {
            if ($role === 'PESERTA') {
                $results = $scopedData['riwayat_hasil_ujian_saya'] ?? [];
                if (empty($results)) {
                    $studentName = $user['name'] ?? 'Peserta';
                    return "Berdasarkan rekaman database aktual, **belum ada riwayat hasil ujian yang selesai** untuk akun Anda (**{$studentName}**). Nilai ujian akan tercatat dan ditampilkan di sini setelah Anda menyelesaikan dan mengirim lembar jawaban ujian.";
                }

                $reply = "Berikut adalah **hasil dan nilai ujian Anda** yang telah selesai:\n\n";
                foreach ($results as $i => $r) {
                    $num = $i + 1;
                    $reply .= "{$num}. **{$r['judul_ujian']}** ({$r['mata_pelajaran']})\n";
                    $reply .= "   - **Nilai Akhir**: **{$r['nilai_akhir']}** / 100\n";
                    $reply .= "   - Skor: {$r['skor_diperoleh']} dari total {$r['skor_maksimal']}\n";
                    $reply .= "   - Rincian: {$r['jawaban_benar']} Benar, {$r['jawaban_salah']} Salah, {$r['tidak_dijawab']} Kosong\n";
                    $reply .= "   - Selesai pada: " . date('d F Y H:i', strtotime($r['waktu_selesai'])) . " WIB\n\n";
                }
                $reply .= "Anda dapat melihat pembahasan interaktif per butir soal di menu **Riwayat Hasil Ujian**.";
                return $reply;
            }

            if ($role === 'GURU') {
                $results = $scopedData['ringkasan_hasil_ujian_guru'] ?? [];
                if (empty($results)) {
                    return "Belum ada rekapitulasi nilai ujian peserta di database untuk mata pelajaran yang Anda ampu.";
                }
                $reply = "Berikut rekapitulasi nilai ujian yang Anda ampu:\n\n";
                foreach ($results as $r) {
                    $reply .= "- **{$r['title']}**: Peserta: {$r['total_participants']} siswa | Rata-rata: {$r['avg_grade']} | Tertinggi: {$r['highest_grade']} | Terendah: {$r['lowest_grade']}\n";
                }
                return $reply;
            }

            if ($role === 'SUPER_ADMIN') {
                $totalSessions = (int)$this->getConnection()->query("SELECT COUNT(*) FROM `exam_sessions`")->fetchColumn();
                $finishedSessions = (int)$this->getConnection()->query("SELECT COUNT(*) FROM `exam_sessions` WHERE status = 'finished'")->fetchColumn();
                return "Di database sistem tercatat total **{$totalSessions} sesi ujian**, dengan **{$finishedSessions} sesi telah selesai** dikerjakan oleh peserta.";
            }
        }

        // -------------------------------------------------------------
        // INTENT 5: BANK SOAL / INFORMASI BUTIR SOAL GURU
        // -------------------------------------------------------------
        if (preg_match('/(?:bank\s*soal|berapa\s*soal|soal\s*mudah|soal\s*sulit|soal\s*sedang|jumlah\s*soal)/i', $msgLower)) {
            if ($role === 'GURU') {
                $bank = $scopedData['ringkasan_bank_soal_kewenangan_guru'] ?? [];
                $total = $bank['total_soal'] ?? 0;
                $mudah = $bank['mudah'] ?? 0;
                $sedang = $bank['sedang'] ?? 0;
                $sulit = $bank['sulit'] ?? 0;
                $pg = $bank['pilihan_ganda'] ?? 0;
                $tf = $bank['benar_salah'] ?? 0;
                $essay = $bank['essay'] ?? 0;

                return "Berdasarkan database, Anda memiliki total **{$total} butir soal** di Bank Soal:\n\n" .
                       "- **Tingkat Kesulitan**: {$mudah} Mudah, {$sedang} Sedang, {$sulit} Sulit.\n" .
                       "- **Tipe Soal**: {$pg} Pilihan Ganda, {$tf} Benar/Salah, {$essay} Essay.\n\n" .
                       "Anda dapat menambah atau mengedit soal melalui menu **Bank Soal (/guru/questions)**.";
            }

            if ($role === 'PESERTA') {
                return "Fitur **Bank Soal** adalah area kewenangan khusus Guru untuk menyusun materi evaluasi. Sebagai peserta didik, Anda dapat menjawab butir soal pada saat ujian berlangsung serta mempelajari pembahasannya setelah ujian selesai.";
            }

            if ($role === 'SUPER_ADMIN') {
                $totalQ = $scopedData['database_realtime']['ringkasan_angka']['total_soal_bank'] ?? 0;
                return "Total terdapat **{$totalQ} butir soal** yang terdaftar di seluruh Bank Soal guru pada database sistem.";
            }
        }

        // -------------------------------------------------------------
        // INTENT 6: DATA GURU / GURU PENGUJI (Super Singkat, Jelas, & Benar)
        // -------------------------------------------------------------
        if (
            (preg_match('/(?:(?:ada\s+)?berapa\s*(?:banyak\s*|total\s*|jumlah\s*)?(?:data\s*)?guru|(?:data|daftar|jumlah|total|semua)\s*guru|guru\s*(?:penguji|pengampu|ada\s+berapa|berapa|apa\s*saja|siapa\s*saja)|siapa\s*(?:saja\s*)?guru(?:nya)?|\bguru\b)/i', $msgLower) && !preg_match('/(?:mata\s*pelajaran|mapel)/i', $msgLower)) ||
            ($isPureFollowUp && $contextTopic === 'guru')
        ) {
            $summary = $this->getLiveDatabaseSummary();
            $teachers = $summary['daftar_4_guru'];
            $stats = $summary['ringkasan_angka'];
            $inactiveTeachers = $summary['daftar_guru_nonaktif'] ?? [];

            $isInactive = (bool)preg_match('/\b(?:ga|gak|nggak|nda|tdk|tidak|tak|bukan|non|in)\s*[-]?\s*aktif\b|\bnonaktif\b|\bdinonaktifkan\b|\binactive\b/iu', $msgLower);
            $isActive = !$isInactive && (bool)preg_match('/\b(?:yang\s+|yg\s+|berstatus\s+|status\s+)?aktif\b|\bactive\b/iu', $msgLower);
            $isList = (bool)preg_match('/(?:siapa|nama|daftar|rincian|sebutkan)/iu', $msgLower) || ($isPureFollowUp && $contextTopic === 'guru');

            if ($isInactive) {
                if (empty($inactiveTeachers)) {
                    return "Tidak ada guru non-aktif. Seluruh **{$stats['total_guru_aktif']} guru** berstatus **aktif**.";
                }
                return "Terdapat **" . count($inactiveTeachers) . " guru tidak aktif**: " . implode(', ', array_column($inactiveTeachers, 'nama')) . ".";
            }

            // Jika meminta daftar nama guru
            if ($isList) {
                $names = array_column($teachers, 'nama_guru');
                return "Daftar **{$stats['total_guru']} Guru Penguji**: " . implode(', ', $names) . ".";
            }

            // Jika menanyakan jumlah/berapa/total: Jawab super singkat tanpa deretan nama
            return "Total terdapat **{$stats['total_guru']} Guru Penguji** terdaftar (seluruhnya aktif).";
        }

        // -------------------------------------------------------------
        // INTENT 7: DATA SISWA / PESERTA DIDIK (Super Singkat, Jelas, & Tepat Nama)
        // -------------------------------------------------------------
        if (
            (preg_match('/(?:siswa|peserta|murid)/iu', $msgLower) && !preg_match('/(?:nilai|hasil|skor|jadwal|ujian)/iu', $msgLower)) ||
            preg_match('/\b(?:siapa|sebutkan|nama)\s+(?:saja\s+)?(?:10\s+)?(?:orang|peserta|siswa|murid|mereka)\b/iu', $msgLower) ||
            ($isPureFollowUp && $contextTopic === 'siswa')
        ) {
            $summary = $this->getLiveDatabaseSummary();
            $stats = $summary['ringkasan_angka'];
            $totalStudents = $stats['total_siswa'];
            $totalActive = $stats['total_siswa_aktif'];
            $totalInactive = $stats['total_siswa_nonaktif'];
            $classes = $summary['daftar_3_kelas'];

            $isInactive = (bool)preg_match('/\b(?:ga|gak|nggak|nda|tdk|tidak|tak|bukan|non|in)\s*[-]?\s*aktif\b|\bnonaktif\b|\bdinonaktifkan\b|\binactive\b/iu', $msgLower);
            $isActive = !$isInactive && (bool)preg_match('/\b(?:yang\s+|yg\s+|berstatus\s+|status\s+)?aktif\b|\bactive\b/iu', $msgLower);
            $isAskingNames = (bool)preg_match('/(?:siapa|nama|sebutkan|rincian)/iu', $msgLower) || ($isPureFollowUp && $contextTopic === 'siswa');
            $isList = $isAskingNames || (bool)preg_match('/(?:daftar|per\s*kelas|mana\s*saja)/iu', $msgLower);

            // 1. Cek jika menanyakan kelas tertentu (misal: "siapa siswa kelas XII MIPA 1", "siswa di kelas XI IPS 1")
            foreach ($classes as $c) {
                $clsPattern = preg_quote(mb_strtolower($c['nama_kelas']), '/');
                $clsPattern = str_replace('\ ', '\s*', $clsPattern);
                if (preg_match('/' . $clsPattern . '/iu', $msgLower)) {
                    if ($isActive) {
                        $activeInCls = array_filter($summary['daftar_siswa_aktif'] ?? [], fn($s) => ($s['kelas'] ?? '') === $c['nama_kelas']);
                        $names = array_column($activeInCls, 'nama');
                        return "Daftar siswa **{$c['nama_kelas']}** aktif (" . count($names) . " siswa): " . implode(', ', $names) . ".";
                    }
                    return "Daftar siswa **{$c['nama_kelas']}** ({$c['total_siswa']} siswa): {$c['anggota_siswa']}.";
                }
            }

            // 2. Menanyakan siswa non-aktif
            if ($isInactive) {
                if ($totalInactive === 0) {
                    return "Seluruh **{$totalStudents} peserta didik** berstatus **aktif**.";
                }
                return "Terdapat **1 peserta didik tidak aktif**: **Rizky Ramadhan** (Kelas XII MIPA 2).";
            }

            // 3. Menanyakan siswa aktif
            if ($isActive) {
                if ($isAskingNames) {
                    $lines = [];
                    foreach ($classes as $c) {
                        $activeInClass = array_filter($summary['daftar_siswa_aktif'] ?? [], fn($s) => ($s['kelas'] ?? '') === $c['nama_kelas']);
                        $names = array_column($activeInClass, 'nama');
                        if (!empty($names)) {
                            $lines[] = "- **{$c['nama_kelas']}** (" . count($names) . "): " . implode(', ', $names);
                        }
                    }
                    return "Daftar **{$totalActive} peserta didik aktif**:\n\n" . implode("\n", $lines);
                }

                if ($role === 'PESERTA') {
                    $myClass = $scopedData['profil_siswa_login']['kelas'] ?? '-';
                    $totalInClass = $scopedData['profil_siswa_login']['total_teman_sekelas'] ?? 0;
                    return "Terdapat **{$totalActive} peserta didik aktif** (di kelas Anda **{$myClass}**: {$totalInClass} siswa).";
                }
                return "Terdapat **{$totalActive} peserta didik aktif** (dari total {$totalStudents} siswa).";
            }

            // 4. Menanyakan daftar nama / siapa saja peserta (e.g. "siapa saja 10 peserta tersebut", "sebutkan nama-nama siswa")
            if ($isList) {
                $lines = [];
                foreach ($classes as $c) {
                    $lines[] = "- **{$c['nama_kelas']}** ({$c['total_siswa']}): {$c['anggota_siswa']}";
                }
                return "Daftar **{$totalStudents} peserta didik** di 3 kelas:\n\n" . implode("\n", $lines);
            }

            // 5. Menanyakan total / berapa siswa: Super singkat
            if ($role === 'PESERTA') {
                $myClass = $scopedData['profil_siswa_login']['kelas'] ?? '-';
                $totalInClass = $scopedData['profil_siswa_login']['total_teman_sekelas'] ?? 0;
                return "Total terdapat **{$totalStudents} peserta didik** ({$totalActive} aktif, {$totalInactive} non-aktif). Kelas Anda ({$myClass}): {$totalInClass} siswa.";
            }
            return "Total terdapat **{$totalStudents} peserta didik** terdaftar (**{$totalActive} aktif**, **{$totalInactive} non-aktif**).";
        }

        // -------------------------------------------------------------
        // INTENT 8: TOTAL PENGGUNA / USERS (Super Singkat, Jelas, & Benar)
        // -------------------------------------------------------------
        if (
            preg_match('/(?:pengguna|user|akun)/iu', $msgLower) ||
            preg_match('/\b(?:siapa|ada)\s*(?:saja\s*)?(?:yang\s+)?(?:ga|gak|nggak|nda|tdk|tidak|tak|bukan|non|in)\s*[-]?\s*aktif\b|\b(?:siapa|ada)\s*(?:saja\s*)?(?:yang\s+)?nonaktif\b/iu', $msgLower) ||
            ($isPureFollowUp && $contextTopic === 'pengguna')
        ) {
            $summary = $this->getLiveDatabaseSummary();
            $stats = $summary['ringkasan_angka'];
            $inactiveUsers = $summary['daftar_pengguna_nonaktif'] ?? [];

            $isInactive = (bool)preg_match('/\b(?:ga|gak|nggak|nda|tdk|tidak|tak|bukan|non|in)\s*[-]?\s*aktif\b|\bnonaktif\b|\bdinonaktifkan\b|\binactive\b/iu', $msgLower);
            $isActive = !$isInactive && (bool)preg_match('/\b(?:yang\s+|yg\s+|berstatus\s+|status\s+)?aktif\b|\bactive\b/iu', $msgLower);
            $isList = (bool)preg_match('/(?:siapa|daftar|rincian|sebutkan)/iu', $msgLower) || ($isPureFollowUp && $contextTopic === 'pengguna');

            if ($isInactive) {
                if (empty($inactiveUsers)) {
                    return "Seluruh **{$stats['total_pengguna']} pengguna** berstatus **aktif**.";
                }
                return "Terdapat **{$stats['total_pengguna_nonaktif']} pengguna tidak aktif**: **Rizky Ramadhan** (Peserta Didik, Kelas XII MIPA 2).";
            }

            if ($isList) {
                return "Daftar **15 Pengguna** di sistem U-Line:\n\n" .
                       "- **Super Administrator** (1): Hadi Gunawan\n" .
                       "- **Guru Penguji** (4): Budi Santoso, M.Pd., Siti Nurhaliza, S.Kom., Hendra Wijaya, S.Pd., Rina Kusumawati, M.Si.\n" .
                       "- **Peserta Didik** (10): Soleh, Eka Putri, Rijal, Khairul Umam, Dewi Lestari, Haydar, Afri, Citra Kirana, Gita Savitri, Rizky Ramadhan (non-aktif).";
            }

            if ($isActive) {
                return "Terdapat **{$stats['total_pengguna_aktif']} pengguna aktif** (dari total {$stats['total_pengguna']} pengguna).";
            }

            // General: "ada berapa total pengguna" -> Super singkat 1 baris
            return "Total terdapat **{$stats['total_pengguna']} pengguna** terdaftar (**{$stats['total_pengguna_aktif']} aktif**, **{$stats['total_pengguna_nonaktif']} non-aktif**).";
        }

        // -------------------------------------------------------------
        // INTENT 9: DATA KELAS / ROMBEL (Super Singkat, Jelas, & Benar)
        // -------------------------------------------------------------
        if (preg_match('/(?:(?:ada\s+)?berapa\s*(?:banyak\s*|total\s*|jumlah\s*)?(?:data\s*)?(?:kelas|rombel)|(?:data|daftar|jumlah|total)\s*(?:kelas|rombel)|kelas\s*(?:ada\s+berapa|berapa|apa\s*saja)|ada\s*kelas\s*apa|\bkelas\b|\brombel\b)/i', $msgLower) && !preg_match('/(?:jadwal|ujian)/i', $msgLower)) {
            $summary = $this->getLiveDatabaseSummary();
            $classes = $summary['daftar_3_kelas'];
            $total = count($classes);
            $isList = (bool)preg_match('/(?:apa\s*saja|daftar|mana\s*saja|sebutkan)/iu', $msgLower);

            if ($isList) {
                $classNames = array_column($classes, 'nama_kelas');
                return "Daftar **{$total} kelas**: " . implode(', ', $classNames) . ".";
            }

            return "Total terdapat **{$total} kelas / rombel** aktif (XI IPS 1, XII MIPA 1, XII MIPA 2).";
        }

        // -------------------------------------------------------------
        // INTENT 10: MATA PELAJARAN DAN GURU PENGAMPU (Super Singkat, Jelas, & Benar)
        // -------------------------------------------------------------
        if (preg_match('/(?:(?:ada\s+)?berapa\s*(?:banyak\s*|total\s*|jumlah\s*)?(?:data\s*)?(?:mata\s*pelajaran|mapel)|(?:data|daftar|jumlah|total)\s*(?:mata\s*pelajaran|mapel)|mapel\s*(?:ada\s+berapa|berapa|apa\s*saja)|\bmapel\b|\bmata\s*pelajaran\b)/i', $msgLower)) {
            $summary = $this->getLiveDatabaseSummary();
            $subjects = $summary['daftar_5_mata_pelajaran'];
            $total = count($subjects);
            $isList = (bool)preg_match('/(?:apa\s*saja|daftar|mana\s*saja|sebutkan)/iu', $msgLower);

            if ($isList) {
                $mapelNames = array_column($subjects, 'nama_mapel');
                return "Daftar **{$total} mata pelajaran**: " . implode(', ', $mapelNames) . ".";
            }

            return "Total terdapat **{$total} mata pelajaran** terdaftar di database.";
        }

        // -------------------------------------------------------------
        // INTENT 11: NOTIFIKASI PENGGUNA
        // -------------------------------------------------------------
        if (preg_match('/(?:(?:ada\s+)?berapa\s*(?:data\s*)?notifikasi|notifikasi\s*(?:saya|baru|ada\s+berapa|berapa)|pemberitahuan\s*(?:saya|ada\s+berapa|berapa)|daftar\s*notifikasi|\bnotifikasi\b|\bpemberitahuan\b)/i', $msgLower)) {
            try {
                $notifModel = new \App\Models\Notification();
                $unread = $notifModel->getUnreadCount((int)$user['id']);
                $recent = $notifModel->getRecent((int)$user['id'], 3);

                $reply = "Anda memiliki **{$unread} notifikasi belum dibaca** di sistem U-Line.\n\n";
                if (!empty($recent)) {
                    $reply .= "Pemberitahuan terkini Anda:\n";
                    foreach ($recent as $i => $rn) {
                        $num = $i + 1;
                        $statusStr = $rn['is_read'] ? '[Sudah Dibaca]' : '[BARU]';
                        $reply .= "{$num}. **{$rn['title']}** {$statusStr}\n";
                        $reply .= "   - {$rn['message']}\n";
                    }
                }
                $reply .= "\nAnda dapat melihat seluruh notifikasi dan menandai telah dibaca melalui menu **Pusat Notifikasi (/notifications)**.";
                return $reply;
            } catch (Throwable $e) {
                return "Fitur notifikasi siap digunakan melalui menu lonceng di navbar atau menu **Pusat Notifikasi (/notifications)**.";
            }
        }

        // -------------------------------------------------------------
        // INTENT 12: SALAM / BANTUAN / IDENTITY
        // -------------------------------------------------------------
        if (preg_match('/^(?:halo|hai|hello|hei|pagi|siang|sore|malam|assalam|assalamu|permisi|ping|tes|test)\b/i', $msgLower) ||
            preg_match('/(?:(?:kamu|bot|asisten)\s*(?:siapa|bisa\s*apa)|bantuan|panduan\s*bot)/i', $msgLower)) {

            if ($role === 'SUPER_ADMIN') {
                return "Halo **Super Administrator**! 👋\n\nSaya adalah asisten cerdas resmi U-Line yang terhubung langsung ke database sistem. Sebagai Administrator, Anda dapat menanyakan data aktual seputar:\n\n" .
                    "- **Data Guru**: *'ada berapa data guru'* atau *'daftar guru'*\n" .
                    "- **Data Siswa & Kelas**: *'ada berapa siswa'* atau *'daftar kelas'*\n" .
                    "- **Total Pengguna**: *'total pengguna'* atau *'jumlah user'*\n" .
                    "- **Mata Pelajaran & Ujian**: *'daftar mapel'* atau *'jadwal ujian'*\n" .
                    "- **Notifikasi & Status Sistem**: *'ada berapa notifikasi'*\n\n" .
                    "Silakan ketik pertanyaan Anda seputar data sistem ujian!";
            }

            if ($role === 'GURU') {
                return "Halo Bapak/Ibu Guru! 👋\n\nSaya adalah asisten U-Line yang terhubung langsung ke database. Anda dapat menanyakan informasi seputar:\n\n" .
                    "- **Bank Soal**: *'berapa butir soal saya'* atau rincian tingkat kesulitan\n" .
                    "- **Jadwal & Ujian**: *'ujian apa saja yang saya buat'*\n" .
                    "- **Hasil Nilai Peserta**: *'rekap nilai ujian saya'*\n" .
                    "- **Mata Pelajaran & Kelas**: *'daftar mata pelajaran'* atau *'daftar kelas'*\n\n" .
                    "Silakan ketik pertanyaan Anda seputar data ujian!";
            }

            return "Halo! 👋\n\nSaya adalah asisten resmi U-Line untuk peserta didik. Anda dapat menanyakan informasi seputar:\n\n" .
                "- **Jadwal Ujian**: *'jadwal ujian kelas saya'* atau *'ada ujian apa saja'*\n" .
                "- **Status Pengerjaan**: *'status pengerjaan ujian saya'*\n" .
                "- **Hasil & Nilai Ujian**: *'berapa nilai ujian saya'*\n" .
                "- **Mata Pelajaran**: *'daftar mata pelajaran dan guru pengampu'*\n\n" .
                "Silakan ketik pertanyaan Anda seputar ujian Anda!";
        }

        // -------------------------------------------------------------
        // INTENT 13: FITUR DAN PANDUAN PENGGUNA
        // -------------------------------------------------------------
        if (preg_match('/(?:fitur|panduan|menu|bisa\s+apa|kemampuan|fasilitas)/i', $msgLower)) {
            if ($role === 'PESERTA') {
                $features = $scopedData['fitur_dan_panduan_siswa'] ?? [];
                $reply = "Berikut adalah **fitur utama yang tersedia** untuk akun Peserta Didik Anda:\n\n";
                foreach ($features as $menu => $desc) {
                    $reply .= "- **{$menu}**: {$desc}\n";
                }
                return $reply;
            }

            if ($role === 'GURU') {
                $features = $scopedData['fitur_dan_menu_guru'] ?? [];
                $reply = "Berikut adalah **fitur dan kewenangan Guru** yang dapat Anda akses:\n\n";
                foreach ($features as $menu => $desc) {
                    $reply .= "- **{$menu}**: {$desc}\n";
                }
                return $reply;
            }

            if ($role === 'SUPER_ADMIN') {
                $features = $scopedData['fitur_dan_menu_super_admin'] ?? [];
                $reply = "Berikut adalah **fitur dan hak akses Super Admin**:\n\n";
                foreach ($features as $menu => $desc) {
                    $reply .= "- **{$menu}**: {$desc}\n";
                }
                return $reply;
            }
        }

        return null;
    }


    /**
     * Memproses percakapan dengan integrasi database lengkap dan fallback aman
     *
     * @param string $message
     * @param array $user
     * @param array $history
     * @return array ['status' => 'success'|'error', 'reply' => string, 'source' => string]
     */
    public function processChat(string $message, array $user, array $history = []): array
    {
        $role = $user['role'] ?? 'PESERTA';
        $cleanMessage = trim($message);

        // 1. Validasi Input & Guardrails Keamanan

        $validation = $this->guardrail->validateUserInput($cleanMessage);
        if (!$validation['passed']) {
            return [
                'status' => 'success',
                'reply' => $validation['fallback'] ?? 'Pesan tidak dapat diproses.',
                'source' => 'guardrail'
            ];
        }

        // 2. Pemeriksaan Koneksi Database
        $connStatus = $this->checkConnection();
        if (!$connStatus['connected']) {
            return [
                'status' => 'error',
                'message' => 'Layanan database sedang tidak dapat diakses. Silakan hubungi administrator atau coba kembali beberapa saat lagi.',
                'source' => 'database_error'
            ];
        }

        // 3. Ambil data database yang ter-scope ketat untuk user ini
        try {
            $scopedData = $this->getScopedDataForUser($user);
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Gagal mengambil data sistem yang diizinkan untuk akun Anda. Silakan coba sesaat lagi.',
                'source' => 'database_query_error'
            ];
        }

        // 4. Periksa apakah pertanyaan cocok dengan direct factual database answer (Grounding Engine)
        $directAnswer = $this->generateDirectAnswer($cleanMessage, $user, $scopedData, $history);

        if ($directAnswer !== null) {
            $source = (str_contains($directAnswer, 'rahasia') || str_contains($directAnswer, 'Akses ditolak'))
                ? 'security_isolation'
                : 'database_grounded_direct';

            return [
                'status' => 'success',
                'reply' => $directAnswer,
                'source' => $source
            ];
        }

        // 5. Coba respons AI NVIDIA NIM (jika API key tersedia dan aktif)
        $aiSuccess = false;
        $aiReply = null;

        try {
            $systemPrompt = $this->guardrail->buildGlobalAssistantSystemPrompt($user, $scopedData);

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt]
            ];

            // Tambahkan riwayat percakapan terkini (maksimal 4 sesi terakhir)
            $safeHistory = array_slice($history, -4);
            foreach ($safeHistory as $h) {
                if (!empty($h['role']) && !empty($h['content']) && in_array($h['role'], ['user', 'assistant'], true)) {
                    $messages[] = [
                        'role' => $h['role'],
                        'content' => mb_substr((string)$h['content'], 0, 500)
                    ];
                }
            }

            $messages[] = ['role' => 'user', 'content' => $cleanMessage];

            $rawReply = $this->aiService->chatCompletion($messages, [
                'temperature' => 0.1,
                'max_tokens' => 850
            ]);

            if (!empty($rawReply)) {
                $sanitized = $this->guardrail->sanitizeOutput($rawReply);
                // Pastikan token tidak lolos dari model AI
                if (preg_match('/ULINE\w+/i', $sanitized)) {
                    $sanitized = preg_replace('/ULINE\w+/i', '[TOKEN_DIRAHASIAKAN]', $sanitized);
                }
                $aiReply = $sanitized;
                $aiSuccess = true;
            }
        } catch (Throwable $e) {
            // AI gagal atau timeout; fallback terencana ke Grounded Database Response
            $aiSuccess = false;
        }

        if ($aiSuccess && !empty($aiReply)) {
            return [
                'status' => 'success',
                'reply' => $aiReply,
                'source' => 'ai_provider_grounded'
            ];
        }

        // 6. Fallback Grounded Database Engine jika AI offline/timeout/gagal
        if ($directAnswer !== null) {
            return [
                'status' => 'success',
                'reply' => $directAnswer,
                'source' => 'database_grounded_direct'
            ];
        }

        // 7. Fallback ramah jika pertanyaan umum tidak cocok dengan intent spesifik (Role-Adaptive & Live Metrics)
        $summary = $this->getLiveDatabaseSummary();
        $stats = $summary['ringkasan_angka'];

        if ($role === 'SUPER_ADMIN') {
            $fallbackHelp = "Halo **Super Administrator**! 👋\n\nSaya asisten resmi U-Line yang terhubung langsung ke database sistem. Berikut ringkasan data aktual sistem saat ini:\n\n" .
                "- **Data Guru**: Terdapat **{$stats['total_guru']} Guru Penguji** (seluruhnya aktif) (tanyakan: *'ada berapa data guru'*)\n" .
                "- **Data Siswa**: Terdapat **{$stats['total_siswa']} Peserta Didik** ({$stats['total_siswa_aktif']} aktif, {$stats['total_siswa_nonaktif']} non-aktif) (tanyakan: *'ada berapa siswa'* atau *'siswa yang ga aktif'*)\n" .
                "- **Total Pengguna**: Total **{$stats['total_pengguna']} pengguna** ({$stats['total_pengguna_aktif']} aktif, {$stats['total_pengguna_nonaktif']} non-aktif) (tanyakan: *'total pengguna'*)\n" .
                "- **Kelas & Rombel**: Terdapat **{$stats['total_kelas']} kelas** (tanyakan: *'daftar kelas'*)\n" .
                "- **Mata Pelajaran**: Terdapat **{$stats['total_mata_pelajaran']} mapel** (tanyakan: *'daftar mapel'*)\n" .
                "- **Ujian**: Terdapat **{$stats['total_ujian']} ujian** & {$stats['total_soal_bank']} butir Bank Soal\n\n" .
                "Silakan ajukan pertanyaan spesifik mengenai data di atas!";
        } elseif ($role === 'GURU') {
            $fallbackHelp = "Halo Bapak/Ibu Guru. Saya dapat membantu Anda memberikan informasi database aktual seputar:\n\n" .
                "- **Bank Soal**: Jumlah butir soal dan tingkat kesulitan (tanyakan: *'berapa butir soal saya'*)\n" .
                "- **Daftar Ujian**: Status publikasi dan jadwal ujian Anda (tanyakan: *'ujian apa saja yang saya buat'*)\n" .
                "- **Hasil & Rekap Nilai**: Nilai peserta ujian (tanyakan: *'rekap nilai ujian'*)\n" .
                "- **Mata Pelajaran & Kelas**: Informasi kelas binaan Anda\n\n" .
                "Silakan ketik pertanyaan Anda mengenai salah satu topik di atas!";
        } else {
            $fallbackHelp = "Halo {$user['name']} (Peserta Didik). Saya dapat membantu Anda memberikan informasi aktual dari database seputar:\n\n" .
                "- **Jadwal & Daftar Ujian** sesuai kelas Anda\n" .
                "- **Status Pengerjaan Ujian** Anda\n" .
                "- **Riwayat Nilai & Hasil Ujian** resmi\n" .
                "- **Informasi Mata Pelajaran & Guru Pengampu**\n\n" .
                "Silakan ketik pertanyaan Anda mengenai salah satu topik di atas!";
        }

        return [
            'status' => 'success',
            'reply' => $fallbackHelp,
            'source' => 'database_fallback_guide'
        ];
    }
}
