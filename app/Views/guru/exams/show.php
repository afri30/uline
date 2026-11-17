<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= base_url('guru/dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('guru/exams') ?>" class="text-decoration-none">Manajemen Ujian</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detail Ujian</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-text text-primary me-2"></i>Detail & Rincian Ujian</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('guru/exams/edit?id=' . $exam['id']) ?>" class="btn btn-warning text-dark rounded-3">
            <i class="bi bi-pencil-square me-1"></i> Edit Ujian
        </a>
        <a href="<?= base_url('guru/exams') ?>" class="btn btn-light border rounded-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- LEFT: EXAM SUMMARY INFO -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold">
                        [<?= htmlspecialchars($exam['subject_code']) ?>] <?= htmlspecialchars($exam['subject_name']) ?>
                    </span>
                    <?php if ($exam['status'] === 'draft'): ?>
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill">Draft</span>
                    <?php elseif ($exam['status'] === 'published'): ?>
                        <span class="badge bg-primary px-3 py-2 rounded-pill">Published</span>
                    <?php elseif ($exam['status'] === 'active'): ?>
                        <span class="badge bg-success px-3 py-2 rounded-pill">Active</span>
                    <?php else: ?>
                        <span class="badge bg-dark px-3 py-2 rounded-pill">Finished</span>
                    <?php endif; ?>
                </div>

                <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($exam['title']) ?></h5>
                <?php if (!empty($exam['description'])): ?>
                    <p class="text-muted small mb-4 bg-light p-3 rounded-3 border-start border-3 border-primary">
                        <?= nl2br(htmlspecialchars($exam['description'])) ?>
                    </p>
                <?php endif; ?>

                <div class="d-flex flex-column gap-3 py-2 border-top border-bottom my-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small"><i class="bi bi-people me-2"></i>Target Kelas:</span>
                        <span class="fw-semibold">Kelas <?= htmlspecialchars($exam['class_name']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small"><i class="bi bi-stopwatch me-2"></i>Durasi Ujian:</span>
                        <span class="fw-bold text-primary"><?= $exam['duration'] ?> Menit</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small"><i class="bi bi-calendar-check me-2"></i>Waktu Mulai:</span>
                        <span class="fw-medium small"><?= date('d M Y, H:i', strtotime($exam['start_at'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small"><i class="bi bi-calendar-x me-2"></i>Waktu Selesai:</span>
                        <span class="fw-medium small"><?= date('d M Y, H:i', strtotime($exam['end_at'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small"><i class="bi bi-card-checklist me-2"></i>Jumlah Soal:</span>
                        <span class="fw-bold"><?= count($questions) ?> Butir Soal</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small"><i class="bi bi-award me-2"></i>Total Poin:</span>
                        <span class="fw-bold text-success fs-6"><?= $exam['total_score'] ?> Poin</span>
                    </div>
                </div>

                <!-- CONFIGURATION STATUS -->
                <h6 class="fw-bold mb-3 small text-uppercase text-muted">Pengawasan & Pengacakan</h6>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between align-items-center p-2 rounded-2 <?= $exam['ai_proctoring'] ? 'bg-danger-subtle text-danger' : 'bg-light text-muted' ?>">
                        <span class="small fw-semibold"><i class="bi bi-robot me-2"></i>AI Proctoring</span>
                        <span class="badge <?= $exam['ai_proctoring'] ? 'bg-danger' : 'bg-secondary' ?>"><?= $exam['ai_proctoring'] ? 'Aktif' : 'Non-Aktif' ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded-2 <?= $exam['dual_camera'] ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted' ?>">
                        <span class="small fw-semibold"><i class="bi bi-camera-video me-2"></i>Dual Camera</span>
                        <span class="badge <?= $exam['dual_camera'] ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= $exam['dual_camera'] ? 'Aktif' : 'Non-Aktif' ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded-2 <?= $exam['random_questions'] ? 'bg-info-subtle text-info-emphasis' : 'bg-light text-muted' ?>">
                        <span class="small fw-semibold"><i class="bi bi-shuffle me-2"></i>Acak Soal</span>
                        <span class="badge <?= $exam['random_questions'] ? 'bg-info text-dark' : 'bg-secondary' ?>"><?= $exam['random_questions'] ? 'Aktif' : 'Non-Aktif' ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded-2 <?= $exam['random_options'] ? 'bg-light text-dark' : 'bg-light text-muted' ?>">
                        <span class="small fw-semibold"><i class="bi bi-shuffle me-2"></i>Acak Jawaban</span>
                        <span class="badge <?= $exam['random_options'] ? 'bg-primary' : 'bg-secondary' ?>"><?= $exam['random_options'] ? 'Aktif' : 'Non-Aktif' ?></span>
                    </div>
                </div>

                <!-- PUBLISH / UNPUBLISH TOGGLE -->
                <div class="mt-4 pt-3 border-top">
                    <?php if ($exam['status'] === 'draft'): ?>
                        <form action="<?= base_url('guru/exams/toggle-status') ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $exam['id'] ?>">
                            <input type="hidden" name="status" value="published">
                            <button type="submit" class="btn btn-primary w-100 py-2 rounded-3">
                                <i class="bi bi-send-check me-2"></i> Publish Ujian Sekarang
                            </button>
                        </form>
                    <?php else: ?>
                        <form action="<?= base_url('guru/exams/toggle-status') ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $exam['id'] ?>">
                            <input type="hidden" name="status" value="draft">
                            <button type="submit" class="btn btn-outline-secondary w-100 py-2 rounded-3">
                                <i class="bi bi-pencil me-2"></i> Kembalikan ke Status Draft
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: QUESTIONS LIST -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-list-check text-primary me-2"></i>Daftar Soal Ujian (<?= count($questions) ?> Butir)</h5>
                    <p class="text-muted small mb-0">Urutan butir pertanyaan yang akan dihadapi peserta ujian</p>
                </div>
                <span class="badge bg-success-subtle text-success fs-6 px-3 py-2 rounded-pill">
                    Total: <?= $exam['total_score'] ?> Poin
                </span>
            </div>
            <div class="card-body p-4">
                <?php if (empty($questions)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-journal-x fs-1 opacity-50 d-block mb-2"></i>
                        <div class="fw-semibold">Tidak ada butir soal yang terhubung.</div>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php $no = 1; ?>
                        <?php foreach ($questions as $q): ?>
                            <div class="card border rounded-3 p-3 bg-body">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 pb-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary rounded-circle p-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                                            <?= $no++ ?>
                                        </span>
                                        <span class="badge bg-light text-dark border">
                                            <?= ucfirst(str_replace('_', ' ', $q['question_type'])) ?>
                                        </span>
                                        <?php if ($q['difficulty'] === 'easy'): ?>
                                            <span class="badge bg-success-subtle text-success">Mudah</span>
                                        <?php elseif ($q['difficulty'] === 'medium'): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis">Sedang</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger">Sulit</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="badge bg-secondary-subtle text-secondary px-3 py-1">
                                        Bobot: <?= $q['score'] ?> Poin
                                    </div>
                                </div>

                                <p class="fw-medium text-dark mb-3">
                                    <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                                </p>

                                <?php if ($q['question_type'] === 'multiple_choice'): ?>
                                    <div class="row g-2">
                                        <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $key => $col): ?>
                                            <?php $isKey = (strtoupper($q['correct_answer']) === $key); ?>
                                            <div class="col-md-6">
                                                <div class="p-2 rounded-2 border <?= $isKey ? 'border-success bg-success-subtle bg-opacity-25 text-success-emphasis fw-semibold' : 'bg-light text-dark' ?>">
                                                    <span class="badge <?= $isKey ? 'bg-success' : 'bg-secondary' ?> me-1"><?= $key ?></span>
                                                    <?= htmlspecialchars($q[$col] ?? '') ?>
                                                    <?php if ($isKey): ?>
                                                        <i class="bi bi-check-circle-fill text-success ms-1" title="Kunci Jawaban"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($q['question_type'] === 'true_false'): ?>
                                    <div class="p-2 rounded-2 bg-light border">
                                        <span class="text-muted small">Kunci Jawaban Benar/Salah:</span>
                                        <span class="badge bg-success ms-2"><?= strtoupper($q['correct_answer']) ?></span>
                                    </div>
                                <?php elseif ($q['question_type'] === 'essay'): ?>
                                    <div class="p-2 rounded-2 bg-light border">
                                        <span class="text-muted small d-block mb-1">Pedoman / Kunci Esai:</span>
                                        <div class="small text-dark"><?= nl2br(htmlspecialchars($q['correct_answer'])) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
