<?php
/**
 * Reusable WebRTC Camera Check & Verification Widget (Shared by Guru & Murid)
 * Expected vars: $verifyUrl, $snapshotUrl, $checkInData, $accentColor, $camWidgetTitle
 */
$accent = $accentColor ?? 'primary';
$widgetTitle = $camWidgetTitle ?? 'Pengecekan Kamera & Kesiapan Perangkat';
$hasSavedCheck = !empty($checkInData['verified']);
?>
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" id="cameraCheckerWidget">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0 fs-6"><i class="bi bi-camera-video-fill text-<?= e($accent) ?> me-2"></i><?= e($widgetTitle) ?></h5>
            <small class="text-muted">Uji izin browser, perangkat kamera, resolusi video, dan verifikasi pengiriman snapshot ke server.</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="checkerReadinessBadge" class="badge <?= $hasSavedCheck ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border' ?> px-3 py-1.5 rounded-pill">
                <i class="bi <?= $hasSavedCheck ? 'bi-check-circle-fill' : 'bi-webcam' ?> me-1"></i>
                <?= $hasSavedCheck ? 'Kamera Terverifikasi' : 'Belum Diuji' ?>
            </span>
        </div>
    </div>

    <div class="card-body p-4">
        <!-- Hardware Summary Banner -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border h-100">
                    <div class="extra-small text-muted fw-bold text-uppercase">Izin Browser</div>
                    <div class="fw-bold text-dark mt-1" id="checkerPermStatus">Menunggu Uji</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border h-100">
                    <div class="extra-small text-muted fw-bold text-uppercase">Jumlah Kamera</div>
                    <div class="fw-bold text-dark mt-1" id="checkerCamCount">-</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border h-100">
                    <div class="extra-small text-muted fw-bold text-uppercase">Resolusi Aktif</div>
                    <div class="fw-bold font-monospace text-dark mt-1" id="checkerResolution"><?= e($checkInData['resolution'] ?? '-') ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border h-100">
                    <div class="extra-small text-muted fw-bold text-uppercase">Cek Terakhir</div>
                    <div class="fw-bold text-dark mt-1 small" id="checkerLastTime">
                        <?= !empty($checkInData['checked_at']) ? date('d/m H:i', strtotime($checkInData['checked_at'])) : 'Belum ada' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warning / Info Box -->
        <div id="checkerAlertBox" class="alert d-none rounded-3 small mb-4" role="alert"></div>

        <!-- Dual Video Previews -->
        <div class="row g-4 mb-4">
            <!-- Camera 1 -->
            <div class="col-md-6">
                <div class="border rounded-4 overflow-hidden bg-light h-100 d-flex flex-column">
                    <div class="px-3 py-2 bg-white border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold small"><span class="badge bg-<?= e($accent) ?> me-1">CAM 1</span> Kamera Utama / Wajah</span>
                        <span id="chkCam1Badge" class="badge bg-secondary extra-small">Off</span>
                    </div>
                    <div class="position-relative bg-dark d-flex align-items-center justify-content-center" style="height: 230px;">
                        <video id="chkVideo1" autoplay playsinline muted class="w-100 h-100 object-fit-cover"></video>
                        <div id="chkPlaceholder1" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white bg-dark p-3 text-center">
                            <i class="bi bi-person-video fs-1 text-white-50 mb-2"></i>
                            <span class="small text-white-50">Klik "Nyalakan & Uji Kamera" untuk memindai kamera utama</span>
                        </div>
                    </div>
                    <div class="p-2.5 bg-white border-top mt-auto d-flex gap-2 align-items-center">
                        <select id="chkSelect1" class="form-select form-select-sm bg-light" onchange="switchCheckerCamera(1, this.value)">
                            <option value="">Deteksi Perangkat...</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Camera 2 -->
            <div class="col-md-6">
                <div class="border rounded-4 overflow-hidden bg-light h-100 d-flex flex-column">
                    <div class="px-3 py-2 bg-white border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold small"><span class="badge bg-dark me-1">CAM 2</span> Kamera Kedua / Meja</span>
                        <span id="chkCam2Badge" class="badge bg-secondary extra-small">Off</span>
                    </div>
                    <div class="position-relative bg-dark d-flex align-items-center justify-content-center" style="height: 230px;">
                        <video id="chkVideo2" autoplay playsinline muted class="w-100 h-100 object-fit-cover"></video>
                        <div id="chkPlaceholder2" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white bg-dark p-3 text-center">
                            <i class="bi bi-camera-video fs-1 text-white-50 mb-2"></i>
                            <span class="small text-white-50" id="chkPlaceholder2Text">Kamera kedua aktif otomatis jika tersedia >= 2 perangkat</span>
                        </div>
                    </div>
                    <div class="p-2.5 bg-white border-top mt-auto d-flex gap-2 align-items-center">
                        <select id="chkSelect2" class="form-select form-select-sm bg-light" onchange="switchCheckerCamera(2, this.value)">
                            <option value="">Deteksi Perangkat...</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons & Server Snapshot Preview -->
        <div class="row g-3 align-items-center pt-2 border-top">
            <div class="col-lg-8 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-<?= e($accent) ?> px-4 py-2 rounded-3 fw-semibold" id="btnStartChecker" onclick="startCameraChecker()">
                    <i class="bi bi-play-circle-fill me-1"></i> Nyalakan & Uji Kamera
                </button>
                <button type="button" class="btn btn-outline-success px-4 py-2 rounded-3 fw-semibold" id="btnVerifySnapshot" onclick="captureAndVerifyCamera()" disabled>
                    <i class="bi bi-camera-fill me-1"></i> Ambil Foto & Verifikasi Server
                </button>
                <button type="button" class="btn btn-outline-secondary px-3 py-2 rounded-3" id="btnStopChecker" onclick="stopCameraChecker()" disabled>
                    <i class="bi bi-stop-circle me-1"></i> Matikan
                </button>
            </div>
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-3 p-2 border rounded-3 bg-light">
                    <div class="rounded overflow-hidden bg-dark flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 72px; height: 54px;">
                        <img id="chkSnapshotPreview"
                             src="<?= !empty($checkInData['has_snapshot']) ? e($snapshotUrl) . '?t=' . time() : '' ?>"
                             alt="Snapshot Uji"
                             class="w-100 h-100 object-fit-cover <?= !empty($checkInData['has_snapshot']) ? '' : 'd-none' ?>">
                        <i id="chkSnapshotEmpty" class="bi bi-image text-white-50 <?= !empty($checkInData['has_snapshot']) ? 'd-none' : '' ?>"></i>
                    </div>
                    <div class="small lh-sm">
                        <div class="fw-bold text-dark">Hasil Tangkapan Uji</div>
                        <div class="text-muted extra-small" id="chkSnapshotStatus">
                            <?= !empty($checkInData['has_snapshot']) ? 'Tersimpan di server' : 'Belum ada foto uji' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const CAM_CHECKER_CFG = {
    verifyUrl: '<?= e($verifyUrl) ?>',
    csrfToken: '<?= csrf_token() ?>'
};

