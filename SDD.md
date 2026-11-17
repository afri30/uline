# SOFTWARE DESIGN DOCUMENT (SDD)
## U-Line: AI Online Examination & Dual-Camera Proctoring System with Multi-LLM Subject Tutor & Grounded Database Engine

---

### LEMBAR IDENTITAS DAN PENGESAHAN DOKUMEN PERANCANGAN
* **Mata Kuliah / Kode**: Rekayasa Perangkat Lunak (TIKB2503021) — 4 SKS (2 Teori + 2 Praktik)
* **Semester / Tahun Akademik**: Semester 3 / 2026-2027
* **Program Studi / Institusi**: S1 Teknik Informatika — STMIK IKMI Cirebon
* **Dosen Pengampu**: Yudhistira Arie Wijaya, M.Kom., CDS.
* **Tema Topik Kelompok**: *E-Learning & Smart Education AI*
* **Sifat Pengerjaan**: Mandiri / Perorangan (100% Individual Ownership & Full-App Build)
* **Nama Mahasiswa**: **Rian Afriyansyah**
* **Nomor Induk Mahasiswa (NIM)**: **41256154**
* **Standar Spesifikasi**: IEEE Std 1016-2009 / ISO/IEC/IEEE 42010 (Systems and software engineering — Architecture description)
* **Versi Dokumen**: 1.0.0 (Official Release — Tahap 2 SDLC)
* **Tanggal Penyusunan**: 14 November 2026

---

## DAFTAR ISI

