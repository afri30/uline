<!-- Welcome Hero Banner -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden text-white dashboard-hero" style="background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #059669 100%) !important;">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <span class="badge bg-white text-success px-3 py-1.5 rounded-pill mb-3 font-monospace small">
                            <i class="bi bi-person-badge-fill me-1"></i> PANEL GURU PENGUJI
                        </span>
                        <h2 class="fw-bold mb-2">Selamat Datang, <?= e($user['name'] ?? 'Guru Penguji') ?>!</h2>
                        <p class="text-white-50 mb-0 fs-6">
                            NIP: <strong><?= e($teacher['nip'] ?? '-') ?></strong> • No. HP: <?= e($teacher['phone'] ?? '-') ?>
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <span class="badge bg-dark bg-opacity-25 border border-white border-opacity-25 px-3 py-2 rounded-3 text-white font-monospace">
                            <i class="bi bi-check2-circle me-1"></i> Role: GURU (Active)
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Guru Stats & Assigned Subjects -->
<div class="row g-4 mb-4">
    <!-- Stat 1: Total Mapel Diampu -->
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-book-half fs-4"></i>
                    </div>
                    <span class="badge bg-success-subtle text-success rounded-pill">Mapel Diampu</span>
                </div>
                <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Mata Pelajaran</div>
                <h3 class="fw-bold mb-0 text-dark"><?= count($mySubjects) ?> Mapel</h3>
            </div>
        </div>
    </div>

    <!-- Stat 2: Total Classes in School -->
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-building fs-4"></i>
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill">Kelas</span>
                </div>
                <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Rombel Sekolah</div>
                <h3 class="fw-bold mb-0 text-dark"><?= (int)$totalClasses ?> Rombel</h3>
            </div>
        </div>
    </div>

    <!-- Stat 3: Total Students -->
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon bg-info-subtle text-info rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-mortarboard-fill fs-4"></i>
                    </div>
                    <span class="badge bg-info-subtle text-info rounded-pill">Siswa</span>
                </div>
                <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Total Siswa</div>
                <h3 class="fw-bold mb-0 text-dark"><?= (int)$totalStudents ?> Siswa</h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- List of Subjects Taught -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-journals me-2 text-success"></i>Mata Pelajaran yang Diampu</h5>
                <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace small"><?= count($mySubjects) ?> Mapel</span>
            </div>
            <div class="card-body p-4">
                <?php if (empty($mySubjects)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        Belum ada mata pelajaran yang ditugaskan kepada Anda.
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($mySubjects as $subj): ?>
                            <div class="col-12 col-md-6">
                                <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-dark font-monospace"><?= e($subj['code']) ?></span>
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1"><?= e($subj['name']) ?></h6>
                                        <p class="text-muted extra-small mb-0"><?= e($subj['description'] ?: 'Tidak ada deskripsi.') ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Quick Action & Security Sandbox -->
    <div class="col-12 col-lg-4">
        <!-- Quick Action Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-lightning-charge-fill me-2 text-success"></i>Aksi Cepat Penguji</h5>
            </div>
            <div class="card-body p-3">
                <div class="d-grid gap-2">
                    <a href="<?= base_url('admin/exam-config') ?>" class="btn btn-outline-warning text-start d-flex justify-content-between align-items-center p-2.5 rounded-3 text-dark">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-sliders2-vertical me-1 text-warning"></i> Konfigurasi Ujian</div>
                            <small class="extra-small text-muted">Durasi, Acak Soal, Token & .env</small>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="<?= base_url('guru/exams') ?>" class="btn btn-outline-success text-start d-flex justify-content-between align-items-center p-2 rounded-3">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-card-checklist me-1"></i> Manajemen Ujian</div>
                            <small class="extra-small text-muted">Jadwal & Soal Ujian</small>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-shield-check me-2 text-warning"></i>Uji Keamanan Hak Akses (RBAC)</h5>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    Sebagai role <strong>GURU</strong>, jika Anda mencoba membuka halaman Super Admin atau Siswa, sistem wajib memblokir (<strong>403 Forbidden</strong>):
                </p>
                <div class="d-grid gap-2">
                    <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-outline-danger text-start d-flex justify-content-between align-items-center p-2.5 rounded-3">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-shield-lock me-1"></i> /admin/dashboard</div>
                            <small class="extra-small opacity-75">Uji Blokir Admin (403 Expected)</small>
                        </div>
                        <i class="bi bi-slash-circle text-danger"></i>
                    </a>
                    <a href="<?= base_url('guru/dashboard') ?>" class="btn btn-outline-success text-start d-flex justify-content-between align-items-center p-2.5 rounded-3 active">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-person-badge-fill me-1"></i> /guru/dashboard</div>
                            <small class="extra-small opacity-75">Dashboard Anda Saat Ini</small>
                        </div>
                        <i class="bi bi-check-circle-fill text-success"></i>
                    </a>
                    <a href="<?= base_url('peserta/dashboard') ?>" class="btn btn-outline-primary text-start d-flex justify-content-between align-items-center p-2.5 rounded-3">
                        <div>
                            <div class="fw-bold small"><i class="bi bi-mortarboard me-1"></i> /peserta/dashboard</div>
                            <small class="extra-small opacity-75">Uji Blokir Siswa (403 Expected)</small>
                        </div>
                        <i class="bi bi-slash-circle text-primary"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