const checkerState = {
    stream1: null,
    stream2: null,
    devices: [],
    cam1Id: sessionStorage.getItem('cam1DeviceId') || '',
    cam2Id: sessionStorage.getItem('cam2DeviceId') || ''
};

function showCheckerAlert(type, msg) {
    const box = document.getElementById('checkerAlertBox');
    box.className = `alert alert-${type} rounded-3 small mb-4`;
    box.innerHTML = msg;
    box.classList.remove('d-none');
}

async function startCameraChecker() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showCheckerAlert('danger', '<i class="bi bi-exclamation-octagon-fill me-1"></i>Browser Anda tidak mendukung akses WebRTC (<code>getUserMedia</code>). Gunakan Chrome/Edge/Firefox versi terbaru.');
        document.getElementById('checkerPermStatus').textContent = 'Tidak Didukung';
        return;
    }

    try {
        document.getElementById('checkerPermStatus').textContent = 'Meminta Izin...';
        const tempStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        tempStream.getTracks().forEach(t => t.stop());

        document.getElementById('checkerPermStatus').innerHTML = '<span class="text-success">Diizinkan (Granted)</span>';
        await enumerateCheckerDevices();

        await startCheckerStream(1);
        if (checkerState.devices.length >= 2) {
            await startCheckerStream(2);
        } else {
            document.getElementById('chkCam2Badge').className = 'badge bg-info-subtle text-info border extra-small';
            document.getElementById('chkCam2Badge').textContent = '1 Kamera Saja';
            document.getElementById('chkPlaceholder2Text').textContent = 'Hanya 1 kamera terdeteksi (Mode 1 Kamera / Fallback siap digunakan).';
        }

        document.getElementById('btnVerifySnapshot').disabled = false;
        document.getElementById('btnStopChecker').disabled = false;
        showCheckerAlert('success', '<i class="bi bi-check-circle-fill me-1"></i>Kamera berhasil terhubung! Klik <strong>Ambil Foto & Verifikasi Server</strong> untuk memastikan gambar terkirim sempurna.');
    } catch (err) {
        document.getElementById('checkerPermStatus').innerHTML = '<span class="text-danger">Ditolak / Gagal</span>';
        showCheckerAlert('danger', `<i class="bi bi-camera-video-off-fill me-1"></i>Gagal mengakses kamera (${err.name}): Pastikan Anda mengizinkan akses kamera pada ikon gembok/kamera di sebelah URL browser.`);
    }
}

