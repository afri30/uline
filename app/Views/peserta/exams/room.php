<!-- EXAM ENGINE ROOM INTERFACE -->
<div class="container-fluid px-0">
    <!-- TOP STATUS HEADER BAR -->
    <div class="card border-0 shadow-sm rounded-4 mb-3 bg-body">
        <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar-circle bg-primary-subtle text-primary fw-bold fs-5">
                    <i class="bi bi-laptop"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($session['exam_title']) ?></h5>
                    <div class="text-muted small">
                        <span>[<?= htmlspecialchars($session['subject_code']) ?>] <?= htmlspecialchars($session['subject_name']) ?></span> • 
                        <span>Peserta: <strong><?= htmlspecialchars($student['student_name'] ?? $student['nis']) ?></strong></span>
                    </div>
                </div>
            </div>

            <!-- AUTOSAVE STATUS BADGE -->
            <div class="d-flex align-items-center gap-2">
                <div id="autosaveIndicator" class="badge bg-light text-secondary border px-3 py-2 rounded-pill font-monospace">
                    <i class="bi bi-cloud-check-fill text-success me-1"></i> <span id="autosaveText">Tersimpan Otomatis</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN SPLIT EXAM LAYOUT -->
    <div class="row g-3">
        <!-- ========================================== -->
        <!-- LEFT COLUMN: QUESTION & OPTIONS WORKSPACE -->
        <!-- ========================================== -->
        <div class="col-lg-8 col-xl-9">
            <!-- CAMERA DISCONNECT WARNING BANNER -->
            <div id="cameraWarningBanner" class="alert alert-warning py-2 px-3 small rounded-4 mb-3 d-none align-items-center justify-content-between shadow-sm border-warning">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                    <div>
                        <strong id="cameraWarningTitle">Peringatan Pengawasan Kamera:</strong> 
                        <span id="cameraWarningMsg">Kamera terputus. Ujian tetap berjalan normal, silakan periksa sambungan kamera atau pilih ulang perangkat.</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-sm" onclick="dismissCameraWarning()" aria-label="Tutup"></button>
            </div>

            <div class="card border-0 shadow-sm rounded-4 h-100 d-flex flex-column" id="examQuestionCard">
                <!-- QUESTION HEADER -->
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary fs-6 px-3 py-1.5 rounded-pill" id="questionNumberBadge">
                            Soal No. <span id="currentQuestionNum">1</span> dari <?= count($questions) ?>
                        </span>
                        <span class="badge bg-light text-dark border" id="questionTypeBadge">Pilihan Ganda</span>
                    </div>
                    <div class="badge bg-secondary-subtle text-secondary px-3 py-1.5 rounded-pill" id="questionScoreBadge">
                        Bobot: 10 Poin
                    </div>
                </div>

                <!-- QUESTION CONTENT & OPTIONS AREA -->
                <div class="card-body p-4 flex-grow-1">
                    <!-- QUESTION TEXT -->
                    <div class="fs-5 text-dark fw-medium mb-4 lh-base" id="questionText">
                        Memuat pertanyaan...
                    </div>

                    <!-- OPTIONS CONTAINER -->
                    <div id="optionsContainer" class="d-flex flex-column gap-3 mb-4">
                        <!-- Rendered dynamically by JavaScript -->
                    </div>
                </div>

                <!-- NAVIGATION BUTTONS FOOTER -->
                <div class="card-footer bg-white border-top p-3 p-lg-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 py-2 rounded-3" id="btnPrev" onclick="navigateQuestion(-1)">
                        <i class="bi bi-chevron-left me-1"></i> Soal Sebelumnya
                    </button>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-warning px-3 py-2 rounded-3" id="btnFlag" onclick="toggleFlagCurrentQuestion()">
                            <i class="bi bi-bookmark-star me-1"></i> <span id="flagButtonText">Tandai Ragu-Ragu</span>
                        </button>
                    </div>

                    <button type="button" class="btn btn-primary px-4 py-2 rounded-3" id="btnNext" onclick="navigateQuestion(1)">
                        Soal Berikutnya <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- RIGHT COLUMN: TIMER, DUAL CAM, GRID       -->
        <!-- ========================================== -->
        <div class="col-lg-4 col-xl-3">
            <div class="d-flex flex-column gap-3">
                <!-- 1. LIVE COUNTDOWN TIMER -->
                <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-dark text-white timer-card" id="timerBox">
                    <div class="text-white-50 extra-small text-uppercase tracking-wider fw-semibold mb-1">
                        <i class="bi bi-stopwatch me-1"></i> Sisa Waktu Ujian
                    </div>
                    <div class="display-6 fw-bold font-monospace text-warning timer-display" id="timerDisplay">
                        00:00:00
                    </div>
                    <small class="text-white-50 extra-small">Waktu disinkronkan aman dengan server</small>
                </div>

                <!-- 2. DUAL CAMERA PROCTORING MONITOR -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden" id="dualCameraCard">
                    <div class="card-header bg-white border-0 py-2.5 px-3 d-flex justify-content-between align-items-center">
                        <span class="fw-bold small"><i class="bi bi-camera-video-fill text-danger me-1"></i> Pengawasan Dual Camera</span>
                        <span class="badge bg-light text-dark border extra-small" id="overallCamBadge">WebRTC Live</span>
                    </div>
                    <div class="card-body p-3 pt-0">
                        <!-- CAMERA 1: FACE CAMERA (KAMERA WAJAH) -->
                        <div class="camera-panel-box mb-3 border rounded-3 overflow-hidden bg-light">
                            <div class="d-flex justify-content-between align-items-center px-2 py-1 bg-white border-bottom">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-primary extra-small py-0.5 px-1.5">CAM 1</span>
                                    <span class="fw-semibold extra-small text-dark">Kamera Wajah (Face)</span>
                                </div>
                                <span class="badge bg-secondary extra-small" id="cam1StatusBadge">
                                    <i class="bi bi-circle-fill me-1 extra-small"></i><span id="cam1StatusText">Menghubungkan...</span>
                                </span>
                            </div>
                            
                            <!-- Video Viewport -->
                            <div class="camera-stream-box position-relative bg-black d-flex align-items-center justify-content-center" style="height: 115px;">
                                <video id="videoCamera1" autoplay playsinline muted class="w-100 h-100 object-fit-cover" style="display: block;"></video>
                                
                                <!-- Placeholder Overlay -->
                                <div id="cam1Placeholder" class="position-absolute w-100 h-100 top-0 start-0 d-flex flex-column align-items-center justify-content-center text-white bg-dark p-2 text-center" style="z-index: 2;">
                                    <i class="bi bi-person-video fs-2 text-white-50 mb-1"></i>
                                    <small class="extra-small text-white-50" id="cam1PlaceholderMsg">Menghubungkan Kamera Wajah...</small>
                                </div>

                                <!-- Live REC Badge -->
                                <div class="position-absolute top-0 start-0 p-1" id="cam1RecBadge" style="display: none; z-index: 3;">
                                    <span class="badge bg-danger extra-small py-0.5 px-1.5"><i class="bi bi-record-fill me-0.5 text-white blink"></i>LIVE</span>
                                </div>
                            </div>

                            <!-- Device Selector & Refresh -->
                            <div class="p-1.5 bg-white d-flex align-items-center justify-content-between gap-1 border-top">
                                <select id="cam1DeviceSelect" class="form-select form-select-sm extra-small py-0.5 px-1.5 bg-light border-0" onchange="switchCameraDevice(1, this.value)">
                                    <option value="">Deteksi Perangkat...</option>
                                </select>
                                <button type="button" class="btn btn-sm btn-light border py-0 px-2 extra-small" onclick="retryCamera(1)" title="Refresh Kamera 1">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </div>

                        <!-- CAMERA 2: DESK & ENVIRONMENT CAMERA (KAMERA MEJA / SEKITAR) -->
                        <div class="camera-panel-box border rounded-3 overflow-hidden bg-light">
                            <div class="d-flex justify-content-between align-items-center px-2 py-1 bg-white border-bottom">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-dark extra-small py-0.5 px-1.5">CAM 2</span>
                                    <span class="fw-semibold extra-small text-dark">Kamera Meja (Desk)</span>
                                </div>
                                <span class="badge bg-secondary extra-small" id="cam2StatusBadge">
                                    <i class="bi bi-circle-fill me-1 extra-small"></i><span id="cam2StatusText">Menghubungkan...</span>
                                </span>
                            </div>
                            
                            <!-- Video Viewport -->
                            <div class="camera-stream-box position-relative bg-black d-flex align-items-center justify-content-center" style="height: 115px;">
                                <video id="videoCamera2" autoplay playsinline muted class="w-100 h-100 object-fit-cover" style="display: block;"></video>
                                
                                <!-- Placeholder Overlay -->
                                <div id="cam2Placeholder" class="position-absolute w-100 h-100 top-0 start-0 d-flex flex-column align-items-center justify-content-center text-white bg-dark p-2 text-center" style="z-index: 2;">
                                    <i class="bi bi-display fs-2 text-white-50 mb-1" id="cam2PlaceholderIcon"></i>
                                    <small class="extra-small text-white-50" id="cam2PlaceholderMsg">Menghubungkan Kamera Meja...</small>
                                </div>

                                <!-- Live REC Badge -->
                                <div class="position-absolute top-0 start-0 p-1" id="cam2RecBadge" style="display: none; z-index: 3;">
                                    <span class="badge bg-danger extra-small py-0.5 px-1.5"><i class="bi bi-record-fill me-0.5 text-white blink"></i>LIVE</span>
                                </div>
                            </div>

                            <!-- Device Selector & Refresh -->
                            <div class="p-1.5 bg-white d-flex align-items-center justify-content-between gap-1 border-top">
                                <select id="cam2DeviceSelect" class="form-select form-select-sm extra-small py-0.5 px-1.5 bg-light border-0" onchange="switchCameraDevice(2, this.value)">
                                    <option value="">Deteksi Perangkat...</option>
                                </select>
                                <button type="button" class="btn btn-sm btn-light border py-0 px-2 extra-small" onclick="retryCamera(2)" title="Refresh Kamera 2">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </div>

                        <!-- GURU PROCTOR SUPERVISION CAMERA BOX -->
                        <div class="mt-2 p-2 bg-white border rounded-3 d-flex align-items-center gap-2" id="proctorSupervisorBox">
                            <div class="rounded overflow-hidden bg-dark flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 52px; height: 40px;">
                                <img id="proctorCameraImg" src="" alt="Pengawas" class="w-100 h-100 object-fit-cover d-none">
                                <i id="proctorCameraIcon" class="bi bi-person-badge text-white-50"></i>
                            </div>
                            <div class="lh-sm flex-grow-1 overflow-hidden">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold extra-small text-dark">Kamera Pengawas (Guru)</span>
                                    <span id="proctorCamBadge" class="badge bg-secondary extra-small" style="font-size: 0.65rem;">Standby</span>
                                </div>
                                <small class="text-muted extra-small d-block text-truncate" id="proctorCamSub">Menunggu koneksi kamera pengawas</small>
                            </div>
                        </div>

                        <!-- Info Footer -->
                        <div class="mt-2 text-muted extra-small d-flex flex-column gap-0.5">
                            <div><i class="bi bi-shield-check text-success me-1"></i>WebRTC Real-time Video Active</div>
                            <div id="camFallbackNotice" class="text-secondary extra-small fst-italic"></div>
                        </div>
                    </div>
                </div>

                <!-- 3. INTERACTIVE QUESTION NUMBER GRID -->
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 py-3 px-3 d-flex justify-content-between align-items-center">
                        <span class="fw-bold small"><i class="bi bi-grid-3x3-gap text-primary me-1"></i> Nomor Soal</span>
                        <span class="badge bg-primary-subtle text-primary extra-small" id="answeredStatsBadge">
                            <span id="answeredCountSpan">0</span>/<?= count($questions) ?> Terjawab
                        </span>
                    </div>
                    <div class="card-body p-3 pt-0">
                        <!-- GRID BUTTONS -->
                        <div class="question-grid-container" id="questionGrid">
                            <!-- Generated by JS -->
                        </div>

                        <!-- LEGEND -->
                        <div class="row g-1 pt-3 mt-3 border-top extra-small text-muted">
                            <div class="col-6 d-flex align-items-center gap-1.5">
                                <span class="legend-box bg-success text-white">✓</span> Sudah Dijawab
                            </div>
                            <div class="col-6 d-flex align-items-center gap-1.5">
                                <span class="legend-box bg-light border">1</span> Belum Dijawab
                            </div>
                            <div class="col-6 d-flex align-items-center gap-1.5">
                                <span class="legend-box bg-warning text-dark">★</span> Ragu-Ragu
                            </div>
                            <div class="col-6 d-flex align-items-center gap-1.5">
                                <span class="legend-box border-primary border-2 bg-primary-subtle">1</span> Sedang Dibuka
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. SUBMIT EXAM BUTTON -->
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-body">
                    <button type="button" class="btn btn-success w-100 py-2.5 rounded-3 fw-bold shadow-sm" onclick="confirmSubmitExam()">
                        <i class="bi bi-check2-circle me-1"></i> Selesai & Kumpulkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CONFIRM SUBMIT EXAM -->
