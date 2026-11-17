<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-robot text-primary me-2"></i>Konfigurasi AI & Model
        </h4>
        <p class="text-muted small mb-0">Kelola API Key penyedia AI (Google Gemini, GroqCloud, NVIDIA NIM, OpenAI) dan atur model kecerdasan buatan.</p>
    </div>
    <div>
        <a href="<?= base_url('admin/settings') ?>" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="bi bi-arrow-left me-1"></i> Pengaturan Sistem
        </a>
    </div>
</div>

<!-- Flash Message Notifications -->
<?php if ($flashSuccess = \App\Core\Session::getFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i> <?= $flashSuccess ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($flashError = \App\Core\Session::getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i> <?= $flashError ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Test Result Container (AJAX) -->
<div id="testResultBox" style="display: none;" class="alert alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4" role="alert">
    <div class="d-flex align-items-center gap-2">
        <span id="testResultIcon"></span>
        <div>
            <strong id="testResultTitle"></strong>
            <div id="testResultMessage" class="small mt-1"></div>
        </div>
    </div>
    <button type="button" class="btn-close" onclick="document.getElementById('testResultBox').style.display='none'"></button>
</div>

<form action="<?= base_url('admin/ai-config/update') ?>" method="POST" id="formAiConfig">
    <?= csrf_field() ?>

    <!-- 1. STATUS & PROVIDER SELECTION CARD -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 fs-6">
                <i class="bi bi-check2-circle text-success me-2"></i>Status & Pemilihan Provider Aktif
            </h5>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" name="ai_enabled" value="1" id="switchAiEnabled" <?= $aiEnabled ? 'checked' : '' ?>>
                <label class="form-check-label small fw-semibold" for="switchAiEnabled">
                    <?= $aiEnabled ? '<span class="text-success">Fitur AI Aktif</span>' : '<span class="text-danger">Fitur AI Nonaktif</span>' ?>
                </label>
            </div>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted mb-3">Pilih provider yang akan digunakan oleh Chatbot U-Line, AI Tutor, dan Proctoring Engine:</p>
            <div class="row g-3">
                <!-- Provider 1: NVIDIA NIM -->
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="card h-100 border-2 rounded-4 p-3 cursor-pointer text-decoration-none <?= $activeProvider === 'nvidia' ? 'border-primary bg-primary-subtle' : 'border-light-subtle bg-light' ?>" style="cursor: pointer;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-dark text-white px-2.5 py-1 rounded-pill">NVIDIA NIM</span>
                            <input type="radio" name="ai_provider" value="nvidia" class="form-check-input mt-0" <?= $activeProvider === 'nvidia' ? 'checked' : '' ?>>
                        </div>
                        <div class="fw-bold text-dark fs-6 mb-1">NVIDIA NIM</div>
                        <small class="text-muted extra-small">Enterprise endpoints (Llama 3.2 Vision, Gemma 3, DeepSeek R1).</small>
                        <div class="mt-2">
                            <?php if (!empty($providers['nvidia']['api_key'])): ?>
                                <span class="badge bg-success-subtle text-success extra-small"><i class="bi bi-key-fill me-1"></i>API Key Tersimpan</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary extra-small">Belum Ada Key</span>
                            <?php endif; ?>
                        </div>
                    </label>
                </div>

                <!-- Provider 2: Google Gemini -->
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="card h-100 border-2 rounded-4 p-3 cursor-pointer text-decoration-none <?= $activeProvider === 'gemini' ? 'border-primary bg-primary-subtle' : 'border-light-subtle bg-light' ?>" style="cursor: pointer;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-primary text-white px-2.5 py-1 rounded-pill">Google Gemini</span>
                            <input type="radio" name="ai_provider" value="gemini" class="form-check-input mt-0" <?= $activeProvider === 'gemini' ? 'checked' : '' ?>>
                        </div>
                        <div class="fw-bold text-dark fs-6 mb-1">Google Gemini</div>
                        <small class="text-muted extra-small">Google AI Studio (Gemini 2.0 Flash, Gemini 1.5 Pro) berkecepatan tinggi.</small>
                        <div class="mt-2">
                            <?php if (!empty($providers['gemini']['api_key'])): ?>
                                <span class="badge bg-success-subtle text-success extra-small"><i class="bi bi-key-fill me-1"></i>API Key Tersimpan</span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning extra-small">Belum Ada Key</span>
                            <?php endif; ?>
                        </div>
                    </label>
                </div>

                <!-- Provider 3: GroqCloud -->
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="card h-100 border-2 rounded-4 p-3 cursor-pointer text-decoration-none <?= $activeProvider === 'groq' ? 'border-primary bg-primary-subtle' : 'border-light-subtle bg-light' ?>" style="cursor: pointer;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill">GroqCloud</span>
                            <input type="radio" name="ai_provider" value="groq" class="form-check-input mt-0" <?= $activeProvider === 'groq' ? 'checked' : '' ?>>
                        </div>
                        <div class="fw-bold text-dark fs-6 mb-1">GroqCloud LPU</div>
                        <small class="text-muted extra-small">Akselerasi chip LPU super cepat (Llama 3.3 70B, Llama 3.1 8B).</small>
                        <div class="mt-2">
                            <?php if (!empty($providers['groq']['api_key'])): ?>
                                <span class="badge bg-success-subtle text-success extra-small"><i class="bi bi-key-fill me-1"></i>API Key Tersimpan</span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning extra-small">Belum Ada Key</span>
                            <?php endif; ?>
                        </div>
                    </label>
                </div>

                <!-- Provider 4: OpenAI -->
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="card h-100 border-2 rounded-4 p-3 cursor-pointer text-decoration-none <?= $activeProvider === 'openai' ? 'border-primary bg-primary-subtle' : 'border-light-subtle bg-light' ?>" style="cursor: pointer;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-success text-white px-2.5 py-1 rounded-pill">OpenAI</span>
                            <input type="radio" name="ai_provider" value="openai" class="form-check-input mt-0" <?= $activeProvider === 'openai' ? 'checked' : '' ?>>
                        </div>
                        <div class="fw-bold text-dark fs-6 mb-1">OpenAI</div>
                        <small class="text-muted extra-small">Model flagship industri (GPT-4o Mini, GPT-4o).</small>
                        <div class="mt-2">
                            <?php if (!empty($providers['openai']['api_key'])): ?>
                                <span class="badge bg-success-subtle text-success extra-small"><i class="bi bi-key-fill me-1"></i>API Key Tersimpan</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary extra-small">Belum Ada Key</span>
                            <?php endif; ?>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. API KEYS & MODEL SELECTION PER PROVIDER -->
    <div class="row g-4 mb-4">
        
        <!-- A. GOOGLE GEMINI SETTINGS -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-gemini text-primary fs-5"></i>
                        <h6 class="fw-bold mb-0">Google Gemini (Google AI Studio)</h6>
                    </div>
                    <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener" class="extra-small text-decoration-none text-primary">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Dapatkan API Key
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Gemini API Key</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control bg-light border-start-0 ps-0 font-monospace" id="geminiApiKey" name="gemini_api_key" placeholder="Contoh: AIzaSy..." value="<?= e($providers['gemini']['api_key']) ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('geminiApiKey')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted extra-small">Kunci API dari Google AI Studio (gratis dengan kuota tinggi).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Pilihan Model Gemini</label>
                        <select class="form-select" name="gemini_model" id="geminiModel">
                            <option value="gemini-3.5-flash-lite" <?= $providers['gemini']['model'] === 'gemini-3.5-flash-lite' ? 'selected' : '' ?>>gemini-3.5-flash-lite (Direkomendasikan - Sangat Cepat & Efisien)</option>
                            <option value="gemini-flash-lite-latest" <?= $providers['gemini']['model'] === 'gemini-flash-lite-latest' ? 'selected' : '' ?>>gemini-flash-lite-latest (Rilis Terkini Gemini Flash-Lite)</option>
                            <option value="gemini-3.8-flash" <?= $providers['gemini']['model'] === 'gemini-3.8-flash' ? 'selected' : '' ?>>gemini-3.8-flash (Generasi Baru Multimodal)</option>
                            <option value="gemini-pro-latest" <?= $providers['gemini']['model'] === 'gemini-pro-latest' ? 'selected' : '' ?>>gemini-pro-latest (Penalaran Flagship)</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="testConnection('gemini', this)">
                            <i class="bi bi-broadcast me-1"></i> Uji Koneksi Gemini
                        </button>
                    </div>
                    <div id="geminiInlineResult" class="mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>

        <!-- B. GROQCLOUD SETTINGS -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-lightning-charge-fill text-warning fs-5"></i>
                        <h6 class="fw-bold mb-0">GroqCloud (Ultra-Fast LPU Inference)</h6>
                    </div>
                    <a href="https://console.groq.com/keys" target="_blank" rel="noopener" class="extra-small text-decoration-none text-warning">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Dapatkan API Key
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Groq API Key</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control bg-light border-start-0 ps-0 font-monospace" id="groqApiKey" name="groq_api_key" placeholder="Contoh: gsk_..." value="<?= e($providers['groq']['api_key']) ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('groqApiKey')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted extra-small">API Key dari Groq Console (inferensi ratusan token per detik).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Pilihan Model Groq</label>
                        <select class="form-select" name="groq_model" id="groqModel">
                            <option value="qwen/qwen3.8-27b" <?= $providers['groq']['model'] === 'qwen/qwen3.8-27b' ? 'selected' : '' ?>>qwen/qwen3.8-27b (Direkomendasikan - Sangat Cepat & Cerdas)</option>
                            <option value="openai/gpt-oss-20b" <?= $providers['groq']['model'] === 'openai/gpt-oss-20b' ? 'selected' : '' ?>>openai/gpt-oss-20b (Ultra Cepat / Ringan)</option>
                            <option value="openai/gpt-oss-120b" <?= $providers['groq']['model'] === 'openai/gpt-oss-120b' ? 'selected' : '' ?>>openai/gpt-oss-120b (Kapasitas Besar 120B)</option>
                            <option value="allam-2-7b" <?= $providers['groq']['model'] === 'allam-2-7b' ? 'selected' : '' ?>>allam-2-7b (Ringan & Cepat)</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3" onclick="testConnection('groq', this)">
                            <i class="bi bi-broadcast me-1"></i> Uji Koneksi Groq
                        </button>
                    </div>
                    <div id="groqInlineResult" class="mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>

        <!-- C. NVIDIA NIM SETTINGS -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cpu text-dark fs-5"></i>
                        <h6 class="fw-bold mb-0">NVIDIA NIM (National AI Endpoints)</h6>
                    </div>
                    <a href="https://build.nvidia.com/" target="_blank" rel="noopener" class="extra-small text-decoration-none text-dark">
                        <i class="bi bi-box-arrow-up-right me-1"></i> build.nvidia.com
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">NVIDIA API Key</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control bg-light border-start-0 ps-0 font-monospace" id="nvidiaApiKey" name="nvidia_api_key" placeholder="Contoh: nvapi-..." value="<?= e($providers['nvidia']['api_key']) ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('nvidiaApiKey')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Model Utama (Primary)</label>
                        <select class="form-select" name="nvidia_model" id="nvidiaModel">
                            <option value="minimaxai/minimax-m3.1-flash" <?= $providers['nvidia']['model'] === 'minimaxai/minimax-m3.1-flash' ? 'selected' : '' ?>>minimaxai/minimax-m3.1-flash (Direkomendasikan - Sangat Cepat & Cerdas)</option>
                            <option value="nvidia/nemotron-3.5-lightning-30b-a3b" <?= $providers['nvidia']['model'] === 'nvidia/nemotron-3.5-lightning-30b-a3b' ? 'selected' : '' ?>>nvidia/nemotron-3.5-lightning-30b-a3b (Nemotron 30B High-Reasoning)</option>
                            <option value="meta/llama-guard-4-12b" <?= $providers['nvidia']['model'] === 'meta/llama-guard-4-12b' ? 'selected' : '' ?>>meta/llama-guard-4-12b (Content Safety Guardrail)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Model Cadangan (Fallback)</label>
                        <select class="form-select" name="nvidia_fallback_model">
                            <option value="nvidia/nemotron-3.5-lightning-30b-a3b" <?= $providers['nvidia']['fallback_model'] === 'nvidia/nemotron-3.5-lightning-30b-a3b' ? 'selected' : '' ?>>nvidia/nemotron-3.5-lightning-30b-a3b</option>
                            <option value="minimaxai/minimax-m3.1-flash" <?= $providers['nvidia']['fallback_model'] === 'minimaxai/minimax-m3.1-flash' ? 'selected' : '' ?>>minimaxai/minimax-m3.1-flash</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3" onclick="testConnection('nvidia', this)">
                            <i class="bi bi-broadcast me-1"></i> Uji Koneksi NVIDIA
                        </button>
                    </div>
                    <div id="nvidiaInlineResult" class="mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>

        <!-- D. OPENAI SETTINGS -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-patch-check-fill text-success fs-5"></i>
                        <h6 class="fw-bold mb-0">OpenAI</h6>
                    </div>
                    <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener" class="extra-small text-decoration-none text-success">
                        <i class="bi bi-box-arrow-up-right me-1"></i> platform.openai.com
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">OpenAI API Key</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control bg-light border-start-0 ps-0 font-monospace" id="openaiApiKey" name="openai_api_key" placeholder="Contoh: sk-..." value="<?= e($providers['openai']['api_key']) ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('openaiApiKey')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Pilihan Model OpenAI</label>
                        <select class="form-select" name="openai_model" id="openaiModel">
                            <option value="gpt-4o-mini" <?= $providers['openai']['model'] === 'gpt-4o-mini' ? 'selected' : '' ?>>gpt-4o-mini (Efisien, Cepat, Vision)</option>
                            <option value="gpt-4o" <?= $providers['openai']['model'] === 'gpt-4o' ? 'selected' : '' ?>>gpt-4o (Flagship Multimodal)</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" onclick="testConnection('openai', this)">
                            <i class="bi bi-broadcast me-1"></i> Uji Koneksi OpenAI
                        </button>
                    </div>
                    <div id="openaiInlineResult" class="mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- 3. PARAMETER & GUARDRAILS ACCORDION -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 fs-6">
                <i class="bi bi-sliders text-secondary me-2"></i>Parameter Lanjutan & Kontrol Keamanan (Guardrails)
            </h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Temperature (0.0 - 1.0)</label>
                    <input type="number" step="0.05" min="0" max="1" name="nvidia_temperature" class="form-control" value="<?= e($providers['nvidia']['temperature']) ?>" required>
                    <small class="text-muted extra-small">Nilai rendah (0.1) untuk mencegah halusinasi ujian.</small>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Top-P</label>
                    <input type="number" step="0.05" min="0" max="1" name="nvidia_top_p" class="form-control" value="<?= e($providers['nvidia']['top_p']) ?>" required>
                    <small class="text-muted extra-small">Sampling diversity filter (default: 0.7).</small>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Max Tokens Output</label>
                    <input type="number" step="50" min="100" max="4000" name="nvidia_max_tokens" class="form-control" value="<?= e($providers['nvidia']['max_tokens']) ?>" required>
                    <small class="text-muted extra-small">Batas panjang maksimal respon (default: 1000).</small>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Request Timeout (detik)</label>
                    <input type="number" min="3" max="60" name="nvidia_timeout" class="form-control" value="<?= e($providers['nvidia']['timeout']) ?>" required>
                    <small class="text-muted extra-small">Batas waktu tunggu koneksi (default: 8s).</small>
                </div>
            </div>

            <div class="alert alert-light border rounded-3 mt-3 mb-0 small text-muted">
                <i class="bi bi-shield-check text-success me-1"></i><strong>Academic Guardrails Aktif:</strong> Sistem secara otomatis memblokir pembocoran kunci jawaban ujian, percobaan jailbreak, dan larangan percodingan. Jawaban pertanyaan sistem otomatis ter-grounding dengan data MySQL database.
            </div>
        </div>
    </div>

    <!-- 4. SUBMIT BUTTON -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-5">
        <span class="text-muted extra-small">
            <i class="bi bi-info-circle me-1"></i>Perubahan akan langsung disimpan ke file <code>.env</code> dan berlaku secara realtime.
        </span>
        <button type="submit" class="btn btn-primary px-4 py-2.5 rounded-3 fw-semibold shadow-sm">
            <i class="bi bi-save-fill me-1"></i> Simpan Konfigurasi AI & Model
        </button>
    </div>

</form>

<!-- AJAX JavaScript for Testing AI Connection & Password Toggle -->
<script>
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
}

function testConnection(provider, btnEl) {
    const box = document.getElementById('testResultBox');
    const icon = document.getElementById('testResultIcon');
    const title = document.getElementById('testResultTitle');
    const msg = document.getElementById('testResultMessage');
    const inlineBox = document.getElementById(provider + 'InlineResult');

    let apiKey = '';
    let model = '';

    if (provider === 'gemini') {
        apiKey = document.getElementById('geminiApiKey').value;
        model = document.getElementById('geminiModel').value;
    } else if (provider === 'groq') {
        apiKey = document.getElementById('groqApiKey').value;
        model = document.getElementById('groqModel').value;
    } else if (provider === 'nvidia') {
        apiKey = document.getElementById('nvidiaApiKey').value;
        model = document.getElementById('nvidiaModel').value;
    } else if (provider === 'openai') {
        apiKey = document.getElementById('openaiApiKey').value;
        model = document.getElementById('openaiModel').value;
    }

    if (!apiKey.trim()) {
        const errorAlert = '<div class="alert alert-warning py-2.5 px-3 small rounded-3 mb-0 border-0 shadow-sm"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Harap masukkan <strong>API Key ' + provider.toUpperCase() + '</strong> terlebih dahulu sebelum melakukan pengujian.</div>';
        if (inlineBox) {
            inlineBox.style.display = 'block';
            inlineBox.innerHTML = errorAlert;
        }
        return;
    }

    // Button loading state
    let originalBtnHtml = '';
    if (btnEl) {
        originalBtnHtml = btnEl.innerHTML;
        btnEl.disabled = true;
        btnEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menguji...';
    }

    // Inline loading state
    if (inlineBox) {
        inlineBox.style.display = 'block';
        inlineBox.innerHTML = '<div class="alert alert-info py-2.5 px-3 small rounded-3 mb-0 border-0 shadow-sm d-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm text-primary"></span><span>Menguji koneksi ke <strong>' + provider.toUpperCase() + '</strong> (Model: <code>' + model + '</code>)...</span></div>';
    }

    // Top alert update
    if (box) {
        box.style.display = 'block';
        box.className = 'alert alert-info alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4';
        if (icon) icon.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
        if (title) title.innerText = 'Menguji Koneksi ke ' + provider.toUpperCase() + '...';
        if (msg) msg.innerText = 'Mengirim permintaan uji chat completion dengan model: ' + model;
    }

    const csrfTokenEl = document.querySelector('input[name="_csrf_token"]');
    const csrfToken = csrfTokenEl ? csrfTokenEl.value : '';

    const formData = new FormData();
    formData.append('_csrf_token', csrfToken);
    formData.append('provider', provider);
    formData.append('api_key', apiKey.trim());
    formData.append('model', model.trim());

    fetch('<?= base_url("admin/ai-config/test") ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const successHtml = '<div class="alert alert-success py-2.5 px-3 small rounded-3 mb-0 border-0 shadow-sm">' +
                '<div class="fw-bold mb-1 text-success d-flex align-items-center gap-1.5"><i class="bi bi-check-circle-fill"></i> Koneksi Berhasil (' + data.provider + ')</div>' +
                '<div><strong>Model Terhubung:</strong> <code>' + data.model + '</code></div>' +
                '<div class="mt-2 p-2 bg-white rounded-2 border small text-dark"><strong>Respons AI:</strong> <em>"' + data.reply + '"</em></div>' +
                '</div>';

            if (inlineBox) {
                inlineBox.innerHTML = successHtml;
            }
            if (box) {
                box.className = 'alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4';
                if (icon) icon.innerHTML = '<i class="bi bi-check-circle-fill text-success fs-5"></i>';
                if (title) title.innerText = 'Koneksi Berhasil (' + data.provider + ')';
                if (msg) msg.innerHTML = '<strong>Model:</strong> ' + data.model + '<br><strong>Respons AI:</strong> <em>"' + data.reply + '"</em>';
            }
        } else {
            const failureHtml = '<div class="alert alert-danger py-2.5 px-3 small rounded-3 mb-0 border-0 shadow-sm">' +
                '<div class="fw-bold mb-1 text-danger d-flex align-items-center gap-1.5"><i class="bi bi-x-circle-fill"></i> Koneksi Gagal (' + provider.toUpperCase() + ')</div>' +
                '<div>' + (data.message || 'Gagal menghubungi penyedia AI.') + '</div>' +
                '</div>';

            if (inlineBox) {
                inlineBox.innerHTML = failureHtml;
            }
            if (box) {
                box.className = 'alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4';
                if (icon) icon.innerHTML = '<i class="bi bi-x-circle-fill text-danger fs-5"></i>';
                if (title) title.innerText = 'Koneksi Gagal (' + provider.toUpperCase() + ')';
                if (msg) msg.innerText = data.message || 'Gagal menghubungi penyedia AI.';
            }
        }
    })
    .catch(err => {
        const networkErrorHtml = '<div class="alert alert-danger py-2.5 px-3 small rounded-3 mb-0 border-0 shadow-sm">' +
            '<div class="fw-bold mb-1 text-danger d-flex align-items-center gap-1.5"><i class="bi bi-exclamation-triangle-fill"></i> Terjadi Kesalahan Jaringan</div>' +
            '<div>' + (err.message || 'Tidak dapat terhubung ke server backend.') + '</div>' +
            '</div>';

        if (inlineBox) {
            inlineBox.innerHTML = networkErrorHtml;
        }
        if (box) {
            box.className = 'alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4';
            if (icon) icon.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>';
            if (title) title.innerText = 'Terjadi Kesalahan Jaringan';
            if (msg) msg.innerText = err.message || 'Tidak dapat terhubung ke server.';
        }
    })
    .finally(() => {
        if (btnEl && originalBtnHtml) {
            btnEl.disabled = false;
            btnEl.innerHTML = originalBtnHtml;
        }
    });
}
</script>

