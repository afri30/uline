# U-Line: AI Online Examination & Dual-Camera Proctoring System

[![PHP Version](https://img.shields.io/badge/PHP-8.1%20%7C%208.2-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL-8.0%20(19%20Tables)-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Frontend](https://img.shields.io/badge/UI-Bootstrap%205.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Streaming](https://img.shields.io/badge/Media-WebRTC%20Dual%20Camera-333333?style=flat-square&logo=webrtc&logoColor=white)](https://webrtc.org/)
[![AI Services](https://img.shields.io/badge/AI%20Inference-NVIDIA%20NIM%20%7C%20Llama--3.1--70B-76B900?style=flat-square&logo=nvidia&logoColor=white)](https://build.nvidia.com/)
[![Notification Gateway](https://img.shields.io/badge/Gateway-Fonnte%20WhatsApp%20v2-25D366?style=flat-square&logo=whatsapp&logoColor=white)](https://fonnte.com/)
[![CI/CD Pipeline](https://img.shields.io/badge/CI%2FCD-GitHub%20Actions%20Passing-2088FF?style=flat-square&logo=githubactions&logoColor=white)](https://github.com/afri30/uline/actions)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)

> **U-Line** adalah sistem ujian online (*Computer-Based Testing* / CBT) generasi mutakhir yang dirancang dengan arsitektur murni **PHP Native Model-View-Controller (MVC)**, terintegrasi dengan **WebRTC Dual-Camera Video Streaming**, **AI Anomaly Risk Scoring Engine**, **Multi-LLM Subject Tutor**, **Factual Database Chatbot Grounding**, dan **Fonnte WhatsApp 2FA Gateway**.

---

## 📌 Identitas Proyek Tugas Akhir / UAS

Dokumen dan kode sumber ini disusun untuk memenuhi persyaratan nilai tugas akhir / UAS mata kuliah **Rekayasa Perangkat Lunak (RPL)**:

* **Nama Mahasiswa**: **Rian Afriyansyah**
* **NIM**: **41257154**
* **Mata Kuliah / Kode**: Rekayasa Perangkat Lunak (TIKB2503021) — 4 SKS
* **Program Studi**: S1 Teknik Informatika
* **Perguruan Tinggi**: **STMIK IKMI Cirebon**
* **Dosen Pengampu**: **Yudhistira Arie Wijaya, M.Kom., CDS.**
* **Tautan Repository GitHub**: [https://github.com/afri30/uline.git](https://github.com/afri30/uline.git)

---

## 🚀 Fitur Utama Sistem U-Line

### 1. Autentikasi & Keamanan Berlapis (RBAC & 2FA OTP)
- **Role-Based Access Control (RBAC)**: Pemisahan hak akses ketat antara `SUPER_ADMIN`, `GURU`, dan `PESERTA` (Siswa).
- **Two-Factor Authentication (2FA) WhatsApp OTP**: Login alternatif aman tanpa password menggunakan token numerik 6-digit yang dikirimkan via Fonnte WhatsApp API (kadaluwarsa 5 menit, proteksi *brute-force* maksimal 5 percobaan).
- **Keamanan Aplikasi Standar OWASP**: Enkripsi password menggunakan `bcrypt`, token CSRF pada setiap form mutasi data, sesi terisolasi (`HttpOnly`, `SameSite=Lax`), sanitasi input PDO prepared statements, dan output escaping anti-XSS via `e()`.

### 2. Distraction-Free Exam Engine & Debounced Autosave
- **Antarmuka Ujian Bebas Distraksi**: Navigasi sidebar dan footer disembunyikan saat ujian aktif agar siswa fokus penuh pada butir soal.
- **Debounced Autosave (1500ms)**: Perubahan jawaban disimpan secara asinkron ke server via Fetch API tanpa me-reload halaman, mengurangi beban lonjakan query basis data.
- **Server-Side Timer Enforcement**: Perhitungan sisa waktu diverifikasi mutlak di server; sesi otomatis ditutup saat durasi berakhir untuk mencegah kecurangan manipulasi jam lokal.
- **Randomisasi Soal & Opsi**: Mendukung pengacakan nomor soal dan pengacakan pilihan ganda per peserta ujian.

### 3. Dual-Camera WebRTC Proctoring
- **Kamera 1 (Face Camera)**: Menangkap wajah peserta dari arah depan layar monitor untuk mendeteksi kehadiran wajah dan fokus tatapan.
- **Kamera 2 (Desk/Workspace Camera)**: Mengawasi area meja kerja, tangan, dan perangkat di sekitar peserta menggunakan smartphone atau webcam eksternal.
- **Graceful Single-Camera Fallback**: Sistem otomatis beradaptasi jika peserta hanya memiliki 1 kamera tanpa membatalkan ujian.
- **Pemeriksaan Pra-Ujian (Camera Pre-Check)**: Siswa wajib menguji kelayakan audio-video sebelum tombol ruang ujian dapat diakses.

### 4. AI Proctoring & Dynamic Risk Scoring System
- **Deteksi Anomali Multimoda**:
  - `FACE_NOT_DETECTED`: Peserta meninggalkan kursi atau kamera tertutup.
  - `MULTIPLE_FACES`: Terdeteksi wajah orang lain yang membantu peserta.
  - `CAMERA_DISCONNECTED`: Aliran WebRTC terhenti atau perangkat dicabut.
  - `TAB_SWITCHED`: Peserta membuka tab baru, mencari contekan, atau meminimalkan browser.
  - `LOOKING_AWAY`: Tatapan menoleh ke arah luar layar secara berulang.
- **Bobot Tingkat Keparahan (*Severity*)**: `INFO` (+1), `WARNING` (+5), `HIGH` (+15).
- **Klasifikasi Risiko Peserta**:
  - `LOW / NORMAL` (Skor 0–20): Aktivitas wajar (Lencana Hijau).
  - `MEDIUM / WARNING` (Skor 21–50): Perlu perhatian pengawas (Lencana Kuning).
  - `HIGH RISK` (Skor >50): Indikasi kuat pelanggaran (Lencana Merah Berkedip).

### 5. Dashboard Live Monitoring Guru Pengawas
- **Grid Peserta Realtime**: Kartu peserta memuat cuplikan streaming kamera, status sesi, skor risiko, dan indikator aktivitas terakhir.
- **Polling Sinkron 15 Detik**: Pembaruan status peserta otomatis tanpa me-refresh halaman dashboard guru.
- **Linimasa Snapshot Bukti Anomali**: Guru dapat meninjau galeri tangkapan kamera saat pelanggaran terjadi.
- **Tindakan Interaktif Pengawas**: Mengirim teguran WhatsApp ke HP siswa, mereset sesi, atau menggugurkan (*kick*) siswa yang terbukti curang.

### 6. Multi-LLM Subject Tutor & Grounded AI Assistant
- **AI Subject Tutor**: Siswa dapat mengonsultasikan butir soal pasca-ujian bersama asisten AI yang dipandu langsung oleh pembahasan resmi guru.
- **Multi-Provider Switcher**: Mendukung pemilihan provider LLM kelas dunia secara dinamis:
  - **NVIDIA NIM** (`meta/llama-3.1-70b-instruct`, `meta/llama-3.2-11b-vision-instruct`).
  - **GroqCloud** (`llama-3.3-70b-versatile`).
  - **Google Gemini API** (`gemini-1.5-flash`).
  - **OpenAI** (`gpt-4o-mini`).
- **Chain-of-Thought (CoT) Cleaner**: Membersihkan tag internal penalaran AI seperti `<think>...</think>` dan *thinking process* agar penjelasan ke siswa tetap rapi dan profesional.
- **Guardrails Service**: Membatasi topik agar asisten AI menolak permintaan kode pemrograman (*coding ban*), pembocoran password/token, dan hal-hal di luar topik akademik resmi U-Line.

### 7. Gateway Notifikasi WhatsApp (Fonnte v2)
- **Otomasi Notifikasi Akademik**:
  - Pengiriman kode OTP login 6-digit.
  - Notifikasi pembukaan jadwal ujian baru.
  - Pengingat ujian 30 menit sebelum mulai (*Exam Reminder*).
  - Rekap hasil perolehan nilai siswa setelah ujian ditutup.
- **Idempotency Key**: Mencegah duplikasi pengiriman pesan pada jaringan yang tidak stabil.

---

## 🛠️ Tumpukan Teknologi (Tech Stack)

| Komponen | Teknologi | Keterangan |
| :--- | :--- | :--- |
| **Bahasa Pemrograman** | PHP 8.1+ / 8.2 | PHP Native Modern berorientasi objek (OOP) |
| **Pola Desain** | MVC (Model-View-Controller) | Pemisahan bersih Core, Controllers, Models, Views, Services |
| **Basis Data** | MySQL 8.0 / MariaDB | 19 Tabel Relasional Fisik Ternormalisasi (3NF) |
| **Akses Data** | PHP Data Objects (PDO) | Koneksi terpusat dengan prepared statements anti-SQLi |
| **Frontend Framework** | Bootstrap 5.3 & Icons | Antarmuka responsif ramah gawai (*mobile-friendly*) |
| **Scripting Klien** | Vanilla JS (ES6+) & Fetch API | Komunikasi HTTP asinkron tanpa ketergantungan library berat |
| **Streaming Video** | WebRTC API | `navigator.mediaDevices.getUserMedia` |
| **Integrasi AI** | NVIDIA NIM / Groq / OpenAI | REST API integrasi Multi-LLM & Vision |
| **WhatsApp Gateway** | Fonnte API v2 | Gateway perpesanan resmi dua arah |
| **Unit Testing & CI/CD**| PHPUnit 10 & GitHub Actions | Pengujian otomatis alur bisnis & scoring risiko |

---

## 📁 Struktur Direktori Proyek

```
uline/
├── .github/
│   └── workflows/
│       └── ci.yml               # Konfigurasi CI/CD GitHub Actions
├── app/
│   ├── Config/                  # Konfigurasi aplikasi, database & AI
│   │   ├── ai.php
│   │   ├── app.php
│   │   └── database.php
│   ├── Controllers/             # Pengendali alur program berbasis peran
│   │   ├── Admin/               # Controller Super Admin (Users, Guru, Siswa, Kelas, Mapel)
│   │   ├── Guru/                # Controller Guru (Question, Exam, Result, Monitoring)
│   │   ├── Peserta/             # Controller Siswa (Dashboard, ExamRoom, Result)
│   │   ├── AuthController.php   # Login, 2FA WhatsApp OTP, Logout
│   │   ├── ChatbotController.php# Asisten AI Grounded & AI Subject Tutor
│   │   └── DashboardController.php
│   ├── Core/                    # Framework Core Engine
│   │   ├── BaseController.php
│   │   ├── Database.php         # Singleton PDO MySQL
│   │   ├── Env.php              # Lightweight .env Parser
│   │   ├── Model.php            # Active Record PDO Model
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Router.php           # RESTful Request Dispatcher
│   │   └── Session.php
│   ├── Helpers/
│   │   └── functions.php        # Helper XSS, CSRF, Auth, Audit Log
│   ├── Middlewares/             # Filter autentikasi & otorisasi peran
│   │   ├── AuthMiddleware.php
│   │   ├── GuestMiddleware.php
│   │   └── RoleMiddleware.php
│   ├── Models/                  # Lapisan persistensi data (19 Model)
│   ├── Services/                # Lapisan logika bisnis
│   │   ├── AI/                  # NvidiaAIService, GuardrailService, ChatbotDatabaseService
│   │   ├── Notification/        # FonnteService, WhatsAppService, WebPushService
│   │   ├── Proctoring/          # DetectionService, RiskScoringService, CameraStreamService
│   │   └── AuditLogger.php
│   └── Views/                   # Antarmuka tampilan antarmuka
├── cron/
│   └── send_exam_reminders.php  # Background scheduler notifikasi WA
├── database/
│   ├── schema.sql               # Skema lengkap 19 tabel fisik MySQL
│   └── seeders.sql              # Data awal untuk demonstrasi sistem
├── docs/                        # Dokumentasi diagram visual interaktif HTML/SVG
│   ├── 01a-use-case-overview.html
│   ├── 02-user-activity-diagram.html
│   ├── 03a-erd-core-akademik.html
│   ├── 04-prototype-uiux-figma.html
│   ├── 05-uml-class-diagram.html
│   ├── 06-sequence-diagram.html
│   └── index.html               # Portal katalog diagram visual
├── public/                      # Document Root publik Web Server
│   ├── assets/                  # CSS, JavaScript modul, Logo
│   ├── .htaccess                # Mod-Rewrite Apache
│   └── index.php                # Front Controller aplikasi
├── storage/                     # Penyimpanan bukti kamera & log
├── tests/                       # Pengujian otomatis & Unit Testing
│   ├── Unit/                    # PHPUnit Unit Test Suites
│   ├── bootstrap.php
│   ├── run_unit_tests.php       # Zero-dependency test runner
│   └── TestCase.php
├── .env.example                 # Template konfigurasi environment aman
├── .gitignore                   # Proteksi berkas sensitif dari commit
├── composer.json                # Definisi dependensi & skrip test
├── phpunit.xml                  # Konfigurasi suite PHPUnit
├── PRD.md & PRD.pdf             # Product Requirement Document resmi
├── SDD.md & SDD.pdf             # Software Design Document resmi
└── README.md
```

---

## 🗄️ Skema Basis Data (19 Tabel Fisik MySQL)

Sistem U-Line menggunakan basis data `uline_db` yang terdiri dari 19 tabel relasional terstruktur:

1. **`users`**: Identitas akun tunggal (Admin, Guru, Siswa).
2. **`teachers`**: Profil pengajar (NIP, kontak WhatsApp).
3. **`students`**: Data peserta didik (NIS, rombel kelas, kontak).
4. **`classes`**: Manajemen rombongan belajar (Tingkat 10, 11, 12).
5. **`subjects`**: Daftar mata pelajaran akademik.
6. **`audit_logs`**: Rekam jejak seluruh mutasi dan aktivitas sistem.
7. **`questions`**: Bank soal evaluasi (Pilihan ganda, True/False, Essay).
8. **`exams`**: Agenda jadwal dan parameter konfigurasi ujian.
9. **`exam_questions`**: Tabel penghubung paket ujian dan butir soal.
10. **`exam_sessions`**: Status sesi pengerjaan, sisa waktu, dan skor risiko peserta.
11. **`answers`**: Jawaban peserta yang tersimpan berkala melalui autosave.
12. **`camera_events`**: Jejak peristiwa kamera (aktif, putus, kalibrasi).
13. **`proctor_events`**: Log anomali AI proctoring beserta snapshot bukti.
14. **`notifications`**: Inbox notifikasi internal sistem.
15. **`push_subscriptions`**: Token Web Push Notification browser.
16. **`whatsapp_otps`**: Token autentikasi 2FA WhatsApp beserta waktu kadaluwarsa.
17. **`whatsapp_logs`**: Riwayat pengiriman pesan WhatsApp via Fonnte.
18. **`whatsapp_settings`**: Parameter sakelar pengiriman notifikasi otomatis.
19. **`exam_configs`**: Konfigurasi parameter khusus per sesi ujian.

---

## 💻 Panduan Instalasi & Menjalankan secara Lokal

Ikuti langkah-langkah mudah di bawah ini untuk menjalankan sistem U-Line di komputer lokal (Windows / XAMPP):

### 1. Prasyarat Sistem
* **Web Server**: Apache (XAMPP / Laragon).
* **PHP**: Versi 8.1 atau 8.2 (Pastikan ekstensi `pdo_mysql`, `curl`, `mbstring`, `openssl` aktif di `php.ini`).
* **Database**: MySQL 8.0 atau MariaDB 10.4+.
* **Git**: Terinstal di perangkat Anda.

### 2. Kloning Repository
Buka terminal (Git Bash, Command Prompt, atau PowerShell), lalu arahkan ke folder `htdocs`:
```bash
cd C:\xampp\htdocs
git clone https://github.com/afri30/uline.git
cd uline
```

### 3. Pembuatan & Impor Basis Data
1. Jalankan layanan **Apache** dan **MySQL** pada panel XAMPP Control Panel.
2. Buka peramban dan akses **phpMyAdmin** di `http://localhost/phpmyadmin`.
3. Buat basis data baru bernama: `uline_db` (dengan collation `utf8mb4_unicode_ci`).
4. Klik tab **Import**, pilih berkas `database/schema.sql`, lalu klik **Go**.
5. *(Opsional)* Impor juga berkas `database/seeders.sql` untuk mendapatkan akun pengujian awal.

### 4. Konfigurasi Environment (`.env`)
Salin file `.env.example` menjadi `.env`:
```bash
# Windows PowerShell
copy .env.example .env
```
Buka file `.env` dan sesuaikan konfigurasi lokal Anda:
```env
APP_NAME="U-Line Examination"
APP_ENV=local
APP_URL=http://localhost/uline/public

# Database Configuration
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=uline_db
DB_USERNAME=root
DB_PASSWORD=

# AI Provider Configuration (NVIDIA NIM / Groq / OpenAI)
AI_PROVIDER=nvidia
NVIDIA_API_KEY=nvapi-your-key-here

# WhatsApp Gateway (Fonnte API v2)
FONNTE_TOKEN=your-fonnte-token-here
```

### 5. Menjalankan Aplikasi
Buka peramban (disarankan Google Chrome untuk dukungan WebRTC optimal) dan akses:
```
http://localhost/uline/public/
```
Atau Anda dapat menggunakan server bawaan PHP:
```bash
php -S localhost:8000 -t public
```
Lalu buka `http://localhost:8000` pada peramban.

---

## 🔑 Akun Default untuk Pengujian

Gunakan kredensial berikut untuk menguji masing-masing hak akses:

| Peran Akun | Username | Password | Kontak WA (Demo OTP) | Akses Utama |
| :--- | :--- | :--- | :--- | :--- |
| **Super Admin** | `admin` | `password123` | `085642349158` | Master Data, User, Kelas, Mapel, Setting AI & WA |
| **Guru Penguji** | `guru1` | `password123` | `081234567891` | Bank Soal, Jadwal Ujian, Live Monitoring Proctoring |
| **Peserta Didik** | `siswa1` | `password123` | `085642349158` | Ruang Ujian, Kalibrasi Dual-Cam, AI Subject Tutor |

---

## 🧪 Pengujian Otomatis & Unit Testing

Sistem U-Line dilengkapi dengan suite pengujian otomatis yang dapat dijalankan secara lokal maupun melalui CI/CD pipeline GitHub Actions:

### Menjalankan Unit Testing Lokal
Jalankan pengujian unit mandiri tanpa membutuhkan dependensi Composer:
```bash
php tests/run_unit_tests.php
```

### Menjalankan melalui PHPUnit
Jika Anda memiliki Composer terpasang di sistem:
```bash
composer install
vendor/bin/phpunit --testdox
```

### Hasil Verifikasi Unit Test
```
========================================================================
  U-LINE CBT & AI PROCTORING SYSTEM - UNIT TEST SUITE
  Standard OBE Skor 4 - Automated Verification
========================================================================

Running Test Suite: RiskScoringServiceTest ...
  [PASS] testEmptyEventsReturnsZeroRisk
  [PASS] testCalculatesCumulativeRiskWithMultipleSeverities
  [PASS] testHighRiskThresholdExceeded
  [PASS] testBreakdownCountsDistribution

Running Test Suite: DetectionServiceTest ...
  [PASS] testValidatesStandardEventTypes
  [PASS] testDefaultSeverityMapping
  [PASS] testEvaluatesRawEventToStandardizedPayload
  [PASS] testMapsAliasesForLooseEventTypes

Running Test Suite: GuardrailServiceTest ...
  [PASS] testRejectsEmptyInput
  [PASS] testRejectsExcessivelyLongInput
  [PASS] testBlocksCredentialLeakAttempts
  [PASS] testAllowsLegitimateAcademicQuestions

Running Test Suite: NvidiaAIServiceTest ...
  [PASS] testCleansXmlThinkTags
  [PASS] testCleansUnclosedThinkTags
  [PASS] testCleansThinkingProcessHeaders

Running Test Suite: SecurityHelperTest ...
  [PASS] testEscapesHtmlSpecialCharacters
  [PASS] testEscapesSingleAndDoubleQuotes

Running Test Suite: PhoneFormatterTest ...
  [PASS] testConvertsIndonesianLocalZeroToCountryCode
  [PASS] testPreservesExistingCountryCode
  [PASS] testStripsHyphensAndSpaces
  [PASS] testRejectsInvalidLengthNumbers

------------------------------------------------------------------------
Summary: 6 Test Classes, 21 Test Methods Executed
Assertions Passed : 61
Assertions Failed : 0
Result: SUCCESS (100% Tests Passed - Ready for CI/CD Deployment)
```

---

## 📜 Lisensi & Pengesahan

Proyek perangkat lunak ini dilisensikan di bawah lisensi terbuka [MIT License](LICENSE).  
Hak cipta © 2026 **Rian Afriyansyah (NIM: 41256154)** — Program Studi S1 Teknik Informatika, **STMIK IKMI Cirebon**.
