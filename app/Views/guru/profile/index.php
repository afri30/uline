<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-badge-fill text-success me-2"></i>Profil Guru Penguji</h4>
        <p class="text-muted small mb-0">Kelola informasi data pengajar dan kata sandi akun Anda</p>
    </div>
</div>

<div class="row g-4">
    <!-- Profile Info Card -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-person-lines-fill text-success me-2"></i>Informasi Data Pengajar</h5>
            </div>
            <div class="card-body p-4">
                <form action="<?= base_url('guru/profile') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">NIP (Nomor Induk Pegawai)</label>
                            <input type="text" class="form-control bg-light" value="<?= e($teacher['nip'] ?? '-') ?>" disabled readonly>
                            <small class="text-muted extra-small">Hubungi Admin untuk ubah NIP</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Jenis Kelamin</label>
                            <select name="gender" class="form-select">
                                <option value="L" <?= (($teacher['gender'] ?? 'L') === 'L') ? 'selected' : '' ?>>Laki-laki (L)</option>
                                <option value="P" <?= (($teacher['gender'] ?? '') === 'P') ? 'selected' : '' ?>>Perempuan (P)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Alamat Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">No. Handphone / WhatsApp</label>
                        <input type="text" name="phone" class="form-control" value="<?= e($teacher['phone'] ?? '') ?>" placeholder="081234567890">
                    </div>
                    <button type="submit" class="btn btn-success px-4 py-2 rounded-3 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Profil
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Password Change Card -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-shield-lock text-danger me-2"></i>Keamanan Kata Sandi</h5>
            </div>
            <div class="card-body p-4">
                <form action="<?= base_url('guru/profile/password') ?>" method="POST">
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
                    <button type="submit" class="btn btn-danger px-4 py-2 rounded-3 fw-semibold">
                        <i class="bi bi-key-fill me-1"></i> Ganti Kata Sandi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