<div class="modal fade" id="submitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-body text-center p-4">
                <div class="avatar-circle bg-success-subtle text-success mx-auto mb-3" style="width: 60px; height: 60px; font-size: 28px;">
                    <i class="bi bi-check2-all"></i>
                </div>
                <h5 class="fw-bold mb-2">Kumpulkan Ujian?</h5>
                <p class="text-muted small mb-3">
                    Anda telah menjawab <strong id="modalAnsweredCount">0</strong> dari <strong><?= count($questions) ?></strong> soal.
                </p>
                <div id="unansweredWarning" class="alert alert-warning py-2 px-3 small rounded-3 mb-3 d-none">
                    <i class="bi bi-exclamation-triangle me-1"></i> Masih ada <strong id="modalUnansweredCount">0</strong> butir soal yang belum dijawab!
                </div>
                <form action="<?= base_url('peserta/exams/finish') ?>" method="POST" id="formFinishExam">
                    <?= csrf_field() ?>
                    <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                    <input type="hidden" name="is_expired" id="isExpiredInput" value="0">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light w-50" data-bs-dismiss="modal">Periksa Lagi</button>
                        <button type="submit" class="btn btn-success w-50 fw-semibold">Kumpulkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT EXAM ENGINE CLIENT                                             -->
<!-- ========================================================================= -->
<script>
const EXAM_DATA = {
    sessionId: <?= (int)$session['id'] ?>,
    questions: <?= json_encode($questions) ?>,
    answersMap: <?= json_encode($answersMap) ?>,
    remainingSeconds: <?= (int)$remainingSeconds ?>,
    saveUrl: '<?= base_url('peserta/exams/save-answer') ?>',
    heartbeatUrl: '<?= base_url('peserta/exams/heartbeat') ?>',
    finishUrl: '<?= base_url('peserta/exams/finish') ?>',
    cameraEventUrl: '<?= base_url('peserta/exams/camera-event') ?>',
    cameraSnapshotUrl: '<?= base_url('peserta/exams/camera-snapshot') ?>',
    proctorCameraUrl: '<?= base_url('peserta/exams/proctor-camera') ?>',
    proctorEventUrl: '<?= base_url('peserta/exams/proctor-event') ?>',
    aiProctoring: <?= !empty($session['ai_proctoring']) ? 'true' : 'false' ?>,
    csrfToken: '<?= csrf_token() ?>'
};

