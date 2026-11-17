<div class="row justify-content-center py-4">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden text-center">
            <div class="card-header bg-danger text-white py-4 border-0">
                <div class="avatar-circle mx-auto mb-2 bg-white text-danger fw-bold fs-2 shadow-sm" style="width: 70px; height: 70px;">
                    <i class="bi bi-shield-x"></i>
                </div>
                <h3 class="fw-bold mb-1">403 Forbidden</h3>
                <p class="text-white-50 small mb-0">Akses Tidak Diizinkan / Role Authorization Failed</p>
            </div>
            <div class="card-body p-4 p-md-5 bg-white">
                <p class="text-muted mb-4">
                    Maaf, akun Anda (<strong><?= e(auth()['role'] ?? 'GUEST') ?></strong>) tidak memiliki hak akses yang memadai untuk membuka halaman ini.
                </p>
                <?php if (isset($requiredRoles) && !empty($requiredRoles)): ?>
                    <div class="p-3 bg-light rounded-3 mb-4 text-start small border">
                        <span class="fw-bold text-secondary">Role yang Diperbolehkan:</span>
                        <div class="mt-1">
                            <?php foreach ($requiredRoles as $r): ?>
                                <span class="badge bg-secondary me-1"><?= e($r) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-center gap-2">
                    <a href="<?= base_url('dashboard') ?>" class="btn btn-primary px-4 py-2 rounded-3">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
