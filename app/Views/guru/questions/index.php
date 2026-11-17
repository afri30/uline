<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-journal-text text-success me-2"></i>Bank Soal Ujian</h4>
        <p class="text-muted small mb-0">Kelola kumpulan soal pilihan ganda, benar/salah, dan essay untuk mata pelajaran Anda</p>
    </div>
    <button type="button" class="btn btn-success px-3 py-2 rounded-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
        <i class="bi bi-plus-lg me-1"></i> Buat Soal Baru
    </button>
</div>

<!-- Stats Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Total Soal</span>
                    <span class="badge bg-success-subtle text-success rounded-pill">Bank</span>
                </div>
                <h4 class="fw-bold mb-0 text-dark"><?= (int)($stats['total'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Pilihan Ganda</span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill">MCQ</span>
                </div>
                <h4 class="fw-bold mb-0 text-dark"><?= (int)($stats['multiple_choice'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Benar / Salah</span>
                    <span class="badge bg-info-subtle text-info rounded-pill">T/F</span>
                </div>
                <h4 class="fw-bold mb-0 text-dark"><?= (int)($stats['true_false'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Soal Essay</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">Essay</span>
                </div>
                <h4 class="fw-bold mb-0 text-dark"><?= (int)($stats['essay'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3 p-md-4">
        <form action="<?= base_url('guru/questions') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control bg-light border-start-0 ps-0" name="q" value="<?= e($search ?? '') ?>" placeholder="Cari pertanyaan / teks soal...">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="subject_id" class="form-select bg-light">
                    <option value="">Semua Mata Pelajaran</option>
                    <?php foreach ($mySubjects as $subj): ?>
                        <option value="<?= $subj['id'] ?>" <?= ($subjectId == $subj['id']) ? 'selected' : '' ?>><?= e($subj['code']) ?> - <?= e($subj['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="difficulty" class="form-select bg-light">
                    <option value="">Semua Kesulitan</option>
                    <option value="easy" <?= ($difficulty === 'easy') ? 'selected' : '' ?>>Mudah</option>
                    <option value="medium" <?= ($difficulty === 'medium') ? 'selected' : '' ?>>Sedang</option>
                    <option value="hard" <?= ($difficulty === 'hard') ? 'selected' : '' ?>>Sulit</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="type" class="form-select bg-light">
                    <option value="">Semua Tipe</option>
                    <option value="multiple_choice" <?= ($type === 'multiple_choice') ? 'selected' : '' ?>>Pilihan Ganda</option>
                    <option value="true_false" <?= ($type === 'true_false') ? 'selected' : '' ?>>Benar/Salah</option>
                    <option value="essay" <?= ($type === 'essay') ? 'selected' : '' ?>>Essay</option>
                </select>
            </div>
            <div class="col-6 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-dark w-100" title="Terapkan Filter"><i class="bi bi-filter"></i></button>
                <?php if (!empty($search) || !empty($subjectId) || !empty($difficulty) || !empty($type)): ?>
                    <a href="<?= base_url('guru/questions') ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Questions Table Card -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Pertanyaan / Soal</th>
                        <th>Mata Pelajaran</th>
                        <th>Tipe</th>
                        <th>Kesulitan</th>
                        <th>Bobot</th>
                        <th class="pe-4 text-end" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($questions)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Belum ada soal yang sesuai dengan kriteria pencarian Anda.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = ($currentPage - 1) * $perPage + 1; ?>
                        <?php foreach ($questions as $q): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td style="max-width: 320px;">
                                    <div class="fw-semibold text-dark text-truncate" title="<?= e($q['question_text']) ?>">
                                        <?= e($q['question_text']) ?>
                                    </div>
                                    <small class="text-muted">
                                        Kunci: <strong class="badge bg-light text-dark border font-monospace"><?= e($q['correct_answer']) ?></strong>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-dark-subtle text-dark border font-monospace extra-small">
                                        <?= e($q['subject_code']) ?>
                                    </span>
                                    <div class="extra-small text-muted text-truncate" style="max-width: 140px;"><?= e($q['subject_name']) ?></div>
                                </td>
                                <td>
                                    <?php if ($q['question_type'] === 'multiple_choice'): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small">Pilihan Ganda</span>
                                    <?php elseif ($q['question_type'] === 'true_false'): ?>
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle extra-small">Benar/Salah</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle extra-small">Essay</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($q['difficulty'] === 'easy'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle extra-small"><i class="bi bi-circle-fill extra-small me-1"></i>Mudah</span>
                                    <?php elseif ($q['difficulty'] === 'medium'): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle extra-small"><i class="bi bi-circle-fill extra-small me-1"></i>Sedang</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small"><i class="bi bi-circle-fill extra-small me-1"></i>Sulit</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold font-monospace small text-dark"><?= (int)$q['score'] ?> Poin</span>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Preview Button -->
                                        <button type="button" class="btn btn-outline-info btn-preview-question" 
                                                data-id="<?= $q['id'] ?>" 
                                                title="Preview Tampilan Soal">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <!-- Edit Button -->
                                        <button type="button" class="btn btn-outline-primary btn-edit-question"
                                                data-id="<?= $q['id'] ?>"
                                                data-subject-id="<?= $q['subject_id'] ?>"
                                                data-question-type="<?= $q['question_type'] ?>"
                                                data-question-text="<?= e($q['question_text']) ?>"
                                                data-option-a="<?= e($q['option_a'] ?? '') ?>"
                                                data-option-b="<?= e($q['option_b'] ?? '') ?>"
                                                data-option-c="<?= e($q['option_c'] ?? '') ?>"
                                                data-option-d="<?= e($q['option_d'] ?? '') ?>"
                                                data-correct-answer="<?= e($q['correct_answer']) ?>"
                                                data-score="<?= $q['score'] ?>"
                                                data-difficulty="<?= $q['difficulty'] ?>"
                                                title="Edit Soal">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Delete Button -->
                                        <button type="button" class="btn btn-outline-danger btn-delete-question"
                                                data-id="<?= $q['id'] ?>"
                                                data-text="<?= e(substr($q['question_text'], 0, 50)) ?>..."
                                                title="Hapus Soal">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalItems > $perPage): ?>
        <div class="card-footer bg-white border-top py-3 px-4">
            <?= paginate_links($totalItems, $perPage, $currentPage, 'guru/questions', [
                'q' => $search,
                'subject_id' => $subjectId,
                'difficulty' => $difficulty,
                'type' => $type
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Add Question -->
<div class="modal fade" id="addQuestionModal" tabindex="-1" aria-labelledby="addQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="addQuestionModalLabel"><i class="bi bi-plus-circle me-2 text-success"></i>Tambah Soal Ujian Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('guru/questions/store') ?>" method="POST" id="addQuestionForm">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-secondary">Mata Pelajaran <span class="text-danger">*</span></label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                <?php foreach ($mySubjects as $subj): ?>
                                    <option value="<?= $subj['id'] ?>"><?= e($subj['code']) ?> - <?= e($subj['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Tipe Soal <span class="text-danger">*</span></label>
                            <select name="question_type" id="add_question_type" class="form-select" required>
                                <option value="multiple_choice" selected>Pilihan Ganda (MCQ)</option>
                                <option value="true_false">Benar / Salah (True/False)</option>
                                <option value="essay">Essay / Uraian</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Tingkat Kesulitan <span class="text-danger">*</span></label>
                            <select name="difficulty" class="form-select" required>
                                <option value="easy">Mudah</option>
                                <option value="medium" selected>Sedang</option>
                                <option value="hard">Sulit</option>
                            </select>
                        </div>
                    </div>

                    <!-- Question Text -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Isi Pertanyaan / Teks Soal <span class="text-danger">*</span></label>
                        <textarea name="question_text" class="form-control" rows="4" placeholder="Tuliskan pertanyaan ujian di sini..." required></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Bobot Skor Nilai <span class="text-danger">*</span></label>
                            <input type="number" name="score" class="form-control" value="10" min="1" max="100" required>
                        </div>
                    </div>

                    <!-- Multiple Choice Section -->
                    <div id="add_mcq_section" class="p-3 bg-light rounded-3 border mb-3">
                        <label class="form-label small fw-bold text-dark mb-2">Pilihan Jawaban (A, B, C, D) & Kunci Jawaban:</label>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white fw-bold">A</span>
                                    <input type="text" name="option_a" class="form-control" placeholder="Teks Pilihan A">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white fw-bold">B</span>
                                    <input type="text" name="option_b" class="form-control" placeholder="Teks Pilihan B">
                                </div>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white fw-bold">C</span>
                                    <input type="text" name="option_c" class="form-control" placeholder="Teks Pilihan C">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white fw-bold">D</span>
                                    <input type="text" name="option_d" class="form-control" placeholder="Teks Pilihan D">
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold text-secondary">Kunci Jawaban Benar <span class="text-danger">*</span></label>
                            <select name="correct_answer" id="add_mcq_correct" class="form-select">
                                <option value="A" selected>Pilihan A</option>
                                <option value="B">Pilihan B</option>
                                <option value="C">Pilihan C</option>
                                <option value="D">Pilihan D</option>
                            </select>
                        </div>
                    </div>

                    <!-- True/False Section (Hidden by default) -->
                    <div id="add_tf_section" class="p-3 bg-light rounded-3 border mb-3 d-none">
                        <label class="form-label small fw-bold text-dark mb-2">Kunci Jawaban Benar / Salah:</label>
                        <select name="correct_answer_tf" id="add_tf_correct" class="form-select">
                            <option value="TRUE" selected>BENAR (TRUE)</option>
                            <option value="FALSE">SALAH (FALSE)</option>
                        </select>
                    </div>

                    <!-- Essay Section (Hidden by default) -->
                    <div id="add_essay_section" class="p-3 bg-light rounded-3 border mb-3 d-none">
                        <label class="form-label small fw-bold text-dark mb-2">Panduan / Kunci Jawaban Essay:</label>
                        <textarea name="correct_answer_essay" id="add_essay_correct" class="form-control" rows="3" placeholder="Tuliskan pedoman penskoran atau kunci jawaban ringkas..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i> Simpan Soal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Question -->
<div class="modal fade" id="editQuestionModal" tabindex="-1" aria-labelledby="editQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="editQuestionModalLabel"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Data Soal Ujian</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('guru/questions/update') ?>" method="POST" id="editQuestionForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="edit_question_id">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-secondary">Mata Pelajaran <span class="text-danger">*</span></label>
                            <select name="subject_id" id="edit_subject_id" class="form-select" required>
                                <?php foreach ($mySubjects as $subj): ?>
                                    <option value="<?= $subj['id'] ?>"><?= e($subj['code']) ?> - <?= e($subj['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Tipe Soal <span class="text-danger">*</span></label>
                            <select name="question_type" id="edit_question_type" class="form-select" required>
                                <option value="multiple_choice">Pilihan Ganda (MCQ)</option>
                                <option value="true_false">Benar / Salah (True/False)</option>
                                <option value="essay">Essay / Uraian</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Tingkat Kesulitan <span class="text-danger">*</span></label>
                            <select name="difficulty" id="edit_difficulty" class="form-select" required>
                                <option value="easy">Mudah</option>
                                <option value="medium">Sedang</option>
                                <option value="hard">Sulit</option>
                            </select>
                        </div>
                    </div>

                    <!-- Question Text -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Isi Pertanyaan / Teks Soal <span class="text-danger">*</span></label>
                        <textarea name="question_text" id="edit_question_text" class="form-control" rows="4" required></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Bobot Skor Nilai <span class="text-danger">*</span></label>
                            <input type="number" name="score" id="edit_score" class="form-control" min="1" max="100" required>
                        </div>
                    </div>

                    <!-- Multiple Choice Section -->
                    <div id="edit_mcq_section" class="p-3 bg-light rounded-3 border mb-3">
                        <label class="form-label small fw-bold text-dark mb-2">Pilihan Jawaban (A, B, C, D):</label>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white fw-bold">A</span>
                                    <input type="text" name="option_a" id="edit_option_a" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white fw-bold">B</span>
                                    <input type="text" name="option_b" id="edit_option_b" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white fw-bold">C</span>
                                    <input type="text" name="option_c" id="edit_option_c" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white fw-bold">D</span>
                                    <input type="text" name="option_d" id="edit_option_d" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold text-secondary">Kunci Jawaban Benar <span class="text-danger">*</span></label>
                            <select name="correct_answer" id="edit_mcq_correct" class="form-select">
                                <option value="A">Pilihan A</option>
                                <option value="B">Pilihan B</option>
                                <option value="C">Pilihan C</option>
                                <option value="D">Pilihan D</option>
                            </select>
                        </div>
                    </div>

                    <!-- True/False Section -->
                    <div id="edit_tf_section" class="p-3 bg-light rounded-3 border mb-3 d-none">
                        <label class="form-label small fw-bold text-dark mb-2">Kunci Jawaban Benar / Salah:</label>
                        <select name="correct_answer_tf" id="edit_tf_correct" class="form-select">
                            <option value="TRUE">BENAR (TRUE)</option>
                            <option value="FALSE">SALAH (FALSE)</option>
                        </select>
                    </div>

                    <!-- Essay Section -->
                    <div id="edit_essay_section" class="p-3 bg-light rounded-3 border mb-3 d-none">
                        <label class="form-label small fw-bold text-dark mb-2">Panduan / Kunci Jawaban Essay:</label>
                        <textarea name="correct_answer_essay" id="edit_essay_correct" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Preview Question -->
<div class="modal fade" id="previewQuestionModal" tabindex="-1" aria-labelledby="previewQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success" id="preview_badge_subject">MAT-12</span>
                    <span class="badge bg-secondary" id="preview_badge_type">Pilihan Ganda</span>
                    <span class="badge bg-warning text-dark" id="preview_badge_diff">Sedang</span>
                    <span class="badge bg-light text-dark font-monospace" id="preview_badge_score">10 Poin</span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                <div class="question-preview-box">
                    <h5 class="fw-bold text-dark mb-4 lh-base" id="preview_question_text"></h5>

                    <div id="preview_options_container" class="d-grid gap-2 mb-4">
                        <!-- Options injected dynamically -->
                    </div>

                    <div class="p-3 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                        <div class="small text-muted">Kunci Jawaban Sistem:</div>
                        <div class="fw-bold text-success font-monospace fs-6" id="preview_correct_key"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light px-4 py-3">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Delete Confirmation -->
<div class="modal fade" id="deleteQuestionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-body p-4 text-center">
                <div class="avatar-circle mx-auto mb-3 bg-danger-subtle text-danger fs-3">
                    <i class="bi bi-trash3"></i>
                </div>
                <h5 class="fw-bold mb-1">Hapus Soal?</h5>
                <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus soal ini dari Bank Soal Anda?</p>
                <form action="<?= base_url('guru/questions/delete') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="delete_question_id">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light w-50" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger w-50">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Dynamic Question Type Switcher (Add Modal)
    const addTypeSelect = document.getElementById('add_question_type');
    const addMcqSec = document.getElementById('add_mcq_section');
    const addTfSec = document.getElementById('add_tf_section');
    const addEssaySec = document.getElementById('add_essay_section');

    function toggleAddSections(type) {
        addMcqSec.classList.add('d-none');
        addTfSec.classList.add('d-none');
        addEssaySec.classList.add('d-none');

        if (type === 'multiple_choice') {
            addMcqSec.classList.remove('d-none');
        } else if (type === 'true_false') {
            addTfSec.classList.remove('d-none');
        } else {
            addEssaySec.classList.remove('d-none');
        }
    }

    if (addTypeSelect) {
        addTypeSelect.addEventListener('change', () => toggleAddSections(addTypeSelect.value));
    }

    // Dynamic Question Type Switcher (Edit Modal)
    const editTypeSelect = document.getElementById('edit_question_type');
    const editMcqSec = document.getElementById('edit_mcq_section');
    const editTfSec = document.getElementById('edit_tf_section');
    const editEssaySec = document.getElementById('edit_essay_section');

    function toggleEditSections(type) {
        editMcqSec.classList.add('d-none');
        editTfSec.classList.add('d-none');
        editEssaySec.classList.add('d-none');

        if (type === 'multiple_choice') {
            editMcqSec.classList.remove('d-none');
        } else if (type === 'true_false') {
            editTfSec.classList.remove('d-none');
        } else {
            editEssaySec.classList.remove('d-none');
        }
    }

    if (editTypeSelect) {
        editTypeSelect.addEventListener('change', () => toggleEditSections(editTypeSelect.value));
    }

    // 2. Edit Modal Population
    const editButtons = document.querySelectorAll('.btn-edit-question');
    const editModal = new bootstrap.Modal(document.getElementById('editQuestionModal'));

    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const data = btn.dataset;
            document.getElementById('edit_question_id').value = data.id;
            document.getElementById('edit_subject_id').value = data.subjectId;
            document.getElementById('edit_question_type').value = data.questionType;
            document.getElementById('edit_question_text').value = data.questionText;
            document.getElementById('edit_difficulty').value = data.difficulty;
            document.getElementById('edit_score').value = data.score;

            toggleEditSections(data.questionType);

            if (data.questionType === 'multiple_choice') {
                document.getElementById('edit_option_a').value = data.optionA;
                document.getElementById('edit_option_b').value = data.optionB;
                document.getElementById('edit_option_c').value = data.optionC;
                document.getElementById('edit_option_d').value = data.optionD;
                document.getElementById('edit_mcq_correct').value = data.correctAnswer;
            } else if (data.questionType === 'true_false') {
                document.getElementById('edit_tf_correct').value = data.correctAnswer;
            } else {
                document.getElementById('edit_essay_correct').value = data.correctAnswer;
            }

            editModal.show();
        });
    });

    // 3. Preview Modal Handling
    const previewButtons = document.querySelectorAll('.btn-preview-question');
    const previewModal = new bootstrap.Modal(document.getElementById('previewQuestionModal'));

    previewButtons.forEach(btn => {
        btn.addEventListener('click', async () => {
            const qId = btn.dataset.id;
            try {
                const res = await fetchApi(`guru/questions/preview?id=${qId}`);
                if (res.status === 'success' && res.data) {
                    const q = res.data;
                    document.getElementById('preview_badge_subject').textContent = `${q.subject_code} - ${q.subject_name}`;
                    document.getElementById('preview_badge_type').textContent = q.question_type.replace('_', ' ').toUpperCase();
                    document.getElementById('preview_badge_diff').textContent = q.difficulty.toUpperCase();
                    document.getElementById('preview_badge_score').textContent = `${q.score} Poin`;
                    document.getElementById('preview_question_text').textContent = q.question_text;
                    document.getElementById('preview_correct_key').textContent = `KUNCI: ${q.correct_answer}`;

                    const optContainer = document.getElementById('preview_options_container');
                    optContainer.innerHTML = '';

                    if (q.question_type === 'multiple_choice') {
                        const options = [
                            { key: 'A', text: q.option_a },
                            { key: 'B', text: q.option_b },
                            { key: 'C', text: q.option_c },
                            { key: 'D', text: q.option_d }
                        ];

                        options.forEach(opt => {
                            const isCorrect = (q.correct_answer === opt.key);
                            const div = document.createElement('div');
                            div.className = `p-3 rounded-3 border d-flex align-items-center justify-content-between ${isCorrect ? 'border-success bg-success bg-opacity-10 text-success-emphasis fw-bold' : 'bg-white'}`;
                            div.innerHTML = `
                                <div><span class="badge ${isCorrect ? 'bg-success' : 'bg-secondary'} me-2">${opt.key}</span> ${opt.text || ''}</div>
                                ${isCorrect ? '<i class="bi bi-check-circle-fill text-success fs-5"></i>' : ''}
                            `;
                            optContainer.appendChild(div);
                        });
                    } else if (q.question_type === 'true_false') {
                        const div = document.createElement('div');
                        div.className = 'p-3 bg-light rounded-3 border';
                        div.innerHTML = `Jawaban Benar: <strong class="badge bg-info text-dark font-monospace">${q.correct_answer}</strong>`;
                        optContainer.appendChild(div);
                    } else {
                        const div = document.createElement('div');
                        div.className = 'p-3 bg-light rounded-3 border';
                        div.innerHTML = `<small class="text-muted d-block mb-1">Pedoman Jawaban:</small><div>${q.correct_answer}</div>`;
                        optContainer.appendChild(div);
                    }

                    previewModal.show();
                }
            } catch (err) {
                alert('Gagal memuat preview soal: ' + err.message);
            }
        });
    });

    // 4. Delete Question Modal
    const deleteButtons = document.querySelectorAll('.btn-delete-question');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteQuestionModal'));

    deleteButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('delete_question_id').value = btn.dataset.id;
            deleteModal.show();
        });
    });
});
</script>
