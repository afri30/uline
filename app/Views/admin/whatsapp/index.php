<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-whatsapp text-success me-2"></i>WhatsApp Gateway (Fonnte API)</h4>
        <p class="text-muted small mb-0">Kelola 2 layanan notifikasi: <strong>Notifikasi OTP Login</strong> & <strong>Notifikasi Informasi Ujian</strong>, integrasi gateway Fonnte, serta audit log pengiriman.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if ($configStatus['is_configured']): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-semibold">
                <i class="bi bi-check-circle-fill me-1"></i> Fonnte Token Aktif
            </span>
        <?php else: ?>
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill fw-semibold">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Token Belum Dikonfigurasi
            </span>
        <?php endif; ?>
        <?php if (!empty($deviceInfo['device']) && $deviceInfo['device'] !== '-'): ?>
            <span class="badge <?= ($deviceInfo['connected'] ?? false) ? 'bg-primary-subtle text-primary border-primary-subtle' : 'bg-warning-subtle text-warning border-warning-subtle' ?> border px-3 py-2 rounded-pill fw-semibold">
                <i class="bi bi-phone me-1"></i> <?= e($deviceInfo['device']) ?> (<?= e($deviceInfo['name'] ?? 'Device') ?>)
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Device & Gateway Info Bar -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-light-subtle border-start border-4 border-success">
    <div class="card-body p-3 p-md-4">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-4 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success text-white p-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px;">
                    <i class="bi bi-whatsapp fs-3"></i>
                </div>
                <div>
                    <span class="text-muted extra-small d-block fw-semibold text-uppercase">Status Perangkat WhatsApp</span>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <?php if ($deviceInfo['connected'] ?? false): ?>
                            <span class="badge bg-success rounded-pill px-2 py-1"><i class="bi bi-wifi me-1"></i> Terhubung (Online)</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark rounded-pill px-2 py-1"><i class="bi bi-exclamation-circle me-1"></i> <?= e($deviceInfo['device_status'] ?? 'Terputus') ?></span>
                        <?php endif; ?>
                        <span class="fw-bold text-dark small font-monospace"><?= e($deviceInfo['device'] ?? '-') ?></span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 border-start-md">
                <span class="text-muted extra-small d-block fw-semibold text-uppercase">Sisa Kuota Pesan</span>
                <div class="d-flex align-items-baseline gap-1 mt-1">
                    <span class="fs-4 fw-bold text-primary"><?= number_format((int)($deviceInfo['quota'] ?? 0)) ?></span>
                    <span class="text-muted small">Pesan</span>
                </div>
            </div>
            <div class="col-6 col-md-3 border-start-md">
                <span class="text-muted extra-small d-block fw-semibold text-uppercase">Paket Fonnte</span>
                <div class="mt-1">
                    <span class="badge bg-secondary-subtle text-dark border px-2 py-1 fw-semibold"><?= e($deviceInfo['package'] ?? 'Free') ?></span>
                    <?php if (!empty($deviceInfo['expired']) && $deviceInfo['expired'] !== '-'): ?>
                        <div class="extra-small text-muted mt-1">Exp: <?= e($deviceInfo['expired']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-12 col-md-2 text-md-end">
                <a href="https://fonnte.com" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm rounded-pill px-3">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Fonnte Web
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Total Log</span>
                <span class="badge bg-light text-secondary rounded-pill"><i class="bi bi-chat-dots-fill"></i></span>
            </div>
            <h3 class="fw-bold text-dark mb-0"><?= number_format($stats['total'] ?? 0) ?></h3>
            <span class="text-muted extra-small">Semua pengiriman</span>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Berhasil Diterima</span>
                <span class="badge bg-success-subtle text-success rounded-pill"><i class="bi bi-check-lg"></i></span>
            </div>
            <h3 class="fw-bold text-success mb-0"><?= number_format($stats['success'] ?? 0) ?></h3>
            <span class="text-muted extra-small">Gateway Fonnte accepted</span>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Gagal / Ditolak</span>
                <span class="badge bg-danger-subtle text-danger rounded-pill"><i class="bi bi-x-circle-fill"></i></span>
            </div>
            <h3 class="fw-bold text-danger mb-0"><?= number_format($stats['failed'] ?? 0) ?></h3>
            <span class="text-muted extra-small">Memerlukan perhatian</span>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Pengiriman Hari Ini</span>
                <span class="badge bg-primary-subtle text-primary rounded-pill"><i class="bi bi-calendar2-check-fill"></i></span>
            </div>
            <h3 class="fw-bold text-primary mb-0"><?= number_format($stats['today_count'] ?? 0) ?></h3>
            <span class="text-muted extra-small">Pesan dalam 24 jam</span>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- DUA SISTEM NOTIFIKASI: 1. NOTIFIKASI OTP | 2. NOTIFIKASI INFORMASI UJIAN  -->
<!-- ========================================================================= -->
<div class="row g-4 mb-4">

    <!-- ===================================================================== -->
    <!-- BAGIAN 1: WHATSAPP NOTIFIKASI OTP (LOGIN & VERIFIKASI AKUN)          -->
    <!-- ===================================================================== -->
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 d-flex flex-column border-top border-4 border-primary">
            <!-- Header Card 1 -->
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-shield-lock-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 fs-6 text-dark">1. WhatsApp Notifikasi OTP</h5>
                        <span class="text-muted extra-small">Login & Verifikasi Akun Peserta</span>
                    </div>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1 extra-small fw-semibold">
                    Layanan OTP
                </span>
            </div>

            <div class="card-body p-4 d-flex flex-column gap-3">
                <p class="text-muted small mb-2">
                    Mengirimkan kode verifikasi 6 digit numerik ke WhatsApp peserta saat masuk ke sistem ujian online tanpa perlu mengingat sandi, lengkap dengan masa berlaku dan peringatan keamanan.
                </p>

                <!-- Form Pengaturan OTP -->
                <form action="<?= base_url('admin/whatsapp/settings') ?>" method="POST" class="p-3 border rounded-3 bg-light-subtle">
                    <?= csrf_field() ?>
                    <input type="hidden" name="scope" value="otp">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-1 small text-dark"><i class="bi bi-key-fill text-primary me-2"></i>Aktifkan Fitur Login dengan WhatsApp OTP</h6>
                            <p class="text-muted extra-small mb-0">Peserta dapat meminta kode OTP ke nomor WA terdaftar untuk login otomatis.</p>
                        </div>
                        <div class="form-check form-switch fs-5 mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" name="notify_login_otp" value="1" <?= $settings['notify_login_otp'] ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="extra-small text-muted"><i class="bi bi-shield-check text-success me-1"></i>Masa aktif kode: <strong>5 Menit</strong></span>
                        <button type="submit" class="btn btn-primary btn-sm px-3 rounded-pill fw-semibold">
                            <i class="bi bi-save me-1"></i> Simpan Pengaturan OTP
                        </button>
                    </div>
                </form>

                <!-- Pratinjau Template Pesan OTP -->
                <div class="accordion" id="accordionOtpPreview">
                    <div class="accordion-item border rounded-3 overflow-hidden">
                        <h2 class="accordion-header" id="headingOtpPreview">
                            <button class="accordion-button collapsed py-2 px-3 small bg-white text-secondary fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOtpPreview" aria-expanded="false" aria-controls="collapseOtpPreview">
                                <i class="bi bi-eye text-primary me-2"></i> Pratinjau Format Pesan WhatsApp OTP
                            </button>
                        </h2>
                        <div id="collapseOtpPreview" class="accordion-collapse collapse" aria-labelledby="headingOtpPreview" data-bs-parent="#accordionOtpPreview">
                            <div class="accordion-body bg-light p-3">
                                <div class="p-3 rounded-3 shadow-sm bg-white border border-success-subtle position-relative" style="max-width: 380px;">
                                    <div class="small fw-bold text-success mb-2"><i class="bi bi-whatsapp me-1"></i> U-Line Notification</div>
                                    <div class="extra-small text-dark font-monospace" style="white-space: pre-wrap; line-height: 1.5;">🔐 *KODE VERIFIKASI (OTP) U-LINE*

Halo *Ahmad Pratama*,
Berikut adalah Kode Verifikasi (OTP) untuk masuk ke akun *Sistem Ujian Online U-Line* Anda:

👉 *482910*

⏱️ Kode ini hanya berlaku selama *5 menit*.
⚠️ *PENTING*: JANGAN pernah membagikan kode OTP ini kepada siapa pun termasuk pihak sekolah atau pengawas ujian.

_Jika Anda tidak merasa meminta kode ini, silakan abaikan pesan ini._</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Uji Coba Pengiriman OTP & Simulasi Verifikasi -->
                <div class="p-3 border rounded-3 bg-white mt-auto">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold mb-0 small text-dark"><i class="bi bi-send-check text-primary me-2"></i>Uji Coba Pengiriman & Verifikasi OTP</h6>
                        <span class="badge bg-secondary-subtle text-secondary extra-small">Simulasi Pengujian</span>
                    </div>

                    <!-- Petunjuk / Alur Penggunaan Nyata -->
                    <div class="alert alert-primary-subtle border border-primary-subtle rounded-3 p-2.5 mb-3 extra-small">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle-fill text-primary fs-6 mt-0.5"></i>
                            <div>
                                <span class="fw-bold text-dark d-block">Di mana kode OTP ini dimasukkan oleh pengguna?</span>
                                <span class="text-secondary d-block mb-1.5" style="line-height: 1.4;">
                                    Pada sistem nyata, kode OTP dimasukkan oleh pengguna (Peserta Didik, Guru, atau Admin) pada <strong>Halaman Login (Tab "WhatsApp OTP")</strong> untuk masuk ke akun tanpa password.
                                </span>
                                <a href="<?= base_url('login?tab=wa') ?>" target="_blank" class="btn btn-outline-primary py-1 px-2.5 extra-small fw-semibold rounded-2 text-decoration-none shadow-sm" style="font-size: 0.72rem;">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Halaman Login WhatsApp OTP
                                </a>
                            </div>
                        </div>
                    </div>

                    <div id="otpTestAlert" class="alert d-none extra-small py-2 px-3 rounded-3 mb-3"></div>

                    <!-- LANGKAH 1: Form Kirim Kode OTP -->
                    <div id="otpStepSend" class="<?= (!empty($hasActiveTestOtp)) ? 'd-none' : '' ?>">
                        <form action="<?= base_url('admin/whatsapp/test-otp') ?>" method="POST" id="formTestOtp">
                            <?= csrf_field() ?>
                            <div class="mb-2">
                                <label class="form-label extra-small fw-semibold text-secondary mb-1">Nomor WhatsApp Tujuan <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-whatsapp"></i></span>
                                    <input type="text" name="phone" id="otpPhoneInput" class="form-control" placeholder="Contoh: 08123456789 atau 628123456789" value="<?= e($testOtpSession['phone'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label extra-small fw-semibold text-secondary mb-1">Nama Pengguna (Simulasi)</label>
                                <input type="text" name="name" id="otpNameInput" class="form-control form-control-sm" placeholder="Contoh: Budi Santoso" value="<?= e($testOtpSession['name'] ?? 'Siswa Uji Coba') ?>">
                            </div>
                            <button type="submit" id="btnTestOtp" class="btn btn-primary btn-sm px-4 py-2 rounded-3 fw-semibold w-100" <?= !$configStatus['is_configured'] ? 'disabled' : '' ?>>
                                <i class="bi bi-send-fill me-1"></i> Kirim Uji Coba Kode OTP
                            </button>
                        </form>
                    </div>

                    <!-- LANGKAH 2: Masukkan & Verifikasi Kode OTP yang Diterima di WhatsApp -->
                    <div id="otpStepVerify" class="<?= (!empty($hasActiveTestOtp)) ? '' : 'd-none' ?>">
                        <div class="bg-light p-3 rounded-3 border border-primary-subtle">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-success-subtle text-success border border-success-subtle extra-small fw-semibold">
                                    <i class="bi bi-check2-circle me-1"></i> OTP Terkirim ke WhatsApp
                                </span>
                                <span class="extra-small text-muted font-monospace"><i class="bi bi-clock me-1"></i>Berlaku 5 menit</span>
                            </div>
                            <div class="extra-small text-secondary mb-2" style="line-height: 1.4;">
                                Masukkan <strong>6 digit kode OTP</strong> yang baru saja Anda terima di pesan WhatsApp nomor <strong id="otpTargetDisplay" class="text-dark font-monospace"><?= e($testOtpSession['phone'] ?? '') ?></strong>:
                            </div>
                            <form action="<?= base_url('admin/whatsapp/verify-test-otp') ?>" method="POST" id="formVerifyTestOtp">
                                <?= csrf_field() ?>
                                <div class="mb-2">
                                    <input type="text" name="otp_code" id="otpVerifyInput" class="form-control form-control-lg text-center font-monospace fw-bold bg-white" placeholder="• • • • • •" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" style="letter-spacing: 0.4rem; font-size: 1.35rem;" required autocomplete="one-time-code">
                                    <div class="form-text extra-small text-muted text-center mt-1">
                                        Ketikkan 6 digit angka kode OTP dari WhatsApp untuk menguji verifikasi.
                                    </div>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" id="btnVerifyTestOtp" class="btn btn-success btn-sm py-2 rounded-3 fw-semibold shadow-sm">
                                        <i class="bi bi-shield-check me-1"></i> Masukkan & Verifikasi Kode OTP
                                    </button>
                                    <button type="button" id="btnResetTestOtp" class="btn btn-link btn-sm text-secondary text-decoration-none extra-small py-0">
                                        <i class="bi bi-arrow-left me-1"></i> Ganti Nomor / Kirim Ulang OTP
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- BAGIAN 2: WHATSAPP NOTIFIKASI INFORMASI UJIAN (JADWAL, PENGINGAT, DLL) -->
    <!-- ===================================================================== -->
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 d-flex flex-column border-top border-4 border-success">
            <!-- Header Card 2 -->
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-success-subtle text-success p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-journal-check fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 fs-6 text-dark">2. WhatsApp Notifikasi Informasi Ujian</h5>
                        <span class="text-muted extra-small">Jadwal, Pengingat, Perubahan, Nilai & Guru</span>
                    </div>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 extra-small fw-semibold">
                    Layanan Ujian
                </span>
            </div>

            <div class="card-body p-4 d-flex flex-column gap-3">
                <p class="text-muted small mb-2">
                    Mengirimkan pemberitahuan otomatis ke WhatsApp peserta dan guru terkait siklus ujian online: publikasi jadwal baru, alarm pengingat mendekati ujian, perubahan jadwal, dan pengumuman nilai hasil ujian.
                </p>

                <!-- Form Pengaturan Informasi Ujian -->
                <form action="<?= base_url('admin/whatsapp/settings') ?>" method="POST" class="p-3 border rounded-3 bg-light-subtle">
                    <?= csrf_field() ?>
                    <input type="hidden" name="scope" value="exam">

                    <div class="d-flex flex-column gap-2 mb-3">
                        <!-- Switch 1: Ujian Baru -->
                        <div class="d-flex justify-content-between align-items-center p-2 rounded bg-white border">
                            <div>
                                <span class="fw-bold small text-dark d-block"><i class="bi bi-calendar-plus text-primary me-2"></i>Ujian Baru Diterbitkan</span>
                                <span class="text-muted extra-small">Kirim WA ke siswa saat ujian baru dijadwalkan untuk kelasnya.</span>
                            </div>
                            <div class="form-check form-switch fs-5 mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" name="notify_new_exam" value="1" <?= $settings['notify_new_exam'] ? 'checked' : '' ?>>
                            </div>
                        </div>

                        <!-- Switch 2: Pengingat Ujian Mendekat -->
                        <div class="d-flex justify-content-between align-items-center p-2 rounded bg-white border">
                            <div>
                                <span class="fw-bold small text-dark d-block"><i class="bi bi-alarm-fill text-warning me-2"></i>Pengingat Jadwal Ujian Mendekat</span>
                                <span class="text-muted extra-small">Alarm otomatis beberapa menit sebelum ujian dimulai.</span>
                            </div>
                            <div class="form-check form-switch fs-5 mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" name="notify_exam_reminder" value="1" <?= $settings['notify_exam_reminder'] ? 'checked' : '' ?>>
                            </div>
                        </div>

                        <!-- Lead Time Input -->
                        <div class="px-2 py-1 d-flex align-items-center justify-content-between bg-white border rounded">
                            <span class="extra-small fw-semibold text-secondary"><i class="bi bi-clock-history me-1"></i> Waktu Pengingat Sebelum Mulai:</span>
                            <div class="input-group input-group-sm" style="width: 140px;">
                                <input type="number" name="reminder_lead_minutes" class="form-control" min="5" max="180" value="<?= e($settings['reminder_lead_minutes']) ?>" required>
                                <span class="input-group-text extra-small">Menit</span>
                            </div>
                        </div>

                        <!-- Switch 3: Perubahan Jadwal -->
                        <div class="d-flex justify-content-between align-items-center p-2 rounded bg-white border">
                            <div>
                                <span class="fw-bold small text-dark d-block"><i class="bi bi-arrow-repeat text-danger me-2"></i>Perubahan Jadwal Ujian</span>
                                <span class="text-muted extra-small">Kirim pemberitahuan saat guru menggeser tanggal/jam ujian.</span>
                            </div>
                            <div class="form-check form-switch fs-5 mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" name="notify_schedule_change" value="1" <?= $settings['notify_schedule_change'] ? 'checked' : '' ?>>
                            </div>
                        </div>

                        <!-- Switch 4: Pengumuman Nilai -->
                        <div class="d-flex justify-content-between align-items-center p-2 rounded bg-white border">
                            <div>
                                <span class="fw-bold small text-dark d-block"><i class="bi bi-trophy-fill text-success me-2"></i>Pengumuman Hasil Nilai Ujian</span>
                                <span class="text-muted extra-small">Kirim skor ujian ke WA siswa setelah ujian selesai dinilai.</span>
                            </div>
                            <div class="form-check form-switch fs-5 mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" name="notify_exam_result" value="1" <?= $settings['notify_exam_result'] ? 'checked' : '' ?>>
                            </div>
                        </div>

                        <!-- Switch 5: Laporan Guru -->
                        <div class="d-flex justify-content-between align-items-center p-2 rounded bg-white border">
                            <div>
                                <span class="fw-bold small text-dark d-block"><i class="bi bi-person-check-fill text-info me-2"></i>Laporan Ringkasan Guru Pengawas</span>
                                <span class="text-muted extra-small">Ringkasan total siswa selesai ke nomor guru pengampu.</span>
                            </div>
                            <div class="form-check form-switch fs-5 mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" name="notify_teacher_finish" value="1" <?= $settings['notify_teacher_finish'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end pt-2 border-top">
                        <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill fw-semibold">
                            <i class="bi bi-save me-1"></i> Simpan Pengaturan Informasi Ujian
                        </button>
                    </div>
                </form>

                <!-- Pratinjau Template Pesan Ujian -->
                <div class="accordion" id="accordionExamPreview">
                    <div class="accordion-item border rounded-3 overflow-hidden">
                        <h2 class="accordion-header" id="headingExamPreview">
                            <button class="accordion-button collapsed py-2 px-3 small bg-white text-secondary fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExamPreview" aria-expanded="false" aria-controls="collapseExamPreview">
                                <i class="bi bi-eye text-success me-2"></i> Pratinjau Format Pesan Informasi Ujian
                            </button>
                        </h2>
                        <div id="collapseExamPreview" class="accordion-collapse collapse" aria-labelledby="headingExamPreview" data-bs-parent="#accordionExamPreview">
                            <div class="accordion-body bg-light p-3">
                                <ul class="nav nav-pills nav-fill mb-2" id="examPreviewTabs" role="tablist">
                                    <li class="nav-item">
                                        <button class="nav-link active py-1 extra-small" data-bs-toggle="tab" data-bs-target="#tabPreviewNewExam" type="button">Ujian Baru</button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link py-1 extra-small" data-bs-toggle="tab" data-bs-target="#tabPreviewReminder" type="button">Pengingat</button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link py-1 extra-small" data-bs-toggle="tab" data-bs-target="#tabPreviewChange" type="button">Perubahan</button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link py-1 extra-small" data-bs-toggle="tab" data-bs-target="#tabPreviewResult" type="button">Hasil Nilai</button>
                                    </li>
                                </ul>
                                <div class="tab-content bg-white p-3 rounded-3 border border-success-subtle shadow-sm font-monospace extra-small" style="line-height: 1.5; white-space: pre-wrap;">
                                    <div class="tab-pane fade show active" id="tabPreviewNewExam">📢 *PEMBERITAHUAN JADWAL UJIAN ONLINE*

Halo *Ahmad Pratama*,
Ujian baru telah dijadwalkan pada sistem *U-Line*:

📝 *Nama Ujian*: Penilaian Akhir Semester (PAS)
📅 *Tanggal*: <?= date('d/m/Y') ?>
⏰ *Waktu*: <?= date('H:i', strtotime('+1 hour')) ?> WIB
⏱️ *Durasi*: 90 Menit

📌 Silakan login ke sistem *U-Line* sebelum waktu ujian dimulai.</div>
                                    <div class="tab-pane fade" id="tabPreviewReminder">⏰ *PENGINGAT UJIAN ONLINE (U-LINE)*

Halo *Ahmad Pratama*,
Ujian Anda akan segera dimulai dalam waktu *15 menit*:

📝 *Nama Ujian*: Penilaian Akhir Semester (PAS)
⏰ *Waktu Mulai*: <?= date('H:i', strtotime('+15 minutes')) ?> WIB
⏳ *Sisa Waktu*: 15 menit

⚠️ Segera login dan bersiap di ruang ujian. Pastikan kamera terhubung dengan baik.</div>
                                    <div class="tab-pane fade" id="tabPreviewChange">📅 *PEMBERITAHUAN PERUBAHAN JADWAL UJIAN*

Halo *Ahmad Pratama*,
Terdapat penyesuaian jadwal pelaksanaan ujian pada sistem *U-Line*:

📝 *Nama Ujian*: Penilaian Akhir Semester (PAS)
🔄 *Jadwal Baru*: <?= date('d/m/Y H:i', strtotime('+1 day 08:00')) ?> WIB

Silakan periksa dashboard akun Anda untuk informasi selengkapnya.</div>
                                    <div class="tab-pane fade" id="tabPreviewResult">🏆 *PENGUMUMAN HASIL UJIAN ONLINE (U-LINE)*

Halo *Ahmad Pratama*,
Hasil pengerjaan ujian Anda telah diterbitkan:

📝 *Nama Ujian*: Penilaian Akhir Semester (PAS)
📊 *Skor Akhir*: *88.50*
🏁 *Status*: *LULUS*

Terima kasih atas partisipasi jujur Anda selama ujian berlangsung!</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Uji Coba Pengiriman Informasi Ujian -->
                <div class="p-3 border rounded-3 bg-white mt-auto">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0 small text-dark"><i class="bi bi-send-check text-success me-2"></i>Uji Coba Pengiriman Informasi Ujian</h6>
                        <span class="badge bg-secondary-subtle text-secondary extra-small">Simulasi Pengiriman</span>
                    </div>

                    <div id="examTestAlert" class="alert d-none extra-small py-2 px-3 rounded-3 mb-3"></div>

                    <form action="<?= base_url('admin/whatsapp/test-exam') ?>" method="POST" id="formTestExam">
                        <?= csrf_field() ?>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label extra-small fw-semibold text-secondary mb-1">Jenis Notifikasi Ujian <span class="text-danger">*</span></label>
                                <select name="notice_type" class="form-select form-select-sm" required>
                                    <option value="exam_new">📅 Jadwal Ujian Baru (New Exam)</option>
                                    <option value="exam_reminder">⏰ Pengingat Jadwal (Reminder 15m)</option>
                                    <option value="schedule_change">🔄 Perubahan Jadwal (Reschedule)</option>
                                    <option value="exam_result">🏆 Pengumuman Hasil Nilai (Result)</option>
                                    <option value="teacher_finish">📋 Laporan Ringkasan Guru (Teacher)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label extra-small fw-semibold text-secondary mb-1">Nomor WhatsApp Tujuan <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-whatsapp"></i></span>
                                    <input type="text" name="phone" id="examPhoneInput" class="form-control" placeholder="Contoh: 08123456789" required>
                                </div>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label extra-small fw-semibold text-secondary mb-1">Nama Siswa / Guru (Simulasi)</label>
                                <input type="text" name="name" class="form-control form-control-sm" placeholder="Contoh: Ahmad Pratama" value="Ahmad Pratama">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label extra-small fw-semibold text-secondary mb-1">Judul Ujian (Simulasi)</label>
                                <input type="text" name="exam_title" class="form-control form-control-sm" placeholder="Contoh: UAS Bahasa Inggris" value="Simulasi Ujian Akhir Semester (UAS)">
                            </div>
                        </div>
                        <button type="submit" id="btnTestExam" class="btn btn-success btn-sm px-4 py-2 rounded-3 fw-semibold w-100" <?= !$configStatus['is_configured'] ? 'disabled' : '' ?>>
                            <i class="bi bi-send-fill me-1"></i> Kirim Uji Coba Informasi Ujian
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- KARTU INFORMASI TEKNIS GATEWAY FONNTE API (ACCORDION)                     -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-gear-wide-connected text-primary me-2"></i>Spesifikasi Teknis Gateway Fonnte API</h5>
        <span class="badge bg-light text-dark border extra-small">REST API v2</span>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-3">
                <span class="text-muted extra-small d-block fw-semibold">ENDPOINT API</span>
                <span class="small font-monospace fw-semibold text-dark"><?= e($configStatus['endpoint']) ?></span>
            </div>
            <div class="col-md-3">
                <span class="text-muted extra-small d-block fw-semibold">STATUS TOKEN .ENV</span>
                <?php if ($configStatus['is_configured']): ?>
                    <span class="small font-monospace fw-semibold text-success"><i class="bi bi-check-circle-fill me-1"></i><?= e($configStatus['masked_token']) ?></span>
                <?php else: ?>
                    <span class="small text-danger"><i class="bi bi-x-circle-fill me-1"></i>Belum dikonfigurasi</span>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <span class="text-muted extra-small d-block fw-semibold">AUTHENTICATION</span>
                <span class="small badge bg-secondary-subtle text-secondary font-monospace border">Header: Authorization</span>
            </div>
            <div class="col-md-3">
                <span class="text-muted extra-small d-block fw-semibold">DEFAULT COUNTRY CODE</span>
                <span class="small badge bg-light text-dark border">+<?= e($configStatus['country_code']) ?> (Indonesia)</span>
            </div>
        </div>
        <div class="alert alert-info border-0 rounded-3 p-3 mt-3 mb-0 small">
            <i class="bi bi-shield-check text-info me-1"></i> <strong>Keamanan Kredensial:</strong> Nilai <code>FONNTE_TOKEN</code> dibaca langsung dari berkas server <code>.env</code> dan diisolasi dari sisi klien (client-side). Setiap pengiriman pesan dicatat ke audit log database dengan pelindung idempotency key untuk mencegah pengiriman pesan ganda.
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- RIWAYAT PENGIRIMAN & FILTER LOG WHATSAPP                                 -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-clock-history text-secondary me-2"></i>Riwayat Log Pengiriman WhatsApp</h5>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted extra-small">Menampilkan <?= count($logs) ?> dari <?= number_format($totalLogs) ?> pesan</span>
        </div>
    </div>
    <div class="card-body p-4">
        <!-- Filter Form -->
        <form method="GET" action="<?= base_url('admin/whatsapp') ?>" class="row g-2 mb-4 align-items-center">
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Cari nomor, nama, atau isi pesan..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="SUCCESS" <?= $statusFilter === 'SUCCESS' ? 'selected' : '' ?>>SUCCESS (Diterima)</option>
                    <option value="FAILED" <?= $statusFilter === 'FAILED' ? 'selected' : '' ?>>FAILED (Gagal/Ditolak)</option>
                    <option value="PENDING" <?= $statusFilter === 'PENDING' ? 'selected' : '' ?>>PENDING (Antrean)</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="type" class="form-select">
                    <option value="">Semua Jenis Notifikasi</option>
                    <option value="LOGIN_OTP" <?= $typeFilter === 'LOGIN_OTP' ? 'selected' : '' ?>>🔐 Kode OTP Login</option>
                    <option value="EXAM_NEW" <?= $typeFilter === 'EXAM_NEW' ? 'selected' : '' ?>>📅 Jadwal Ujian Baru</option>
                    <option value="EXAM_REMINDER" <?= $typeFilter === 'EXAM_REMINDER' ? 'selected' : '' ?>>⏰ Pengingat Ujian</option>
                    <option value="SCHEDULE_CHANGED" <?= $typeFilter === 'SCHEDULE_CHANGED' ? 'selected' : '' ?>>🔄 Perubahan Jadwal</option>
                    <option value="EXAM_RESULT" <?= $typeFilter === 'EXAM_RESULT' ? 'selected' : '' ?>>🏆 Hasil Nilai Ujian</option>
                    <option value="TEACHER_SUMMARY" <?= $typeFilter === 'TEACHER_SUMMARY' ? 'selected' : '' ?>>📋 Laporan Guru</option>
                    <option value="ADMIN_TEST" <?= $typeFilter === 'ADMIN_TEST' ? 'selected' : '' ?>>⚙️ Uji Coba (Test)</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                <?php if (!empty($search) || !empty($statusFilter) || !empty($typeFilter)): ?>
                    <a href="<?= base_url('admin/whatsapp') ?>" class="btn btn-outline-secondary" title="Reset filter"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Log Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 140px;">Waktu</th>
                        <th style="width: 180px;">Penerima</th>
                        <th style="width: 160px;">Layanan / Tipe</th>
                        <th>Isi Pesan</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 110px;" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-chat-square-dots fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Belum ada riwayat pengiriman pesan WhatsApp.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="font-monospace small text-muted">#<?= (int)$log['id'] ?></td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?= date('d/m/Y', strtotime((string)$log['created_at'])) ?></div>
                                    <div class="extra-small text-muted"><?= date('H:i:s', strtotime((string)$log['created_at'])) ?> WIB</div>
                                </td>
                                <td>
                                    <?php if (!empty($log['user_name'])): ?>
                                        <div class="fw-semibold small text-dark"><?= e($log['user_name']) ?></div>
                                    <?php endif; ?>
                                    <div class="font-monospace extra-small text-primary">
                                        <i class="bi bi-whatsapp me-1"></i><?= e($log['phone_number']) ?>
                                    </div>
                                    <?php if (!empty($log['user_role'])): ?>
                                        <span class="badge bg-secondary-subtle text-secondary extra-small"><?= strtoupper(e($log['user_role'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $typeBadge = match($log['notification_type']) {
                                        'LOGIN_OTP' => 'bg-purple-subtle text-purple border-purple-subtle',
                                        'EXAM_NEW' => 'bg-info-subtle text-info border-info-subtle',
                                        'EXAM_REMINDER' => 'bg-warning-subtle text-warning border-warning-subtle',
                                        'SCHEDULE_CHANGED' => 'bg-danger-subtle text-danger border-danger-subtle',
                                        'EXAM_RESULT' => 'bg-success-subtle text-success border-success-subtle',
                                        'TEACHER_SUMMARY', 'TEACHER_EXAM_SUMMARY' => 'bg-primary-subtle text-primary border-primary-subtle',
                                        'ADMIN_TEST', 'TEST_MESSAGE' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                        default => 'bg-light text-dark border'
                                    };
                                    $typeLabel = match($log['notification_type']) {
                                        'LOGIN_OTP' => '🔐 Kode OTP',
                                        'EXAM_NEW' => '📅 Ujian Baru',
                                        'EXAM_REMINDER' => '⏰ Pengingat',
                                        'SCHEDULE_CHANGED' => '🔄 Ubah Jadwal',
                                        'EXAM_RESULT' => '🏆 Nilai Ujian',
                                        'TEACHER_SUMMARY', 'TEACHER_EXAM_SUMMARY' => '📋 Guru',
                                        'ADMIN_TEST', 'TEST_MESSAGE' => '⚙️ Uji Coba',
                                        default => e($log['notification_type'] ?? 'GENERAL')
                                    };
                                    ?>
                                    <span class="badge <?= $typeBadge ?> border px-2 py-1 extra-small">
                                        <?= $typeLabel ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-truncate small" style="max-width: 320px;" title="<?= e($log['message']) ?>">
                                        <?= e($log['message']) ?>
                                    </div>
                                    <?php if (!empty($log['error_message'])): ?>
                                        <div class="text-danger extra-small mt-1"><i class="bi bi-exclamation-circle-fill me-1"></i><?= e($log['error_message']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['status'] === 'SUCCESS'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i> SUCCESS
                                        </span>
                                    <?php elseif ($log['status'] === 'FAILED'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            <i class="bi bi-x-circle-fill me-1"></i> FAILED
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                            <i class="bi bi-hourglass-split me-1"></i> PENDING
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" onclick="showLogDetail(<?= htmlspecialchars(json_encode($log), ENT_QUOTES, 'UTF-8') ?>)" title="Lihat Detail Pesan">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if ($log['status'] === 'FAILED'): ?>
                                            <form action="<?= base_url('admin/whatsapp/retry/' . (int)$log['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Kirim ulang pesan ini ke nomor <?= e($log['phone_number']) ?>?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-outline-primary" title="Coba Kirim Ulang (Retry)">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 pt-3 border-top">
                <span class="text-muted extra-small">Halaman <?= $currentPage ?> dari <?= $totalPages ?></span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= base_url('admin/whatsapp?page=' . ($currentPage - 1) . '&status=' . urlencode($statusFilter) . '&type=' . urlencode($typeFilter) . '&q=' . urlencode($search)) ?>">Sebelumnya</a>
                            </li>
                        <?php endif; ?>
                        <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
                            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="<?= base_url('admin/whatsapp?page=' . $p . '&status=' . urlencode($statusFilter) . '&type=' . urlencode($typeFilter) . '&q=' . urlencode($search)) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= base_url('admin/whatsapp?page=' . ($currentPage + 1) . '&status=' . urlencode($statusFilter) . '&type=' . urlencode($typeFilter) . '&q=' . urlencode($search)) ?>">Selanjutnya</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Detail Log -->
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="logDetailModalLabel"><i class="bi bi-file-text me-2 text-primary"></i>Detail Log Pengiriman WhatsApp</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="text-muted extra-small fw-semibold d-block">ID LOG / WAKTU</label>
                        <span id="modalLogId" class="fw-semibold small font-monospace"></span>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted extra-small fw-semibold d-block">PENERIMA & NOMOR</label>
                        <span id="modalLogReceiver" class="fw-semibold small"></span>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted extra-small fw-semibold d-block">LAYANAN / TIPE NOTIFIKASI</label>
                        <span id="modalLogType" class="badge bg-light text-dark border"></span>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted extra-small fw-semibold d-block">STATUS GATEWAY</label>
                        <span id="modalLogStatus" class="badge"></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="text-muted extra-small fw-semibold d-block mb-1">PESAN LENGKAP</label>
                    <pre id="modalLogMessage" class="p-3 bg-light rounded-3 text-dark small" style="white-space: pre-wrap; font-family: inherit;"></pre>
                </div>

                <div class="mb-3" id="modalErrorContainer" style="display: none;">
                    <label class="text-danger extra-small fw-semibold d-block mb-1">PESAN KESALAHAN / PENOLAKAN</label>
                    <div id="modalLogError" class="alert alert-danger p-2 extra-small mb-0"></div>
                </div>

                <div>
                    <label class="text-muted extra-small fw-semibold d-block mb-1">RESPONS GATEWAY (SANITIZED JSON)</label>
                    <pre id="modalLogResponse" class="p-3 bg-dark text-light rounded-3 font-monospace extra-small mb-0" style="max-height: 160px; overflow-y: auto;"></pre>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4">
                <button type="button" class="btn btn-secondary px-4 rounded-3 btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<style>
.bg-purple-subtle {
    background-color: #f3e8ff !important;
}
.text-purple {
    color: #7e22ce !important;
}
.border-purple-subtle {
    border-color: #d8b4fe !important;
}
@media (min-width: 768px) {
    .border-start-md {
        border-left: 1px solid #dee2e6 !important;
    }
}
</style>

<script>
function showLogDetail(log) {
    document.getElementById('modalLogId').innerText = '#' + log.id + ' (' + (log.created_at || '-') + ')';
    document.getElementById('modalLogReceiver').innerText = (log.user_name ? log.user_name + ' | ' : '') + log.phone_number;
    document.getElementById('modalLogType').innerText = log.notification_type || 'GENERAL';
    
    var statusEl = document.getElementById('modalLogStatus');
    statusEl.innerText = log.status;
    if (log.status === 'SUCCESS') {
        statusEl.className = 'badge bg-success';
    } else if (log.status === 'FAILED') {
        statusEl.className = 'badge bg-danger';
    } else {
        statusEl.className = 'badge bg-warning text-dark';
    }

    document.getElementById('modalLogMessage').innerText = log.message || '-';

    var errBox = document.getElementById('modalErrorContainer');
    if (log.error_message) {
        errBox.style.display = 'block';
        document.getElementById('modalLogError').innerText = log.error_message;
    } else {
        errBox.style.display = 'none';
    }

    var respFormatted = '-';
    if (log.provider_response) {
        try {
            var parsed = JSON.parse(log.provider_response);
            respFormatted = JSON.stringify(parsed, null, 2);
        } catch (e) {
            respFormatted = log.provider_response;
        }
    }
    document.getElementById('modalLogResponse').innerText = respFormatted;

    var modal = new bootstrap.Modal(document.getElementById('logDetailModal'));
    modal.show();
}

// AJAX Handler untuk Pengujian Notifikasi 1: WhatsApp OTP (Kirim & Verifikasi)
document.addEventListener('DOMContentLoaded', function() {
    var formTestOtp = document.getElementById('formTestOtp');
    var btnTestOtp = document.getElementById('btnTestOtp');
    var otpAlert = document.getElementById('otpTestAlert');
    var otpStepSend = document.getElementById('otpStepSend');
    var otpStepVerify = document.getElementById('otpStepVerify');
    var otpTargetDisplay = document.getElementById('otpTargetDisplay');
    var formVerifyTestOtp = document.getElementById('formVerifyTestOtp');
    var btnVerifyTestOtp = document.getElementById('btnVerifyTestOtp');
    var btnResetTestOtp = document.getElementById('btnResetTestOtp');
    var otpVerifyInput = document.getElementById('otpVerifyInput');

    // 1. Submit Kirim Kode OTP
    if (formTestOtp) {
        formTestOtp.addEventListener('submit', function(e) {
            e.preventDefault();
            var origHtml = btnTestOtp.innerHTML;
            btnTestOtp.disabled = true;
            btnTestOtp.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Mengirim OTP...';
            otpAlert.className = 'alert d-none extra-small py-2 px-3 rounded-3 mb-3';

            var formData = new FormData(formTestOtp);
            formData.append('format', 'json');

            fetch(formTestOtp.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function(res) {
                return res.text().then(function(text) {
                    try {
                        return JSON.parse(text);
                    } catch(e) {
                        return { success: false, message: 'Respons server (' + res.status + '): ' + (text.substring(0, 200) || res.statusText) };
                    }
                });
            })
            .then(function(data) {
                btnTestOtp.disabled = false;
                btnTestOtp.innerHTML = origHtml;
                otpAlert.classList.remove('d-none');
                if (data.success) {
                    otpAlert.className = 'alert alert-success extra-small py-2 px-3 rounded-3 mb-3';
                    otpAlert.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> <strong>Berhasil Dikirim!</strong> ' + (data.message || 'OTP berhasil dikirim ke WhatsApp.') + '<br><span class="text-dark">Silakan masukkan 6 digit kode OTP yang diterima ke kolom di bawah ini untuk memverifikasi.</span>';
                    
                    // Buka Langkah 2 Verifikasi
                    if (otpStepSend && otpStepVerify) {
                        otpStepSend.classList.add('d-none');
                        otpStepVerify.classList.remove('d-none');
                        if (otpTargetDisplay) {
                            otpTargetDisplay.textContent = data.phone || document.getElementById('otpPhoneInput').value;
                        }
                        if (otpVerifyInput) {
                            otpVerifyInput.value = '';
                            setTimeout(function() { otpVerifyInput.focus(); }, 250);
                        }
                    }
                } else {
                    otpAlert.className = 'alert alert-danger extra-small py-2 px-3 rounded-3 mb-3';
                    otpAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Gagal:</strong> ' + (data.message || 'Terjadi kesalahan.');
                }
            })
            .catch(function(err) {
                btnTestOtp.disabled = false;
                btnTestOtp.innerHTML = origHtml;
                otpAlert.classList.remove('d-none');
                otpAlert.className = 'alert alert-danger extra-small py-2 px-3 rounded-3 mb-3';
                otpAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> ' + (err.message || 'Kesalahan koneksi browser atau jaringan.');
            });
        });
    }

    // 2. Tombol Reset / Kembali ke Kirim OTP Baru
    if (btnResetTestOtp) {
        btnResetTestOtp.addEventListener('click', function() {
            if (otpStepVerify && otpStepSend) {
                otpStepVerify.classList.add('d-none');
                otpStepSend.classList.remove('d-none');
                otpAlert.className = 'alert d-none extra-small py-2 px-3 rounded-3 mb-3';
                var phoneInp = document.getElementById('otpPhoneInput');
                if (phoneInp) phoneInp.focus();
            }
        });
    }

    // 3. Format input hanya angka & auto submit saat 6 digit
    if (otpVerifyInput) {
        otpVerifyInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    // 4. Submit Verifikasi Kode OTP
    if (formVerifyTestOtp) {
        formVerifyTestOtp.addEventListener('submit', function(e) {
            e.preventDefault();
            var code = otpVerifyInput.value.trim();
            if (code.length !== 6) {
                otpAlert.classList.remove('d-none');
                otpAlert.className = 'alert alert-danger extra-small py-2 px-3 rounded-3 mb-3';
                otpAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> Silakan ketikkan 6 digit angka kode OTP dari WhatsApp Anda.';
                otpVerifyInput.focus();
                return;
            }

            var origHtml = btnVerifyTestOtp.innerHTML;
            btnVerifyTestOtp.disabled = true;
            btnVerifyTestOtp.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Memverifikasi OTP...';
            otpAlert.className = 'alert d-none extra-small py-2 px-3 rounded-3 mb-3';

            var formData = new FormData(formVerifyTestOtp);
            formData.append('format', 'json');

            fetch(formVerifyTestOtp.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function(res) {
                return res.text().then(function(text) {
                    try {
                        return JSON.parse(text);
                    } catch(e) {
                        return { success: false, message: 'Respons server (' + res.status + '): ' + (text.substring(0, 200) || res.statusText) };
                    }
                });
            })
            .then(function(data) {
                btnVerifyTestOtp.disabled = false;
                btnVerifyTestOtp.innerHTML = origHtml;
                otpAlert.classList.remove('d-none');
                if (data.success) {
                    otpAlert.className = 'alert alert-success extra-small py-2 px-3 rounded-3 mb-3';
                    otpAlert.innerHTML = '<i class="bi bi-patch-check-fill me-1"></i> <strong>Verifikasi Berhasil!</strong> ' + (data.message || 'Kode OTP valid.');
                } else {
                    otpAlert.className = 'alert alert-danger extra-small py-2 px-3 rounded-3 mb-3';
                    otpAlert.innerHTML = '<i class="bi bi-exclamation-octagon-fill me-1"></i> <strong>Verifikasi Gagal:</strong> ' + (data.message || 'Kode OTP salah.');
                }
            })
            .catch(function(err) {
                btnVerifyTestOtp.disabled = false;
                btnVerifyTestOtp.innerHTML = origHtml;
                otpAlert.classList.remove('d-none');
                otpAlert.className = 'alert alert-danger extra-small py-2 px-3 rounded-3 mb-3';
                otpAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> ' + (err.message || 'Kesalahan koneksi browser atau jaringan.');
            });
        });
    }

    // AJAX Handler untuk Pengujian Notifikasi 2: Informasi Ujian
    var formTestExam = document.getElementById('formTestExam');
    var btnTestExam = document.getElementById('btnTestExam');
    var examAlert = document.getElementById('examTestAlert');

    if (formTestExam) {
        formTestExam.addEventListener('submit', function(e) {
            e.preventDefault();
            var origHtml = btnTestExam.innerHTML;
            btnTestExam.disabled = true;
            btnTestExam.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Mengirim Notifikasi Ujian...';
            examAlert.className = 'alert d-none extra-small py-2 px-3 rounded-3 mb-3';

            var formData = new FormData(formTestExam);
            formData.append('format', 'json');

            fetch(formTestExam.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function(res) {
                return res.text().then(function(text) {
                    try {
                        return JSON.parse(text);
                    } catch(e) {
                        return { success: false, message: 'Respons server (' + res.status + '): ' + (text.substring(0, 200) || res.statusText) };
                    }
                });
            })
            .then(function(data) {
                btnTestExam.disabled = false;
                btnTestExam.innerHTML = origHtml;
                examAlert.classList.remove('d-none');
                if (data.success) {
                    examAlert.className = 'alert alert-success extra-small py-2 px-3 rounded-3 mb-3';
                    examAlert.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> <strong>Berhasil!</strong> ' + (data.message || 'Notifikasi ujian berhasil dikirim.');
                } else {
                    examAlert.className = 'alert alert-danger extra-small py-2 px-3 rounded-3 mb-3';
                    examAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Gagal:</strong> ' + (data.message || 'Terjadi kesalahan.');
                }
            })
            .catch(function(err) {
                btnTestExam.disabled = false;
                btnTestExam.innerHTML = origHtml;
                examAlert.classList.remove('d-none');
                examAlert.className = 'alert alert-danger extra-small py-2 px-3 rounded-3 mb-3';
                examAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> ' + (err.message || 'Kesalahan koneksi browser atau jaringan.');
            });
        });
    }
});
</script>
