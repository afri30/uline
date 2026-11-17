<!-- Welcome Hero Banner -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden text-white dashboard-hero" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%) !important;">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <span class="badge bg-danger text-white px-3 py-1.5 rounded-pill mb-3 font-monospace small">
                            <i class="bi bi-shield-fill-check me-1"></i> PANEL SUPER ADMINISTRATOR
                        </span>
                        <h2 class="fw-bold mb-2">Selamat Datang, <?= e($user['name'] ?? 'Super Admin') ?>!</h2>
                        <p class="text-white-50 mb-0 fs-6">
                            Anda memiliki akses penuh untuk mengelola pengguna (Guru & Peserta Didik), master data kelas, mata pelajaran, serta pengaturan sistem ujian AI Proctoring.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <span class="badge bg-dark bg-opacity-25 border border-white border-opacity-25 px-3 py-2 rounded-3 text-white font-monospace">
                            <i class="bi bi-person-check me-1"></i> Role: <?= e($user['role']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <!-- Stat 1: Total Users -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="<?= base_url('admin/users') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-card">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <span class="badge bg-primary-subtle text-primary rounded-pill">Total</span>
                    </div>
                    <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Total Pengguna</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['total_users'] ?? 0) ?></h3>
                </div>
            </div>
        </a>
    </div>

    <!-- Stat 2: Teachers -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="<?= base_url('admin/teachers') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-card">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-person-badge-fill fs-4"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success rounded-pill">Guru</span>
                    </div>
                    <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Data Guru</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['total_teachers'] ?? 0) ?></h3>
                </div>
            </div>
        </a>
    </div>

    <!-- Stat 3: Students -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="<?= base_url('admin/students') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-card border-info border-2">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-info-subtle text-info rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-mortarboard-fill fs-4"></i>
                        </div>
                        <span class="badge bg-info-subtle text-info rounded-pill">Peserta</span>
                    </div>
                    <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Peserta Didik</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['total_students'] ?? 0) ?></h3>
                </div>
            </div>
        </a>
    </div>

    <!-- Stat 4: Classes -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="<?= base_url('admin/classes') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-card">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-warning-subtle text-warning-emphasis rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-building fs-4"></i>
                        </div>
                        <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">Kelas</span>
                    </div>
                    <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Total Rombel</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['total_classes'] ?? 0) ?></h3>
                </div>
            </div>
        </a>
    </div>

    <!-- Stat 5: Subjects -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="<?= base_url('admin/subjects') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-card">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-danger-subtle text-danger rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-book-half fs-4"></i>
                        </div>
                        <span class="badge bg-danger-subtle text-danger rounded-pill">Mapel</span>
                    </div>
                    <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Mata Pelajaran</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['total_subjects'] ?? 0) ?></h3>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Users Table & Role Guard Tester -->
<div class="row g-4">
    <!-- Recent Users List -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-people me-2 text-primary"></i>Daftar Pengguna Sistem Terdaftar</h5>
                <span class="badge bg-light text-secondary border font-monospace small"><?= count($recentUsers) ?> Akun</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Nama / Pengguna</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Terdaftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $u): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold text-dark"><?= e($u['name']) ?></div>
                                        <small class="text-muted font-monospace">@<?= e($u['username']) ?></small>
                                    </td>
                                    <td><small><?= e($u['email']) ?></small></td>
                                    <td>
                                        <?php if ($u['role'] === 'SUPER_ADMIN'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">SUPER_ADMIN</span>
                                        <?php elseif ($u['role'] === 'GURU'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">GURU</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">PESERTA</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($u['status'] === 'active'): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-end text-muted small font-monospace">
                                        <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Security Sandbox -->
    <div class="col-12 col-lg-4">
        <!-- Quick Action Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-lightning-charge-fill me-2 text-primary"></i>Aksi Cepat Master Data</h5>
            </div>
            <div class="card-body p-3">
                <div class="d-grid gap-2">
                    <a href="<?= base_url('admin/students') ?>" class="btn btn-primary text-start d-flex justify-content-between align-items-center p-2.5 rounded-3 shadow-sm">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-mortarboard-fill me-1"></i> Kelola Peserta Didik</div>
                            <small class="extra-small opacity-75">Master Siswa, NIS, & Rombel</small>
                        </div>
                        <i class="bi bi-arrow-right-circle-fill"></i>
                    </a>
                    <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-dark text-start d-flex justify-content-between align-items-center p-2 rounded-3">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-people-fill me-1"></i> Kelola Pengguna</div>
                            <small class="extra-small text-muted">Semua Akun Sistem</small>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="<?= base_url('admin/exam-config') ?>" class="btn btn-outline-warning text-start d-flex justify-content-between align-items-center p-2 rounded-3 text-dark">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-sliders2-vertical me-1 text-warning"></i> Konfigurasi Ujian</div>
                            <small class="extra-small text-muted">Durasi, Acak Soal, Token & .env</small>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Security & Role Guard Verification Sandbox -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-shield-check me-2 text-warning"></i>Uji Keamanan Hak Akses (RBAC)</h5>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    Sebagai <strong>SUPER_ADMIN</strong>, rute khusus di bawah dilindungi oleh <code>RoleMiddleware</code> (klik untuk uji proteksi 403):
                </p>
                <div class="d-grid gap-2">
                    <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-outline-danger text-start d-flex justify-content-between align-items-center p-2.5 rounded-3 active">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-shield-lock-fill me-1"></i> /admin/dashboard</div>
                            <small class="extra-small opacity-75">Dashboard Saat Ini (SUPER_ADMIN)</small>
                        </div>
                        <i class="bi bi-check-circle-fill text-danger"></i>
                    </a>
                    <a href="<?= base_url('guru/dashboard') ?>" class="btn btn-outline-success text-start d-flex justify-content-between align-items-center p-2.5 rounded-3">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-person-badge-fill me-1"></i> /guru/dashboard</div>
                            <small class="extra-small opacity-75">Uji Isolasi Guru (403 Blokir)</small>
                        </div>
                        <i class="bi bi-slash-circle text-success"></i>
                    </a>
                    <a href="<?= base_url('peserta/dashboard') ?>" class="btn btn-outline-primary text-start d-flex justify-content-between align-items-center p-2.5 rounded-3">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-mortarboard-fill me-1"></i> /peserta/dashboard</div>
                            <small class="extra-small opacity-75">Uji Isolasi Peserta (403 Blokir)</small>
                        </div>
                        <i class="bi bi-slash-circle text-primary"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
