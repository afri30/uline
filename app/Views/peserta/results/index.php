<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-award-fill text-warning me-2"></i>Riwayat & Hasil Ujian Saya</h4>
        <p class="text-muted small mb-0">Lihat rekapitulasi perolehan nilai, jumlah soal benar/salah, dan status ujian yang telah dikerjakan</p>
    </div>
    <div>
        <a href="<?= base_url('peserta/exams') ?>" class="btn btn-primary rounded-3 shadow-sm">
            <i class="bi bi-card-checklist me-1"></i> Daftar Ujian Aktif
        </a>
    </div>
</div>

<!-- STATS CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-primary-subtle text-primary me-3">
                    <i class="bi bi-journal-check fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $stats['total_exams'] ?></div>
                    <div class="text-muted small">Ujian Diikuti</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-success-subtle text-success me-3">
                    <i class="bi bi-check2-circle fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $stats['completed_exams'] ?></div>
                    <div class="text-muted small">Ujian Selesai</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-info-subtle text-info me-3">
                    <i class="bi bi-calculator fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold <?= $stats['avg_grade'] >= 75 ? 'text-success' : ($stats['avg_grade'] >= 50 ? 'text-primary' : 'text-danger') ?>">
                        <?= number_format((float)$stats['avg_grade'], 1) ?>
                    </div>
                    <div class="text-muted small">Rata-rata Nilai (0-100)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-warning-subtle text-warning me-3">
                    <i class="bi bi-trophy-fill fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-success">
                        <?= number_format((float)$stats['highest_grade'], 1) ?>
                    </div>
                    <div class="text-muted small">Nilai Tertinggi</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SEARCH BAR -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= base_url('peserta/results') ?>" class="row g-2 align-items-center">
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Cari nama ujian atau mata pelajaran..." value="<?= e($search ?? '') ?>">
                </div>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Cari</button>
                <?php if (!empty($search)): ?>
                    <a href="<?= base_url('peserta/results') ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- RESULTS TABLE -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-3 pb-2 px-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-table me-2"></i>Daftar Riwayat Hasil Ujian</h6>
        <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1"><?= count($history) ?> Riwayat</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($history)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                <p class="mb-0">Belum ada riwayat hasil ujian yang tercatat.</p>
                <a href="<?= base_url('peserta/exams') ?>" class="btn btn-sm btn-primary mt-3">
                    <i class="bi bi-card-checklist me-1"></i> Mulai Ujian Baru
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 50px;">No</th>
                            <th>Ujian & Mata Pelajaran</th>
                            <th>Guru Penguji</th>
                            <th class="text-center">Analisis Jawaban</th>
                            <th class="text-center">Nilai (0-100)</th>
                            <th class="text-center">Status</th>
                            <th class="pe-3 text-end" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $idx => $h): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-muted"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= e($h['exam_title']) ?></div>
                                    <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                                        <span class="badge bg-light text-dark border"><?= e($h['subject_name']) ?></span>
                                        <span><i class="bi bi-stopwatch me-1"></i><?= $h['exam_duration'] ?> Menit</span>
                                        <span><i class="bi bi-clock-history me-1"></i><?= $h['finished_at'] ? date('d/m/Y H:i', strtotime($h['finished_at'])) : ($h['started_at'] ? date('d/m/Y H:i', strtotime($h['started_at'])) : '-') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?= e($h['teacher_name'] ?? 'Guru Penguji') ?></div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <span class="badge bg-success-subtle text-success" title="Jawaban Benar">
                                            <i class="bi bi-check-circle-fill me-1"></i><?= $h['correct_count'] ?> Benar
                                        </span>
                                        <span class="badge bg-danger-subtle text-danger" title="Jawaban Salah">
                                            <i class="bi bi-x-circle-fill me-1"></i><?= $h['incorrect_count'] ?> Salah
                                        </span>
                                        <span class="badge bg-secondary-subtle text-secondary" title="Tidak Dijawab">
                                            <i class="bi bi-dash-circle-fill me-1"></i><?= $h['unanswered_count'] ?> Kosong
                                        </span>
                                    </div>
                                    <small class="text-muted extra-small">dari <?= $h['total_questions'] ?> butir soal</small>
                                </td>
                                <td class="text-center">
                                    <div class="fs-5 fw-bold <?= $h['grade'] >= 75 ? 'text-success' : ($h['grade'] >= 50 ? 'text-primary' : 'text-danger') ?>">
                                        <?= number_format((float)$h['grade'], 1) ?>
                                    </div>
                                    <small class="text-muted extra-small">Skor: <?= $h['earned_score'] ?> / <?= $h['max_score'] ?></small>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $statusBadge = match($h['session_status']) {
                                        'finished' => 'bg-success',
                                        'expired' => 'bg-warning text-dark',
                                        'started' => 'bg-info',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $statusBadge ?> px-2 py-1">
                                        <?= strtoupper(e($h['session_status'])) ?>
                                    </span>
                                </td>
                                <td class="pe-3 text-end">
                                    <a href="<?= base_url('peserta/results/' . $h['exam_id']) ?>" class="btn btn-sm btn-outline-primary" title="Lihat Pembahasan & AI Tutor">
                                        <i class="bi bi-file-earmark-text me-1"></i> Pembahasan
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
