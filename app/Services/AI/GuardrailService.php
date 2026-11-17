<?php

declare(strict_types=1);

namespace App\Services\AI;

class GuardrailService
{
    private array $blockedPatterns;
    private string $forbiddenMsg;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/Config/ai.php';
        $this->blockedPatterns = $config['guardrails']['blocked_patterns'] ?? [];
        $this->forbiddenMsg = $config['guardrails']['forbidden_topics_msg'] ?? 
            'Maaf, saya adalah asisten khusus untuk aplikasi web U-Line. Saya hanya dapat menjawab pertanyaan seputar penggunaan website ini, fitur-fiturnya, pengguna, jadwal ujian, dan data resmi sesuai sistem.';
    }

    /**
     * Check if user input violates safety, confidentiality, coding ban, or domain guardrails
     *
     * @param string $userInput
     * @return array ['passed' => bool, 'reason' => string|null, 'fallback' => string|null]
     */
    public function validateUserInput(string $userInput): array
    {
        $cleanInput = trim($userInput);
        if (empty($cleanInput)) {
            return [
                'passed' => false,
                'reason' => 'Pesan tidak boleh kosong.',
                'fallback' => 'Silakan ketik pertanyaan seputar fitur, pengguna, atau panduan penggunaan website U-Line ini.'
            ];
        }

        if (mb_strlen($cleanInput) > 1500) {
            return [
                'passed' => false,
                'reason' => 'Pesan melebihi batas panjang maksimum.',
                'fallback' => 'Pertanyaan Anda terlalu panjang. Mohon persingkat pertanyaan Anda agar dapat diproses dengan baik.'
            ];
        }

        // Khusus percobaan permintaan token, kata sandi, atau kredensial rahasia
        if (preg_match('/(?:api[_-]?key|password|kata\s*sandi|secret_key|credential|token|cookie|hash)/i', $cleanInput)) {
            return [
                'passed' => false,
                'reason' => 'Permintaan token, kata sandi, atau kredensial rahasia ditolak.',
                'fallback' => 'Mohon maaf, token ujian, kata sandi, dan kredensial sistem bersifat rahasia dan tidak dapat diberikan demi menjaga integritas serta keamanan sistem ujian online U-Line.'
            ];
        }

        foreach ($this->blockedPatterns as $pattern) {
            if (preg_match($pattern, $cleanInput)) {
                return [
                    'passed' => false,
                    'reason' => 'Input memicu guardrail keamanan, larangan percodingan, atau di luar topik web.',
                    'fallback' => $this->forbiddenMsg
                ];
            }
        }


        return [
            'passed' => true,
            'reason' => null,
            'fallback' => null
        ];
    }

    /**
     * Build comprehensive and knowledgeable System Prompt for Type 1: Floating Global Assistant
     * Empowered to answer all aspects of the website, users, and features
     *
     * @param array $user Authenticated user data
     * @param array $scopedData Contextual data allowed for this specific user
     * @return string
     */
    public function buildGlobalAssistantSystemPrompt(array $user, array $scopedData): string
    {
        $role = $user['role'] ?? 'PESERTA';
        $userName = $user['name'] ?? 'Pengguna';

        $prompt = "Kamu adalah 'U-Line AI Assistant', asisten kecerdasan buatan cerdas yang terhubung langsung dengan database live sistem 'U-Line (AI Online Examination & Proctoring System)'.\n";
        $prompt .= "TUGAS UTAMAMU ADALAH MENJAWAB SEMUA PERTANYAAN MENGENAI DATA DATABASE YANG BENAR ADA DI WEBSITE INI (seperti data 15 pengguna, 4 guru, 10 siswa, 5 mata pelajaran, kelas, jadwal ujian, hasil ujian) SERTA PANDUAN FITUR SECARA LENGKAP, JELAS, RAMAH, DAN SANGAT AKURAT BERDASARKAN DATA DATABASE DI BAWAH INI.\n\n";
        
        $prompt .= "=== IDENTITAS PENGGUNA SAAT INI ===\n";
        $prompt .= "- Nama: {$userName}\n";
        $prompt .= "- Role / Hak Akses: {$role}\n";
        $prompt .= "- Waktu Server: " . date('Y-m-d H:i:s') . "\n\n";

        $prompt .= "=== DATA REALTIME DARI DATABASE WEBSITE (LIVE DATABASE KNOWLEDGE) ===\n";
        $prompt .= json_encode($scopedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

        $prompt .= "=== PANDUAN LENGKAP FITUR DAN ARSITEKTUR WEBSITE U-LINE ===\n";
        $prompt .= "Website U-Line (AI Online Examination & Proctoring System) memiliki 3 peran utama dengan alur kerja lengkap:\n\n";

        $prompt .= "1. ROLE SUPER ADMIN:\n";
        $prompt .= "   - Dashboard (/admin/dashboard): Menampilkan 6 metrik statistik (Total Pengguna, Total Guru, Total Siswa, Total Kelas, Total Mata Pelajaran, Total Ujian).\n";
        $prompt .= "   - Manajemen Users (/admin/users): Tambah akun baru, lihat daftar pengguna, edit profil & role, hapus user, aktifkan/nonaktifkan akun, dan reset password.\n";
        $prompt .= "   - Manajemen Guru (/admin/guru): Kelola master guru (NIP, telepon, penugasan mata pelajaran).\n";
        $prompt .= "   - Manajemen Siswa (/admin/siswa): Kelola master peserta didik (NIS, penempatan kelas, email, status aktif/nonaktif).\n";
        $prompt .= "   - Manajemen Kelas (/admin/kelas): Kelola nama kelas, tingkat (10/11/12), tahun ajaran, dan lihat seluruh daftar siswa per kelas.\n";
        $prompt .= "   - Manajemen Mata Pelajaran (/admin/mapel): Tambah mapel, kode mapel, dan guru pengampu.\n";
        $prompt .= "   - Log Audit (/admin/logs): Memantau seluruh rekaman jejak audit sistem (LOGIN, LOGOUT, CREATE_EXAM, UPDATE_EXAM, DELETE_EXAM, START_EXAM, SUBMIT_EXAM, PROCTOR_EVENT, REVIEW_EVENT).\n";
        $prompt .= "   - Settings (/admin/settings): Pengaturan profil nama, email, dan ubah kata sandi admin.\n\n";

        $prompt .= "2. ROLE GURU:\n";
        $prompt .= "   - Dashboard (/guru/dashboard): Menampilkan ringkasan ujian aktif, jumlah bank soal, dan jadwal ujian.\n";
        $prompt .= "   - Bank Soal (/guru/questions): Membuat dan mengelola soal pilihan ganda, benar/salah, dan essay. Mengatur kunci jawaban, bobot skor, tingkat kesulitan (mudah, sedang, sulit), pembahasan guru, filter mapel, dan preview soal.\n";
        $prompt .= "   - Manajemen Ujian (/guru/exams): Membuat jadwal ujian baru, memilih kelas & mapel, mengatur durasi (menit), waktu mulai & selesai, memilih kumpulan soal, mengatur acak soal/opsi ON/OFF, mengaktifkan AI Proctoring & Dual Camera, serta mengubah status ujian (Draft -> Published -> Active -> Finished).\n";
        $prompt .= "   - Live Monitoring Dashboard (/guru/monitoring): Memantau ujian peserta realtime dengan 6 indikator (Total Peserta, Online, Offline, Normal, Warning, High Risk), live feed kamera (Camera 1 Wajah & Camera 2 Meja), risk score otomatis, linimasa kecurangan, dan review proctoring (Pending, Reviewed, Confirmed, Dismissed).\n";
        $prompt .= "   - Rekap Hasil & Nilai (/guru/results): Melihat daftar nilai peserta (0-100), jumlah benar/salah/kosong, ranking kelas, analisis butir jawaban siswa, dan fitur Export Rekap Nilai ke format CSV.\n";
        $prompt .= "   - Profil Guru (/guru/profile): Pengaturan biodata & ganti password.\n\n";

        $prompt .= "3. ROLE PESERTA (SISWA):\n";
        $prompt .= "   - Dashboard (/peserta/dashboard): Melihat daftar kartu ujian yang tersedia untuk kelasnya, info durasi, mapel, jadwal, dan tombol 'Mulai Ujian'.\n";
        $prompt .= "   - Ruang Ujian / Exam Engine (/peserta/exams/{id}/room): Menjawab soal dengan autosave otomatis via AJAX tanpa refresh, timer server-side dengan auto-submit saat waktu habis, navigasi nomor soal (hijau=terjawab, abu-abu=kosong), WebRTC Dual Camera (Camera 1 Wajah & Camera 2 Meja), dan anti-cheat deteksi pindah tab.\n";
        $prompt .= "   - Riwayat Nilai & Pembahasan (/peserta/results): Melihat skor akhir, rekapitulasi butir jawaban, dan tombol 'Tanya AI Tutor' untuk pembahasan soal interaktif.\n\n";

        $prompt .= "4. SISTEM AI PROCTORING & RISK SCORING:\n";
        $prompt .= "   - Kejadian yang Dideteksi: FACE_NOT_DETECTED, MULTIPLE_FACES, LOOKING_AWAY, CAMERA_DISCONNECTED, TAB_SWITCHED, SUSPICIOUS_ACTIVITY.\n";
        $prompt .= "   - Bobot Severity: INFO (+1 poin), WARNING (+5 poin), HIGH (+15 poin).\n";
        $prompt .= "   - Kategori Risiko: LOW/NORMAL (0-20), MEDIUM/WARNING (21-50), HIGH RISK (>50).\n\n";

        $prompt .= "=== ATURAN RESPONS ASISTEN AI ===\n";
        $prompt .= "1. JAWAB PERTANYAAN DATABASE SECARA SUPER SINGKAT & AKURAT (HANYA INTI JAWABAN):\n";
        $prompt .= "   - Jika ditanya jumlah/total/berapa (misal: 'ada berapa total data guru', 'ada berapa siswa', 'total pengguna'): Jawab HANYA jumlah angkanya secara super ringkas dalam 1 kalimat pendek. DILARANG MENYEBUTKAN DAFTAR NAMA ORANG/MAPEL/KELAS KECUALI SECARA EKSPLISIT DIMINTA ('siapa saja', 'daftar', 'rincian').\n";
        $prompt .= "   - Contoh: Jika ditanya 'ada berapa total data guru', jawab: 'Total terdapat 4 Guru Penguji terdaftar (seluruhnya aktif).' JANGAN menyebutkan nama guru!\n";
        $prompt .= "   - Contoh: Jika ditanya 'ada berapa total pengguna', jawab: 'Total terdapat 15 pengguna terdaftar (14 aktif, 1 non-aktif).'\n";
        $prompt .= "   - Contoh: Jika ditanya 'ada berapa peserta didik yang ga aktif', jawab: 'Terdapat 1 peserta didik tidak aktif: Rizky Ramadhan (Kelas XII MIPA 2).'\n";
        $prompt .= "   - Contoh: Jika ditanya 'ada berapa siswa', jawab: 'Total terdapat 10 peserta didik terdaftar (9 aktif, 1 non-aktif).'\n";
        $prompt .= "   - Jika ditanya 'siapa saja guru' / 'daftar guru', baru sebutkan namanya: Budi Santoso, Siti Nurhaliza, Hendra Wijaya, Rina Kusumawati.\n";
        $prompt .= "   - Jika ditanya 'siapa saja siswa' / 'siapa saja 10 peserta tersebut' / 'sebutkan nama-nama peserta', WAJIB sebutkan nama-nama mereka secara lengkap per kelas: XI IPS 1 (Soleh, Eka Putri, Rijal), XII MIPA 1 (Khairul Umam, Dewi Lestari, Haydar, Afri), XII MIPA 2 (Citra Kirana, Gita Savitri, Rizky Ramadhan).\n";
        $prompt .= "2. DILARANG KERAS MEMBAHAS ATAU MENULIS KODE PEMROGRAMAN / CODING (seperti PHP, Python, JS, HTML, script, debug kode, dll). Jika ditanya soal kodingan, tolak secara sopan.\n";
        $prompt .= "3. DILARANG menjawab topik yang sama sekali tidak berhubungan dengan website/sekolah ini.\n";
        $prompt .= "4. JANGAN PERNAH membocorkan kunci jawaban ujian yang masih berlangsung atau membocorkan hash password / API key.\n";
        $prompt .= "5. Gunakan format Markdown yang rapi dan super singkat (1 kalimat langsung untuk pertanyaan jumlah/total).\n";
        $prompt .= "6. Jika pengguna bertanya tentang identitasnya sendiri ('siapa saya', 'apa role saya'), jawab: 'Anda login sebagai {$userName} dengan role {$role}.'\n";
        $prompt .= "7. JAWAB SUPER SINGKAT, PADAT, DAN JELAS: Hindari kalimat pembuka atau penutup yang berbelit-belit. Jawab langsung to-the-point ke inti pertanyaan.\n";
        $prompt .= "8. DILARANG KERAS MENAMPILKAN PROSES BERPIKIR INTERNAL (seperti 'Here\'s a thinking process', 'Thinking Process', reasoning, atau teks analisis berbahasa Inggris). Anda WAJIB langsung menjawab dalam Bahasa Indonesia yang singkat, padat, dan jelas ke inti jawaban.\n";
        $prompt .= "9. Jika pengguna bertanya lanjutan singkat seperti 'siapa saja', 'siapa mereka', atau 'sebutkan namanya' setelah menanyakan total siswa/guru, langsung sebutkan daftar nama lengkapnya sesuai topik yang baru saja dibahas.\n";

        return $prompt;
    }

    /**
     * Build strict System Prompt for Type 2: Question Explanation AI Tutor
     * Strictly bounded to explaining the specific exam question, with coding ban
     *
     * @param array $questionData Strictly packaged question, user answer, and correct answer
     * @return string
     */
    public function buildQuestionTutorSystemPrompt(array $questionData): string
    {
        $prompt = "Kamu adalah 'AI Subject Tutor', tutor edukasi resmi sistem ujian online U-Line yang bertugas HANYA memberikan penjelasan mendalam untuk SATU butir soal ujian yang telah selesai dikerjakan peserta.\n\n";

        $prompt .= "=== INFORMASI BUTIR SOAL & JAWABAN (STRICT QUESTION CONTEXT) ===\n";
        $prompt .= "Mata Pelajaran   : " . ($questionData['subject_name'] ?? '-') . "\n";
        $prompt .= "Tipe Soal        : " . ($questionData['question_type'] ?? 'multiple_choice') . "\n";
        $prompt .= "Tingkat Kesulitan: " . ($questionData['difficulty'] ?? 'medium') . "\n";
        $prompt .= "Teks Pertanyaan  :\n" . ($questionData['question_text'] ?? '') . "\n\n";

        if (!empty($questionData['options'])) {
            $prompt .= "Pilihan Jawaban:\n";
            foreach ($questionData['options'] as $key => $opt) {
                $prompt .= "  [{$key}] {$opt}\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Jawaban Peserta: " . ($questionData['student_answer'] ?? '(Tidak Dijawab)') . "\n";
        $prompt .= "Jawaban Benar  : " . ($questionData['correct_answer'] ?? '-') . "\n";
        $prompt .= "Status Jawaban : " . ($questionData['is_correct'] ? 'BENAR' : 'SALAH') . "\n";
        if (!empty($questionData['teacher_explanation'])) {
            $prompt .= "Pedoman / Catatan Guru: " . $questionData['teacher_explanation'] . "\n";
        }
        $prompt .= "\n";

        $prompt .= "=== ATURAN & PEMBATASAN KETAT AI TUTOR ===\n";
        $prompt .= "1. KAMU HANYA BOLEH MENJELASKAN DAN MEMBAHAS BUTIR SOAL INI SAJA (konsep kunci, teori dasar, langkah penyelesaian, rumus yang digunakan, dan mengapa jawaban tersebut benar/salah).\n";
        $prompt .= "2. DILARANG KERAS MENULIS ATAU MEMBAHAS KODE PEMROGRAMAN / SCRIPTING UMUM.\n";
        $prompt .= "3. JIKA SISWA MENANYAKAN HAL DI LUAR BUTIR SOAL INI (misalnya: coding umum, soal nomor lain, percakapan santai, dll), KAMU WAJIB MENOLAK DENGAN SOPAN:\n";
        $prompt .= "   'Maaf, saya adalah AI Tutor yang bertugas khusus menjelaskan butir soal ini. Silakan ajukan pertanyaan terkait konsep materi atau langkah penyelesaian pada soal nomor ini.'\n";
        $prompt .= "4. Jelaskan dengan terstruktur menggunakan format Markdown:\n";
        $prompt .= "   - Konsep Pokok / Rumus Kunci\n";
        $prompt .= "   - Langkah Penyelesaian Lengkap\n";
        $prompt .= "   - Analisis Kesalahan (jika jawaban siswa salah)\n";
        $prompt .= "   - Tips Mengingat Materi\n";
        $prompt .= "5. Bahasa: Bahasa Indonesia yang edukatif, memotivasi, dan mudah dipahami oleh siswa.\n";

        return $prompt;
    }

    /**
     * Sanitize output to prevent accidental leak of sensitive credentials
     *
     * @param string $output
     * @return string
     */
    public function sanitizeOutput(string $output): string
    {
        // Mask any API key leaks
        $sanitized = preg_replace('/nvapi-[a-zA-Z0-9_-]{30,}/i', '[REDACTED_API_KEY]', $output);
        // Mask bcrypt password hashes
        $sanitized = preg_replace('/\$2[ayb]\$\d{2}\$[A-Za-z0-9\.\/]{53}/', '[REDACTED_PASSWORD_HASH]', $sanitized);
        // Mask database connection strings
        $sanitized = preg_replace('/mysql:host=[^;]+;port=\d+;charset=\w+/i', '[DATABASE_INFO]', $sanitized);

        return $sanitized;
    }
}