let currentIndex = 0;
let userAnswers = {};
let flaggedQuestions = {};
let timerInterval = null;

// Initialize state from existing answers map
for (let qId in EXAM_DATA.answersMap) {
    if (EXAM_DATA.answersMap.hasOwnProperty(qId)) {
        userAnswers[qId] = EXAM_DATA.answersMap[qId].answer;
    }
}

// -----------------------------------------------------------------------------
// 1. QUESTION RENDERING
// -----------------------------------------------------------------------------
function renderCurrentQuestion() {
    const q = EXAM_DATA.questions[currentIndex];
    const totalQ = EXAM_DATA.questions.length;

    document.getElementById('currentQuestionNum').textContent = currentIndex + 1;
    document.getElementById('questionText').innerHTML = escapeHtml(q.question_text).replace(/\n/g, '<br>');
    document.getElementById('questionScoreBadge').textContent = `Bobot: ${q.score} Poin`;

    // Type badge
    let typeLabel = 'Pilihan Ganda';
    if (q.question_type === 'true_false') typeLabel = 'Benar / Salah';
    else if (q.question_type === 'essay') typeLabel = 'Esai / Uraian';
    document.getElementById('questionTypeBadge').textContent = typeLabel;

    // Navigation buttons state
    document.getElementById('btnPrev').disabled = (currentIndex === 0);
    if (currentIndex === totalQ - 1) {
        document.getElementById('btnNext').innerHTML = 'Selesai & Kumpulkan <i class="bi bi-check2-circle ms-1"></i>';
        document.getElementById('btnNext').className = 'btn btn-success px-4 py-2 rounded-3';
    } else {
        document.getElementById('btnNext').innerHTML = 'Soal Berikutnya <i class="bi bi-chevron-right ms-1"></i>';
        document.getElementById('btnNext').className = 'btn btn-primary px-4 py-2 rounded-3';
    }

    // Flag button state
    const isFlagged = flaggedQuestions[q.id];
    document.getElementById('flagButtonText').textContent = isFlagged ? 'Hapus Tanda Ragu' : 'Tandai Ragu-Ragu';
    document.getElementById('btnFlag').className = isFlagged ? 'btn btn-warning text-dark px-3 py-2 rounded-3' : 'btn btn-outline-warning px-3 py-2 rounded-3';

    // Render Options
    const optContainer = document.getElementById('optionsContainer');
    optContainer.innerHTML = '';

    const currentAns = userAnswers[q.id] || '';

    if (q.question_type === 'multiple_choice') {
        const options = [
            { key: 'A', text: q.option_a },
            { key: 'B', text: q.option_b },
            { key: 'C', text: q.option_c },
            { key: 'D', text: q.option_d }
        ];

        options.forEach(opt => {
            if (!opt.text) return;
            const isSelected = (currentAns.toUpperCase() === opt.key);
            const card = document.createElement('div');
            card.className = `option-row p-3 rounded-3 border transition-all cursor-pointer ${isSelected ? 'border-primary bg-primary-subtle bg-opacity-10 shadow-sm' : 'bg-light'}`;
            card.onclick = () => selectOption(q.id, opt.key);

            card.innerHTML = `
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check m-0">
                        <input class="form-check-input fs-5" type="radio" name="option_radio" value="${opt.key}" ${isSelected ? 'checked' : ''}>
                    </div>
                    <span class="badge ${isSelected ? 'bg-primary' : 'bg-secondary'} rounded-circle p-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">${opt.key}</span>
                    <div class="flex-grow-1 text-dark fs-6">${escapeHtml(opt.text)}</div>
                </div>
            `;
            optContainer.appendChild(card);
        });
    } else if (q.question_type === 'true_false') {
        const tfOptions = [
            { key: 'TRUE', label: 'BENAR (TRUE)', icon: 'bi-check-circle' },
            { key: 'FALSE', label: 'SALAH (FALSE)', icon: 'bi-x-circle' }
        ];

        tfOptions.forEach(opt => {
            const isSelected = (currentAns.toUpperCase() === opt.key);
            const card = document.createElement('div');
            card.className = `option-row p-3 rounded-3 border transition-all cursor-pointer ${isSelected ? 'border-primary bg-primary-subtle bg-opacity-10 shadow-sm' : 'bg-light'}`;
            card.onclick = () => selectOption(q.id, opt.key);

            card.innerHTML = `
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check m-0">
                        <input class="form-check-input fs-5" type="radio" name="option_radio" value="${opt.key}" ${isSelected ? 'checked' : ''}>
                    </div>
                    <i class="bi ${opt.icon} fs-4 ${isSelected ? 'text-primary' : 'text-muted'}"></i>
                    <div class="flex-grow-1 text-dark fw-bold fs-6">${opt.label}</div>
                </div>
            `;
            optContainer.appendChild(card);
        });
    } else if (q.question_type === 'essay') {
        const textareaDiv = document.createElement('div');
        textareaDiv.innerHTML = `
            <label class="form-label fw-semibold text-muted small">Ketik jawaban uraian/esai Anda di bawah ini:</label>
            <textarea class="form-control rounded-3 p-3" rows="6" placeholder="Tuliskan jawaban Anda secara rinci..." oninput="debounceSaveEssay(${q.id}, this.value)">${escapeHtml(currentAns)}</textarea>
        `;
        optContainer.appendChild(textareaDiv);
    }

    renderQuestionGrid();
}

