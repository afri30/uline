<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-circle text-primary me-2"></i>Profil Peserta Didik</h4>
        <p class="text-muted small mb-0">Kelola data profil siswa, kontak WhatsApp, dan kata sandi akun Anda</p>
    </div>
</div>

<div class="row g-4">
    <!-- Profile Info Card -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-person-badge-fill text-primary me-2"></i>Informasi Data Siswa</h5>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill extra-small">
                    <?= e($student['class_name'] ?? 'Peserta Ujian') ?>
                </span>
            </div>
            <div class="card-body p-4">
                <form action="<?= base_url('peserta/profile') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold text-secondary">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-secondary">Username</label>
                            <input type="text" class="form-control bg-light" value="<?= e($user['username'] ?? '') ?>" disabled readonly>
                            <small class="text-muted extra-small">Username login Anda</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">NIS (Nomor Induk Siswa)</label>
                            <input type="text" class="form-control bg-light font-monospace" value="<?= e($student['nis'] ?? '-') ?>" disabled readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">NISN</label>
                            <input type="text" class="form-control bg-light font-monospace" value="<?= e($student['nisn'] ?? '-') ?>" disabled readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Kelas / Rombel</label>
                            <input type="text" class="form-control bg-light" value="<?= e($student['class_name'] ?? '-') ?> (Tingkat <?= e($student['grade'] ?? '-') ?>)" disabled readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Jenis Kelamin</label>
                            <select name="gender" class="form-select">
                                <option value="L" <?= (($student['gender'] ?? 'L') === 'L') ? 'selected' : '' ?>>Laki-laki (L)</option>
                                <option value="P" <?= (($student['gender'] ?? '') === 'P') ? 'selected' : '' ?>>Perempuan (P)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Alamat Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">No. Handphone / WhatsApp</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($student['phone'] ?? $user['phone_number'] ?? '') ?>" placeholder="081234567890">
                            <small class="text-muted extra-small">Digunakan untuk login via WhatsApp OTP</small>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                        <small class="text-muted extra-small"><i class="bi bi-info-circle me-1"></i>Untuk perubahan NIS, NISN, atau Kelas, silakan hubungi Administrator sekolah.</small>
                        <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold">
                            <i class="bi bi-save me-1"></i> Simpan Profil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Password Change Card -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-shield-lock text-danger me-2"></i>Keamanan Kata Sandi</h5>
            </div>
            <div class="card-body p-4">
                <form action="<?= base_url('peserta/profile/password') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Kata Sandi Saat Ini <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Kata Sandi Baru <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Ulangi Kata Sandi Baru <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Konfirmasi kata sandi" required>
                    </div>

                    <button type="submit" class="btn btn-danger px-4 py-2 rounded-3 fw-semibold w-100">
                        <i class="bi bi-key-fill me-1"></i> Perbarui Kata Sandi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

