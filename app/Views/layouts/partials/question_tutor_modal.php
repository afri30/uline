<!-- AI QUESTION TUTOR MODAL & INTERACTIVE DRAWER (TIPE 2) -->
<div class="modal fade" id="aiTutorModal" tabindex="-1" aria-labelledby="aiTutorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- MODAL HEADER -->
            <div class="modal-header bg-primary text-white p-3 border-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-circle bg-white text-primary fw-bold" style="width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-mortarboard-fill fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-white" id="aiTutorModalLabel">AI Subject Tutor • Pembahasan Soal</h6>
                        <small class="text-white-50" id="aiTutorSubjectTitle">Memuat pembahasan konsep materi...</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="closeAITutorModal()"></button>
            </div>

            <!-- MODAL BODY -->
            <div class="modal-body p-4 bg-light" style="font-size: 0.92rem;">
                <!-- QUESTION CONTEXT SUMMARY CARD -->
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3" id="aiTutorQuestionCard">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary-subtle text-primary" id="aiTutorQNumBadge">Soal No. -</span>
                        <div class="d-flex gap-2">
                            <span class="badge" id="aiTutorStudentAnsBadge">-</span>
                            <span class="badge bg-success" id="aiTutorCorrectAnsBadge">-</span>
                        </div>
                    </div>
                    <div class="fw-semibold text-dark mb-2" id="aiTutorQText">
                        Memuat pertanyaan...
                    </div>
                    <div id="aiTutorQOptions" class="small text-muted mb-0"></div>
                </div>

                <!-- AI EXPLANATION STREAM CONTAINER -->
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2 text-primary fw-bold">
                            <i class="bi bi-robot"></i>
                            <span>Penjelasan & Analisis Konsep oleh AI Tutor</span>
                        </div>
                        <span class="badge bg-light text-secondary border font-monospace extra-small">NVIDIA NIM Guardrails</span>
                    </div>

                    <!-- LOADING STATE -->
                    <div id="aiTutorLoading" class="text-center py-4">
                        <div class="spinner-border text-primary mb-2" role="status" style="width: 2rem; height: 2rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted small mb-0">Sedang menyusun analisis pedagogis dan langkah pembahasan soal...</p>
                    </div>

                    <!-- EXPLANATION CONTENT -->
                    <div id="aiTutorContent" class="d-none text-dark lh-base tutor-explanation-body">
                        <!-- Rendered Markdown Explanation -->
                    </div>
                </div>

                <!-- FOLLOW-UP PROMPT CHIPS -->
                <div class="mb-2">
                    <div class="extra-small text-secondary fw-semibold mb-1"><i class="bi bi-chat-left-dots me-1"></i>Ajukan Pertanyaan Lanjutan Terkait Soal Ini:</div>
                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 extra-small rounded-pill" onclick="sendTutorFollowUp('Jelaskan langkah-langkah penyelesaiannya secara detail.')">📝 Rincian Langkah</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 extra-small rounded-pill" onclick="sendTutorFollowUp('Mengapa pilihan jawaban lain salah?')">❌ Analisis Opsi Lain</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 extra-small rounded-pill" onclick="sendTutorFollowUp('Beri tips atau rumus cepat untuk mengingat konsep soal ini.')">💡 Tips & Rumus</button>
                    </div>
                </div>
            </div>

            <!-- MODAL FOOTER FORM -->
            <div class="modal-footer bg-white p-3 border-top d-flex flex-column gap-1">
                <form id="aiTutorForm" onsubmit="handleTutorFollowUpSubmit(event)" class="d-flex gap-2 w-100">
                    <input type="text" id="aiTutorInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Tanyakan hal yang belum kamu pahami dari soal ini..." autocomplete="off">
                    <button type="submit" id="aiTutorSendBtn" class="btn btn-primary btn-sm rounded-pill px-3 d-flex align-items-center gap-1">
                        <i class="bi bi-send-fill"></i> Tanya
                    </button>
                </form>
                <div class="text-center w-100">
                    <small class="text-muted extra-small" style="font-size: 0.7rem;"><i class="bi bi-shield-check me-1 text-success"></i>Tutor AI dibatasi secara ketat hanya membahas soal ini.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.tutor-explanation-body h1, .tutor-explanation-body h2, .tutor-explanation-body h3, .tutor-explanation-body h4 {
    font-size: 1.05rem;
    font-weight: 700;
    color: #0d6efd;
    margin-top: 14px;
    margin-bottom: 6px;
}
.tutor-explanation-body ul, .tutor-explanation-body ol {
    padding-left: 20px;
    margin-bottom: 10px;
}
.tutor-explanation-body li {
    margin-bottom: 4px;
}
.tutor-explanation-body strong {
    color: #212529;
}
.tutor-explanation-body blockquote {
    border-left: 4px solid #0d6efd;
    padding-left: 12px;
    color: #495057;
    margin: 10px 0;
    font-style: italic;
}
</style>

