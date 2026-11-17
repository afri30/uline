<?php
$role = $user['role'] ?? 'GUEST';
?>

<!-- Welcome Banner -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-primary text-white position-relative dashboard-hero">
            <div class="card-body p-4 p-lg-5 position-relative z-1">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <span class="badge bg-white text-primary fw-semibold px-3 py-1.5 rounded-pill mb-3 font-monospace small">
                            <i class="bi bi-shield-check me-1"></i> Fondasi Sistem Siap
                        </span>
                        <h2 class="fw-bold mb-2">Selamat Datang, <?= e($user['name'] ?? 'Pengguna') ?>!</h2>
                        <p class="text-white-50 mb-0 fs-6">
                            Anda masuk dengan hak akses <strong><?= e($role) ?></strong>. Struktur MVC, Session, Database PDO, Middleware RBAC, dan Layout Bootstrap 5 telah aktif dan berjalan lancar.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <span class="badge bg-dark bg-opacity-25 border border-white border-opacity-25 px-3 py-2 rounded-3 text-white font-monospace">
                            <i class="bi bi-clock me-1"></i> <?= date('d M Y, H:i') ?> WIB
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Architecture & Foundation Verification Cards -->
<div class="row g-3 mb-4">
    <!-- Card 1: MVC Status -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-diagram-3-fill fs-4"></i>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Active</span>
                </div>
                <h6 class="text-muted mb-1 small fw-semibold text-uppercase tracking-wider">Arsitektur</h6>
                <h4 class="fw-bold mb-1">PHP Native MVC</h4>
                <p class="text-muted extra-small mb-0">Router, BaseController, BaseModel, Session & Helpers</p>
            </div>
        </div>
    </div>

    <!-- Card 2: Database PDO -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-database-fill-check fs-4"></i>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Connected</span>
                </div>
                <h6 class="text-muted mb-1 small fw-semibold text-uppercase tracking-wider">Database</h6>
                <h4 class="fw-bold mb-1">MySQL PDO</h4>
                <p class="text-muted extra-small mb-0">Prepared Statements, UTF8mb4 & Singleton Connection</p>
            </div>
        </div>
    </div>

    <!-- Card 3: Session & RBAC -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon bg-warning-subtle text-warning-emphasis rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-shield-lock-fill fs-4"></i>
                    </div>
                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill"><?= e($role) ?></span>
                </div>
                <h6 class="text-muted mb-1 small fw-semibold text-uppercase tracking-wider">Sistem Keamanan</h6>
                <h4 class="fw-bold mb-1">Role Middleware</h4>
                <p class="text-muted extra-small mb-0">CSRF Token, Session Cookie & Role Guard Protection</p>
            </div>
        </div>
    </div>

    <!-- Card 4: WebRTC & AI Ready -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon bg-info-subtle text-info-emphasis rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-camera-video-fill fs-4"></i>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Ready</span>
                </div>
                <h6 class="text-muted mb-1 small fw-semibold text-uppercase tracking-wider">Proctoring AI</h6>
                <h4 class="fw-bold mb-1">WebRTC Ready</h4>
                <p class="text-muted extra-small mb-0">Vanilla JS Fetch API & Media Stream Interface</p>
            </div>
        </div>
    </div>
</div>

<!-- Session Details & Role Tester -->
<div class="row g-4">
    <!-- Active Session Card -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-person-lines-fill text-primary me-2"></i>Informasi Sesi Login Aktif</h5>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Authenticated</span>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-borderless table-hover align-middle mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted small ps-0" style="width: 140px;">Nama Lengkap</th>
                                <td class="fw-semibold text-dark"><?= e($user['name'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted small ps-0">Username</th>
                                <td><code class="text-primary"><?= e($user['username'] ?? '-') ?></code></td>
                            </tr>
                            <tr>
                                <th class="text-muted small ps-0">Email</th>
                                <td><?= e($user['email'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted small ps-0">Hak Akses (Role)</th>
                                <td>
                                    <?php if ($role === 'SUPER ADMIN'): ?>
                                        <span class="badge bg-danger text-white"><i class="bi bi-shield-fill-check me-1"></i>SUPER ADMIN</span>
                                    <?php elseif ($role === 'GURU'): ?>
                                        <span class="badge bg-success text-white"><i class="bi bi-person-badge-fill me-1"></i>GURU</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark"><i class="bi bi-mortarboard-fill me-1"></i>PESERTA DIDIK</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted small ps-0">Session ID</th>
                                <td><small class="font-monospace text-muted"><?= session_id() ?></small></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Authorization Sandbox -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-shield-check text-warning me-2"></i>Uji Coba Middleware Role (RBAC)</h5>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    Klik rute di bawah untuk menguji apakah <code>RoleMiddleware</code> mengizinkan atau menolak (403 Forbidden) akses sesuai role Anda saat ini (<strong><?= e($role) ?></strong>):
                </p>
                <div class="d-grid gap-2">
                    <a href="<?= base_url('admin-only') ?>" class="btn btn-outline-danger text-start d-flex justify-content-between align-items-center p-2.5 rounded-3">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-shield-lock me-1"></i> Rute Khusus SUPER ADMIN</div>
                            <small class="extra-small opacity-75">Hanya role SUPER ADMIN</small>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="<?= base_url('guru-only') ?>" class="btn btn-outline-success text-start d-flex justify-content-between align-items-center p-2.5 rounded-3">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-person-workspace me-1"></i> Rute Khusus GURU</div>
                            <small class="extra-small opacity-75">SUPER ADMIN & GURU diizinkan</small>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="<?= base_url('peserta-only') ?>" class="btn btn-outline-primary text-start d-flex justify-content-between align-items-center p-2.5 rounded-3">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-mortarboard me-1"></i> Rute Khusus PESERTA DIDIK</div>
                            <small class="extra-small opacity-75">SUPER ADMIN & PESERTA DIDIK diizinkan</small>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
