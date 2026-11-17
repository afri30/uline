<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= base_url('peserta/dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('peserta/exams') ?>" class="text-decoration-none">Daftar Ujian</a></li>
                <li class="breadcrumb-item active" aria-current="page">Hasil Ujian</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0"><i class="bi bi-trophy text-success me-2"></i>Hasil & Analisis Nilai Ujian</h4>
    </div>
    <div>
        <a href="<?= base_url('peserta/exams') ?>" class="btn btn-light border rounded-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Ujian
        </a>
    </div>
</div>

<div class="row g-4 justify-content-center">
    <div class="col-lg-8">
        <!-- SCORE HERO CARD -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 text-center">
            <div class="card-header bg-success text-white p-5">
                <div class="avatar-circle bg-white text-success mx-auto mb-3" style="width: 70px; height: 70px; font-size: 32px;">
                    <i class="bi bi-award-fill"></i>
                </div>
                <h2 class="fw-bold mb-1">Ujian Selesai!</h2>
                <p class="text-white-50 mb-3 fs-6">Jawaban Anda telah berhasil tersimpan dan dievaluasi sistem.</p>
                <div class="badge bg-white bg-opacity-25 text-white px-3 py-1.5 rounded-pill font-monospace small">
                    [<?= htmlspecialchars($session['subject_code']) ?>] <?= htmlspecialchars($session['subject_name']) ?>
                </div>
            </div>

            <div class="card-body p-4 p-lg-5">
                <h4 class="fw-bold text-dark mb-4"><?= htmlspecialchars($session['exam_title']) ?></h4>

                <!-- SCORE STATISTIC BOX -->
                <div class="row g-3 justify-content-center mb-5">
                    <div class="col-md-5">
                        <div class="p-4 bg-primary-subtle text-primary rounded-4">
                            <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Perolehan Nilai</div>
                            <div class="display-4 fw-bold my-1 text-primary"><?= $stats['earned_score'] ?></div>
                            <small class="text-muted">dari total bobot <?= $stats['max_score'] ?> Poin</small>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="p-4 bg-success-subtle text-success rounded-4">
                            <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Persentase Capaian</div>
                            <div class="display-4 fw-bold my-1 text-success"><?= $stats['percentage'] ?>%</div>
                            <small class="text-muted">Tingkat Ketepatan</small>
                        </div>
                    </div>
                </div>

                <!-- BREAKDOWN DETAILS -->
                <h6 class="fw-bold text-start text-dark mb-3"><i class="bi bi-pie-chart text-primary me-2"></i>Rincian Statistik Pengerjaan:</h6>
                <div class="row g-3 text-start mb-4">
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted extra-small">Total Soal</div>
                            <div class="fs-5 fw-bold text-dark"><?= $stats['total_questions'] ?> Butir</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted extra-small">Soal Terjawab</div>
                            <div class="fs-5 fw-bold text-primary"><?= $stats['answered_questions'] ?> Soal</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted extra-small">Jawaban Benar</div>
                            <div class="fs-5 fw-bold text-success"><?= $stats['correct_answers'] ?> Soal</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted extra-small">Jawaban Salah</div>
                            <div class="fs-5 fw-bold text-danger"><?= $stats['incorrect_answers'] ?> Soal</div>
                        </div>
                    </div>
                </div>

                <?php if ($stats['essay_count'] > 0): ?>
                    <div class="alert alert-info small text-start rounded-3 d-flex align-items-center gap-2 mb-4">
                        <i class="bi bi-info-circle-fill fs-5"></i>
                        <div>
                            Ujian ini memuat <strong><?= $stats['essay_count'] ?> butir soal esai/uraian</strong> yang akan dinilai secara manual oleh guru penguji. Nilai akhir Anda dapat bertambah setelah pemeriksaan esai selesai.
                        </div>
                    </div>
                <?php endif; ?>

                <!-- SESSION TIMESTAMPS -->
                <div class="p-3 bg-light rounded-3 text-muted small text-start mb-4">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <i class="bi bi-clock me-1"></i>Waktu Mulai: <strong><?= date('d M Y, H:i:s', strtotime($session['started_at'])) ?></strong>
                        </div>
                        <div class="col-md-6">
                            <i class="bi bi-check-circle me-1"></i>Waktu Selesai: <strong><?= $session['finished_at'] ? date('d M Y, H:i:s', strtotime($session['finished_at'])) : '-' ?></strong>
                        </div>
                    </div>
                </div>

                <!-- BUTTONS -->
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?= base_url('peserta/exams') ?>" class="btn btn-primary px-4 py-2 rounded-3">
                        <i class="bi bi-journal-check me-1"></i> Buka Daftar Ujian
                    </a>
                    <a href="<?= base_url('peserta/dashboard') ?>" class="btn btn-light border px-4 py-2 rounded-3">
                        <i class="bi bi-house me-1"></i> Dashboard Utama
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
