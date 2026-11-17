<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= base_url('guru/results') ?>" class="text-decoration-none">Hasil Ujian</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($exam['title']) ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-1"><i class="bi bi-award-fill text-warning me-2"></i>Hasil & Peringkat Peserta</h4>
        <div class="text-muted small d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary"><?= e($exam['subject_name']) ?> (<?= e($exam['subject_code']) ?>)</span>
            <span class="badge bg-info-subtle text-info"><i class="bi bi-building me-1"></i><?= e($exam['class_name']) ?></span>
            <span><i class="bi bi-stopwatch me-1"></i><?= $exam['duration'] ?> Menit</span>
            <span><i class="bi bi-calendar-event me-1"></i><?= date('d M Y', strtotime($exam['start_at'])) ?></span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('guru/results') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        <a href="<?= base_url('guru/results/export?exam_id=' . $exam['id']) ?>" class="btn btn-success shadow-sm">
            <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Export Nilai (CSV)
        </a>
    </div>
</div>

<!-- STATS SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-primary-subtle text-primary me-3">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $stats['total_participants'] ?></div>
                    <div class="text-muted small">Total Peserta Ikut</div>
                </div>
            </div>
            <div class="mt-2 text-muted extra-small">
                <span class="text-success fw-bold"><?= $stats['finished_count'] ?></span> Selesai, 
                <span class="text-warning fw-bold"><?= $stats['started_count'] ?></span> Berlangsung
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-success-subtle text-success me-3">
                    <i class="bi bi-calculator-fill fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold <?= $stats['avg_grade'] >= 75 ? 'text-success' : 'text-primary' ?>">
                        <?= number_format((float)$stats['avg_grade'], 1) ?>
                    </div>
                    <div class="text-muted small">Rata-rata Nilai (0-100)</div>
                </div>
            </div>
            <div class="mt-2 text-muted extra-small">
                Rata-rata skor: <strong><?= number_format((float)$stats['avg_score'], 1) ?> poin</strong>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-info-subtle text-info me-3">
                    <i class="bi bi-trophy-fill fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark">
                        <span class="text-success"><?= number_format((float)$stats['highest_grade'], 1) ?></span> / 
                        <span class="text-danger"><?= number_format((float)$stats['lowest_grade'], 1) ?></span>
                    </div>
                    <div class="text-muted small">Tertinggi / Terendah</div>
                </div>
            </div>
            <div class="mt-2 text-muted extra-small">
                Rentang nilai kelas
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-warning-subtle text-warning me-3">
                    <i class="bi bi-check-circle-fill fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $stats['pass_percentage'] ?>%</div>
                    <div class="text-muted small">Kelulusan (&ge; 70)</div>
                </div>
            </div>
            <div class="mt-2 text-muted extra-small">
                <strong class="text-success"><?= $stats['pass_count'] ?></strong> dari <?= $stats['total_participants'] ?> peserta lulus KKM
            </div>
        </div>
    </div>
</div>