function selectOption(questionId, value) {
    userAnswers[questionId] = value;
    saveAnswerToServer(questionId, value);
    renderCurrentQuestion();
}

let essayTimer = null;
function debounceSaveEssay(questionId, value) {
    userAnswers[questionId] = value;
    clearTimeout(essayTimer);
    showSavingIndicator('Menyimpan...');
    essayTimer = setTimeout(() => {
        saveAnswerToServer(questionId, value);
    }, 800);
}

// -----------------------------------------------------------------------------
// 2. AJAX AUTO-SAVE ANSWER
// -----------------------------------------------------------------------------
async function saveAnswerToServer(questionId, answerValue) {
    showSavingIndicator('Menyimpan...');

    try {
        const formData = new FormData();
        formData.append('_csrf_token', EXAM_DATA.csrfToken);
        formData.append('session_id', EXAM_DATA.sessionId);
        formData.append('question_id', questionId);
        formData.append('answer', answerValue);

        const response = await fetch(EXAM_DATA.saveUrl, {
            method: 'POST',
            body: formData
        });

        const res = await response.json();

        if (res.status === 'expired') {
            alert('Waktu ujian telah habis! Sistem akan mengumpulkan ujian Anda sekarang.');
            submitExam(true);
            return;
        }

        if (res.status === 'success') {
            showSavingIndicator('Tersimpan ✓', true);
            updateAnsweredCount();
            renderQuestionGrid();
        }
    } catch (err) {
        console.error('Save error:', err);
        showSavingIndicator('Gagal Menyimpan (Cek Jaringan)', false);
    }
}

function showSavingIndicator(text, isSuccess = true) {
    const el = document.getElementById('autosaveText');
    const badge = document.getElementById('autosaveIndicator');
    el.textContent = text;
    if (text.includes('Tersimpan')) {
        badge.className = 'badge bg-success-subtle text-success border px-3 py-2 rounded-pill font-monospace';
    } else if (text.includes('Gagal')) {
        badge.className = 'badge bg-danger-subtle text-danger border px-3 py-2 rounded-pill font-monospace';
    } else {
        badge.className = 'badge bg-light text-secondary border px-3 py-2 rounded-pill font-monospace';
    }
}