<script>
(function() {
    let currentTutorSessionId = null;
    let currentTutorQuestionId = null;
    let tutorModalInstance = null;

    window.closeAITutorModal = function() {
        const modalEl = document.getElementById('aiTutorModal');
        if (tutorModalInstance) {
            tutorModalInstance.hide();
        } else if (modalEl) {
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
            document.body.classList.remove('modal-open');
            document.querySelector('.modal-backdrop')?.remove();
        }
    };

    window.openAITutorForQuestion = async function(sessionId, questionId, qNum, qText, studentAns, correctAns, isCorrect, optionsJson) {
        currentTutorSessionId = sessionId;
        currentTutorQuestionId = questionId;

        const modalEl = document.getElementById('aiTutorModal');
        if (!modalEl) return;

        if (window.bootstrap && bootstrap.Modal) {
            tutorModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            tutorModalInstance.show();
        } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
            modalEl.removeAttribute('aria-hidden');
            document.body.classList.add('modal-open');
        }

        // Fill Question Metadata
        const numBadge = document.getElementById('aiTutorQNumBadge');
        if (numBadge) numBadge.textContent = 'Soal No. ' + (qNum || '-');

        const qTextEl = document.getElementById('aiTutorQText');
        if (qTextEl) qTextEl.textContent = qText || '-';

        const studentBadge = document.getElementById('aiTutorStudentAnsBadge');
        if (studentBadge) {
            if (isCorrect) {
                studentBadge.className = 'badge bg-success';
                studentBadge.textContent = 'Jawaban Kamu: ' + (studentAns || '-') + ' (BENAR)';
            } else {
                studentBadge.className = 'badge bg-danger';
                studentBadge.textContent = 'Jawaban Kamu: ' + (studentAns || '(Kosong)') + ' (SALAH)';
            }
        }

        const correctBadge = document.getElementById('aiTutorCorrectAnsBadge');
        if (correctBadge) {
            correctBadge.textContent = 'Kunci Jawaban: ' + (correctAns || '-');
        }

        // Render options if available
        const optContainer = document.getElementById('aiTutorQOptions');
        if (optContainer) {
            optContainer.innerHTML = '';
            if (optionsJson && typeof optionsJson === 'object') {
                for (const [k, v] of Object.entries(optionsJson)) {
                    if (v) {
                        const optDiv = document.createElement('div');
                        optDiv.className = 'extra-small py-0.5';
                        optDiv.innerHTML = `<strong>${k}.</strong> ${v}`;
                        optContainer.appendChild(optDiv);
                    }
                }
            }
        }

        // Load Initial AI Tutor Explanation
        await fetchTutorExplanation('');
    };

    window.sendTutorFollowUp = function(promptText) {
        const input = document.getElementById('aiTutorInput');
        if (input) {
            input.value = promptText;
            document.getElementById('aiTutorForm')?.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    };

    window.handleTutorFollowUpSubmit = async function(e) {
        if (e) e.preventDefault();
        const input = document.getElementById('aiTutorInput');
        if (!input) return;
        const text = input.value.trim();
        if (!text) return;
        input.value = '';
        await fetchTutorExplanation(text);
    };

    async function fetchTutorExplanation(customQuestion) {
        const loading = document.getElementById('aiTutorLoading');
        const content = document.getElementById('aiTutorContent');
        const input = document.getElementById('aiTutorInput');
        const sendBtn = document.getElementById('aiTutorSendBtn');

        if (loading) loading.classList.remove('d-none');
        if (content && !customQuestion) {
            content.classList.add('d-none');
        }
        if (input) input.disabled = true;
        if (sendBtn) sendBtn.disabled = true;

        try {
            const targetUrl = (window.APP_URL ? window.APP_URL.replace(/\/$/, '') : '') + '/api/chat/explain-question';
            const res = await fetch(targetUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    session_id: currentTutorSessionId,
                    question_id: currentTutorQuestionId,
                    question: customQuestion
                })
            });

            const data = await res.json();
            if (loading) loading.classList.add('d-none');
            if (content) content.classList.remove('d-none');

            if (data.status === 'success' && data.explanation) {
                if (customQuestion) {
                    const followUpDiv = document.createElement('div');
                    followUpDiv.className = 'mt-3 pt-3 border-top';
                    followUpDiv.innerHTML = `<div class="fw-bold text-secondary mb-1"><i class="bi bi-question-circle me-1"></i>Pertanyaan: "${customQuestion}"</div><div>${formatMarkdownSimple(data.explanation)}</div>`;
                    content.appendChild(followUpDiv);
                } else {
                    content.innerHTML = formatMarkdownSimple(data.explanation);
                }
            } else {
                if (content) content.innerHTML = `<div class="alert alert-warning mb-0">${data.message || 'Gagal memuat pembahasan AI.'}</div>`;
            }
        } catch (err) {
            if (loading) loading.classList.add('d-none');
            if (content) {
                content.classList.remove('d-none');
                content.innerHTML = `<div class="alert alert-danger mb-0">Tidak dapat terhubung ke AI Tutor. Periksa koneksi internet Anda.</div>`;
            }
        } finally {
            if (input) {
                input.disabled = false;
                input.focus();
            }
            if (sendBtn) sendBtn.disabled = false;
        }
    }

    function formatMarkdownSimple(text) {
        if (!text) return '';
        let escaped = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");

        // Headers
        escaped = escaped.replace(/^### (.*$)/gim, '<h4>$1</h4>');
        escaped = escaped.replace(/^## (.*$)/gim, '<h3>$1</h3>');
        escaped = escaped.replace(/^# (.*$)/gim, '<h2>$1</h2>');

        // Bold & Italic
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');

        // Bullet lists
        escaped = escaped.replace(/^\* (.*$)/gim, '<li>$1</li>');
        escaped = escaped.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');

        // Line breaks
        escaped = escaped.replace(/\n\n/g, '<br><br>').replace(/\n/g, '<br>');

        return escaped;
    }
})();
</script>
