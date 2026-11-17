<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-danger text-white py-3 px-4 rounded-top-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-shield-shaded me-2"></i>Area Khusus Super Administrator</h5>
            </div>
            <div class="card-body p-4 p-md-5">
                <div class="alert alert-danger bg-danger-subtle border border-danger-subtle text-danger-emphasis rounded-3 mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Otorisasi Berhasil:</strong> Anda berhasil melewati <code>RoleMiddleware</code> dengan role <strong>SUPER ADMIN</strong>.
                </div>
                
                <h6 class="fw-bold mb-3">Placeholder Fitur Super Admin:</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <i class="bi bi-people fs-3 text-danger"></i>
                            <h6 class="fw-bold mt-2 mb-1">Manajemen Pengguna</h6>
                            <p class="text-muted extra-small mb-0">Kelola akun Admin, Guru, dan Siswa secara terpusat.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <i class="bi bi-cpu fs-3 text-danger"></i>
                            <h6 class="fw-bold mt-2 mb-1">Konfigurasi AI Proctor</h6>
                            <p class="text-muted extra-small mb-0">Pengaturan model AI, sensitivitas kecurangan, dan WebRTC.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <i class="bi bi-shield-check fs-3 text-danger"></i>
                            <h6 class="fw-bold mt-2 mb-1">Audit Log Keamanan</h6>
                            <p class="text-muted extra-small mb-0">Catatan aktivitas login dan integritas sistem ujian.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