// -----------------------------------------------------------------------------
// 3. NAVIGATION & GRID MANAGEMENT
// -----------------------------------------------------------------------------
function navigateQuestion(delta) {
    const nextIdx = currentIndex + delta;
    if (nextIdx >= 0 && nextIdx < EXAM_DATA.questions.length) {
        currentIndex = nextIdx;
        renderCurrentQuestion();
    } else if (nextIdx >= EXAM_DATA.questions.length) {
        confirmSubmitExam();
    }
}

function jumpToQuestion(index) {
    if (index >= 0 && index < EXAM_DATA.questions.length) {
        currentIndex = index;
        renderCurrentQuestion();
    }
}

function toggleFlagCurrentQuestion() {
    const qId = EXAM_DATA.questions[currentIndex].id;
    flaggedQuestions[qId] = !flaggedQuestions[qId];
    renderCurrentQuestion();
}

function renderQuestionGrid() {
    const grid = document.getElementById('questionGrid');
    grid.innerHTML = '';

    EXAM_DATA.questions.forEach((q, idx) => {
        const isCurrent = (idx === currentIndex);
        const hasAnswer = (userAnswers[q.id] && userAnswers[q.id].trim() !== '');
        const isFlagged = flaggedQuestions[q.id];

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `grid-number-btn ${isCurrent ? 'current' : ''} ${hasAnswer ? 'answered' : ''} ${isFlagged ? 'flagged' : ''}`;
        btn.textContent = idx + 1;
        btn.onclick = () => jumpToQuestion(idx);

        grid.appendChild(btn);
    });

    updateAnsweredCount();
}

function updateAnsweredCount() {
    let count = 0;
    EXAM_DATA.questions.forEach(q => {
        if (userAnswers[q.id] && userAnswers[q.id].trim() !== '') {
            count++;
        }
    });
    document.getElementById('answeredCountSpan').textContent = count;
}

// -----------------------------------------------------------------------------
// 4. SERVER-SYNCED COUNTDOWN TIMER
// -----------------------------------------------------------------------------
let secondsLeft = EXAM_DATA.remainingSeconds;

function startTimer() {
    updateTimerDisplay();

    timerInterval = setInterval(() => {
        secondsLeft--;
        if (secondsLeft <= 0) {
            clearInterval(timerInterval);
            secondsLeft = 0;
            updateTimerDisplay();
            alert('Waktu ujian telah selesai! Ujian Anda akan otomatis dikumpulkan.');
            submitExam(true);
        } else {
            updateTimerDisplay();
        }
    }, 1000);
}

function updateTimerDisplay() {
    const hrs = Math.floor(secondsLeft / 3600);
    const mins = Math.floor((secondsLeft % 3600) / 60);
    const secs = secondsLeft % 60;

    const formatted = [
        String(hrs).padStart(2, '0'),
        String(mins).padStart(2, '0'),
        String(secs).padStart(2, '0')
    ].join(':');

    const display = document.getElementById('timerDisplay');
    display.textContent = formatted;

    // Visual alert when less than 5 minutes remaining
    if (secondsLeft <= 300) {
        display.classList.remove('text-warning');
        display.classList.add('text-danger', 'blink');
    }
}

// -----------------------------------------------------------------------------
// 5. SUBMIT EXAM MODAL & AUTO-SUBMIT
// -----------------------------------------------------------------------------
function confirmSubmitExam() {
    let answered = 0;
    EXAM_DATA.questions.forEach(q => {
        if (userAnswers[q.id] && userAnswers[q.id].trim() !== '') answered++;
    });
    const total = EXAM_DATA.questions.length;
    const unanswered = total - answered;

    document.getElementById('modalAnsweredCount').textContent = answered;
    document.getElementById('modalUnansweredCount').textContent = unanswered;

    const warningEl = document.getElementById('unansweredWarning');
    if (unanswered > 0) {
        warningEl.classList.remove('d-none');
    } else {
        warningEl.classList.add('d-none');
    }

    new bootstrap.Modal(document.getElementById('submitModal')).show();
}

function submitExam(isExpired = false) {
    if (isExpired) {
        document.getElementById('isExpiredInput').value = '1';
    }
    document.getElementById('formFinishExam').submit();
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// -----------------------------------------------------------------------------
// 6. WEBRTC DUAL CAMERA PROCTORING ENGINE
// -----------------------------------------------------------------------------
const CAMERA_STATE = {
    stream1: null,
    stream2: null,
    devices: [],
    cam1DeviceId: sessionStorage.getItem('cam1DeviceId') || '',
    cam2DeviceId: sessionStorage.getItem('cam2DeviceId') || '',
    isSingleCameraFallback: false
};

async function initDualCameraSystem() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setCameraStatus(1, 'danger', 'Not Supported', 'Browser tidak mendukung WebRTC API.');
        setCameraStatus(2, 'secondary', 'Not Supported', 'Browser tidak mendukung WebRTC API.');
        sendCameraEvent('camera_1', 'stream_error', { reason: 'WebRTC not supported in this browser' });
        return;
    }

    try {
        setCameraStatus(1, 'info', 'Requesting...', 'Meminta izin kamera...');
        setCameraStatus(2, 'info', 'Requesting...', 'Meminta izin kamera...');

        // Initial permission request
        const initStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        
        // Send initial permission granted event
        sendCameraEvent('camera_1', 'permission_granted');

        // Stop temporary initial stream and allow Windows camera driver to release handle
        initStream.getTracks().forEach(track => track.stop());
        await new Promise(resolve => setTimeout(resolve, 150));

        // Enumerate available video devices
        await refreshVideoDevices();

        // Start cameras based on available device count
        await startCameras();

    } catch (err) {
        console.warn('Camera permission denied or error:', err);
        setCameraStatus(1, 'danger', 'Permission Denied', 'Izin kamera ditolak oleh peserta.');
        setCameraStatus(2, 'secondary', 'Permission Denied', 'Izin kamera ditolak oleh peserta.');
        showCameraWarning('Izin kamera ditolak. Mohon izinkan akses webcam pada browser untuk pengawasan ujian.');
        sendCameraEvent('camera_1', 'permission_denied', { error: err.name, message: err.message });
    }
}

