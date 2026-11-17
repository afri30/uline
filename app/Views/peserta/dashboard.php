<!-- Welcome Hero Banner -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden text-white dashboard-hero" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%) !important;">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <span class="badge bg-white text-primary px-3 py-1.5 rounded-pill mb-3 font-monospace small">
                            <i class="bi bi-mortarboard-fill me-1"></i> PANEL PESERTA UJIAN
                        </span>
                        <h2 class="fw-bold mb-2">Halo, <?= e($user['name'] ?? 'Peserta Didik') ?>!</h2>
                        <p class="text-white-50 mb-0 fs-6">
                            Kelas: <strong><?= e($student['class_name'] ?? 'Belum terdaftar') ?> (Tingkat <?= e($student['grade'] ?? '-') ?>)</strong> • NIS: <strong><?= e($student['nis'] ?? '-') ?></strong> • NISN: <?= e($student['nisn'] ?? '-') ?>
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <span class="badge bg-dark bg-opacity-25 border border-white border-opacity-25 px-3 py-2 rounded-3 text-white font-monospace">
                            <i class="bi bi-person-badge me-1"></i> Role: PESERTA (Active)
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Student Profile Info Cards -->
<div class="row g-4 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-building fs-4"></i>
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill">Rombel</span>
                </div>
                <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Rombongan Belajar</div>
                <h4 class="fw-bold mb-0 text-dark"><?= e($student['class_name'] ?? 'Kelas -') ?></h4>
                <small class="text-muted">Tahun Ajaran <?= e($student['academic_year'] ?? '2026/2027') ?></small>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-card-checklist fs-4"></i>
                    </div>
                    <span class="badge bg-success-subtle text-success rounded-pill">Status Akun</span>
                </div>
                <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Status Peserta</div>
                <h4 class="fw-bold mb-0 text-success"><i class="bi bi-check-circle-fill me-1"></i>Terverifikasi</h4>
                <small class="text-muted font-monospace">NIS: <?= e($student['nis'] ?? '-') ?></small>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon bg-warning-subtle text-warning-emphasis rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-camera-video-fill fs-4"></i>
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">Proctoring</span>
                </div>
                <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Kesiapan AI Proctor</div>
                <h4 class="fw-bold mb-0 text-dark">Siap Ujian</h4>
                <small class="text-muted">Kamera & WebRTC Interface Siap</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- List of School Subjects -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-journals me-2 text-primary"></i>Daftar Mata Pelajaran Tersedia</h5>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace small"><?= count($subjects) ?> Mapel</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <?php foreach ($subjects as $subj): ?>
                        <div class="col-12 col-md-6">
                            <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-primary font-monospace"><?= e($subj['code']) ?></span>
                                        <small class="text-muted extra-small"><i class="bi bi-person me-1"></i><?= e($subj['teacher_name'] ?? 'Guru Pengajar') ?></small>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1"><?= e($subj['name']) ?></h6>
                                    <p class="text-muted extra-small mb-0"><?= e($subj['description'] ?: 'Mata pelajaran kurikulum aktif.') ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Security & Role Guard Verification Sandbox -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-shield-check me-2 text-warning"></i>Uji Keamanan Hak Akses (RBAC)</h5>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    Sebagai role <strong>PESERTA</strong>, akses Anda ke dashboard Admin dan Guru akan diblokir oleh <code>RoleMiddleware</code> (<strong>403 Forbidden</strong>):
                </p>
                <div class="d-grid gap-2">
                    <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-outline-danger text-start d-flex justify-content-between align-items-center p-2.5 rounded-3">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-shield-lock me-1"></i> /admin/dashboard</div>
                            <small class="extra-small opacity-75">Uji Blokir Admin (403 Expected)</small>
                        </div>
                        <i class="bi bi-slash-circle text-danger"></i>
                    </a>
                    <a href="<?= base_url('guru/dashboard') ?>" class="btn btn-outline-success text-start d-flex justify-content-between align-items-center p-2.5 rounded-3">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-person-badge-fill me-1"></i> /guru/dashboard</div>
                            <small class="extra-small opacity-75">Uji Blokir Guru (403 Expected)</small>
                        </div>
                        <i class="bi bi-slash-circle text-success"></i>
                    </a>
                    <a href="<?= base_url('peserta/dashboard') ?>" class="btn btn-outline-primary text-start d-flex justify-content-between align-items-center p-2.5 rounded-3 active">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-mortarboard-fill me-1"></i> /peserta/dashboard</div>
                            <small class="extra-small opacity-75">Dashboard Anda Saat Ini</small>
                        </div>
                        <i class="bi bi-check-circle-fill text-primary"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
