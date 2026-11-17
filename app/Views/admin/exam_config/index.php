<?php
/**
 * View: Konfigurasi Ujian Online
 * Path: app/Views/admin/exam_config/index.php
 * Role Access: SUPER_ADMIN & GURU
 */
$config = $config ?? [];
$secretKey = $secretKey ?? '';
$windowStatus = $windowStatus ?? ['isOpen' => false, 'status' => 'INACTIVE', 'message' => ''];
$currentUser = $currentUser ?? auth();

// Format datetime values for HTML datetime-local input
$startTimeVal = !empty($config['start_time']) ? date('Y-m-d\TH:i', strtotime($config['start_time'])) : date('Y-m-d\TH:i');
$endTimeVal = !empty($config['end_time']) ? date('Y-m-d\TH:i', strtotime($config['end_time'])) : date('Y-m-d\TH:i', strtotime('+4 hours'));
$durationVal = (int)($config['duration'] ?? 60);
$tokenVal = $config['token'] ?? 'ULINE2026';
$randomQuestions = !empty($config['random_questions']);
$randomOptions = !empty($config['random_options']);
$isActive = isset($config['is_active']) ? (int)$config['is_active'] === 1 : true;
?>

<!-- Breadcrumbs & Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                <?php if (($currentUser['role'] ?? '') === 'SUPER_ADMIN'): ?>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>" class="text-decoration-none">Admin</a></li>
                <?php else: ?>
                    <li class="breadcrumb-item"><a href="<?= base_url('guru/dashboard') ?>" class="text-decoration-none">Guru</a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page">Konfigurasi Ujian</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-sliders2-vertical text-primary"></i>
            Konfigurasi Ujian Online
        </h1>
        <p class="text-secondary small mb-0 mt-1">
            Atur durasi pengerjaan, pengacakan butir soal, jadwal waktu mulai/selesai, token pengerjaan, dan kredensial keamanan sistem (.env).
        </p>
    </div>

    <!-- Quick Status Badge & Role Indicator -->
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill font-monospace">
            <i class="bi bi-shield-check me-1"></i>Role: <?= e($currentUser['role'] ?? 'AUTHORIZED') ?>
        </span>
        <?php if ($windowStatus['isOpen']): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                <i class="bi bi-broadcast me-1"></i>Ujian Aktif
            </span>
        <?php else: ?>
            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill">
                <i class="bi bi-clock-history me-1"></i><?= e($windowStatus['status'] ?? 'TERJADWAL') ?>
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Exam Window Alert Banner -->
<div class="card border-0 shadow-sm mb-4 bg-<?= $windowStatus['isOpen'] ? 'success-subtle' : 'light' ?> border-start border-4 border-<?= $windowStatus['isOpen'] ? 'success' : 'primary' ?>">
    <div class="card-body p-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-2 rounded-circle bg-<?= $windowStatus['isOpen'] ? 'success' : 'primary' ?> text-white">
                <i class="bi bi-<?= $windowStatus['isOpen'] ? 'door-open-fill' : 'calendar-event-fill' ?> fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1 text-dark">Status Jadwal Ujian: <?= e($windowStatus['message']) ?></h6>
                <div class="text-secondary small">
                    <span class="me-3"><i class="bi bi-clock me-1"></i>Mulai: <strong><?= date('d M Y, H:i', strtotime($config['start_time'] ?? 'now')) ?> WIB</strong></span>
                    <span><i class="bi bi-flag me-1"></i>Selesai: <strong><?= date('d M Y, H:i', strtotime($config['end_time'] ?? 'now')) ?> WIB</strong></span>
                </div>
            </div>
        </div>
        <div class="text-sm-end">
            <span class="badge bg-dark text-white font-monospace py-2 px-3 fs-6">
                <i class="bi bi-key-fill text-warning me-1"></i>TOKEN: <?= e($tokenVal) ?>
            </span>
        </div>
    </div>
</div>