async function refreshVideoDevices() {
    try {
        const allDevices = await navigator.mediaDevices.enumerateDevices();
        CAMERA_STATE.devices = allDevices.filter(d => d.kind === 'videoinput');

        const select1 = document.getElementById('cam1DeviceSelect');
        const select2 = document.getElementById('cam2DeviceSelect');

        select1.innerHTML = '';
        select2.innerHTML = '';

        if (CAMERA_STATE.devices.length === 0) {
            select1.innerHTML = '<option value="">Tidak ada kamera terdeteksi</option>';
            select2.innerHTML = '<option value="">Tidak ada kamera terdeteksi</option>';
            return;
        }

        CAMERA_STATE.devices.forEach((dev, idx) => {
            const label = dev.label || `Kamera ${idx + 1} (${dev.deviceId.substring(0, 8)}...)`;
            
            const opt1 = document.createElement('option');
            opt1.value = dev.deviceId;
            opt1.textContent = `CAM 1: ${label}`;
            select1.appendChild(opt1);

            const opt2 = document.createElement('option');
            opt2.value = dev.deviceId;
            opt2.textContent = `CAM 2: ${label}`;
            select2.appendChild(opt2);
        });

        // Smart device assignment
        if (!CAMERA_STATE.cam1DeviceId || !CAMERA_STATE.devices.some(d => d.deviceId === CAMERA_STATE.cam1DeviceId)) {
            CAMERA_STATE.cam1DeviceId = CAMERA_STATE.devices[0].deviceId;
        }
        select1.value = CAMERA_STATE.cam1DeviceId;

        if (CAMERA_STATE.devices.length >= 2) {
            if (!CAMERA_STATE.cam2DeviceId || CAMERA_STATE.cam2DeviceId === CAMERA_STATE.cam1DeviceId || !CAMERA_STATE.devices.some(d => d.deviceId === CAMERA_STATE.cam2DeviceId)) {
                CAMERA_STATE.cam2DeviceId = CAMERA_STATE.devices[1].deviceId;
            }
            select2.value = CAMERA_STATE.cam2DeviceId;
        } else {
            CAMERA_STATE.cam2DeviceId = '';
        }
    } catch (e) {
        console.error('Error enumerating devices:', e);
    }
}

async function startCameras() {
    // 1. Start Camera 1 (Face Camera)
    await startCamera1();

    // 2. Start Camera 2 (Desk/Environment) or activate Fallback
    if (CAMERA_STATE.devices.length >= 2) {
        await startCamera2();
    } else {
        activateSingleCameraFallback('Hanya 1 kamera video terdeteksi pada perangkat ini.');
    }
}

function setCameraPlaceholderVisible(camNumber, visible) {
    const placeholder = document.getElementById(`cam${camNumber}Placeholder`);
    const recBadge = document.getElementById(`cam${camNumber}RecBadge`);
    if (placeholder) {
        placeholder.classList.toggle('d-none', !visible);
        placeholder.classList.toggle('d-flex', visible);
    }
    if (recBadge) {
        recBadge.style.display = visible ? 'none' : 'block';
    }
}

async function startCamera1() {
    try {
        if (CAMERA_STATE.stream1) {
            CAMERA_STATE.stream1.getTracks().forEach(t => t.stop());
        }

        const primaryConstraints = {
            video: CAMERA_STATE.cam1DeviceId ? { deviceId: { exact: CAMERA_STATE.cam1DeviceId } } : { facingMode: 'user' },
            audio: false
        };

        let stream;
        try {
            stream = await navigator.mediaDevices.getUserMedia(primaryConstraints);
        } catch (constraintErr) {
            // Fallback to default device if saved cam1DeviceId is unavailable
            stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        }
        CAMERA_STATE.stream1 = stream;

        const video1 = document.getElementById('videoCamera1');
        video1.srcObject = stream;
        await video1.play().catch(e => console.log('Autoplay handled:', e));

        setCameraPlaceholderVisible(1, false);
        setCameraStatus(1, 'success', 'Connected');

        const activeTrack = stream.getVideoTracks()[0];
        const activeDeviceId = activeTrack?.getSettings?.().deviceId || CAMERA_STATE.cam1DeviceId;
        if (activeDeviceId) {
            CAMERA_STATE.cam1DeviceId = activeDeviceId;
            sessionStorage.setItem('cam1DeviceId', activeDeviceId);
            const sel1 = document.getElementById('cam1DeviceSelect');
            if (sel1 && Array.from(sel1.options).some(o => o.value === activeDeviceId)) {
                sel1.value = activeDeviceId;
            }
        }
        sendCameraEvent('camera_1', 'connected', { deviceId: CAMERA_STATE.cam1DeviceId });
        setTimeout(() => uploadLiveCameraFrame(1), 800);

        // Attach track onended listener for disconnect detection
        stream.getVideoTracks().forEach(track => {
            track.onended = () => {
                handleCameraDisconnect(1, 'Kabel atau sambungan Kamera 1 terputus.');
            };
        });

    } catch (err) {
        console.warn('Error starting Camera 1:', err);
        setCameraPlaceholderVisible(1, true);
        setCameraStatus(1, 'warning', 'Camera Error', 'Gagal membuka aliran video kamera 1.');
        sendCameraEvent('camera_1', 'stream_error', { error: err.name, message: err.message });
    }
}