<!-- FILTER, SEARCH & SORT BAR -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= base_url('guru/results/' . $exam['id']) ?>" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-secondary">Cari Peserta Didik</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Ketik nama, NIS, atau email..." value="<?= e($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Status Sesi</label>
                <select name="status" class="form-select bg-light">
                    <option value="">Semua Status Sesi</option>
                    <option value="finished" <?= (($filters['status'] ?? '') === 'finished') ? 'selected' : '' ?>>Finished (Disubmit)</option>
                    <option value="expired" <?= (($filters['status'] ?? '') === 'expired') ? 'selected' : '' ?>>Expired (Waktu Habis)</option>
                    <option value="started" <?= (($filters['status'] ?? '') === 'started') ? 'selected' : '' ?>>Started (Sedang Mengerjakan)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Urutan (Sorting)</label>
                <select name="sort" class="form-select bg-light">
                    <option value="rank_asc" <?= ($sort === 'rank_asc') ? 'selected' : '' ?>>Peringkat & Nilai Tertinggi</option>
                    <option value="score_asc" <?= ($sort === 'score_asc') ? 'selected' : '' ?>>Nilai Terendah</option>
                    <option value="name_asc" <?= ($sort === 'name_asc') ? 'selected' : '' ?>>Nama Peserta (A - Z)</option>
                    <option value="name_desc" <?= ($sort === 'name_desc') ? 'selected' : '' ?>>Nama Peserta (Z - A)</option>
                    <option value="time_desc" <?= ($sort === 'time_desc') ? 'selected' : '' ?>>Waktu Selesai Terbaru</option>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100" title="Terapkan Filter"><i class="bi bi-funnel-fill"></i></button>
                <?php if (!empty($filters['search']) || !empty($filters['status']) || $sort !== 'rank_asc'): ?>
                    <a href="<?= base_url('guru/results/' . $exam['id']) ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- PARTICIPANTS LIST TABLE -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-3 pb-2 px-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-trophy me-2"></i>Tabel Peringkat & Hasil Ujian Peserta</h6>
        <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1"><?= count($participants) ?> Peserta</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($participants)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people fs-1 d-block mb-2 text-secondary opacity-50"></i>
                <p class="mb-0">Belum ada peserta yang memulai atau mengerjakan ujian ini.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 text-center" style="width: 70px;">Rank</th>
                            <th>Peserta Didik</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Analisis Jawaban</th>
                            <th class="text-center">Nilai (0-100)</th>
                            <th class="text-center">Durasi & Waktu</th>
                            <th class="pe-3 text-end" style="width: 140px;">Lembar Jawaban</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $p): ?>
                            <tr>
                                <td class="ps-3 text-center">
                                    <?php if ($p['rank'] === 1): ?>
                                        <span class="badge rounded-circle p-2 bg-warning text-dark shadow-sm" title="Juara 1"><i class="bi bi-trophy-fill fs-6"></i> 1</span>
                                    <?php elseif ($p['rank'] === 2): ?>
                                        <span class="badge rounded-circle p-2 bg-secondary text-white shadow-sm" title="Juara 2"><i class="bi bi-award-fill fs-6"></i> 2</span>
                                    <?php elseif ($p['rank'] === 3): ?>
                                        <span class="badge rounded-circle p-2 bg-danger-subtle text-danger border border-danger shadow-sm" title="Juara 3"><i class="bi bi-award fs-6"></i> 3</span>
                                    <?php else: ?>
                                        <span class="fw-bold text-muted fs-6">#<?= $p['rank'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= e($p['student_name']) ?></div>
                                    <div class="small text-muted font-monospace">
                                        <span>NIS: <?= e($p['student_nis']) ?></span> &bull; 
                                        <span><?= e($p['class_name']) ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $sessionBadge = match($p['session_status']) {
                                        'finished' => 'bg-success-subtle text-success border border-success',
                                        'expired' => 'bg-warning-subtle text-warning-emphasis border border-warning',
                                        'started' => 'bg-info-subtle text-info border border-info',
                                        default => 'bg-secondary text-white'
                                    };
                                    ?>
                                    <span class="badge <?= $sessionBadge ?> px-2 py-1">
                                        <?= strtoupper(e($p['session_status'])) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <span class="badge bg-success-subtle text-success" title="Jawaban Benar">
                                            <i class="bi bi-check-circle-fill me-1"></i><?= $p['correct_count'] ?>
                                        </span>
                                        <span class="badge bg-danger-subtle text-danger" title="Jawaban Salah">
                                            <i class="bi bi-x-circle-fill me-1"></i><?= $p['incorrect_count'] ?>
                                        </span>
                                        <span class="badge bg-secondary-subtle text-secondary" title="Tidak Dijawab">
                                            <i class="bi bi-dash-circle-fill me-1"></i><?= $p['unanswered_count'] ?>
                                        </span>
                                    </div>
                                    <small class="text-muted extra-small">dari <?= $p['total_questions'] ?> butir soal</small>
                                </td>
                                <td class="text-center">
                                    <div class="fs-5 fw-bold <?= $p['grade'] >= 75 ? 'text-success' : ($p['grade'] >= 50 ? 'text-primary' : 'text-danger') ?>">
                                        <?= number_format((float)$p['grade'], 1) ?>
                                    </div>
                                    <small class="text-muted extra-small">Skor: <?= $p['earned_score'] ?> / <?= $p['max_score'] ?></small>
                                </td>
                                <td class="text-center">
                                    <div class="small fw-semibold text-dark">
                                        <i class="bi bi-stopwatch text-muted me-1"></i>
                                        <?= sprintf('%02d:%02d', floor($p['time_spent_seconds'] / 60), $p['time_spent_seconds'] % 60) ?>
                                    </div>
                                    <small class="text-muted extra-small d-block">
                                        <?= $p['finished_at'] ? date('H:i, d/m/y', strtotime($p['finished_at'])) : ($p['started_at'] ? date('H:i, d/m/y', strtotime($p['started_at'])) : '-') ?>
                                    </small>
                                </td>
                                <td class="pe-3 text-end">
                                    <a href="<?= base_url('guru/results/' . $exam['id'] . '/' . $p['student_id']) ?>" class="btn btn-sm btn-outline-primary" title="Lihat Lembar Jawaban Detail">
                                        <i class="bi bi-eye-fill me-1"></i> Detail
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
