<?php
$activeTab = $_GET['tab'] ?? 'password';
$activeStep = $_GET['step'] ?? 'input';
$sessionWa = \App\Core\Session::get('whatsapp_login_state', []);
$hasActiveOtp = !empty($sessionWa['user_id']) && ($activeStep === 'verify' || !empty($_GET['step']));
?>
<div class="card card-auth border-0 shadow-lg rounded-4 overflow-hidden">
    <div class="card-header bg-dark text-white p-4 text-center border-0">
        <div class="brand-avatar mx-auto mb-3 bg-gradient-primary rounded-circle d-flex align-items-center justify-content-center shadow">
            <i class="bi bi-shield-lock-fill fs-2 text-white"></i>
        </div>
        <h4 class="fw-bold mb-1 tracking-tight text-white">Portal Masuk Sistem</h4>
        <p class="text-secondary small mb-0">AI Online Examination & Proctoring System</p>
    </div>

    <div class="card-body p-4 p-md-4 pt-4 bg-white">
        <!-- Auth Method Selector Tabs -->
        <ul class="nav nav-pills nav-fill mb-4 p-1 bg-light rounded-3" id="authTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= ($activeTab !== 'wa') ? 'active' : '' ?> py-2 rounded-2 fw-semibold small" id="tab-password-btn" data-bs-toggle="pill" data-bs-target="#tab-password" type="button" role="tab" aria-controls="tab-password" aria-selected="<?= ($activeTab !== 'wa') ? 'true' : 'false' ?>">
                    <i class="bi bi-key-fill me-1"></i> Kata Sandi
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= ($activeTab === 'wa') ? 'active' : '' ?> py-2 rounded-2 fw-semibold small text-success" id="tab-whatsapp-btn" data-bs-toggle="pill" data-bs-target="#tab-whatsapp" type="button" role="tab" aria-controls="tab-whatsapp" aria-selected="<?= ($activeTab === 'wa') ? 'true' : 'false' ?>">
                    <i class="bi bi-whatsapp me-1"></i> WhatsApp OTP
                </button>
            </li>
        </ul>

        <div class="tab-content" id="authTabsContent">
            <!-- TAB 1: Password Login Form (Existing Standar) -->
            <div class="tab-pane fade <?= ($activeTab !== 'wa') ? 'show active' : '' ?>" id="tab-password" role="tabpanel" aria-labelledby="tab-password-btn">
                <form action="<?= base_url('login') ?>" method="POST" id="loginForm" novalidate>
                    <?= csrf_field() ?>

                    <!-- Username / Email Input -->
                    <div class="mb-3">
                        <label for="identifier" class="form-label small fw-semibold text-secondary">Username atau Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person-fill"></i></span>
                            <input type="text" class="form-control bg-light border-start-0 ps-0" id="identifier" name="identifier" autocomplete="username" required autofocus>
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label small fw-semibold text-secondary mb-0">Kata Sandi (Password) <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-link p-0 text-decoration-none extra-small text-muted" id="togglePasswordBtn">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i> Lihat
                            </button>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key-fill"></i></span>
                            <input type="password" class="form-control bg-light border-start-0 ps-0" id="password" name="password" autocomplete="current-password" required>
                        </div>
                    </div>

                    <!-- Remember Option -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="rememberMe" name="remember" checked>
                        <label class="form-check-label extra-small text-secondary user-select-none" for="rememberMe">
                            Ingat Saya
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary w-100 py-2.5 fw-semibold rounded-3 shadow-sm mt-1" id="loginSubmitBtn">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang
                    </button>
                </form>
            </div>

            <!-- TAB 2: WhatsApp OTP Login Form (Fonnte API) -->
            <div class="tab-pane fade <?= ($activeTab === 'wa') ? 'show active' : '' ?>" id="tab-whatsapp" role="tabpanel" aria-labelledby="tab-whatsapp-btn">
                
                <!-- Dynamic Alert for WA Login -->
                <div id="waAlertContainer" style="display: none;" class="alert alert-dismissible fade show border-0 rounded-3 mb-3 p-3 small" role="alert">
                    <span id="waAlertText"></span>
                    <button type="button" class="btn-close" onclick="document.getElementById('waAlertContainer').style.display='none'"></button>
                </div>

                <!-- STEP 1: Input Nomor WhatsApp -->
                <div id="waStepInput" style="<?= $hasActiveOtp ? 'display: none;' : 'display: block;' ?>">
                    <form action="<?= base_url('auth/whatsapp/send-otp') ?>" method="POST" id="formSendWaOtp" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="waPhone" class="form-label small fw-semibold text-secondary">
                                Nomor WhatsApp Terdaftar <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-success"><i class="bi bi-whatsapp"></i></span>
                                <input type="tel" class="form-control bg-light border-start-0 ps-0 font-monospace" id="waPhone" name="phone_number" placeholder="Contoh: 08123456789 atau 628123456789" required autocomplete="tel" value="<?= e($sessionWa['phone'] ?? '') ?>">
                            </div>
                            <div class="form-text extra-small text-muted mt-1">
                                <i class="bi bi-shield-lock text-success me-1"></i>
                                Masukkan nomor WhatsApp yang terdaftar pada akun Admin, Guru, atau Peserta Didik.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2.5 fw-semibold rounded-3 shadow-sm mt-2" id="btnSubmitWaPhone">
                            <span class="spinner-border spinner-border-sm me-1" id="spinnerSendWa" style="display: none;" role="status" aria-hidden="true"></span>
                            <i class="bi bi-send-fill me-1" id="iconSendWa"></i> Kirim Kode OTP WhatsApp
                        </button>
                    </form>
                </div>

                <!-- STEP 2: Input 6-Digit OTP -->
                <div id="waStepVerify" style="<?= $hasActiveOtp ? 'display: block;' : 'display: none;' ?>">
                    <div class="text-center mb-3">
                        <div class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill mb-2">
                            <i class="bi bi-check-circle-fill me-1"></i> Kode OTP Telah Dikirim
                        </div>
                        <p class="text-muted extra-small mb-1">
                            Masukkan 6 digit kode OTP yang diterima melalui WhatsApp:
                        </p>
                        <div class="fw-bold font-monospace text-dark small" id="waTargetPhoneDisplay">
                            <i class="bi bi-whatsapp text-success me-1"></i><?= e($sessionWa['masked_phone'] ?? '') ?>
                        </div>
                    </div>

                    <form action="<?= base_url('auth/whatsapp/verify-otp') ?>" method="POST" id="formVerifyWaOtp" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" id="waUserId" value="<?= (int)($sessionWa['user_id'] ?? 0) ?>">

                        <div class="mb-3">
                            <label for="otpCode" class="form-label extra-small fw-bold text-uppercase text-secondary tracking-wider text-center d-block mb-2">
                                6 DIGIT KODE OTP
                            </label>
                            <input type="text" class="form-control form-control-lg bg-light text-center font-monospace fw-bold" id="otpCode" name="otp_code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="• • • • • •" style="letter-spacing: 0.6rem; font-size: 1.5rem;" required autocomplete="one-time-code">
                            <div class="form-text extra-small text-muted text-center mt-1">
                                Berlaku selama 5 menit. Jangan bagikan kode ini kepada siapa pun.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2.5 fw-semibold rounded-3 shadow-sm mt-1" id="btnSubmitVerifyOtp">
                            <span class="spinner-border spinner-border-sm me-1" id="spinnerVerifyWa" style="display: none;" role="status" aria-hidden="true"></span>
                            <i class="bi bi-box-arrow-in-right me-1" id="iconVerifyWa"></i> Verifikasi & Masuk
                        </button>
                    </form>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-link text-decoration-none extra-small text-secondary p-0" id="btnChangeWaPhone">
                            <i class="bi bi-arrow-left me-1"></i> Ganti Nomor
                        </button>
                        <button type="button" class="btn btn-link text-decoration-none extra-small text-primary p-0 fw-semibold" id="btnResendWaOtp" disabled>
                            <i class="bi bi-arrow-clockwise me-1"></i> Kirim Ulang (<span id="resendTimer">60</span>s)
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // 1. Password Visibility Toggle
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const toggleIcon = document.getElementById('togglePasswordIcon');

    if (toggleBtn && passwordInput && toggleIcon) {
        toggleBtn.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    }

    // 2. WhatsApp OTP Dynamic Flow
    const waAlert = document.getElementById('waAlertContainer');
    const waAlertText = document.getElementById('waAlertText');
    const waStepInput = document.getElementById('waStepInput');
    const waStepVerify = document.getElementById('waStepVerify');
    const formSendWaOtp = document.getElementById('formSendWaOtp');
    const formVerifyWaOtp = document.getElementById('formVerifyWaOtp');
    const waPhoneInput = document.getElementById('waPhone');
    const otpCodeInput = document.getElementById('otpCode');
    const waUserIdInput = document.getElementById('waUserId');
    const waTargetDisplay = document.getElementById('waTargetPhoneDisplay');
    const btnSubmitWaPhone = document.getElementById('btnSubmitWaPhone');
    const spinnerSendWa = document.getElementById('spinnerSendWa');
    const iconSendWa = document.getElementById('iconSendWa');
    const btnSubmitVerifyOtp = document.getElementById('btnSubmitVerifyOtp');
    const spinnerVerifyWa = document.getElementById('spinnerVerifyWa');
    const iconVerifyWa = document.getElementById('iconVerifyWa');
    const btnChangeWaPhone = document.getElementById('btnChangeWaPhone');
    const btnResendWaOtp = document.getElementById('btnResendWaOtp');
    const resendTimer = document.getElementById('resendTimer');

    let countdownInterval = null;

    function showAlert(msg, isSuccess = false) {
        if (!waAlert || !waAlertText) return;
        waAlert.className = 'alert alert-dismissible fade show border-0 rounded-3 mb-3 p-3 small ' + 
                            (isSuccess ? 'alert-success' : 'alert-danger');
        waAlertText.innerHTML = (isSuccess ? '<i class="bi bi-check-circle-fill me-1"></i> ' : '<i class="bi bi-exclamation-triangle-fill me-1"></i> ') + msg;
        waAlert.style.display = 'block';
    }

    function startCooldown(seconds = 60) {
        if (!btnResendWaOtp || !resendTimer) return;
        btnResendWaOtp.disabled = true;
        let left = seconds;
        resendTimer.textContent = left;

        if (countdownInterval) clearInterval(countdownInterval);

        countdownInterval = setInterval(function() {
            left--;
            if (left <= 0) {
                clearInterval(countdownInterval);
                btnResendWaOtp.disabled = false;
                btnResendWaOtp.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Kirim Ulang Sekarang';
            } else {
                resendTimer.textContent = left;
            }
        }, 1000);
    }

    // Step 1: Submit Phone Number
    if (formSendWaOtp) {
        formSendWaOtp.addEventListener('submit', function(e) {
            e.preventDefault();
            const phone = waPhoneInput.value.trim();
            if (!phone) {
                showAlert('Silakan masukkan nomor WhatsApp Anda.');
                return;
            }

            btnSubmitWaPhone.disabled = true;
            spinnerSendWa.style.display = 'inline-block';
            iconSendWa.style.display = 'none';

            const formData = new FormData(formSendWaOtp);

            fetch(formSendWaOtp.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                btnSubmitWaPhone.disabled = false;
                spinnerSendWa.style.display = 'none';
                iconSendWa.style.display = 'inline-block';

                if (data.status === 'success') {
                    showAlert(data.message, true);
                    waUserIdInput.value = data.user_id;
                    waTargetDisplay.innerHTML = '<i class="bi bi-whatsapp text-success me-1"></i>' + data.masked_phone;
                    waStepInput.style.display = 'none';
                    waStepVerify.style.display = 'block';
                    startCooldown(data.cooldown || 60);
                    setTimeout(() => otpCodeInput.focus(), 300);
                } else {
                    showAlert(data.message || 'Gagal mengirim kode OTP.');
                }
            })
            .catch(err => {
                btnSubmitWaPhone.disabled = false;
                spinnerSendWa.style.display = 'none';
                iconSendWa.style.display = 'inline-block';
                showAlert('Terjadi kesalahan jaringan. Silakan coba beberapa saat lagi.');
            });
        });
    }

    // Step 2: Submit OTP Code
    if (formVerifyWaOtp) {
        formVerifyWaOtp.addEventListener('submit', function(e) {
            e.preventDefault();
            const otp = otpCodeInput.value.trim();
            if (otp.length < 6) {
                showAlert('Silakan masukkan 6 digit kode OTP lengkap.');
                return;
            }

            btnSubmitVerifyOtp.disabled = true;
            spinnerVerifyWa.style.display = 'inline-block';
            iconVerifyWa.style.display = 'none';

            const formData = new FormData(formVerifyWaOtp);

            fetch(formVerifyWaOtp.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert(data.message, true);
                    setTimeout(function() {
                        window.location.href = data.redirect || '<?= base_url("dashboard") ?>';
                    }, 500);
                } else {
                    btnSubmitVerifyOtp.disabled = false;
                    spinnerVerifyWa.style.display = 'none';
                    iconVerifyWa.style.display = 'inline-block';
                    showAlert(data.message || 'Verifikasi kode OTP gagal.');
                }
            })
            .catch(err => {
                btnSubmitVerifyOtp.disabled = false;
                spinnerVerifyWa.style.display = 'none';
                iconVerifyWa.style.display = 'inline-block';
                showAlert('Terjadi kesalahan koneksi saat verifikasi OTP.');
            });
        });
    }

    // Change Phone Button
    if (btnChangeWaPhone) {
        btnChangeWaPhone.addEventListener('click', function() {
            waStepVerify.style.display = 'none';
            waStepInput.style.display = 'block';
            if (waAlert) waAlert.style.display = 'none';
            waPhoneInput.focus();
        });
    }

    // Resend OTP Button
    if (btnResendWaOtp) {
        btnResendWaOtp.addEventListener('click', function() {
            if (btnResendWaOtp.disabled) return;
            formSendWaOtp.dispatchEvent(new Event('submit'));
        });
    }

    // Only allow numbers in OTP input
    if (otpCodeInput) {
        otpCodeInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 6) {
                formVerifyWaOtp.dispatchEvent(new Event('submit'));
            }
        });
    }

    // Check if initial verify step active
    <?php if ($hasActiveOtp): ?>
        startCooldown(60);
    <?php endif; ?>
})();
</script>

<style>
.card-auth .form-control:focus,
.card-auth .input-group-text:focus {
    box-shadow: none !important;
}
.card-auth .input-group:focus-within .input-group-text,
.card-auth .input-group:focus-within .form-control {
    border-color: #4f46e5 !important;
    box-shadow: none !important;
}
</style>