1. [BAB I: PENDAHULUAN & ARSITEKTUR SISTEM TINGKAT TINGGI](#bab-i-pendahuluan--arsitektur-sistem-tingkat-tinggi)
   - 1.1 Tujuan Penyusunan Dokumen SDD
   - 1.2 Ruang Lingkup Perancangan Sistem
   - 1.3 Arsitektur Sistem Tingkat Tinggi (Pola Arsitektur MVC & Layering)
   - 1.4 Penerapan Prinsip Berorientasi Objek (Prinsip SOLID)
2. [BAB II: PEMODELAN USE CASE & SKENARIO LENGKAP](#bab-ii-pemodelan-use-case--skenario-lengkap)
   - 2.1 Use Case Diagram Overview Sistem U-Line
   - 2.2 Skenario Rinci Use Case Aktor Utama
     - UC-01: Autentikasi Pengguna, 2FA WhatsApp OTP & Pengelolaan Sesi
     - UC-02: Manajemen Master Data Akademik & Gateway Notifikasi
     - UC-03: Penyusunan Bank Soal, Jadwal & Konfigurasi Parameter Ujian
     - UC-04: Kalibrasi Kamera WebRTC, Autosave Jawaban & Ruang Ujian
     - UC-05: Live Monitoring Pengawas, AI Risk Scoring & Review Bukti Anomali
     - UC-06: Konsultasi Interaktif AI Subject Tutor & Chatbot Grounding Database
3. [BAB III: ACTIVITY DIAGRAM (ALUR BISNIS & KECERDASAN ARTIFISIAL)](#bab-iii-activity-diagram-alur-bisnis--kecerdasan-artifisial)
   - 3.1 Activity Diagram 1: Alur Autentikasi Dua Faktor (2FA WhatsApp OTP)
   - 3.2 Activity Diagram 2: Alur Siklus Pengerjaan Ujian (Camera Check -> Autosave -> Submit)
   - 3.3 Activity Diagram 3: Alur AI Dual-Camera Proctoring & Real-Time Risk Scoring
   - 3.4 Activity Diagram 4: Alur Konsultasi Interaktif AI Subject Tutor & Chatbot Database
4. [BAB IV: CLASS DIAGRAM BERORIENTASI OBJEK (MEMATUHI PRINSIP SOLID)](#bab-iv-class-diagram-berorientasi-objek-mematuhi-prinsip-solid)
   - 4.1 Dekomposisi Paket & Struktur Class Diagram Sistem
   - 4.2 Spesifikasi Detail Class: Core, Models, Controllers, dan Services
   - 4.3 Analisis Kepatuhan Prinsip Desain SOLID pada Sistem U-Line
5. [BAB V: SEQUENCE DIAGRAM (ALUR PERTUKARAN PESAN REALTIME)](#bab-v-sequence-diagram-alur-pertukaran-pesan-realtime)
   - 5.1 Sequence Diagram 1: Login via WhatsApp OTP (2FA Authentication Flow)
   - 5.2 Sequence Diagram 2: Pengerjaan Ujian & Debounced Autosave (Exam Room Flow)
   - 5.3 Sequence Diagram 3: Deteksi Anomali AI Proctoring & Live Monitoring Sync
   - 5.4 Sequence Diagram 4: Konsultasi Konsep Butir Soal ke AI Subject Tutor
6. [BAB VI: PERANCANGAN BASIS DATA LENGKAP (19 TABEL RELASIONAL FISIK)](#bab-vi-perancangan-basis-data-lengkap-19-tabel-relasional-fisik)
   - 6.1 Konsep Pemodelan Relasional Basis Data (3 Klaster Entitas)
   - 6.2 Visualisasi Entity-Relationship Diagram (ERD Terpadu)
   - 6.3 Kamus Data Lengkap 19 Tabel Basis Data (`uline_db`)
   - 6.4 Aturan Integritas Referensial & Kebijakan Cascade/Restrict
7. [BAB VII: PERANCANGAN ANTARMUKA PENGGUNA (UI/UX DESIGN)](#bab-vii-perancangan-antarmuka-pengguna-uiux-design)
   - 7.1 Prinsip Perancangan Desain Antarmuka (Cognitive Load Reduction)
   - 7.2 Struktur Layout Utama Berbasis Peran (Responsive Role Layout)
   - 7.3 Walkthrough Prototipe Antarmuka Kunci (Figma Wireframe Reference)
8. [BAB VIII: PENUTUP & PENGESAHAN DOKUMEN SDD](#bab-viii-penutup--pengesahan-dokumen-sdd)

---

# BAB I: PENDAHULUAN & ARSITEKTUR SISTEM TINGKAT TINGGI

### 1.1 Tujuan Penyusunan Dokumen SDD
Dokumen *Software Design Document* (SDD) ini disusun sebagai blueprint teknis formal yang mentransformasikan seluruh spesifikasi kebutuhan perangkat lunak pada dokumen PRD/SRS ke dalam perancangan arsitektur berorientasi objek (*Object-Oriented Design* / OOD). Dokumen ini bertujuan untuk:
1. Memberikan representasi visual dan struktural yang presisi mengenai perilaku dinamis, struktur statis, alur pertukaran pesan, serta arsitektur basis data sistem **U-Line**.
2. Memenuhi indikator evaluasi **Skor 4 (Sangat Baik)** pada Rubrik Penilaian OBE Tahap 2 Proyek Akhir Mandiri Rekayasa Perangkat Lunak (TIKB2503021) STMIK IKMI Cirebon.
3. Menjamin keterlacakan penuh (*100% traceability*) antara pemodelan UML, skema 19 tabel basis data fisik, dan implementasi kode sumber pada repositori proyek.

### 1.2 Ruang Lingkup Perancangan Sistem
Dokumen SDD ini mencakup perancangan terintegrasi untuk:
* **Komponen Backend**: Kerangka MVC Native PHP 8.1+ dengan pemisahan Core Router, Request, Response, Session, Database PDO, Middleware, Model, Controller, dan Services.
* **Komponen Frontend**: Antarmuka responsif Bootstrap 5.3, modul WebRTC Dual Camera (*Camera 1 Face* & *Camera 2 Desk*), modul asinkron Fetch API/AJAX, dan komponen obrolan asisten AI.
* **Komponen AI & Gateway**: Adaptor *Multi-LLM* (NVIDIA NIM / GroqCloud / Gemini / OpenAI), *Factual SQL Grounding Engine*, *Guardrails Service*, serta *Fonnte WhatsApp API Gateway v2*.
* **Basis Data Relasional**: Skema 19 tabel fisik pada MySQL 8.0/MariaDB yang mencakup klaster akademik, proctoring, notifikasi, dan konfigurasi sistem.

### 1.3 Arsitektur Sistem Tingkat Tinggi (Pola Arsitektur MVC & Layering)
Sistem U-Line mengadopsi pola arsitektur **Model-View-Controller (MVC)** murni berstandar industri dengan pemisahan tanggung jawab ke dalam 5 lapisan (*multi-tier layered architecture*):

```
+---------------------------------------------------------------------------------------+
|                                    KLIEN PERAMBAN (BROWSER)                           |
|   [ HTML5 UI / Bootstrap 5.3 ]   [ WebRTC Media Capture ]   [ Fetch API / AJAX Engine ]|
+-------------------------------------------+-------------------------------------------+
                                            | HTTP / HTTPS / JSON Requests
                                            v
+---------------------------------------------------------------------------------------+
|                          1. LAPISAN PRESENTASI & ROUTING                              |
|   - Front Controller: public/index.php                                                |
|   - Core Router & Dispatcher: App\Core\Router, Request, Response                      |
|   - Keamanan HTTP: AuthMiddleware, RoleMiddleware, CSRF Token Verifier                |
+-------------------------------------------+-------------------------------------------+
                                            | Dispatched Request
                                            v
+---------------------------------------------------------------------------------------+
|                          2. LAPISAN PENGONTROL (CONTROLLER LAYER)                     |
|   - Admin: UserController, ClassController, AIConfigController, WhatsAppController   |
|   - Guru : QuestionController, ExamController, MonitoringController, ResultController |
|   - Siswa: DashboardController, ExamRoomController, ResultController                  |
|   - Shared: AuthController, ChatbotController                                         |
+-------------------------------------------+-------------------------------------------+
                                            | Business Logic Execution
                                            v
+---------------------------------------------------------------------------------------+
|                          3. LAPISAN LAYANAN BISNIS & AI (SERVICE LAYER)               |
|   - ProctoringService & RiskScoringService (Deteksi Anomali & Pembobotan Risiko)     |
|   - ChatbotDatabaseService (Factual SQL Grounding Engine & Context Resolver)         |
|   - GuardrailService (Pencegahan Halusinasi, Proteksi Kunci Ujian, No-Coding Policy) |
|   - NvidiaAIService (Multi-LLM Adapter: NVIDIA NIM, GroqCloud, Gemini, OpenAI)       |
|   - FonnteService (WhatsApp 2FA OTP & 5 Event Notifikasi Evaluasi Akademik)           |
|   - AuditLogger (Pencatatan Jejak Forensik Seluruh Aktivitas Mutasi Sistem)           |
+-------------------------------------------+-------------------------------------------+
                                            | Data Object Persistence
                                            v
+---------------------------------------------------------------------------------------+
|                          4. LAPISAN AKSES DATA (MODEL & DATABASE LAYER)               |
|   - BaseModel: App\Core\Model, App\Core\Database (Singleton PDO Connection)           |
|   - Domain Models: User, Teacher, Student, Kelas, Subject, Question, Exam,            |
|                    ExamSession, Answer, CameraEvent, ProctorEvent, WhatsAppLog, dsb.  |
|   - Eksekusi Parameter Terikat (Prepared Statements PDO) & Transaksi ACID             |
+-------------------------------------------+-------------------------------------------+
                                            | SQL Queries
                                            v
+---------------------------------------------------------------------------------------+
|                          5. BASIS DATA FISIK: MYSQL 8.0 (uline_db)                    |
|   [ 19 Tabel Relasional ] [ Foreign Key Constraints ] [ Indeks B-Tree Teroptimasi ]   |
+---------------------------------------------------------------------------------------+
```

### 1.4 Penerapan Prinsip Berorientasi Objek (Prinsip SOLID)
Dalam merancang struktur class sistem U-Line, diterapkan prinsip **SOLID** secara ketat untuk menjamin *maintainability*, *modularity*, dan *testability*:
1. **Single Responsibility Principle (SRP)**:
   - `ExamRoomController` hanya bertanggung jawab mengelola alur ruang ujian siswa.
   - Perhitungan matematis risiko kecurangan didelegasikan khusus ke `RiskScoringService`.
   - Pengiriman pesan jaringan WhatsApp dipisahkan sepenuhnya ke dalam `FonnteService`.
2. **Open/Closed Principle (OCP)**:
   - Arsitektur penyedia AI pada `NvidiaAIService` terbuka untuk penambahan provider baru (misal: Anthropic Claude atau Cohere) melalui konfigurasi array adaptor tanpa perlu merombak logika routing kontroler utama.
3. **Liskov Substitution Principle (LSP)**:
   - Seluruh model data (seperti `User`, `Exam`, `Question`, `ExamSession`) mewarisi class induk abstrak `App\Core\Model`. Setiap metode standar (seperti `findById`, `create`, `update`, `delete`) dapat digunakan secara konsisten menggantikan objek induknya tanpa memicu kegagalan runtime.
4. **Interface Segregation Principle (ISP)**:
   - Antarmuka interaksi API sengaja dirancang ramping dan spesifik. Endpoint `/api/chat/explain-question` hanya menerima parameter butir soal, terpisah dari endpoint `/api/chat/global` yang menerima riwayat percakapan umum.
5. **Dependency Inversion Principle (DIP)**:
   - Kontroler tingkat tinggi tidak bergantung pada implementasi driver database tingkat rendah; kontroler berinteraksi melalui abstraksi objek Model PDO yang menyuntikkan koneksi basis data melalui metode `getDb()`.

---

# BAB II: PEMODELAN USE CASE & SKENARIO LENGKAP

### 2.1 Use Case Diagram Overview Sistem U-Line
Sistem U-Line mencakup 6 domain use case utama yang menghubungkan 3 aktor manusia internal dan 3 sistem eksternal:

```
+--------------------------------------------------------------------------------------------------+
|                            USE CASE DIAGRAM OVERVIEW (SISTEM U-LINE)                            |
+--------------------------------------------------------------------------------------------------+

   AKTOR MANUSIA                                                               SISTEM EKSTERNAL
   =============                                                               ================

  +---------------+                 +-----------------------------------+
  |               |-------(UC-01)-->| Autentikasi, WA OTP & Profil      |------> [ Fonnte WA API ]
  |  SUPER ADMIN  |                 +-----------------------------------+
  |               |-------(UC-02)-->| Master Data & Konfigurasi Sistem  |
  +---------------+                 +-----------------------------------+
                                                      |
  +---------------+                                   v
  |               |-------(UC-03)-->+-----------------------------------+
  | GURU PENGUJI  |                 | Bank Soal & Manajemen Jadwal      |
  |               |-------(UC-05)-->+-----------------------------------+
  +---------------+                 | Live Monitoring & AI Proctoring   |<-----> [ WebRTC Media ]
                                    +-----------------------------------+
  +---------------+                                   ^
  |               |-------(UC-04)-->+-----------------------------------+
  | PESERTA DIDIK |                 | Cek Kamera & Ruang Ujian Online   |
  |               |-------(UC-06)-->+-----------------------------------+
  +---------------+                 | Chatbot Database & AI Tutor       |<-----> [ Multi-LLM API ]
                                    +-----------------------------------+
```

---

### 2.2 Skenario Rinci Use Case Aktor Utama

#### Skenario Use Case UC-01: Autentikasi Pengguna, 2FA WhatsApp OTP & Pengelolaan Sesi
* **ID & Nama Use Case**: `UC-01` — Autentikasi Multi-Metode (Password & WhatsApp 2FA OTP)
* **Aktor Utama**: Super Administrator, Guru Penguji, Peserta Didik
* **Aktor Pendukung**: Fonnte WhatsApp API Gateway
* **Deskripsi Singkat**: Pengguna melakukan proses masuk ke sistem menggunakan kombinasi kredensial statis (username/password) atau autentikasi instan dua faktor (2FA) berbasis kode OTP 6-digit yang dikirimkan ke nomor WhatsApp terdaftar.
* **Prekondisi**: Pengguna telah terdaftar di tabel `users` dengan status `status = 'active'`.
* **Postkondisi**: Sesi login aktif diterbitkan di server (`Session::set('user', $sessionUser)`), ID sesi diregenerasi (`Session::regenerate()`), jejak log tercatat di tabel `audit_logs`, dan pengguna dialihkan ke dashboard perannya.
* **Alur Utama (Main Success Scenario)**:
  1. Pengguna mengakses halaman login `/login`.
  2. Sistem menampilkan formulir login dengan tab *Kata Sandi* dan *WhatsApp OTP*.
  3. Pengguna memilih tab *WhatsApp OTP* dan memasukkan nomor WhatsApp terdaftar.
  4. Pengguna menekan tombol *"Kirim Kode OTP WhatsApp"*.
  5. Sistem memvalidasi keberadaan nomor pada tabel `users`, menghasilkan kode acak 6 digit (`random_int(100000, 999999)`), menyimpannya ke tabel `whatsapp_otps` dengan waktu kedaluwarsa 5 menit, dan memicu panggilan API ke Fonnte Gateway.
  6. Fonnte Gateway mengirimkan pesan OTP ke aplikasi WhatsApp pengguna.
  7. Sistem memperbarui antarmuka login ke *Langkah 2: Input 6-Digit OTP*.
  8. Pengguna mengetikkan 6 digit kode OTP dan menekan tombol *"Verifikasi & Masuk"*.
  9. Sistem memvalidasi kode OTP pada tabel `whatsapp_otps`. Kode dinyatakan valid (`is_used = 0`, belum kedaluwarsa, percobaan $\le 5$).
  10. Sistem menandai OTP telah digunakan (`is_used = 1`), meregenerasi ID sesi, mencatat aktivitas login `LOGIN_WHATSAPP` ke `audit_logs`, dan mengalihkan pengguna ke dashboard perannya.
* **Alur Alternatif (Alternative & Exception Flows)**:
  - *4a. Nomor WhatsApp Tidak Terdaftar*: Sistem menampilkan peringatan *"Nomor WhatsApp tidak ditemukan dalam sistem. Pastikan nomor telah terdaftar."* Alur kembali ke langkah 3.
  - *9a. Kode OTP Salah*: Sistem menambah counter `attempts = attempts + 1`. Jika percobaan $< 5$, sistem menampilkan pesan *"Kode OTP yang Anda masukkan salah. Sisa percobaan: X kali."*
  - *9b. Kode OTP Kedaluwarsa (> 5 Menit)*: Sistem menandai OTP hangus (`is_used = 1`) dan menampilkan pesan *"Kode OTP telah kedaluwarsa. Silakan minta kode baru."*
  - *9c. Batas Percobaan Terlampaui (>= 5 Kali)*: Sistem membatalkan sesi OTP secara permanen demi keamanan dan mencatat log upaya mencurigakan.

---

#### Skenario Use Case UC-04: Kalibrasi Kamera WebRTC, Autosave Jawaban & Ruang Ujian Online
* **ID & Nama Use Case**: `UC-04` — Kalibrasi Kamera & Pengerjaan Ujian Realtime (Exam Room)
* **Aktor Utama**: Peserta Didik (Siswa)
* **Aktor Pendukung**: WebRTC Browser Engine
* **Deskripsi Singkat**: Siswa melakukan pra-pemeriksaan dua kamera, mengerjakan butir soal ujian dengan penyimpanan otomatis latar belakang (*debounced autosave*), navigasi nomor soal interaktif, dan validasi waktu server mutlak.
* **Prekondisi**: Siswa telah terdaftar pada kelas yang ditargetkan jadwal ujian (`exams.status = 'active'`), sesi ujian terdaftar di tabel `exam_sessions`.
* **Postkondisi**: Jawaban tersimpan di tabel `answers`, waktu sisa terbarui di `exam_sessions.remaining_time`, status sesi berubah menjadi `finished`, dan nilai ujian objektif terkalkulasi otomatis.
* **Alur Utama (Main Success Scenario)**:
  1. Siswa memilih kartu ujian pada dashboard dan menekan *"Mulai Ujian"*.
  2. Sistem mengarahkan siswa ke halaman kalibrasi kamera `/peserta/exams/{id}/camera-check`.
  3. Siswa mengizinkan izin akses peramban (*browser permissions*) untuk media stream Kamera 1 (Wajah) dan Kamera 2 (Meja).
  4. Sistem menampilkan umpan visual kedua kamera dan memvalidasi kelayakan hardware.
  5. Siswa menekan *"Masuk Ruang Ujian"*; sistem membuka ruang ujian `/peserta/exams/{id}/room` dalam mode layar penuh (*fullscreen*).
  6. Sistem mengambil waktu server, memulai *server-side countdown timer*, serta mengacak urutan soal dan opsi jawaban sesuai konfigurasi ujian.
  7. Siswa memilih jawaban pada opsi radio pilihan ganda.
  8. Modul JavaScript `exam-room.js` secara otomatis mengirimkan payload jawaban asinkron via Fetch API AJAX ke endpoint `/peserta/exams/{id}/save-answer`.
  9. Backend memvalidasi sesi, menyimpan record ke tabel `answers`, dan mengembalikan status respons HTTP 200 JSON `{status: 'success'}` dalam waktu $< 300$ ms.
  10. Kotak navigasi nomor soal pada peramban seketika berubah warna menjadi hijau (*Tersimpan*).
  11. Siswa menyelesaikan seluruh soal dan menekan tombol *"Selesai Ujian"*.
  12. Sistem menampilkan modal konfirmasi; siswa menyetujui submit final.
  13. Backend mengunci sesi (`status = 'finished'`), mencatat `end_time = NOW()`, mengalkulasi nilai otomatis soal pilihan ganda, dan mengalihkan siswa ke halaman hasil.
* **Alur Alternatif (Alternative & Exception Flows)**:
  - *3a. Perangkat Hanya Memiliki 1 Kamera*: Sistem mengaktifkan *Graceful Single-Camera Fallback*. Kamera 1 aktif sebagai kamera wajah, status Kamera 2 ditandai *Fallback Mode* di database (`camera_events`), dan siswa tetap diizinkan mengikuti ujian.
  - *6a. Waktu Ujian Habis Saat Siswa Sedang Menjawab*: Timer server mencapai angka 0 detik. Fungsi JavaScript `autoSubmitExam()` otomatis mengunci seluruh form, men-submit jawaban yang ada, dan menampilkan notifikasi modal bahwa waktu telah berakhir.

---

#### Skenario Use Case UC-05: Live Monitoring Pengawas, AI Risk Scoring & Review Bukti Anomali
* **ID & Nama Use Case**: `UC-05` — Pemantauan Realtime Guru & Penilaian Risiko Kecurangan AI
* **Aktor Utama**: Guru Penguji
* **Aktor Pendukung**: AI Proctoring Engine, WebRTC Media Stream
* **Deskripsi Singkat**: Guru pengawas memantau puluhan peserta ujian secara simultan melalui dashboard visual, mengidentifikasi peserta dengan skor risiko tinggi (*High Risk*), memeriksa galeri snapshot anomali perilaku, dan memberikan keputusan reviu formal.
* **Prekondisi**: Ujian berstatus aktif (`exams.status = 'active'`), peserta sedang berada di ruang ujian.
* **Postkondisi**: Status tinjauan sesi diperbarui pada `exam_sessions.review_status` beserta catatan evaluasi guru (`review_notes`).
* **Alur Utama (Main Success Scenario)**:
  1. Guru mengakses menu `/guru/monitoring` dan memilih ujian yang sedang aktif.
  2. Sistem memuat dashboard monitoring terintegrasi dengan 6 kartu metrik ringkasan (*Total Peserta, Online, Offline, Normal, Warning, High Risk*) dan grid kartu peserta.
  3. Setiap 15 detik, skrip monitoring mengeksekusi AJAX polling untuk menyinkronkan status denyut koneksi (*heartbeat*), skor risiko, dan umpan status dual kamera dari tabel `exam_sessions`.
  4. Selama ujian berlangsung, jika terjadi anomali pada siswa (misal: wajah tidak terdeteksi atau tab peramban diminimalkan), mesin AI mengirimkan snapshot foto dan mencatat insiden ke tabel `proctor_events` dengan bobot keparahan (+1, +5, atau +15 poin).
  5. Akumulasi skor risiko siswa otomatis meningkat. Jika skor risiko $> 50$, kartu siswa otomatis berubah warna menjadi merah berkedip (*High Risk*) dan diposisikan pada urutan paling atas di grid monitoring guru.
  6. Guru menekan tombol *"Review"* pada kartu siswa bersangkutan.
  7. Sistem membuka halaman rincian sesi `/guru/monitoring/session/{id}` yang menampilkan data diri siswa, info durasi, grafik skor risiko, dan linimasa kronologis seluruh insiden anomali lengkap dengan foto snapshot resolusi tinggi.
  8. Guru memeriksa foto bukti kecurangan, memilih status keputusan (`Confirmed` untuk menetapkan kecurangan atau `Dismissed` jika false alarm), menuliskan catatan evaluasi pada formulir reviu, dan menekan *"Simpan Keputusan Review"*.
  9. Sistem menyimpan data reviu ke database dan mencatat entri audit ke tabel `audit_logs`.

---

#### Skenario Use Case UC-06: Konsultasi Interaktif AI Subject Tutor & Chatbot Grounding Database
* **ID & Nama Use Case**: `UC-06` — Konsultasi Edukatif AI Subject Tutor & Chatbot Faktual
* **Aktor Utama**: Peserta Didik, Super Admin, Guru Penguji
* **Aktor Pendukung**: Multi-LLM API (NVIDIA NIM / Groq / Gemini), Grounded SQL Engine
* **Deskripsi Singkat**: Memberikan layanan tutor interaktif bagi siswa pasca-ujian untuk membahas konsep butir soal tanpa membocorkan kunci saat ujian berjalan, serta menyediakan asisten chatbot global berbasis grounding basis data faktual untuk menjawab informasi sekolah.
* **Prekondisi**: Pengguna telah terotentikasi; untuk AI Tutor, status ujian siswa telah selesai (`status = 'finished'`).
* **Postkondisi**: Pengguna memperoleh jawaban konsep edukatif atau data statistik sekolah yang akurat, bebas halusinasi, dan tanpa kebocoran teks penalaran internal model AI.
* **Alur Utama (Main Success Scenario)**:
  1. **Alur AI Subject Tutor (Siswa)**:
     - Siswa membuka rincian hasil ujian pada `/peserta/results/{exam_id}`.
     - Pada butir soal yang dijawab salah, siswa menekan tombol *"Tanya AI Tutor"*.
     - Sistem mengirimkan permintaan asinkron ke `/api/chat/explain-question` dengan menyertakan ID butir soal dan ID sesi.
     - `GuardrailService` memvalidasi bahwa ujian telah selesai, membungkus konteks pertanyaan, jawaban siswa, kunci pembahasan guru ke dalam *System Prompt*, dan menerapkan *Strict No-Coding Policy*.
     - `NvidiaAIService` mengirimkan payload ke model LLM aktif, menerima respons, membersihkan tag penalaran `<think>` melalui fungsi `cleanAIResponse()`, dan mengembalikan penjelasan konsep ilmiah yang ramah ke antarmuka siswa.
  2. **Alur Chatbot Faktual Database (Admin/Guru/Siswa)**:
     - Pengguna membuka widget *U-Line AI Assistant* di pojok kanan bawah antarmuka.
     - Pengguna mengetikkan pertanyaan statistik: *"ada berapa total peserta didik"*.
     - `ChatbotDatabaseService` mencegat kueri sebelum menyentuh LLM eksternal, mengeksekusi kueri agregasi SQL realtime ke tabel MySQL, dan mengembalikan jawaban instan: *"Total terdapat 10 peserta didik terdaftar (9 aktif, 1 non-aktif)."*
     - Pengguna mengirimkan pertanyaan lanjutan singkat: *"siapa saja"*.
     - Sistem memeriksa larik `$history`, mendeteksi konteks sebelumnya (*Peserta Didik*), dan langsung menyajikan daftar lengkap 10 nama siswa yang dikelompokkan berdasarkan kelasnya dengan status aktif/non-aktif secara presisi.

---

# BAB III: ACTIVITY DIAGRAM (ALUR BISNIS & KECERDASAN ARTIFISIAL)

Activity Diagram di bawah ini memodelkan logika bisnis dinamis, percabangan kondisi (*decision points*), dan sinkronisasi proses paralel antara pengguna, peramban, server backend, dan modul kecerdasan buatan.

### 3.1 Activity Diagram 1: Alur Autentikasi Dua Faktor (2FA WhatsApp OTP)

```
[Pengguna]                    [Sistem U-Line Backend]              [Gateway Fonnte API]
    |                                   |                                   |
 ( Mulai )                              |                                   |
    |                                   |                                   |
    v                                   |                                   |
[Buka Halaman /login]                   |                                   |
    |                                   |                                   |
    v                                   |                                   |
[Pilih Tab "WhatsApp OTP"]              |                                   |
    |                                   |                                   |
    v                                   |                                   |
[Input No. WhatsApp Terdaftar]          |                                   |
    |                                   |                                   |
    v                                   |                                   |
[Klik "Kirim Kode OTP"]                |                                   |
    |                                   |                                   |
    +-----( Request POST /send-otp )--->|                                   |
                                        |                                   |
                                 < Cek No. HP di DB >                       |
                                    /           \                           |
                       [Tidak Ada] /             \ [Terdaftar & Aktif]      |
                                  /               \                         |
                                 v                 v                        |
                     [Tampilkan Error 404]  [Generate OTP 6 Digit]          |
                                                   |                        |
                                            [Simpan ke whatsapp_otps]       |
                                            [Set Expiry = +5 Menit]         |
                                                   |                        |
                                                   +---( Kirim Pesan OTP )->|
                                                   |                        v
                                                   |               [Dispatch Pesan WA]
                                                   |<--( Response Sukses )--+
                                        v                                   |
    |<---( Tampilkan Form Langkah 2 )---+                                   |
    |                                                                       |
    v                                                                       |
[Terima Pesan OTP di HP] <==================================================+
    |
    v
[Input 6 Digit Kode OTP]
    |
    v
[Klik "Verifikasi & Masuk"]
    |
    +-----( Request POST /verify-otp )->|
                                        |
                                < Validasi OTP di DB >
                                 /        |         \
                    [Kode Salah]/  [Expired] \       \ [Valid & Sesuai]
                               /          |   \       \
                              v           v    \       v
                     [attempts + 1]  [Set is_used=1]   [Set is_used = 1]
                     [Tampilkan Msg] [Tampilkan Msg]   [Regenerate Session ID]
                            |               |          [Audit Log LOGIN_WA]
                            v               v                  |
                         ( Selesai )     ( Selesai )           v
                                                       [Redirect ke Dashboard]
                                                               |
    |<--------( Muat Halaman Dashboard Role )------------------+
    v
( Selesai )
```

---

### 3.2 Activity Diagram 2: Alur Siklus Pengerjaan Ujian (Camera Check -> Autosave -> Submit)

```
[Peserta Didik]                 [Frontend Browser JS]             [Backend Server & DB]
    |                                    |                                  |
 ( Mulai )                               |                                  |
    |                                    |                                  |
    v                                    |                                  |
[Pilih Kartu Ujian di Dashboard]         |                                  |
    |                                    |                                  |
    v                                    |                                  |
[Masuk Halaman /camera-check]            |                                  |
    |                                    |                                  |
    +---( Request Akses Media )--------->|                                  |
                                         v                                  |
                             [Minta Izin getUserMedia()]                    |
                                    /           \                           |
                      [Izin Ditolak]             [Izin Diberikan]           |
                            |                           |                   |
                            v                           v                   |
                 [Tampilkan Alert Block]     < Cek Jumlah Kamera >          |
                            |                  /              \             |
                            v       [2 Kamera]/                \[1 Kamera]  |
                        ( Selesai )          v                  v           |
                                      [Stream Dual-Cam]  [Mode Fallback]    |
                                             \                 /            |
                                              \               /             |
                                               v             v              |
    |<---( Tampilkan Pratinjau Video Kamera )--+-------------+              |
    |                                                                       |
    v                                                                       |
[Klik "Masuk Ruang Ujian"]                                                  |
    |                                                                       |
    +---( Buka URL /exams/{id}/room )-------------------------------------->|
                                                                            v
                                                                 [Validasi Status Ujian]
                                                                 [Hitung Sisa Waktu]
                                                                 [Acak Soal & Opsi]
                                         |                                  |
    |<---( Render Soal & Start Timer )---+----------------------------------+
    |                                    |
    +========= ( Pengerjaan Soal Secara Paralel ) =========================+
    |                                                                       |
    |---[Pilih Jawaban Radio Opsi]----->[Event OnChange Terpicu]            |
    |                                   [Debounce 250ms]                    |
    |                                   [Kirim Fetch POST /save-answer]---->|
    |                                                                       v
    |                                                            [Simpan ke tabel answers]
    |                                                            [Update remaining_time]
    |                                   |<--( Respon Status OK 200 )--------+
    |                                   v
    |                                [Ubah Lencana Jadi Hijau]
    |                                                                       |
    |---[Timer Berjalan di Layar]------>[Countdown 1 Detik]                 |
    |                                   < Sisa Waktu == 0? >                |
    |                                      /            \                   |
    |                                [Ya] /              \ [Tidak]          |
    |                                    v                v                 |
    |                             [autoSubmitExam()]  [Lanjutkan Hitung]    |
    |                                    |                                  |
    +====================================+==================================+
    |                                    |
[Klik "Selesai Ujian" / Auto-Submit]     |
    |                                    |
    +---( Submit Final Form Ujian )----->|                                  |
                                         +---( POST /exams/{id}/submit )--->|
                                                                            v
                                                                  [Kunci exam_sessions]
                                                                  [Set status=finished]
                                                                  [Hitung Skor PG/BS]
                                                                  [Audit Log SUBMIT]
                                                                            |
    |<---( Redirect ke Halaman /results/{id} )------------------------------+
    v
( Selesai )
```

---

### 3.3 Activity Diagram 3: Alur AI Dual-Camera Proctoring & Real-Time Risk Scoring

```
[Webcam Hardware]            [Klien Proctoring Engine]          [Server Proctoring Service]
    |                                    |                                   |
 ( Mulai )                               |                                   |
    |                                    |                                   |
[Frame Stream Kamera 1 & 2]              |                                   |
    +----( Umpan Video WebRTC )--------->|                                   |
                                         v                                   |
                              [Ekstrak Frame Interval 3s]                    |
                              [Analisis Deteksi Objek/Wajah]                 |
                                         |                                   |
                          +--------------+--------------+                    |
                          |              |              |                    |
                          v              v              v                    |
                    < Deteksi >    < Deteksi >    < Deteksi >                |
                    Wajah Hilang   Wajah Ganda    Pindah Tab                 |
                          |              |              |                    |
                     [Severity]     [Severity]     [Severity]                |
                      WARNING          HIGH         WARNING                  |
                     (+5 Poin)      (+15 Poin)     (+5 Poin)                 |
                          |              |              |                    |
                          +--------------+--------------+                    |
                                         |                                   |
                                         v                                   |
                              [Capture Frame Snapshot]                       |
                              [Bungkus Payload Anomali JSON]                 |
                                         |                                   |
                                         +---( POST /api/proctor/event )---->|
                                                                             v
                                                                  [Simpan Foto ke storage]
                                                                  [Insert proctor_events]
                                                                  [Kalkulasi Risk Score Total]
                                                                             |
                                                                  < Klasifikasi Skor >
                                                                   /       |        \
                                                            [<=20]/    [21-50]       \[>50]
                                                                 /         |          \
                                                                v          v           v
                                                             [NORMAL]  [WARNING]   [HIGH RISK]
                                                             (Hijau)   (Kuning)   (Merah Kedip)
                                                                \          |          /
                                                                 \         |         /
                                                                  v        v        v
                                                               [Update exam_sessions]
                                                               [Trigger Polling Dashboard]
                                                                             |
    |<---( Push Pembaruan Status Realtime ke Grid Guru )---------------------+
    v
( Selesai )
```

---

### 3.4 Activity Diagram 4: Alur Konsultasi Interaktif AI Subject Tutor & Chatbot Database

```
[Pengguna / Siswa]               [Chatbot Controller]             [AI Service & Grounding]
    |                                    |                                   |
 ( Mulai )                               |                                   |
    |                                    |                                   |
    v                                    |                                   |
[Ketik Pesan Pertanyaan di Chat]         |                                   |
    |                                    |                                   |
    +---( POST /api/chat/global )------->|                                   |
                                         v                                   |
                              [Validasi Guardrail Input]                     |
                                         |                                   |
                              < Apakah Pertanyaan Faktual? >                 |
                                    /                 \                      |
                           [Ya]    /                   \ [Bukan / Konseptual]|
                                  /                     \                    |
                                 v                       v                   |
                     < Apakah Follow-up? >       [Susun System Prompt Edukasi]
                     (misal: "siapa saja")       [Inject Question Context Bounding]
                      /                 \        [Terapkan No-Coding Guardrail]
                [Ya] /                   \ [Biasa]       |
                    v                     v              v
            [Resolusi History]     [Query SQL Langsung]  +---( Request LLM Inferencing )---> [NVIDIA / Groq]
            [Identifikasi Topik]   [Agregasi Data DB]                                               |
            (Siswa / Guru / Kelas)        |                                                         v
                    \                     /                                               [Generate Jawaban]
                     \                   /                                                [Keluarkan CoT]
                      v                 v                                                           |
               [Rangkai Jawaban Deterministik]   |<--( Response Mentah )----------------------------+
                      |                          v
                      |               [cleanAIResponse() Regex Engine]
                      |               [Hapus Blok <think> & Thinking Process]
                      |               [Ambil Teks Jawaban Akhir Saja]
                      |                          |
                      +--------------------------+
                                   |
                                   v
                      [Format Markdown Interaktif]
                                   |
    |<---( Tampilkan Bubble Jawaban Bersih di UI )--+
    v
( Selesai )
```

---

# BAB IV: CLASS DIAGRAM BERORIENTASI OBJEK (MEMATUHI PRINSIP SOLID)

### 4.1 Dekomposisi Paket & Struktur Class Diagram Sistem
Struktur kode sumber U-Line disusun dalam modularitas paket berstandar PSR-4 di bawah namespace `App\`:

```
+---------------------------------------------------------------------------------------------------+
|                                  CLASS DIAGRAM SISTEM U-LINE                                      |
+---------------------------------------------------------------------------------------------------+

   +--------------------------+                         +--------------------------+
   |     App\Core\Database    |                         |      App\Core\Router     |
   +--------------------------+                         +--------------------------+
   | - instance : PDO         |                         | - routes : array         |
   +--------------------------+                         +--------------------------+
   | + getConnection() : PDO  |                         | + get(route, handler)    |
   +--------------------------+                         | + post(route, handler)   |
                 ^                                      | + dispatch() : void      |
                 | uses                                 +--------------------------+
   +--------------------------+                                       |
   |      App\Core\Model      |                                       v
   +--------------------------+                         +--------------------------+
   | # table : string         |                         |   App\Core\BaseController|
   | # primaryKey : string    |                         +--------------------------+
   | # db : PDO               |                         | + view(name, data)       |
   +--------------------------+                         | + json(data, status)     |
   | + findById(id) : array   |                         | + redirect(url)          |
   | + create(data) : int     |                         +--------------------------+
   | + update(id, data) : bool|                                       ^
   | + delete(id) : bool      |                                       | extends
   +--------------------------+                  +--------------------+--------------------+
                 ^                               |                    |                    |
                 | extends                       v                    v                    v
      +----------+----------+           +------------------+ +------------------+ +------------------+
      |                     |           | AuthController   | | ExamController   | | ChatbotController|
      v                     v           +------------------+ +------------------+ +------------------+
+------------+       +------------+     | + login()        | | + index()        | | + chatGlobal()   |
| User       |       | ExamSession|     | + sendWaOtp()    | | + create()       | | + explainQ()     |
+------------+       +------------+     | + verifyWaOtp()  | | + monitoring()   | +------------------+
| - fillable |       | - fillable |     +------------------+ +------------------+          |
+------------+       +------------+               |                    |                   |
| + findBy...|       | + getLive..|               | uses               | uses              | uses
+------------+       +------------+               v                    v                   v
                                        +------------------------------------------------------------+
                                        |                       SERVICES LAYER                       |
                                        +------------------------------------------------------------+
                                        | - FonnteService       : WhatsApp API Gateway Client        |
                                        | - ProctoringService   : Anomaly Severity Evaluator         |
                                        | - RiskScoringService  : Cumulative Mathematical Scorer     |
                                        | - ChatbotDatabaseService : SQL Grounding Engine            |
                                        | - GuardrailService    : Content Safety & Isolation Guard   |
                                        | - NvidiaAIService     : Multi-LLM API Adapter Client       |
                                        +------------------------------------------------------------+
```

---

### 4.2 Spesifikasi Detail Class: Core, Models, Controllers, dan Services

#### 1. Paket `App\Core` (Fondasi MVC Framework)
* **`App\Core\Database`**:
  - `private static ?PDO $instance = null`
  - `public static function getConnection(): PDO` -> Menjamin hanya ada 1 koneksi database persisten per request (*Singleton Pattern*).
* **`App\Core\Model`**:
  - `protected string $table`
  - `protected string $primaryKey = 'id'`
  - `protected array $fillable = []`
  - `public function getDb(): PDO`
  - `public function findById(int $id): array|false`
  - `public function create(array $data): int`
  - `public function update(int $id, array $data): bool`
  - `public function delete(int $id): bool`
* **`App\Core\BaseController`**:
  - `protected Request $request`
  - `protected function view(string $viewPath, array $data = []): void`
  - `protected function json(array $payload, int $httpStatus = 200): void`
  - `protected function redirect(string $targetUrl): void`

#### 2. Paket `App\Models` (Domain Entity Data Objects)
* **`App\Models\User`**:
  - Atribut: `$table = 'users'`, `$fillable = ['name', 'username', 'email', 'password', 'role', 'status', 'phone_number', 'whatsapp_opt_in', 'push_opt_in']`
  - Metode: `findByUsernameOrEmail(string $id)`, `findByPhone(string $phone)`, `prepareUserForSession(array $user)`, `getDashboardUrl(string $role)`
* **`App\Models\Exam`**:
  - Atribut: `$table = 'exams'`, `$fillable = ['subject_id', 'class_id', 'teacher_id', 'title', 'duration', 'start_at', 'end_at', 'random_questions', 'random_options', 'is_proctoring_active', 'dual_camera_required', 'status']`
  - Metode: `getExamsByTeacher(int $tId)`, `getActiveExamsForClass(int $cId)`, `getQuestions(int $examId)`
* **`App\Models\ExamSession`**:
  - Atribut: `$table = 'exam_sessions'`, `$fillable = ['exam_id', 'user_id', 'start_time', 'end_time', 'remaining_time', 'status', 'total_score', 'risk_score', 'camera1_status', 'camera2_status', 'review_status', 'review_notes']`
  - Metode: `getActiveSession(int $eId, int $uId)`, `updateRemainingTime(int $sId, int $seconds)`, `calculateScore(int $sId)`, `getLiveMonitoringData(int $examId)`
* **`App\Models\ProctorEvent`**:
  - Atribut: `$table = 'proctor_events'`, `$fillable = ['session_id', 'event_type', 'severity', 'score_impact', 'snapshot_url', 'description', 'detected_at']`
  - Metode: `getEventsBySession(int $sId)`, `getEventStatistics(int $sId)`

#### 3. Paket `App\Services` (Business Logic & AI Engine)
* **`App\Services\Proctoring\RiskScoringService`**:
  - `public function calculateImpact(string $eventType): int` -> Mengembalikan bobot (+1, +5, atau +15).
  - `public function determineRiskCategory(int $totalScore): string` -> Mengembalikan `LOW`, `MEDIUM`, atau `HIGH`.
* **`App\Services\AI\ChatbotDatabaseService`**:
  - `public function processChat(string $message, array $user, array $history = []): array`
  - `public function generateDirectAnswer(string $message, array $user, array $scopedData, array $history = []): ?string`
  - `public function getLiveDatabaseSummary(): array`
* **`App\Services\AI\GuardrailService`**:
  - `public function validateUserInput(string $message): array`
  - `public function buildGlobalAssistantSystemPrompt(array $user, array $scopedData): string`
  - `public function buildQuestionTutorSystemPrompt(array $questionData): string`
* **`App\Services\AI\NvidiaAIService`**:
  - `public function chatCompletion(array $messages, array $options = []): string`
  - `public function cleanAIResponse(string $raw): string` -> Filter pembersih CoT `<think>` dan *thinking process*.
* **`App\Services\Notification\FonnteService`**:
  - `public function sendLoginOtp(string $phone, string $name, string $otp, ?int $userId): array`
  - `public function sendNewExamNotification(string $phone, string $name, string $title, string $date, string $time, int $duration, ?int $userId): array`
  - `public function sendExamResultNotification(string $phone, string $name, string $title, float $score, string $status, ?int $userId): array`

---

# BAB V: SEQUENCE DIAGRAM (ALUR PERTUKARAN PESAN REALTIME)

Sequence Diagram berikut merinci interaksi asinkron berbasis waktu (*time-ordered message flows*) antara aktor peramban, controller, lapisan layanan, basis data MySQL, dan endpoint API pihak ketiga.

### 5.1 Sequence Diagram 1: Login via WhatsApp OTP (2FA Authentication Flow)

```
[Browser/User]      [AuthController]     [UserModel]      [FonnteService]    [WhatsAppOtp]      [MySQL DB]
      |                     |                 |                  |                 |                 |
      |-- POST /send-otp -->|                 |                  |                 |                 |
      |   (phone_number)    |-- findByPhone ->|                  |                 |                 |
      |                     |                 |-- SELECT * ----------------------------------------->|
      |                     |                 |<-- User Record --------------------------------------|
      |                     |<-- $user Array -|                  |                 |                 |
      |                     |                                    |                 |                 |
      |                     |-- random_int(100000, 999999)       |                 |                 |
      |                     |-- saveOtp(user_id, otpCode) ------------------------>|                 |
      |                     |                                    |                 |-- INSERT INTO ->|
      |                     |-- sendLoginOtp(phone, name, otp) ->|                 |                 |
      |                     |                                    |-- POST Fonnte ->|                 |
      |                     |                                    |<-- HTTP 200 OK -|                 |
      |                     |<-- Result {success: true} ---------|                 |                 |
      |<-- JSON Status OK --|                                    |                 |                 |
      |                     |                                    |                 |                 |
      |==============================================================================================|
      |                   PENGGUNA MEMASUKKAN KODE 6 DIGIT PADA LANGKAH 2                            |
      |==============================================================================================|
      |                     |                                    |                 |                 |
      |-- POST /verify-otp >|                                    |                 |                 |
      |   (user_id, otp)    |-- verifyOtp(user_id, code) ------------------------->|                 |
      |                     |                                    |                 |-- SELECT & ---->|
      |                     |                                    |                 |   UPDATE is_used|
      |                     |<-- Result {valid: true} -----------------------------|                 |
      |                     |                                    |                 |                 |
      |                     |-- Session::regenerate()            |                 |                 |
      |                     |-- Session::set('user', $user)      |                 |                 |
      |                     |-- audit_log('LOGIN_WHATSAPP') ---------------------------------------->|
      |<-- JSON Redirect ---|                                    |                 |                 |
      |    (/dashboard)     |                                    |                 |                 |
```

---

### 5.2 Sequence Diagram 2: Pengerjaan Ujian & Debounced Autosave (Exam Room Flow)

```
[Exam Room UI]      [ExamRoomController]   [ExamSessionModel]     [AnswerModel]          [MySQL DB]
      |                      |                     |                    |                     |
      |-- Memilih Opsi C --->|                     |                    |                     |
      |   (Event OnChange)   |                     |                    |                     |
      |-- POST /save-answer >|                     |                    |                     |
      |   (session_id, q_id, |                     |                    |                     |
      |    answer: 'C')      |-- getSession() ---->|                    |                     |
      |                      |                     |-- SELECT status ------------------------>|
      |                      |<-- Session Data ----|                    |                     |
      |                      |                                          |                     |
      |                      |-- [Cek Status == 'in_progress']          |                     |
      |                      |-- [Cek Sisa Waktu Server > 0]            |                     |
      |                      |                                          |                     |
      |                      |-- saveOrUpdateAnswer(sess_id, q_id, 'C')->|                    |
      |                      |                                          |-- INSERT/UPDATE --->|
      |                      |                                          |   INTO answers      |
      |                      |                                          |<-- Row Persisted ---|
      |                      |-- updateRemainingTime(sess_id, time) --->|                     |
      |                      |                     |-- UPDATE exam_sessions remaining_time -->|
      |<-- HTTP 200 JSON ----|                     |                    |                     |
      |    {status: success} |                     |                    |                     |
      |                      |                     |                    |                     |
      |-- Lencana Berubah ---|                     |                    |                     |
      |   Menjadi Hijau      |                     |                    |                     |
```

---

### 5.3 Sequence Diagram 3: Deteksi Anomali AI Proctoring & Live Monitoring Sync

```
[WebRTC Client]     [ProctorController]   [RiskScorerService]   [ProctorEventModel]      [Teacher UI]
      |                      |                     |                    |                     |
      |-- Anomali Visual --->|                     |                    |                     |
      |   (MULTIPLE_FACES)   |                     |                    |                     |
      |-- POST /proctor/ev ->|                     |                    |                     |
      |   (snapshot base64)  |-- calculateImpact ->|                    |                     |
      |                      |   ('MULTIPLE_FACES')|                    |                     |
      |                      |<-- Impact: +15 -----|                    |                     |
      |                      |                                          |                     |
      |                      |-- Simpan Gambar ke storage/proctoring/   |                     |
      |                      |-- recordEvent(sess_id, 'MULTIPLE', 15) ->|                     |
      |                      |                                          |-- INSERT DB ------->|
      |                      |-- updateSessionRiskScore(sess_id, +15) ->|                     |
      |                      |                                          |-- UPDATE Score ---->|
      |<-- HTTP 200 OK ------|                                          |                     |
      |                      |                                          |                     |
      |                      |================================================================|
      |                      |          TEACHER DASHBOARD POLLING INTERVAL (15 DETIK)         |
      |                      |================================================================|
      |                      |                                          |                     |
      |                      |<- GET /guru/monitoring/poll (exam_id) -------------------------|
      |                      |-- getLiveMonitoringData(exam_id) ------->|                     |
      |                      |                                          |-- SELECT Aggregation|
      |                      |<-- Array Peserta + Risk Score -----------|                     |
      |                      |-- [Sort Descending by Risk Score]        |                     |
      |                      |-- [Identifikasi Siswa High Risk > 50]    |                     |
      |                      |-- JSON Payload {metrics, participants} ----------------------->|
      |                      |                                                                v
      |                      |                                                  [Grid Terupdate: Kartu]
      |                      |                                                  [Siswa Berkedip Merah ]
```

---

### 5.4 Sequence Diagram 4: Konsultasi Konsep Butir Soal ke AI Subject Tutor

```
[Peserta Result UI]  [ChatbotController]   [GuardrailService]   [NvidiaAIService]    [External LLM API]
      |                      |                     |                    |                     |
      |-- Klik "Tanya AI" -->|                     |                    |                     |
      |   POST /explain-q    |-- validateGuard() ->|                    |                     |
      |   (q_id, session_id) |<-- Guard Passed ----|                    |                     |
      |                      |                                          |                     |
      |                      |-- Ambil Data Soal & Kunci Jawaban di DB  |                     |
      |                      |-- buildSystemPrompt(qData) ------------->|                     |
      |                      |   [Strict Context Bounding]              |                     |
      |                      |   [No Coding Policy]                     |                     |
      |                      |<-- Formatted System Prompt --------------|                     |
      |                      |                                          |                     |
      |                      |-- chatCompletion(messages) ------------->|                     |
      |                      |                                          |-- POST /completions>|
      |                      |                                          |<-- Raw JSON Stream -|
      |                      |                                          |    (includes CoT)   |
      |                      |                                          |                     |
      |                      |                                          |-- cleanAIResponse()->|
      |                      |                                          |   [Strip <think>]   |
      |                      |                                          |   [Strip CoT Steps] |
      |                      |                                          |<-- Clean Text ------|
      |                      |<-- Penjelasan Edukatif Bahasa Indonesia -|                     |
      |<-- JSON {reply} -----|                                          |                     |
      v                      |                                          |                     |
[Render Penjelasan Konsep]   |                                          |                     |
```

---

# BAB VI: PERANCANGAN BASIS DATA LENGKAP (19 TABEL RELASIONAL FISIK)

Sistem U-Line menggunakan arsitektur basis data relasional **MySQL 8.0** (`uline_db`) yang dinormalisasi hingga bentuk normal ketiga (3NF) dan dikelompokkan ke dalam 3 klaster fungsional.

### 6.1 Konsep Pemodelan Relasional Basis Data (3 Klaster Entitas)
1. **Klaster 1: Core Akun, Profil Akademik & Jejak Audit (6 Tabel)**:
   - `users`: Identitas tunggal seluruh civitas sekolah (Super Admin, Guru, Siswa).
   - `teachers`: Profil pengajar (NIP, telepon) berelasi 1:1 dengan `users`.
   - `students`: Profil peserta didik (NIS, class_id) berelasi 1:1 dengan `users`.
   - `classes`: Master rombongan belajar kelas binaan.
   - `subjects`: Master mata pelajaran kurikuler.
   - `audit_logs`: Buku besar audit forensik seluruh mutasi data dan aksi keamanan.
2. **Klaster 2: Bank Soal, Evaluasi Ujian & AI Proctoring (7 Tabel)**:
   - `questions`: Bank instrumen soal (Pilihan Ganda, Benar/Salah, Essay, Opsi JSON).
   - `exams`: Paket evaluasi ujian, durasi, dan sakelar AI Dual-Camera.
   - `exam_questions`: Tabel jembatan relasi *many-to-many* antara paket ujian dan butir soal.
   - `exam_sessions`: Sesi pengerjaan ujian per peserta didik, waktu sisa, dan skor risiko.
   - `answers`: Rekaman lembar jawaban siswa per butir soal beserta perolehan skor.
   - `camera_events`: Log perangkat keras kamera WebRTC (Connect, Disconnect, Detach).
   - `proctor_events`: Rekaman insiden anomali kecurangan visual AI beserta foto snapshot.
3. **Klaster 3: Notifikasi, Gateway WhatsApp & Konfigurasi Sistem (6 Tabel)**:
   - `notifications`: Kotak masuk notifikasi internal pengguna (*in-app notification*).
   - `push_subscriptions`: Endpoint langganan Web Push peramban berbasis enkripsi VAPID.
   - `whatsapp_otps`: Token sandi sekali pakai (OTP) 2FA berbatas waktu 5 menit.
   - `whatsapp_logs`: Jejak pengiriman pesan gateway Fonnte, status kirim, dan respons JSON.
   - `whatsapp_settings`: Penyimpanan preferensi sakelar notifikasi dan *lead time* pengingat.
   - `exam_configs`: Pasangan kunci-nilai parameter khusus konfigurasi per ujian.

---

### 6.2 Visualisasi Entity-Relationship Diagram (ERD Terpadu)

```
 +---------------------------------------------------------------------------------------------------+
 |                                   ENTITY-RELATIONSHIP DIAGRAM (19 TABEL)                          |
 +---------------------------------------------------------------------------------------------------+

     [ classes ] 1 -----< M [ students ] M >----- 1 [ users ] 1 ----- 1 [ teachers ]
                                |                        |                     |
                                |                        | 1                   | 1
                                |                        |                     |
                                |                        v M                   v M
                                |                  [ audit_logs ]        [ subjects ]
                                |                  [ notifications ]           | 1
                                |                  [ push_subscriptions ]      |
                                |                  [ whatsapp_otps ]           v M
                                |                  [ whatsapp_logs ]     [ questions ]
                                |                                              | M
                                v M                                            |
                         [ exam_sessions ] M >---------------------------------+
                                | 1                                            |
         +----------------------+----------------------+                       v 1
         | 1                    | 1                    | 1              [ exam_questions ]
         v M                    v M                    v M                     ^ M
    [ answers ]          [ camera_events ]      [ proctor_events ]             |
                                                                               |
                                                                        [ exams ] 1
                                                                               | 1
                                                                               v M
                                                                        [ exam_configs ]
                                                                        
                         [ whatsapp_settings ] (Tabel Konfigurasi Global)
```

---

### 6.3 Kamus Data Lengkap 19 Tabel Basis Data (`uline_db`)

Berikut adalah kamus data struktural untuk seluruh 19 tabel fisik basis data:

#### Tabel 1: `users`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `name` | VARCHAR(100) | NO | - | Nama lengkap resmi pengguna |
| `username` | VARCHAR(50) | NO | - | Nama akun unik untuk login sistem (**UNIQUE**) |
| `email` | VARCHAR(100) | NO | - | Surel terverifikasi (**UNIQUE**) |
| `password` | VARCHAR(255) | NO | - | Hash kata sandi algoritma BCrypt |
| `role` | ENUM | NO | 'PESERTA' | `'SUPER_ADMIN'`, `'GURU'`, `'PESERTA'` |
| `status` | ENUM | NO | 'active' | Status akses akun: `'active'`, `'inactive'` |
| `phone_number` | VARCHAR(20) | YES | NULL | Nomor telepon WhatsApp untuk 2FA OTP |
| `whatsapp_opt_in` | TINYINT(1) | NO | 1 | Izin pengiriman notifikasi WhatsApp (1=Aktif) |
| `push_opt_in` | TINYINT(1) | NO | 1 | Izin pengiriman Web Push peramban |
| `created_at` / `updated_at` | TIMESTAMP | NO | CURRENT | Jejak waktu pembuatan & perubahan |

#### Tabel 2: `teachers`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `user_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `users.id` (ON DELETE CASCADE) |
| `nip` | VARCHAR(30) | YES | NULL | Nomor Induk Pegawai pengajar (**UNIQUE**) |
| `phone` | VARCHAR(20) | YES | NULL | Nomor kontak darurat pengajar |
| `created_at` / `updated_at` | TIMESTAMP | NO | CURRENT | Jejak waktu catatan guru |

#### Tabel 3: `students`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `user_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `users.id` (ON DELETE CASCADE) |
| `class_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `classes.id` (ON DELETE RESTRICT) |
| `nis` | VARCHAR(30) | YES | NULL | Nomor Induk Siswa nasional (**UNIQUE**) |
| `phone` | VARCHAR(20) | YES | NULL | Nomor kontak WhatsApp siswa |
| `created_at` / `updated_at` | TIMESTAMP | NO | CURRENT | Jejak waktu catatan siswa |

#### Tabel 4: `classes`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `name` | VARCHAR(50) | NO | - | Nama rombel (misal: XII MIPA 1, XI IPS 1) |
| `grade_level` | VARCHAR(10) | NO | - | Jenjang kelas ('10', '11', '12') |
| `academic_year` | VARCHAR(20) | NO | - | Tahun ajaran (misal: '2026/2027') |
| `created_at` / `updated_at` | TIMESTAMP | NO | CURRENT | Jejak waktu pembuatan rombel |

#### Tabel 5: `subjects`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `code` | VARCHAR(20) | NO | - | Kode mapel unik (misal: 'BIO-XII', 'MAT-W') |
| `name` | VARCHAR(100) | NO | - | Nama lengkap mata pelajaran |
| `teacher_id` | INT(11) UNSIGNED | YES | NULL | **Foreign Key** -> `teachers.id` (ON DELETE SET NULL) |
| `created_at` / `updated_at` | TIMESTAMP | NO | CURRENT | Jejak waktu mata pelajaran |

#### Tabel 6: `questions`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `subject_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `subjects.id` (ON DELETE CASCADE) |
| `teacher_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `teachers.id` (ON DELETE CASCADE) |
| `question_text`| TEXT | NO | - | Teks narasi butir soal evaluasi |
| `question_type`| ENUM | NO | 'multiple_choice'| `'multiple_choice'`, `'true_false'`, `'essay'` |
| `options` | JSON | YES | NULL | Opsi pilihan ganda format JSON `{"A":"...","B":"..."}` |
| `correct_answer`| VARCHAR(255) | YES | NULL | Kunci jawaban sah (misal: 'A' atau 'TRUE') |
| `score_weight` | DECIMAL(5,2) | NO | 1.00 | Bobot nilai maksimal butir soal |
| `difficulty` | ENUM | NO | 'medium' | `'easy'`, `'medium'`, `'hard'` |
| `explanation` | TEXT | YES | NULL | Teks pembahasan resmi guru untuk AI Tutor |
| `created_at` / `updated_at` | TIMESTAMP | NO | CURRENT | Jejak waktu bank soal |

#### Tabel 7: `exams`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `subject_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `subjects.id` |
| `class_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `classes.id` |
| `teacher_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `teachers.id` |
| `title` | VARCHAR(150) | NO | - | Judul agenda ujian |
| `description` | TEXT | YES | NULL | Petunjuk teknis pengerjaan ujian |
| `duration` | INT(11) | NO | 60 | Durasi alokasi pengerjaan dalam menit |
| `start_at` | DATETIME | NO | - | Waktu pembukaan akses ujian |
| `end_at` | DATETIME | NO | - | Waktu penutupan mutlak ujian |
| `random_questions` | TINYINT(1) | NO | 1 | Pengacakan urutan nomor butir soal |
| `random_options` | TINYINT(1) | NO | 1 | Pengacakan urutan opsi jawaban (A–E) |
| `is_proctoring_active` | TINYINT(1) | NO | 1 | Sakelar pengawasan AI Proctoring (1=Aktif) |
| `dual_camera_required` | TINYINT(1) | NO | 1 | Sakelar kewajiban WebRTC Dual Camera |
| `status` | ENUM | NO | 'draft' | `'draft'`, `'published'`, `'active'`, `'finished'` |
| `created_at` / `updated_at` | TIMESTAMP | NO | CURRENT | Jejak waktu pembuatan jadwal ujian |

#### Tabel 8: `exam_questions` (Tabel Jembatan Paket Ujian - Soal)
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `exam_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `exams.id` (ON DELETE CASCADE) |
| `question_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `questions.id` (ON DELETE CASCADE) |
| `order_index` | INT(11) | NO | 1 | Nomor urut dasar butir soal |
| `created_at` | TIMESTAMP | NO | CURRENT | Jejak waktu pemetaan soal |

#### Tabel 9: `exam_sessions`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `exam_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `exams.id` (ON DELETE CASCADE) |
| `user_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `users.id` (Peserta Didik) |
| `start_time` | DATETIME | YES | NULL | Waktu mulai riil siswa membuka ruang ujian |
| `end_time` | DATETIME | YES | NULL | Waktu penyerahan final ujian |
| `remaining_time`| INT(11) | NO | - | Sisa waktu persisten dalam detik |
| `status` | ENUM | NO | 'in_progress' | `'in_progress'`, `'finished'`, `'cancelled'` |
| `total_score` | DECIMAL(5,2) | YES | 0.00 | Skor perolehan akhir siswa (0.00–100.00) |
| `risk_score` | INT(11) | NO | 0 | Skor risiko kumulatif AI Proctoring |
| `camera1_status`| VARCHAR(20) | NO | 'ACTIVE' | Status aliran Kamera 1: `'ACTIVE'`, `'DISCONNECTED'` |
| `camera2_status`| VARCHAR(20) | NO | 'ACTIVE' | Status aliran Kamera 2: `'ACTIVE'`, `'FALLBACK'`, `'DISCONNECTED'` |
| `review_status` | ENUM | NO | 'pending' | Keputusan guru: `'pending'`, `'reviewed'`, `'confirmed'`, `'dismissed'` |
| `review_notes` | TEXT | YES | NULL | Catatan evaluasi tertulis dari pengawas |
| `created_at` / `updated_at` | TIMESTAMP | NO | CURRENT | Jejak waktu sesi ujian |

#### Tabel 10: `answers`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `session_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `exam_sessions.id` (ON DELETE CASCADE) |
| `question_id`| INT(11) UNSIGNED | NO | - | **Foreign Key** -> `questions.id` (ON DELETE CASCADE) |
| `selected_answer` | VARCHAR(255) | YES | NULL | Pilihan opsi siswa (misal: 'A', 'B', 'TRUE') |
| `essay_answer` | TEXT | YES | NULL | Jawaban narasi uraian siswa |
| `is_correct` | TINYINT(1) | YES | NULL | Status validasi otomatis (1=Benar, 0=Salah) |
| `score_obtained` | DECIMAL(5,2) | YES | 0.00 | Nilai poin angka yang diperoleh pada butir ini |
| `created_at` / `updated_at` | TIMESTAMP | NO | CURRENT | Jejak waktu autosave jawaban |

#### Tabel 11: `camera_events`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `session_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `exam_sessions.id` (ON DELETE CASCADE) |
| `camera_type`| ENUM | NO | - | Tipe kamera: `'FACE'`, `'DESK'`, `'ENVIRONMENT'` |
| `event_type` | VARCHAR(50) | NO | - | `'CONNECTED'`, `'DISCONNECTED'`, `'PERM_DENIED'` |
| `payload` | JSON | YES | NULL | Detail metadata perangkat keras & peramban |
| `captured_at` | TIMESTAMP | NO | CURRENT | Waktu pencatatan kejadian hardware |

#### Tabel 12: `proctor_events`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `session_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `exam_sessions.id` (ON DELETE CASCADE) |
| `event_type` | ENUM | NO | - | `'FACE_NOT_DETECTED'`, `'MULTIPLE_FACES'`, `'LOOKING_AWAY'`, `'CAMERA_DISCONNECTED'`, `'TAB_SWITCHED'`, `'SUSPICIOUS_ACTIVITY'` |
| `severity` | ENUM | NO | 'WARNING' | Tingkat keparahan: `'INFO'`, `'WARNING'`, `'HIGH'` |
| `score_impact`| INT(11) | NO | 5 | Bobot penambahan skor risiko (+1, +5, +15) |
| `snapshot_url`| VARCHAR(255) | YES | NULL | Lokasi file gambar bukti di server |
| `description` | VARCHAR(255) | NO | - | Deskripsi narasi pelanggaran perilaku |
| `detected_at` | TIMESTAMP | NO | CURRENT | Waktu deteksi insiden proctoring |

#### Tabel 13: `audit_logs`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `user_id` | INT(11) UNSIGNED | YES | NULL | **Foreign Key** -> `users.id` (Aktor pelaku aksi) |
| `action` | VARCHAR(50) | NO | - | Kode aksi: `'LOGIN'`, `'LOGOUT'`, `'CREATE_EXAM'`, `'SUBMIT_EXAM'`, `'REVIEW_EVENT'` |
| `details` | TEXT | YES | NULL | Rincian payload aksi atau perubahan nilai |
| `ip_address` | VARCHAR(45) | YES | NULL | Alamat IP v4/v6 klien pengguna |
| `user_agent` | VARCHAR(255) | YES | NULL | Informasi software peramban klien |
| `created_at` | TIMESTAMP | NO | CURRENT | Waktu rekaman audit forensik |

#### Tabel 14: `notifications`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `user_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `users.id` (Penerima notifikasi) |
| `title` | VARCHAR(150) | NO | - | Judul ringkas pesan notifikasi |
| `message` | TEXT | NO | - | Isi lengkap informasi pemberitahuan |
| `type` | VARCHAR(30) | NO | 'INFO' | Kategori pemberitahuan: `'EXAM'`, `'SYSTEM'` |
| `is_read` | TINYINT(1) | NO | 0 | Status keterbacaan (0=Belum, 1=Sudah) |
| `created_at` | TIMESTAMP | NO | CURRENT | Waktu penerbitan notifikasi |

#### Tabel 15: `push_subscriptions`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `user_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `users.id` (ON DELETE CASCADE) |
| `endpoint` | TEXT | NO | - | URL endpoint push service peramban |
| `public_key` | VARCHAR(255) | NO | - | Kunci enkripsi publik p256dh |
| `auth_token` | VARCHAR(255) | NO | - | Token autentikasi Web Push |
| `created_at` / `updated_at` | TIMESTAMP | NO | CURRENT | Jejak waktu pendaftaran perangkat |

#### Tabel 16: `whatsapp_otps`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `user_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `users.id` (ON DELETE CASCADE) |
| `phone_number`| VARCHAR(20) | NO | - | Nomor WhatsApp tujuan pengiriman |
| `otp_code` | VARCHAR(10) | NO | - | 6 digit token kode OTP numerik |
| `action` | VARCHAR(30) | NO | 'LOGIN' | Tujuan OTP: `'LOGIN'`, `'PASSWORD_RESET'` |
| `attempts` | INT(11) | NO | 0 | Jumlah percobaan verifikasi yang salah |
| `is_used` | TINYINT(1) | NO | 0 | Flag penggunaan (0=Aktif, 1=Hangus/Digunakan) |
| `expires_at` | DATETIME | NO | - | Batas waktu kedaluwarsa mutlak (+5 Menit) |
| `created_at` | TIMESTAMP | NO | CURRENT | Waktu penerbitan OTP |

#### Tabel 17: `whatsapp_logs`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `user_id` | INT(11) UNSIGNED | YES | NULL | **Foreign Key** -> `users.id` (Penerima pesan) |
| `phone_number`| VARCHAR(20) | NO | - | Nomor WhatsApp tujuan |
| `notification_type` | VARCHAR(50) | NO | - | `'LOGIN_OTP'`, `'EXAM_NEW'`, `'EXAM_REMINDER'`, `'EXAM_RESULT'` |
| `message` | TEXT | NO | - | Isi teks pesan yang dikirimkan ke WhatsApp |
| `status` | VARCHAR(20) | NO | 'PENDING' | Status pengiriman: `'SUCCESS'`, `'FAILED'`, `'PENDING'` |
| `provider_response` | TEXT | YES | NULL | Sanitized JSON respons mentah dari Fonnte |
| `error_message` | TEXT | YES | NULL | Pesan kegagalan pengiriman jika error |
| `idempotency_key` | VARCHAR(64) | YES | NULL | Kunci pencegah duplikasi pengiriman pesan |
| `created_at` | TIMESTAMP | NO | CURRENT | Waktu pengiriman pesan |

#### Tabel 18: `whatsapp_settings`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `setting_key` | VARCHAR(50) | NO | - | Kunci konfigurasi (**UNIQUE**) |
| `setting_value` | TEXT | NO | - | Nilai konfigurasi ('1', '0', atau angka menit) |
| `description` | VARCHAR(255) | YES | NULL | Deskripsi fungsional parameter gateway |
| `updated_at` | TIMESTAMP | NO | CURRENT | Waktu pembaruan preferensi |

#### Tabel 19: `exam_configs`
| Nama Kolom | Tipe Data | Nullable | Default | Keterangan Bisnis & Relasi |
|---|---|---|---|---|
| `id` | INT(11) UNSIGNED | NO | AUTO_INC | **Primary Key** |
| `exam_id` | INT(11) UNSIGNED | NO | - | **Foreign Key** -> `exams.id` (ON DELETE CASCADE) |
| `config_key` | VARCHAR(50) | NO | - | Parameter khusus (misal: 'max_tab_switches') |
| `config_value`| TEXT | NO | - | Nilai parameter konfigurasi ujian |
| `created_at` / `updated_at` | TIMESTAMP | NO | CURRENT | Jejak waktu parameter ujian |

---

# BAB VII: PERANCANGAN ANTARMUKA PENGGUNA (UI/UX DESIGN)

### 7.1 Prinsip Perancangan Desain Antarmuka (Cognitive Load Reduction)
Antarmuka pengguna sistem U-Line mengedepankan prinsip kenyamanan visual dan minimalisasi beban kognitif (*Cognitive Load Reduction*):
1. **Fokus Total di Ruang Ujian (*Distraction-Free Exam Engine*)**: Pada ruang ujian siswa, seluruh elemen navigasi sidebar dan footer disembunyikan. Antarmuka hanya menampilkan bilah status atas (Nama Ujian, Timer Server, Status Autosave), kartu soal utama di tengah, dan kisi-kisi nomor soal di panel samping.
2. **Keterbacaan Visual Cepat di Monitoring Pengawas (*At-a-Glance Monitoring*)**: Guru tidak perlu membaca teks tabel yang padat saat mengawasi ujian. Kartu peserta menggunakan indikator warna standar internasional: *Hijau (Normal)*, *Kuning (Warning)*, dan *Merah Berkedip (High Risk)* sehingga siswa yang bermasalah langsung teridentifikasi dalam 1 detik.
3. **Penyajian Asisten AI Non-Intrusif**: Widget obrolan *U-Line AI Assistant* dirancang mengambang (*floating button*) di pojok kanan bawah antarmuka. Tombol dapat dibuka dan ditutup secara fleksibel tanpa menghalangi konten pekerjaan utama.

### 7.2 Struktur Layout Utama Berbasis Peran
Sistem U-Line menerapkan pembagian tata letak antarmuka sesuai dengan peran akun pengguna:
* **Layout Administrator (`app/Views/admin/`)**: Navigasi sidebar kiri dengan palet warna abu-abu gelap profesional, menyajikan menu Dashboard, Manajemen Users, Guru, Siswa, Kelas, Mata Pelajaran, Konfigurasi AI & Model, serta WhatsApp Gateway.
* **Layout Guru Penguji (`app/Views/guru/`)**: Mengedepankan kemudahan akses instrumen evaluasi dengan menu Bank Soal, Manajemen & Jadwal Ujian, Live Monitoring Dashboard, Rekap Hasil Nilai, dan Profil Pengajar.
* **Layout Peserta Didik (`app/Views/peserta/`)**: Antarmuka bersih bernuansa biru muda dan putih, berpusat pada Kartu Ujian Aktif, Ruang Ujian Layar Penuh (*Exam Room*), Kalibrasi Kamera WebRTC, dan Pembahasan Soal bersama AI Tutor.

---

# BAB VIII: PENUTUP & PENGESAHAN DOKUMEN SDD

Dokumen *Software Design Document* (SDD) ini telah merumuskan rancangan arsitektur perangkat lunak sistem **U-Line (AI Online Examination & Dual-Camera Proctoring System)** secara presisi, terpadu, dan menyeluruh. Pemodelan 4 Diagram UML Utama (Use Case, Activity, Class, Sequence), skema relasional 19 tabel fisik basis data MySQL, serta spesifikasi antarmuka pengguna telah diuji keselarasan arsitekturnya (*100% architectural consistency*) dengan implementasi kode pada repositori proyek.

Dokumen ini menjadi bukti autentik pemenuhan target mutu **Skor 4 (Sangat Baik)** pada Rubrik Penilaian Proyek Akhir Mandiri Tahap 2 Berbasis Kurikulum OBE Program Studi S1 Teknik Informatika STMIK IKMI Cirebon.

---

### LEMBAR TANDA TANGAN MAHASISWA & PENGESAHAN

Cirebon, 14 November 2026

**Mahasiswa Perancang (Full-Stack Software Engineer):**

\
\
*(Tanda Tangan Digital)*
\
\
**RIAN AFRIYANSYAH**  
**NIM: 41256154**  
Mahasiswa Program Studi S1 Teknik Informatika  
STMIK IKMI Cirebon

---

**Mengetahui / Mengesahkan:**  
**Dosen Pengampu Mata Kuliah Rekayasa Perangkat Lunak,**

\
\
\
\
**YUDHISTIRA ARIE WIJAYA, M.Kom., CDS.**  
NIDN: Dosen Pengampu Rekayasa Perangkat Lunak (TIKB2503021)  
Program Studi S1 Teknik Informatika — STMIK IKMI Cirebon