async function startCamera2() {
    try {
        if (CAMERA_STATE.stream2) {
            CAMERA_STATE.stream2.getTracks().forEach(t => t.stop());
        }

        const constraints = {
            video: CAMERA_STATE.cam2DeviceId ? { deviceId: { exact: CAMERA_STATE.cam2DeviceId } } : { facingMode: 'environment' },
            audio: false
        };

        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        CAMERA_STATE.stream2 = stream;
        CAMERA_STATE.isSingleCameraFallback = false;

        const video2 = document.getElementById('videoCamera2');
        video2.srcObject = stream;
        await video2.play().catch(e => console.log('Autoplay handled:', e));

        const icon2 = document.getElementById('cam2PlaceholderIcon');
        if (icon2) icon2.className = 'bi bi-display fs-2 text-white-50 mb-1';
        const noticeEl = document.getElementById('camFallbackNotice');
        if (noticeEl) noticeEl.textContent = '';

        setCameraPlaceholderVisible(2, false);
        setCameraStatus(2, 'success', 'Connected');

        sessionStorage.setItem('cam2DeviceId', CAMERA_STATE.cam2DeviceId);
        sendCameraEvent('camera_2', 'connected', { deviceId: CAMERA_STATE.cam2DeviceId });
        setTimeout(() => uploadLiveCameraFrame(2), 1000);

        stream.getVideoTracks().forEach(track => {
            track.onended = () => {
                handleCameraDisconnect(2, 'Kabel atau sambungan Kamera 2 terputus.');
            };
        });

    } catch (err) {
        console.warn('Error starting Camera 2 (Simultaneous access may be restricted):', err);
        activateSingleCameraFallback('Perangkat/driver tidak mengizinkan akses 2 kamera bersamaan.');
    }
}

function activateSingleCameraFallback(reason) {
    CAMERA_STATE.isSingleCameraFallback = true;
    setCameraStatus(2, 'secondary', 'Single Cam Fallback', reason);
    setCameraPlaceholderVisible(2, true);
    document.getElementById('cam2PlaceholderIcon').className = 'bi bi-info-circle fs-2 text-info mb-1';
    document.getElementById('cam2PlaceholderMsg').textContent = 'Mode 1 Kamera Aktif (Fallback)';
    document.getElementById('camFallbackNotice').textContent = '* Mode 1 Kamera Aktif: ' + reason;

    sendCameraEvent('camera_2', 'single_camera_fallback', { reason: reason });
}

function setCameraStatus(camNumber, badgeType, text, detailMsg = '') {
    const badgeEl = document.getElementById(`cam${camNumber}StatusBadge`);
    const textEl = document.getElementById(`cam${camNumber}StatusText`);
    
    badgeEl.className = `badge bg-${badgeType} extra-small`;
    textEl.textContent = text;

    if (detailMsg) {
        const placeholderMsgEl = document.getElementById(`cam${camNumber}PlaceholderMsg`);
        if (placeholderMsgEl) placeholderMsgEl.textContent = detailMsg;
    }
}

function handleCameraDisconnect(camNumber, msg) {
    console.warn(`Camera ${camNumber} Disconnected!`);
    setCameraStatus(camNumber, 'danger', 'Camera Disconnected', msg);
    setCameraPlaceholderVisible(camNumber, true);

    showCameraWarning(`Kamera ${camNumber} terputus. Ujian Anda tetap berjalan aman.`);
    sendCameraEvent(`camera_${camNumber}`, 'disconnected', { reason: msg });
    sendProctorEvent(camNumber === 1 ? 'primary' : 'secondary', 'CAMERA_DISCONNECTED', 0.95, { reason: msg });
}

function showCameraWarning(msg) {
    const banner = document.getElementById('cameraWarningBanner');
    document.getElementById('cameraWarningMsg').textContent = msg;
    banner.classList.remove('d-none');
    banner.classList.add('d-flex');
}

function dismissCameraWarning() {
    const banner = document.getElementById('cameraWarningBanner');
    banner.classList.remove('d-flex');
    banner.classList.add('d-none');
}

async function switchCameraDevice(camNumber, deviceId) {
    if (!deviceId) return;

    if (camNumber === 1) {
        CAMERA_STATE.cam1DeviceId = deviceId;
        await startCamera1();
    } else {
        CAMERA_STATE.cam2DeviceId = deviceId;
        await startCamera2();
    }
}

async function retryCamera(camNumber) {
    await refreshVideoDevices();
    if (camNumber === 1) {
        await startCamera1();
    } else {
        if (CAMERA_STATE.devices.length >= 2) {
            await startCamera2();
        } else {
            activateSingleCameraFallback('Hanya 1 kamera video terdeteksi.');
        }
    }
}

// Device plug-and-play listener
if (navigator.mediaDevices && navigator.mediaDevices.ondevicechange !== undefined) {
    navigator.mediaDevices.ondevicechange = async () => {
        console.log('Media devices changed (webcam plugged/unplugged)');
        await refreshVideoDevices();
        sendCameraEvent('camera_1', 'device_changed', { deviceCount: CAMERA_STATE.devices.length });
    };
}

// Asynchronous Event Logger via Fetch API
function sendCameraEvent(cameraType, eventType, metadata = null) {
    fetch(EXAM_DATA.cameraEventUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            _csrf_token: EXAM_DATA.csrfToken,
            session_id: EXAM_DATA.sessionId,
            camera_type: cameraType,
            event_type: eventType,
            metadata: metadata
        })
    }).then(res => res.json())
      .then(data => {
          // Logged successfully
      })
      .catch(err => {
          console.warn('Failed to log camera event:', err);
      });
}