async function enumerateCheckerDevices() {
    const all = await navigator.mediaDevices.enumerateDevices();
    checkerState.devices = all.filter(d => d.kind === 'videoinput');
    document.getElementById('checkerCamCount').textContent = checkerState.devices.length + ' Perangkat';

    const s1 = document.getElementById('chkSelect1');
    const s2 = document.getElementById('chkSelect2');
    s1.innerHTML = '';
    s2.innerHTML = '';

    checkerState.devices.forEach((dev, i) => {
        const label = dev.label || `Kamera ${i + 1}`;
        s1.add(new Option(`CAM 1: ${label}`, dev.deviceId));
        s2.add(new Option(`CAM 2: ${label}`, dev.deviceId));
    });

    if (checkerState.devices.length > 0) {
        if (!checkerState.cam1Id || !checkerState.devices.some(d => d.deviceId === checkerState.cam1Id)) {
            checkerState.cam1Id = checkerState.devices[0].deviceId;
        }
        s1.value = checkerState.cam1Id;
    }

    if (checkerState.devices.length >= 2) {
        if (!checkerState.cam2Id || checkerState.cam2Id === checkerState.cam1Id || !checkerState.devices.some(d => d.deviceId === checkerState.cam2Id)) {
            checkerState.cam2Id = checkerState.devices[1].deviceId;
        }
        s2.value = checkerState.cam2Id;
    }
}

async function startCheckerStream(camNum) {
    const videoEl = document.getElementById(`chkVideo${camNum}`);
    const phEl = document.getElementById(`chkPlaceholder${camNum}`);
    const badgeEl = document.getElementById(`chkCam${camNum}Badge`);
    const devId = camNum === 1 ? checkerState.cam1Id : checkerState.cam2Id;

    if (camNum === 1 && checkerState.stream1) {
        checkerState.stream1.getTracks().forEach(t => t.stop());
    }
    if (camNum === 2 && checkerState.stream2) {
        checkerState.stream2.getTracks().forEach(t => t.stop());
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: devId ? { deviceId: { exact: devId } } : true,
            audio: false
        });
        if (camNum === 1) {
            checkerState.stream1 = stream;
            sessionStorage.setItem('cam1DeviceId', devId || '');
        } else {
            checkerState.stream2 = stream;
            sessionStorage.setItem('cam2DeviceId', devId || '');
        }

        videoEl.srcObject = stream;
        await videoEl.play().catch(() => {});
        phEl.classList.add('d-none');
        badgeEl.className = 'badge bg-success extra-small';
        badgeEl.textContent = 'Live';

        if (camNum === 1) {
            const track = stream.getVideoTracks()[0];
            if (track && track.getSettings) {
                const st = track.getSettings();
                if (st.width && st.height) {
                    document.getElementById('checkerResolution').textContent = `${st.width}x${st.height}`;
                }
            }
        }
    } catch (e) {
        badgeEl.className = 'badge bg-warning text-dark extra-small';
        badgeEl.textContent = 'Gagal';
    }
}