<!-- Main Configuration Form -->
<form id="examConfigForm" action="<?= base_url('admin/exam-config/update') ?>" method="POST" novalidate>
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- Left Column: Primary Settings -->
        <div class="col-lg-8">
            <!-- 1. Durasi & Waktu Pengerjaan -->
            <div class="card border-0 shadow-sm mb-4 rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                    <i class="bi bi-stopwatch text-primary fs-5"></i>
                    <h5 class="card-title fw-bold mb-0">1. Durasi & Waktu Pengerjaan Ujian</h5>
                </div>
                <div class="card-body p-4">
                    <!-- Title Input -->
                    <div class="mb-4">
                        <label for="cfg_title" class="form-label fw-semibold text-dark">
                            Nama / Judul Konfigurasi Ujian <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg fs-6" 
                               id="cfg_title" 
                               name="title" 
                               value="<?= e($config['title'] ?? 'Konfigurasi Utama Ujian Online') ?>" 
                               required 
                               placeholder="Contoh: Penilaian Akhir Semester (PAS) Ganjil 2026/2027">
                        <div class="form-text">Judul yang ditampilkan kepada peserta saat mengakses portal ujian.</div>
                    </div>

                    <!-- Duration Input with Quick Preset Chips -->
                    <div class="mb-4">
                        <label for="cfg_duration" class="form-label fw-semibold text-dark d-flex justify-content-between align-items-center">
                            <span>Durasi Pengerjaan Siswa (Menit) <span class="text-danger">*</span></span>
                            <span class="badge bg-primary-subtle text-primary fw-bold fs-6 font-monospace" id="durationBadge"><?= $durationVal ?> Menit</span>
                        </label>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light text-secondary"><i class="bi bi-hourglass-split"></i></span>
                            <input type="number" 
                                   class="form-control form-control-lg fs-6" 
                                   id="cfg_duration" 
                                   name="duration" 
                                   value="<?= $durationVal ?>" 
                                   min="5" 
                                   max="600" 
                                   required>
                            <span class="input-group-text bg-light">Menit</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="text-muted extra-small align-self-center me-1">Pilihan Cepat:</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-duration" data-minutes="30">30 Menit</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-duration" data-minutes="45">45 Menit</button>
                            <button type="button" class="btn btn-sm btn-outline-primary preset-duration" data-minutes="60">60 Menit</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-duration" data-minutes="90">90 Menit</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary preset-duration" data-minutes="120">120 Menit</button>
                        </div>
                    </div>

                    <!-- Start & End Datetime -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cfg_start_time" class="form-label fw-semibold text-dark">
                                <i class="bi bi-calendar-check text-success me-1"></i>Waktu Mulai Ujian <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" 
                                   class="form-control" 
                                   id="cfg_start_time" 
                                   name="start_time" 
                                   value="<?= e($startTimeVal) ?>" 
                                   required>
                            <div class="form-text">Peserta hanya dapat membuka soal setelah waktu ini.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="cfg_end_time" class="form-label fw-semibold text-dark">
                                <i class="bi bi-calendar-x text-danger me-1"></i>Waktu Selesai Ujian <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" 
                                   class="form-control" 
                                   id="cfg_end_time" 
                                   name="end_time" 
                                   value="<?= e($endTimeVal) ?>" 
                                   required>
                            <div class="form-text">Pengerjaan akan otomatis ditutup setelah waktu ini.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Pengacakan Soal & Jawaban -->
            <div class="card border-0 shadow-sm mb-4 rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                    <i class="bi bi-shuffle text-info fs-5"></i>
                    <h5 class="card-title fw-bold mb-0">2. Opsi Pengacakan Butir Soal & Pilihan Jawaban</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <!-- Acak Soal Toggle -->
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 h-100 bg-light-subtle d-flex flex-column justify-content-between">
                                <div>
                                    <div class="form-check form-switch fs-5 mb-2">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               role="switch" 
                                               id="cfg_random_questions" 
                                               name="random_questions" 
                                               value="1" 
                                               <?= $randomQuestions ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold fs-6 text-dark" for="cfg_random_questions">
                                            Acak Urutan Soal
                                        </label>
                                    </div>
                                    <p class="text-secondary small mb-0">
                                        Urutan nomor butir soal akan diacak secara dinamis untuk setiap siswa guna meminimalisir saling contek.
                                    </p>
                                </div>
                                <div class="mt-3">
                                    <span class="badge bg-<?= $randomQuestions ? 'primary' : 'secondary' ?>-subtle text-<?= $randomQuestions ? 'primary' : 'secondary' ?> font-monospace" id="lbl_random_questions">
                                        <?= $randomQuestions ? 'Acak Soal: AKTIF' : 'Acak Soal: NONAKTIF' ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Acak Pilihan Jawaban Toggle -->
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 h-100 bg-light-subtle d-flex flex-column justify-content-between">
                                <div>
                                    <div class="form-check form-switch fs-5 mb-2">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               role="switch" 
                                               id="cfg_random_options" 
                                               name="random_options" 
                                               value="1" 
                                               <?= $randomOptions ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold fs-6 text-dark" for="cfg_random_options">
                                            Acak Pilihan Jawaban
                                        </label>
                                    </div>
                                    <p class="text-secondary small mb-0">
                                        Opsi pilihan ganda (A, B, C, D) akan diacak posisinya tanpa merubah kunci jawaban yang benar.
                                    </p>
                                </div>
                                <div class="mt-3">
                                    <span class="badge bg-<?= $randomOptions ? 'info' : 'secondary' ?>-subtle text-<?= $randomOptions ? 'info' : 'secondary' ?> font-monospace" id="lbl_random_options">
                                        <?= $randomOptions ? 'Acak Opsi: AKTIF' : 'Acak Opsi: NONAKTIF' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Token, Credentials & Submit Actions -->
        <div class="col-lg-4">
            <!-- 3. Token Pengerjaan Ujian -->
            <div class="card border-0 shadow-sm mb-4 rounded-3 border-top border-4 border-warning">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-key-fill text-warning fs-5"></i>
                        <h5 class="card-title fw-bold mb-0">3. Token Ujian</h5>
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis">Wajib Ujian</span>
                </div>
                <div class="card-body p-4">
                    <label for="cfg_token" class="form-label fw-semibold text-dark">
                        Token Akses Siswa <span class="text-danger">*</span>
                    </label>
                    <div class="input-group mb-2">
                        <input type="text" 
                               class="form-control form-control-lg font-monospace fw-bold text-center tracking-wider text-primary fs-5" 
                               id="cfg_token" 
                               name="token" 
                               value="<?= e($tokenVal) ?>" 
                               maxlength="30" 
                               required 
                               placeholder="ULINE2026">
                        <button class="btn btn-outline-secondary" type="button" id="btnCopyToken" title="Salin Token">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <p class="text-secondary extra-small mb-3">
                        Token ini harus dibagikan oleh Pengawas/Guru kepada peserta saat ujian dimulai.
                    </p>

                    <!-- Token Action Buttons -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center gap-2" id="btnGenerateToken">
                            <i class="bi bi-arrow-repeat"></i>
                            <span>Generate Token Acak</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4. Kredensial Rahasia (.env) -->
            <div class="card border-0 shadow-sm mb-4 rounded-3 border-top border-4 border-danger">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock-fill text-danger fs-5"></i>
                        <h5 class="card-title fw-bold mb-0">4. Kredensial .env</h5>
                    </div>
                    <span class="badge bg-danger-subtle text-danger font-monospace">SECRET</span>
                </div>
                <div class="card-body p-4">
                    <label for="cfg_token_secret" class="form-label fw-semibold text-dark d-flex justify-content-between align-items-center">
                        <span>EXAM_TOKEN_SECRET_KEY</span>
                        <span class="badge bg-success-subtle text-success extra-small">Tersimpan di .env</span>
                    </label>
                    <div class="input-group mb-2">
                        <input type="password" 
                               class="form-control font-monospace" 
                               id="cfg_token_secret" 
                               name="token_secret" 
                               value="<?= e($secretKey) ?>" 
                               required 
                               placeholder="exam_sec_...">
                        <button class="btn btn-outline-secondary" type="button" id="btnToggleSecret" title="Lihat / Sembunyikan Kredensial">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="extra-small text-muted mb-3">
                        <i class="bi bi-info-circle me-1"></i>Kredensial rahasia disimpan langsung di file server <code>.env</code>. Nilai ini dipakai untuk enkripsi dan verifikasi token siswa.
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 d-flex align-items-center justify-content-center gap-1" id="btnGenerateSecret">
                        <i class="bi bi-magic"></i>
                        <span>Buat Kredensial Acak Baru</span>
                    </button>
                </div>
            </div>

            <!-- 5. Status Aktifasi & Simpan Form -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" 
                               type="checkbox" 
                               role="switch" 
                               id="cfg_is_active" 
                               name="is_active" 
                               value="1" 
                               <?= $isActive ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold text-dark" for="cfg_is_active">
                            Status Pengaturan Aktif
                        </label>
                    </div>

                    <hr class="my-3">

                    <!-- Save Submission Button -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" id="btnSubmitConfig">
                            <i class="bi bi-check2-circle fs-5"></i>
                            <span id="btnSubmitText">Simpan Konfigurasi</span>
                            <span class="spinner-border spinner-border-sm d-none" id="btnSubmitSpinner" role="status" aria-hidden="true"></span>
                        </button>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-light text-secondary">
                            Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Bootstrap 5 Toast Notification Container (Fixed at Top-Right) -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
    <div id="saveToast" class="toast align-items-center text-bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-3">
                <div class="p-2 rounded-circle bg-white text-success d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-check-lg fs-5 fw-bold"></i>
                </div>
                <div>
                    <strong class="d-block text-white fs-6">Berhasil Disimpan!</strong>
                    <span id="toastMessage" class="text-white-50 small">Konfigurasi ujian dan kredensial rahasia di .env berhasil diperbarui.</span>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Interactive Client-side Script for Form, Presets, Token Gen, and Toast -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('examConfigForm');
    const toastEl = document.getElementById('saveToast');
    const toastMessage = document.getElementById('toastMessage');
    const toast = new bootstrap.Toast(toastEl, { delay: 5000 });

    const durationInput = document.getElementById('cfg_duration');
    const durationBadge = document.getElementById('durationBadge');
    const tokenInput = document.getElementById('cfg_token');
    const secretInput = document.getElementById('cfg_token_secret');
    const btnCopyToken = document.getElementById('btnCopyToken');
    const btnGenerateToken = document.getElementById('btnGenerateToken');
    const btnToggleSecret = document.getElementById('btnToggleSecret');
    const btnGenerateSecret = document.getElementById('btnGenerateSecret');
    const btnSubmit = document.getElementById('btnSubmitConfig');
    const btnText = document.getElementById('btnSubmitText');
    const btnSpinner = document.getElementById('btnSubmitSpinner');

    const switchRandQuestions = document.getElementById('cfg_random_questions');
    const lblRandQuestions = document.getElementById('lbl_random_questions');
    const switchRandOptions = document.getElementById('cfg_random_options');
    const lblRandOptions = document.getElementById('lbl_random_options');

    // 1. Duration input badge update & preset chips
    function updateDurationBadge() {
        const val = durationInput.value || 0;
        durationBadge.textContent = val + ' Menit';
    }
    durationInput.addEventListener('input', updateDurationBadge);

    document.querySelectorAll('.preset-duration').forEach(btn => {
        btn.addEventListener('click', function () {
            durationInput.value = this.dataset.minutes;
            updateDurationBadge();
            document.querySelectorAll('.preset-duration').forEach(b => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-primary');
        });
    });

    // 2. Toggle switches status labels
    if (switchRandQuestions) {
        switchRandQuestions.addEventListener('change', function () {
            if (this.checked) {
                lblRandQuestions.textContent = 'Acak Soal: AKTIF';
                lblRandQuestions.className = 'badge bg-primary-subtle text-primary font-monospace';
            } else {
                lblRandQuestions.textContent = 'Acak Soal: NONAKTIF';
                lblRandQuestions.className = 'badge bg-secondary-subtle text-secondary font-monospace';
            }
        });
    }

    if (switchRandOptions) {
        switchRandOptions.addEventListener('change', function () {
            if (this.checked) {
                lblRandOptions.textContent = 'Acak Opsi: AKTIF';
                lblRandOptions.className = 'badge bg-info-subtle text-info font-monospace';
            } else {
                lblRandOptions.textContent = 'Acak Opsi: NONAKTIF';
                lblRandOptions.className = 'badge bg-secondary-subtle text-secondary font-monospace';
            }
        });
    }

    // 3. Generate Random Exam Token
    btnGenerateToken.addEventListener('click', async function () {
        btnGenerateToken.disabled = true;
        btnGenerateToken.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';

        try {
            const formData = new FormData();
            formData.append('_csrf_token', '<?= csrf_token() ?>');

            const resp = await fetch('<?= base_url('admin/exam-config/generate-token') ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const data = await resp.json();
            if (data && data.token) {
                tokenInput.value = data.token;
            } else {
                // Client-side fallback generator
                tokenInput.value = generateClientToken(6);
            }
        } catch (e) {
            tokenInput.value = generateClientToken(6);
        } finally {
            btnGenerateToken.disabled = false;
            btnGenerateToken.innerHTML = '<i class="bi bi-arrow-repeat"></i><span>Generate Token Acak</span>';
        }
    });

    function generateClientToken(length = 6) {
        const chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }

    // 4. Copy Token to Clipboard
    btnCopyToken.addEventListener('click', function () {
        if (!tokenInput.value) return;
        navigator.clipboard.writeText(tokenInput.value).then(() => {
            const origHtml = btnCopyToken.innerHTML;
            btnCopyToken.innerHTML = '<i class="bi bi-check text-success"></i>';
            setTimeout(() => { btnCopyToken.innerHTML = origHtml; }, 1500);
        });
    });

    // 5. Toggle Secret Visibility
    btnToggleSecret.addEventListener('click', function () {
        if (secretInput.type === 'password') {
            secretInput.type = 'text';
            btnToggleSecret.innerHTML = '<i class="bi bi-eye-slash"></i>';
        } else {
            secretInput.type = 'password';
            btnToggleSecret.innerHTML = '<i class="bi bi-eye"></i>';
        }
    });

    // 6. Generate New Random Secret Key
    btnGenerateSecret.addEventListener('click', function () {
        const randChars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        let randHex = '';
        for (let i = 0; i < 16; i++) {
            randHex += randChars.charAt(Math.floor(Math.random() * randChars.length));
        }
        secretInput.value = 'exam_sec_' + randHex;
    });

    // 7. Form Submission with AJAX & Instant Toast Notification
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Basic HTML5 validation
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const start = new Date(document.getElementById('cfg_start_time').value);
        const end = new Date(document.getElementById('cfg_end_time').value);
        if (end <= start) {
            alert('Waktu selesai ujian harus lebih besar dari waktu mulai.');
            return;
        }

        // Show loading state
        btnSubmit.disabled = true;
        btnText.textContent = 'Menyimpan...';
        btnSpinner.classList.remove('d-none');

        try {
            const formData = new FormData(form);

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                toastMessage.textContent = result.message || 'Konfigurasi ujian dan kredensial .env berhasil disimpan!';
                toast.show();
            } else {
                alert(result.message || 'Gagal menyimpan konfigurasi.');
            }
        } catch (err) {
            // Fallback to normal form submit if fetch fails
            form.submit();
        } finally {
            btnSubmit.disabled = false;
            btnText.textContent = 'Simpan Konfigurasi';
            btnSpinner.classList.add('d-none');
        }
    });

    // 8. If loaded with PHP Flash success message, automatically show Toast!
    <?php if (has_flash('success')): ?>
        toastMessage.textContent = "<?= e(flash('success')) ?>";
        toast.show();
    <?php endif; ?>
});
</script>