// Asynchronous AI Proctoring Event Logger
function sendProctorEvent(cameraType, eventType, confidence = 0.90, metadata = {}) {
    if (!EXAM_DATA.aiProctoring) return;

    fetch(EXAM_DATA.proctorEventUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            _csrf_token: EXAM_DATA.csrfToken,
            session_id: EXAM_DATA.sessionId,
            camera_type: cameraType,
            event_type: eventType,
            confidence: confidence,
            metadata: metadata
        })
    }).then(res => res.json())
      .then(data => {
          // Proctoring event recorded
      })
      .catch(err => {
          console.warn('Failed to log proctoring event:', err);
      });
}

// Listen for Tab Switching / Window Blur if Proctoring is enabled
let lastTabSwitchTime = 0;
document.addEventListener('visibilitychange', () => {
    if (document.hidden && EXAM_DATA.aiProctoring) {
        const now = Date.now();
        if (now - lastTabSwitchTime > 3000) { // Debounce 3s
            lastTabSwitchTime = now;
            sendProctorEvent('primary', 'TAB_SWITCHED', 0.92, {
                reason: 'Peserta beralih ke tab lain atau aplikasi lain di background'
            });
        }
    }
});

// Periodic Heartbeat to maintain online status
function startHeartbeat() {
    // Send immediate initial ping
    sendHeartbeatPing();

    // Repeat every 25 seconds
    setInterval(sendHeartbeatPing, 25000);
}

function sendHeartbeatPing() {
    fetch(EXAM_DATA.heartbeatUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            _csrf_token: EXAM_DATA.csrfToken,
            session_id: EXAM_DATA.sessionId
        })
    }).catch(e => console.debug('Heartbeat network handled:', e));
}

// Live WebRTC Camera Snapshot Streaming & Proctor Camera Status
const snapshotCanvas = document.createElement('canvas');
snapshotCanvas.width = 480;
snapshotCanvas.height = 360;

async function uploadLiveCameraFrame(camNumber) {
    const videoEl = document.getElementById(`videoCamera${camNumber}`);
    const stream = camNumber === 1 ? CAMERA_STATE.stream1 : CAMERA_STATE.stream2;
    if (!videoEl || !stream || videoEl.readyState < 2) return;

    try {
        const ctx = snapshotCanvas.getContext('2d');
        ctx.drawImage(videoEl, 0, 0, snapshotCanvas.width, snapshotCanvas.height);
        const imageBase64 = snapshotCanvas.toDataURL('image/jpeg', 0.65);

        const resp = await fetch(EXAM_DATA.cameraSnapshotUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                _csrf_token: EXAM_DATA.csrfToken,
                session_id: EXAM_DATA.sessionId,
                camera_type: `camera_${camNumber}`,
                image_base64: imageBase64
            })
        });
        const res = await resp.json().catch(() => null);
        if (res && typeof res.proctor_camera_live === 'boolean') {
            refreshProctorCameraUI(res.proctor_camera_live);
        }
    } catch (e) {
        console.debug('Snapshot upload handled:', e);
    }
}

function refreshProctorCameraUI(isLive) {
    const img = document.getElementById('proctorCameraImg');
    const icon = document.getElementById('proctorCameraIcon');
    const badge = document.getElementById('proctorCamBadge');
    const sub = document.getElementById('proctorCamSub');
    if (!img || !badge) return;

    if (isLive) {
        img.src = `${EXAM_DATA.proctorCameraUrl}?session_id=${EXAM_DATA.sessionId}&t=${Date.now()}`;
        img.classList.remove('d-none');
        icon.classList.add('d-none');
        badge.className = 'badge bg-success extra-small';
        badge.textContent = 'LIVE';
        sub.textContent = 'Guru Pengawas memantau dengan kamera aktif';
    } else {
        img.classList.add('d-none');
        icon.classList.remove('d-none');
        badge.className = 'badge bg-secondary extra-small';
        badge.textContent = 'Standby';
        sub.textContent = 'Pengawas memantau dari ruang kontrol';
    }
}

function startCameraSnapshotStreaming() {
    setInterval(() => {
        uploadLiveCameraFrame(1);
        if (CAMERA_STATE.stream2 && !CAMERA_STATE.isSingleCameraFallback) {
            uploadLiveCameraFrame(2);
        }
    }, 5000);
}

// -----------------------------------------------------------------------------
// INITIALIZE ON LOAD
// -----------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    renderCurrentQuestion();
    startTimer();
    initDualCameraSystem();
    startHeartbeat();
    startCameraSnapshotStreaming();
});
</script>

<style>
.camera-panel-box {
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.camera-stream-box {
    background-color: #000;
}
.camera-stream-box video {
    transform: scaleX(-1); /* Mirror view for face camera */
}
#videoCamera2 {
    transform: none; /* Normal view for desk/environment camera */
}

.cursor-pointer { cursor: pointer; }
.transition-all { transition: all 0.2s ease-in-out; }
.option-row:hover { border-color: #0d6efd !important; background-color: #f8faff !important; }

/* Interactive Question Grid Styles */
.question-grid-container {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 8px;
    max-height: 250px;
    overflow-y: auto;
    padding: 2px;
}

.grid-number-btn {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 13px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    background-color: #f8f9fa;
    color: #495057;
    transition: all 0.15s ease;
}

.grid-number-btn:hover {
    border-color: #0d6efd;
    background-color: #e7f1ff;
    color: #0d6efd;
}

.grid-number-btn.answered {
    background-color: #198754;
    border-color: #198754;
    color: #ffffff;
}

.grid-number-btn.current {
    outline: 2px solid #0d6efd;
    outline-offset: 1px;
    font-weight: 800;
}

.grid-number-btn.flagged {
    background-color: #ffc107 !important;
    border-color: #ffc107 !important;
    color: #000000 !important;
}

.legend-box {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: bold;
}

.blink {
    animation: blinker 1s linear infinite;
}

@keyframes blinker {
    50% { opacity: 0.3; }
}
</style>
