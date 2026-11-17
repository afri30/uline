<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-badge-fill text-danger me-2"></i>Profil Super Administrator</h4>
        <p class="text-muted small mb-0">Kelola informasi akun Administrator, kontak WhatsApp, dan kata sandi Anda</p>
    </div>
    <div>
        <a href="<?= base_url('admin/settings') ?>" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="bi bi-gear-fill me-1"></i> Pengaturan Sistem
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Admin Profile Info Card -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-person-lines-fill text-danger me-2"></i>Informasi Akun Administrator</h5>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 rounded-pill extra-small">
                    <i class="bi bi-shield-fill-check me-1"></i>SUPER_ADMIN
                </span>
            </div>
            <div class="card-body p-4">
                <form action="<?= base_url('admin/settings/profile') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_to" value="admin/profile">

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold text-secondary">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-secondary">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" value="<?= e($user['username'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Alamat Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">No. Handphone / WhatsApp</label>
                            <input type="text" name="phone_number" class="form-control" value="<?= e($user['phone_number'] ?? '') ?>" placeholder="081234567890">
                            <small class="text-muted extra-small">Untuk notifikasi & login WhatsApp OTP</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Hak Akses Role</label>
                            <input type="text" class="form-control bg-light font-monospace" value="<?= e($user['role'] ?? 'SUPER_ADMIN') ?>" disabled readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Status Akun</label>
                            <input type="text" class="form-control bg-light text-success fw-semibold" value="<?= strtoupper(e($user['status'] ?? 'active')) ?>" disabled readonly>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Profil Admin
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Admin Password Card -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-shield-lock text-danger me-2"></i>Keamanan Kata Sandi</h5>
            </div>
            <div class="card-body p-4">
                <form action="<?= base_url('admin/settings/password') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_to" value="admin/profile">

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
                        <input type="password" name="confirm_password" class="form-control" placeholder="Konfirmasi kata sandi baru" required>
                    </div>
                    <button type="submit" class="btn btn-danger px-4 py-2 rounded-3 fw-semibold w-100">
                        <i class="bi bi-key-fill me-1"></i> Perbarui Kata Sandi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

