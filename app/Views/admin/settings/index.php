<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-gear-fill text-secondary me-2"></i>Pengaturan Akun & Sistem</h4>
        <p class="text-muted small mb-0">Kelola profil Administrator, keamanan kata sandi, dan preferensi sistem</p>
    </div>
</div>

<div class="row g-4">
    <!-- Profile Settings Card -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-person-gear text-primary me-2"></i>Profil Administrator</h5>
            </div>
            <div class="card-body p-4">
                <form action="<?= base_url('admin/settings/profile') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" value="<?= e($user['username'] ?? '') ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Alamat Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Profil
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Password Settings Card -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-shield-lock text-danger me-2"></i>Ubah Kata Sandi</h5>
            </div>
            <div class="card-body p-4">
                <form action="<?= base_url('admin/settings/password') ?>" method="POST">
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
                        <input type="password" name="confirm_password" class="form-control" placeholder="Konfirmasi kata sandi baru" required>
                    </div>
                    <button type="submit" class="btn btn-danger px-4 py-2 rounded-3 fw-semibold">
                        <i class="bi bi-key-fill me-1"></i> Perbarui Kata Sandi
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- AI & Model Configuration Card -->
    <?php $ai = $aiConfig ?? config('ai'); ?>
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary p-2 rounded-circle"><i class="bi bi-robot fs-5"></i></span>
                    <div>
                        <h5 class="fw-bold mb-0 fs-6">Konfigurasi AI & Model Sistem</h5>
                        <small class="text-muted">Mesin kecerdasan buatan untuk U-Line AI Assistant & AI Proctoring</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($ai['enabled'])): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>AI Active (<?= strtoupper(e($ai['provider'] ?? 'nvidia')) ?>)</span>
                    <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i>AI Non-Aktif</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-4">
                <!-- Status Row -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light h-100">
                            <small class="text-muted text-uppercase extra-small fw-bold">Active Provider</small>
                            <div class="fw-bold text-dark mt-1 d-flex align-items-center gap-1">
                                <i class="bi bi-cpu text-primary"></i> <?= strtoupper(e($ai['provider'] ?? 'nvidia')) ?> NIM
                            </div>
                            <small class="text-secondary font-monospace">Enterprise Endpoints</small>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light h-100">
                            <small class="text-muted text-uppercase extra-small fw-bold">Model Utama (Primary)</small>
                            <div class="fw-bold text-dark mt-1 text-truncate" title="<?= e($ai['nvidia']['model'] ?? '-') ?>">
                                <?= e($ai['nvidia']['model'] ?? '-') ?>
                            </div>
                            <small class="text-success extra-small fw-semibold"><i class="bi bi-eye-fill me-1"></i>Vision Multimodal</small>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light h-100">
                            <small class="text-muted text-uppercase extra-small fw-bold">Model Cadangan (Fallback)</small>
                            <div class="fw-bold text-dark mt-1 text-truncate" title="<?= e($ai['nvidia']['fallback_model'] ?? '-') ?>">
                                <?= e($ai['nvidia']['fallback_model'] ?? '-') ?>
                            </div>
                            <small class="text-secondary font-monospace">Google Gemma Multimodal</small>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light h-100">
                            <small class="text-muted text-uppercase extra-small fw-bold">Parameter Sampling</small>
                            <div class="fw-bold text-dark mt-1">Temp: <?= e($ai['nvidia']['temperature'] ?? '0.1') ?> | Top-P: <?= e($ai['nvidia']['top_p'] ?? '0.7') ?></div>
                            <small class="text-secondary font-monospace">Max: <?= e($ai['nvidia']['max_tokens'] ?? 1000) ?> tok, <?= e($ai['nvidia']['timeout'] ?? 8) ?>s</small>
                        </div>
                    </div>
                </div>

                <!-- Model Catalog Table -->
                <h6 class="fw-bold text-secondary mb-3 small text-uppercase"><i class="bi bi-grid-3x3-gap-fill text-primary me-1"></i>Katalog Model AI yang Didukung (.env)</h6>
                <div class="table-responsive border rounded-3">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Model</th>
                                <th>Model ID</th>
                                <th>Kategori & Spesialisasi</th>
                                <th>Jendela Konteks</th>
                                <th>Vision</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $currentModel = $ai['nvidia']['model'] ?? '';
                            $fallbackModel = $ai['nvidia']['fallback_model'] ?? '';
                            $catalog = $ai['available_models']['nvidia'] ?? [];
                            foreach ($catalog as $m): 
                                $isPrimary = ($m['id'] === $currentModel);
                                $isFallback = ($m['id'] === $fallbackModel);
                            ?>
                            <tr class="<?= $isPrimary ? 'table-primary-subtle' : '' ?>">
                                <td>
                                    <strong><?= e($m['name']) ?></strong>
                                    <div class="extra-small text-muted"><?= e($m['description']) ?></div>
                                </td>
                                <td><code class="text-dark bg-light px-2 py-1 rounded"><?= e($m['id']) ?></code></td>
                                <td><span class="badge bg-light text-dark border"><?= e($m['category']) ?></span></td>
                                <td class="font-monospace text-muted"><?= e($m['context_window']) ?></td>
                                <td>
                                    <?php if (!empty($m['vision_support'])): ?>
                                        <span class="badge bg-success-subtle text-success"><i class="bi bi-check-lg"></i> Ya</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">Teks Saja</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isPrimary): ?>
                                        <span class="badge bg-primary text-white"><i class="bi bi-star-fill me-1"></i>Aktif Utama</span>
                                    <?php elseif ($isFallback): ?>
                                        <span class="badge bg-info text-dark"><i class="bi bi-shield-check me-1"></i>Fallback</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Tersedia</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 p-3 bg-light border rounded-3 extra-small text-muted d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <i class="bi bi-shield-check text-success me-1"></i><strong>Academic Guardrails:</strong> Proteksi anti-bocor ujian, larangan percodingan, dan grounding database aktif secara server-side.
                    </div>
                    <div>
                        <span class="text-secondary">Ubah konfigurasi melalui: <code>.env</code> & <code>app/Config/ai.php</code></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Info Card -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-info-circle text-info me-2"></i>Informasi Sistem & Lingkungan Server</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light">
                            <small class="text-muted text-uppercase extra-small fw-bold">Aplikasi</small>
                            <div class="fw-bold text-dark mt-1">AI Exam & Proctoring</div>
                            <small class="text-secondary font-monospace">Versi 1.0.0</small>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light">
                            <small class="text-muted text-uppercase extra-small fw-bold">PHP Runtime</small>
                            <div class="fw-bold text-dark mt-1">PHP <?= PHP_VERSION ?></div>
                            <small class="text-secondary font-monospace">Native MVC Engine</small>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light">
                            <small class="text-muted text-uppercase extra-small fw-bold">Database Server</small>
                            <div class="fw-bold text-dark mt-1">MySQL PDO</div>
                            <small class="text-secondary font-monospace">Database: uline_db</small>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="p-3 border rounded-3 bg-light">
                            <small class="text-muted text-uppercase extra-small fw-bold">Timezone</small>
                            <div class="fw-bold text-dark mt-1">Asia/Jakarta (WIB)</div>
                            <small class="text-secondary font-monospace"><?= date('d-m-Y H:i') ?> WIB</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