async function switchCheckerCamera(camNum, deviceId) {
    if (camNum === 1) checkerState.cam1Id = deviceId;
    else checkerState.cam2Id = deviceId;
    await startCheckerStream(camNum);
}

function stopCameraChecker() {
    [checkerState.stream1, checkerState.stream2].forEach(s => {
        if (s) s.getTracks().forEach(t => t.stop());
    });
    checkerState.stream1 = null;
    checkerState.stream2 = null;

    [1, 2].forEach(n => {
        document.getElementById(`chkVideo${n}`).srcObject = null;
        document.getElementById(`chkPlaceholder${n}`).classList.remove('d-none');
        const b = document.getElementById(`chkCam${n}Badge`);
        b.className = 'badge bg-secondary extra-small';
        b.textContent = 'Off';
    });

    document.getElementById('btnVerifySnapshot').disabled = true;
    document.getElementById('btnStopChecker').disabled = true;
}

async function captureAndVerifyCamera() {
    const video1 = document.getElementById('chkVideo1');
    if (!checkerState.stream1 || video1.readyState < 2) {
        showCheckerAlert('warning', 'Kamera utama belum menyala. Silakan klik Nyalakan & Uji Kamera terlebih dahulu.');
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.width = 480;
    canvas.height = 360;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video1, 0, 0, canvas.width, canvas.height);
    const dataUrl = canvas.toDataURL('image/jpeg', 0.72);

    const s1 = document.getElementById('chkSelect1');
    const s2 = document.getElementById('chkSelect2');
    const btn = document.getElementById('btnVerifySnapshot');
    btn.disabled = true;

    try {
        const resp = await fetch(CAM_CHECKER_CFG.verifyUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                _csrf_token: CAM_CHECKER_CFG.csrfToken,
                camera_count: checkerState.devices.length,
                cam1_label: s1.selectedOptions[0]?.textContent || 'Kamera Utama',
                cam2_label: checkerState.devices.length >= 2 ? (s2.selectedOptions[0]?.textContent || '') : '',
                resolution: document.getElementById('checkerResolution').textContent,
                image_base64: dataUrl
            })
        });
        const data = await resp.json();
        if (data.status === 'success') {
            const img = document.getElementById('chkSnapshotPreview');
            img.src = dataUrl;
            img.classList.remove('d-none');
            document.getElementById('chkSnapshotEmpty').classList.add('d-none');
            document.getElementById('chkSnapshotStatus').innerHTML = '<span class="text-success fw-semibold">Terverifikasi di Server</span>';
            document.getElementById('checkerLastTime').textContent = 'Baru saja';
            document.getElementById('checkerReadinessBadge').className = 'badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill';
            document.getElementById('checkerReadinessBadge').innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Kamera Terverifikasi';
            showCheckerAlert('success', '<i class="bi bi-shield-check me-1"></i>Verifikasi kamera & pengiriman snapshot ke server <strong>BERHASIL</strong>. Perangkat Anda siap digunakan!');
        } else {
            showCheckerAlert('danger', data.message || 'Gagal memverifikasi snapshot ke server.');
        }
    } catch (e) {
        showCheckerAlert('danger', 'Terjadi kesalahan koneksi saat verifikasi ke server.');
    } finally {
        btn.disabled = false;
    }
}

window.addEventListener('beforeunload', () => stopCameraChecker());
</script>

