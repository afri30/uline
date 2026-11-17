<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= base_url('guru/results') ?>" class="text-decoration-none">Hasil Ujian</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('guru/results/' . $exam['id']) ?>" class="text-decoration-none"><?= e($exam['title']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($session['student_name']) ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-check-fill text-primary me-2"></i>Lembar Jawaban Peserta</h4>
        <div class="text-muted small d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary"><?= e($session['student_name']) ?> (NIS: <?= e($session['student_nis']) ?>)</span>
            <span class="badge bg-info-subtle text-info"><?= e($session['class_name']) ?></span>
            <span><i class="bi bi-book me-1"></i><?= e($session['subject_name']) ?></span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('guru/results/' . $exam['id']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Nilai
        </a>
    </div>
</div>

<!-- SCOREBOARD & STATS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Nilai Akhir (Skala 0-100)</span>
                    <div class="display-5 fw-bold <?= $summary['grade'] >= 75 ? 'text-success' : ($summary['grade'] >= 50 ? 'text-primary' : 'text-danger') ?> mt-1">
                        <?= number_format((float)$summary['grade'], 1) ?>
                    </div>
                </div>
                <div class="avatar-circle-lg bg-light text-primary rounded-circle p-3">
                    <i class="bi bi-award fs-1"></i>
                </div>
            </div>
            <div class="mt-2 text-muted small border-top pt-2">
                Total Skor Diperoleh: <strong><?= $summary['earned_score'] ?></strong> dari <?= $summary['max_score'] ?> poin maksimal
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body h-100">
            <span class="text-muted small fw-semibold mb-2 d-block">Analisis Butir Jawaban</span>
            <div class="row g-2 text-center">
                <div class="col-4">
                    <div class="p-2 rounded bg-success-subtle text-success">
                        <div class="fs-4 fw-bold"><?= $summary['correct_count'] ?></div>
                        <small class="extra-small fw-semibold">Benar</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded bg-danger-subtle text-danger">
                        <div class="fs-4 fw-bold"><?= $summary['incorrect_count'] ?></div>
                        <small class="extra-small fw-semibold">Salah</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded bg-secondary-subtle text-secondary">
                        <div class="fs-4 fw-bold"><?= $summary['unanswered_count'] ?></div>
                        <small class="extra-small fw-semibold">Kosong</small>
                    </div>
                </div>
            </div>
            <div class="mt-2 text-muted extra-small text-center">
                Total Keseluruhan: <strong><?= $summary['total_questions'] ?></strong> butir soal
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body h-100">
            <span class="text-muted small fw-semibold mb-2 d-block">Informasi Sesi Pengerjaan</span>
            <div class="small">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Status:</span>
                    <span class="badge bg-success-subtle text-success border border-success"><?= strtoupper(e($session['status'])) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Mulai:</span>
                    <span class="fw-semibold text-dark"><?= $session['started_at'] ? date('H:i:s, d M Y', strtotime($session['started_at'])) : '-' ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Selesai:</span>
                    <span class="fw-semibold text-dark"><?= $session['finished_at'] ? date('H:i:s, d M Y', strtotime($session['finished_at'])) : '-' ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QUESTIONS & ANSWERS BREAKDOWN -->
<h5 class="fw-bold mb-3 text-dark"><i class="bi bi-list-check me-2"></i>Rincian Soal & Jawaban Peserta</h5>

<div class="d-flex flex-column gap-3 mb-5">
    <?php foreach ($questions as $idx => $q): ?>
        <?php
        $cardBorder = 'border';
        $badgeClass = 'bg-secondary';
        $badgeText = 'Tidak Dijawab (0)';

        if ($q['is_answered']) {
            if ($q['is_correct']) {
                $cardBorder = 'border-success border-opacity-50';
                $badgeClass = 'bg-success';
                $badgeText = 'Jawaban Benar (+' . $q['earned_score'] . ' Poin)';
            } else {
                $cardBorder = 'border-danger border-opacity-50';
                $badgeClass = 'bg-danger';
                $badgeText = 'Jawaban Salah (0 Poin)';
            }
        }
        ?>
        <div class="card <?= $cardBorder ?> shadow-sm rounded-3">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark text-white rounded-pill px-3 py-1">Soal #<?= $idx + 1 ?></span>
                    <span class="badge bg-light text-secondary border"><?= ucfirst(str_replace('_', ' ', $q['question_type'])) ?></span>
                    <span class="badge bg-light text-secondary border"><?= ucfirst($q['difficulty']) ?></span>
                </div>
                <div>
                    <span class="badge <?= $badgeClass ?> px-3 py-1 fs-7"><?= $badgeText ?></span>
                </div>
            </div>
            <div class="card-body p-3">
                <!-- Pertanyaan -->
                <div class="fs-6 text-dark mb-3 fw-medium">
                    <?= nl2br(e($q['question_text'])) ?>
                </div>

                <!-- Pilihan Ganda -->
                <?php if ($q['question_type'] === 'multiple_choice' || $q['question_type'] === 'true_false'): ?>
                    <div class="row g-2">
                        <?php
                        $options = [
                            'A' => $q['option_a'],
                            'B' => $q['option_b']
                        ];
                        if ($q['question_type'] === 'multiple_choice') {
                            $options['C'] = $q['option_c'];
                            $options['D'] = $q['option_d'];
                        }

                        $studentAns = strtoupper(trim((string)$q['student_answer']));
                        $correctAns = strtoupper(trim((string)$q['correct_answer']));

                        foreach ($options as $key => $val):
                            if (empty($val) && $key !== 'A' && $key !== 'B') continue;

                            $isThisStudentChoice = ($studentAns === $key);
                            $isThisCorrectChoice = ($correctAns === $key);

                            $optClass = 'bg-light border text-dark';
                            $optBadge = '';

                            if ($isThisCorrectChoice && $isThisStudentChoice) {
                                $optClass = 'bg-success-subtle border-success text-success-emphasis fw-bold';
                                $optBadge = '<span class="badge bg-success ms-auto"><i class="bi bi-check-circle-fill me-1"></i>Pilihan Siswa & Kunci Benar</span>';
                            } elseif ($isThisCorrectChoice) {
                                $optClass = 'bg-success-subtle border-success text-success-emphasis fw-bold';
                                $optBadge = '<span class="badge bg-success ms-auto"><i class="bi bi-key-fill me-1"></i>Kunci Jawaban</span>';
                            } elseif ($isThisStudentChoice) {
                                $optClass = 'bg-danger-subtle border-danger text-danger-emphasis fw-bold';
                                $optBadge = '<span class="badge bg-danger ms-auto"><i class="bi bi-x-circle-fill me-1"></i>Pilihan Siswa (Salah)</span>';
                            }
                        ?>
                            <div class="col-md-6">
                                <div class="p-2 rounded-3 border d-flex align-items-center <?= $optClass ?>">
                                    <span class="badge bg-dark bg-opacity-75 text-white me-2" style="width: 28px;"><?= $key ?></span>
                                    <span class="small"><?= e($val) ?></span>
                                    <?= $optBadge ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($q['question_type'] === 'essay'): ?>
                    <div class="bg-light p-3 rounded-3 border">
                        <label class="form-label small fw-bold text-secondary mb-1">Jawaban Siswa:</label>
                        <div class="p-2 bg-white rounded border mb-2">
                            <?= !empty($q['student_answer']) ? nl2br(e($q['student_answer'])) : '<em class="text-muted">Tidak dijawab</em>' ?>
                        </div>
                        <div class="small text-muted">
                            Kunci/Pedoman Penilaian: <strong><?= e($q['correct_answer']) ?></strong>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent border-top py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1"
                        onclick="openAITutorForQuestion(
                            <?= (int)$session['id'] ?>,
                            <?= (int)$q['id'] ?>,
                            <?= $idx + 1 ?>,
                            <?= htmlspecialchars(json_encode($q['question_text']), ENT_QUOTES) ?>,
                            <?= htmlspecialchars(json_encode($q['student_answer'] ?? ''), ENT_QUOTES) ?>,
                            <?= htmlspecialchars(json_encode($q['correct_answer'] ?? ''), ENT_QUOTES) ?>,
                            <?= $q['is_correct'] ? 'true' : 'false' ?>,
                            <?= htmlspecialchars(json_encode($options ?? []), ENT_QUOTES) ?>
                        )">
                        <i class="bi bi-robot me-1"></i> Tanya AI Tutor
                    </button>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <small class="text-muted extra-small">
                        Bobot Soal: <strong><?= $q['max_score'] ?> Poin</strong>
                    </small>
                    <small class="text-muted extra-small">
                        Waktu Dijawab: <?= $q['answered_at'] ? date('H:i:s, d/m/Y', strtotime($q['answered_at'])) : '<span class="text-secondary">-</span>' ?>
                    </small>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- INCLUDE AI QUESTION TUTOR MODAL PARTIAL -->
<?php require dirname(__DIR__, 2) . '/layouts/partials/question_tutor_modal.php'; ?>
