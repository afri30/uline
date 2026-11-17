<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-bar-graph-fill text-primary me-2"></i>Hasil & Penilaian Ujian</h4>
        <p class="text-muted small mb-0">Lihat rekapitulasi nilai, statistik analitik per ujian, peringkat peserta didik, dan lembar jawaban</p>
    </div>
</div>

<!-- STATS CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-primary-subtle text-primary me-3">
                    <i class="bi bi-journal-check fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $stats['total_exams'] ?></div>
                    <div class="text-muted small">Total Ujian Dibuat</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-info-subtle text-info me-3">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $stats['total_participants'] ?></div>
                    <div class="text-muted small">Peserta Mulai Ujian</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-success-subtle text-success me-3">
                    <i class="bi bi-check2-all fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $stats['total_completed'] ?></div>
                    <div class="text-muted small">Sesi Ujian Selesai / Disubmit</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FILTER & SEARCH BAR -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= base_url('guru/results') ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Cari Judul Ujian</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Ketik kata kunci ujian..." value="<?= e($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Mata Pelajaran</label>
                <select name="subject_id" class="form-select bg-light">
                    <option value="">Semua Mata Pelajaran</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ((int)($filters['subject_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
                            <?= e($s['name']) ?> (<?= e($s['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Kelas</label>
                <select name="class_id" class="form-select bg-light">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ((int)($filters['class_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                <?php if (!empty($filters['search']) || !empty($filters['subject_id']) || !empty($filters['class_id'])): ?>
                    <a href="<?= base_url('guru/results') ?>" class="btn btn-outline-secondary" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- EXAM RESULTS LIST TABLE -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-3 pb-2 px-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-table me-2"></i>Daftar Ujian & Rekapitulasi Nilai</h6>
        <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1"><?= count($exams) ?> Ujian Ditemukan</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($exams)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                <p class="mb-0">Belum ada data ujian yang sesuai dengan kriteria filter.</p>
                <a href="<?= base_url('guru/results') ?>" class="btn btn-sm btn-outline-primary mt-2">Reset Filter</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 50px;">No</th>
                            <th>Ujian & Mata Pelajaran</th>
                            <th>Kelas</th>
                            <th class="text-center">Peserta / Kelas</th>
                            <th class="text-center">Rata-rata Nilai</th>
                            <th class="text-center">Tertinggi / Terendah</th>
                            <th class="text-center">Status</th>
                            <th class="pe-3 text-end" style="min-width: 180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exams as $idx => $exam): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-muted"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= e($exam['title']) ?></div>
                                    <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                                        <span class="badge bg-light text-dark border"><?= e($exam['subject_name']) ?></span>
                                        <span><i class="bi bi-question-circle me-1"></i><?= $exam['total_questions'] ?> Soal</span>
                                        <span><i class="bi bi-stopwatch me-1"></i><?= $exam['duration'] ?> Menit</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info fw-semibold px-2 py-1">
                                        <i class="bi bi-building me-1"></i><?= e($exam['class_name']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold text-dark">
                                        <?= $exam['finished_participants'] ?> <span class="text-muted fw-normal">/ <?= $exam['total_students_in_class'] ?></span>
                                    </div>
                                    <small class="text-muted extra-small">
                                        <?= $exam['total_participants'] ?> mengikuti
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?php if ($exam['total_participants'] > 0): ?>
                                        <span class="fs-6 fw-bold <?= $exam['avg_grade'] >= 75 ? 'text-success' : ($exam['avg_grade'] >= 50 ? 'text-primary' : 'text-danger') ?>">
                                            <?= number_format((float)$exam['avg_grade'], 1) ?>
                                        </span>
                                        <div class="extra-small text-muted">Skala 0-100</div>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($exam['total_participants'] > 0): ?>
                                        <div class="small">
                                            <span class="text-success fw-bold"><?= number_format((float)$exam['highest_grade'], 1) ?></span> / 
                                            <span class="text-danger fw-bold"><?= number_format((float)$exam['lowest_grade'], 1) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $statusBadge = match($exam['exam_status']) {
                                        'active' => 'bg-success',
                                        'published' => 'bg-primary',
                                        'finished' => 'bg-secondary',
                                        default => 'bg-warning text-dark'
                                    };
                                    ?>
                                    <span class="badge <?= $statusBadge ?> px-2 py-1">
                                        <?= ucfirst(e($exam['exam_status'])) ?>
                                    </span>
                                </td>
                                <td class="pe-3 text-end">
                                    <div class="btn-group">
                                        <a href="<?= base_url('guru/results/' . $exam['exam_id']) ?>" class="btn btn-sm btn-primary" title="Lihat Hasil & Peringkat Siswa">
                                            <i class="bi bi-bar-chart-line-fill me-1"></i> Hasil
                                        </a>
                                        <a href="<?= base_url('guru/results/export?exam_id=' . $exam['exam_id']) ?>" class="btn btn-sm btn-outline-success" title="Download Format CSV">
                                            <i class="bi bi-file-earmark-spreadsheet-fill"></i> CSV
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
