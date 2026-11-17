<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= base_url('guru/dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('guru/exams') ?>" class="text-decoration-none">Manajemen Ujian</a></li>
                <li class="breadcrumb-item active" aria-current="page">Buat Ujian</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0"><i class="bi bi-calendar-plus text-primary me-2"></i>Buat Jadwal Ujian Baru</h4>
    </div>
    <div>
        <a href="<?= base_url('guru/exams') ?>" class="btn btn-light border rounded-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

<form action="<?= base_url('guru/exams/store') ?>" method="POST" id="formCreateExam">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- LEFT COLUMN: EXAM CONFIGURATION -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-1"><i class="bi bi-info-circle text-primary me-2"></i>Informasi Utama Ujian</h5>
                    <p class="text-muted small mb-0">Tentukan mata pelajaran, kelas sasaran, dan alokasi waktu ujian</p>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Ujian <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control rounded-3" placeholder="Contoh: Penilaian Harian 1: Kalkulus Diferensial" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mata Pelajaran <span class="text-danger">*</span></label>
                            <select name="subject_id" id="selectSubject" class="form-select rounded-3" required onchange="filterQuestionsBySubject()">
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                <?php foreach ($mySubjects as $s): ?>
                                    <option value="<?= $s['id'] ?>">[<?= htmlspecialchars($s['code']) ?>] <?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Target Kelas <span class="text-danger">*</span></label>
                            <select name="class_id" class="form-select rounded-3" required>
                                <option value="">-- Pilih Rombel Kelas --</option>
                                <?php foreach ($allClasses as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (Tingkat <?= htmlspecialchars($c['grade']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Petunjuk / Deskripsi Ujian</label>
                        <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Tuliskan petunjuk pengerjaan atau tata tertib bagi peserta..."></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Durasi (Menit) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="duration" class="form-control rounded-start-3" value="60" min="1" max="600" required>
                                <span class="input-group-text bg-light text-muted">Mnt</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Waktu Mulai <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="start_at" class="form-control rounded-3" required
                                   value="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Waktu Selesai <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="end_at" class="form-control rounded-3" required
                                   value="<?= date('Y-m-d\TH:i', strtotime('+3 hour')) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECURITY & PROCTORING OPTIONS -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-1"><i class="bi bi-shield-check text-primary me-2"></i>Pengaturan Ujian & Proctoring</h5>
                    <p class="text-muted small mb-0">Konfigurasi pengacakan dan modul pengawasan ujian online</p>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 bg-light rounded-3 d-flex align-items-center justify-content-between mb-0">
                                <div>
                                    <label class="form-check-label fw-semibold d-block" for="chkRandomQ">Acak Soal</label>
                                    <small class="text-muted">Urutan soal berbeda untuk tiap peserta</small>
                                </div>
                                <input class="form-check-input ms-0 fs-5" type="checkbox" name="random_questions" value="1" id="chkRandomQ" checked>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 bg-light rounded-3 d-flex align-items-center justify-content-between mb-0">
                                <div>
                                    <label class="form-check-label fw-semibold d-block" for="chkRandomOpt">Acak Jawaban</label>
                                    <small class="text-muted">Pilihan A, B, C, D diacak otomatis</small>
                                </div>
                                <input class="form-check-input ms-0 fs-5" type="checkbox" name="random_options" value="1" id="chkRandomOpt" checked>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 bg-light rounded-3 d-flex align-items-center justify-content-between mb-0">
                                <div>
                                    <label class="form-check-label fw-semibold d-block" for="chkAiProctoring">
                                        <i class="bi bi-robot text-danger me-1"></i> AI Proctoring
                                    </label>
                                    <small class="text-muted">Deteksi kecurangan via webcam</small>
                                </div>
                                <input class="form-check-input ms-0 fs-5" type="checkbox" name="ai_proctoring" value="1" id="chkAiProctoring">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 bg-light rounded-3 d-flex align-items-center justify-content-between mb-0">
                                <div>
                                    <label class="form-check-label fw-semibold d-block" for="chkDualCamera">
                                        <i class="bi bi-camera-video text-warning-emphasis me-1"></i> Dual Camera
                                    </label>
                                    <small class="text-muted">Kamera samping (smartphone)</small>
                                </div>
                                <input class="form-check-input ms-0 fs-5" type="checkbox" name="dual_camera" value="1" id="chkDualCamera">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: QUESTION SELECTION FROM BANK SOAL -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 d-flex flex-column">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-collection text-primary me-2"></i>Pilih Soal dari Bank Soal <span class="text-danger">*</span></h5>
                            <p class="text-muted small mb-0">Centang butir soal yang ingin dimasukkan ke dalam ujian ini</p>
                        </div>
                        <div>
                            <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill" id="selectedBadge">
                                <span id="selectedCount">0</span> Soal (<span id="totalPoints">0</span> Poin)
                            </span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-2 pt-2 border-top">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllQuestions" onchange="toggleSelectAll(this)">
                            <label class="form-check-label small fw-semibold" for="selectAllQuestions">Pilih Semua Soal</label>
                        </div>
                        <div class="text-muted small">
                            Tersedia: <strong><?= count($questions) ?></strong> soal
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 flex-grow-1" style="max-height: 520px; overflow-y: auto;">
                    <?php if (empty($questions)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-journal-x fs-1 opacity-50 d-block mb-2"></i>
                            <div class="fw-semibold">Bank Soal Anda masih kosong.</div>
                            <small class="d-block mb-3">Buat beberapa butir soal terlebih dahulu di modul Bank Soal.</small>
                            <a href="<?= base_url('guru/questions') ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="bi bi-plus-circle me-1"></i> Buka Bank Soal
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3" id="questionListContainer">
                            <?php foreach ($questions as $q): ?>
                                <div class="card border rounded-3 p-3 question-item transition-all" 
                                     data-subject-id="<?= $q['subject_id'] ?>" 
                                     id="card-q-<?= $q['id'] ?>">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="pt-1">
                                            <input class="form-check-input fs-5 question-checkbox" type="checkbox" 
                                                   name="questions[]" value="<?= $q['id'] ?>" 
                                                   data-score="<?= $q['score'] ?>"
                                                   id="q_<?= $q['id'] ?>" onchange="updateSelectedSummary()">
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 mb-1">
                                                <label for="q_<?= $q['id'] ?>" class="fw-bold mb-0 cursor-pointer text-dark">
                                                    [#<?= $q['id'] ?>] <?= htmlspecialchars($q['subject_name']) ?>
                                                </label>
                                                <div class="d-flex gap-1">
                                                    <span class="badge bg-light text-dark border"><?= ucfirst(str_replace('_', ' ', $q['question_type'])) ?></span>
                                                    <span class="badge bg-secondary-subtle text-secondary"><?= $q['score'] ?> Poin</span>
                                                </div>
                                            </div>
                                            <p class="mb-2 text-dark small">
                                                <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                                            </p>
                                            <?php if ($q['question_type'] === 'multiple_choice'): ?>
                                                <div class="row g-1 small text-muted">
                                                    <div class="col-6"><strong>A:</strong> <?= htmlspecialchars($q['option_a'] ?? '') ?></div>
                                                    <div class="col-6"><strong>B:</strong> <?= htmlspecialchars($q['option_b'] ?? '') ?></div>
                                                    <div class="col-6"><strong>C:</strong> <?= htmlspecialchars($q['option_c'] ?? '') ?></div>
                                                    <div class="col-6"><strong>D:</strong> <?= htmlspecialchars($q['option_d'] ?? '') ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer bg-white border-top p-3 d-flex justify-content-end gap-2">
                    <input type="hidden" name="status" id="examStatusInput" value="draft">
                    <button type="submit" class="btn btn-light border px-4 py-2" onclick="document.getElementById('examStatusInput').value='draft'">
                        <i class="bi bi-save me-1"></i> Simpan sebagai Draft
                    </button>
                    <button type="submit" class="btn btn-primary px-4 py-2" onclick="document.getElementById('examStatusInput').value='published'">
                        <i class="bi bi-send-check me-1"></i> Simpan & Publish Ujian
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function updateSelectedSummary() {
    const checkboxes = document.querySelectorAll('.question-checkbox:checked');
    let totalScore = 0;
    checkboxes.forEach(cb => {
        totalScore += parseInt(cb.getAttribute('data-score') || 0, 10);
    });

    document.getElementById('selectedCount').textContent = checkboxes.length;
    document.getElementById('totalPoints').textContent = totalScore;
}

function toggleSelectAll(master) {
    const visibleCards = document.querySelectorAll('.question-item:not([style*="display: none"]) .question-checkbox');
    visibleCards.forEach(cb => {
        cb.checked = master.checked;
    });
    updateSelectedSummary();
}

function filterQuestionsBySubject() {
    const subjectId = document.getElementById('selectSubject').value;
    const items = document.querySelectorAll('.question-item');

    items.forEach(item => {
        if (!subjectId || item.getAttribute('data-subject-id') === subjectId) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
            // Uncheck hidden items
            const cb = item.querySelector('.question-checkbox');
            if (cb) cb.checked = false;
        }
    });

    // Reset select all checkbox
    document.getElementById('selectAllQuestions').checked = false;
    updateSelectedSummary();
}

// Initial count on page load
document.addEventListener('DOMContentLoaded', () => {
    updateSelectedSummary();
});
</script>

<style>
.cursor-pointer { cursor: pointer; }
.transition-all { transition: all 0.2s ease-in-out; }
.question-item:hover { border-color: #0d6efd !important; background-color: #f8faff; }
</style>
