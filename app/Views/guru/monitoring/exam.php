<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= base_url('guru/monitoring') ?>">Monitoring</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($exam['title']) ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-1"><i class="bi bi-display text-primary me-2"></i>Live Monitoring Ujian: <?= e($exam['title']) ?></h4>
        <div class="d-flex align-items-center gap-2 flex-wrap text-muted small">
            <span class="badge bg-light text-dark border"><?= e($exam['subject_name']) ?></span>
            <span class="badge bg-info-subtle text-info"><i class="bi bi-building me-1"></i><?= e($exam['class_name']) ?></span>
            <span><i class="bi bi-stopwatch me-1"></i><?= $exam['duration'] ?> Menit</span>
            <?php if ($exam['dual_camera']): ?>
                <span class="badge bg-purple-subtle text-primary border border-primary-subtle"><i class="bi bi-camera-video me-1"></i>Dual Camera</span>
            <?php else: ?>
                <span class="badge bg-light text-secondary border"><i class="bi bi-webcam me-1"></i>Single Camera</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('guru/monitoring/' . $exam['id']) ?>" class="btn btn-outline-primary btn-sm" title="Segarkan Data Realtime">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </a>
        <a href="<?= base_url('guru/results/' . $exam['id']) ?>" class="btn btn-outline-success btn-sm">
            <i class="bi bi-bar-chart-line-fill me-1"></i> Hasil & Nilai
        </a>
        <a href="<?= base_url('guru/monitoring') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<!-- LIVE METRICS CARDS: Total, Online, Offline, Normal, Warning, High Risk -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body h-100">
            <div class="text-muted extra-small fw-bold text-uppercase">Total Peserta</div>
            <div class="fs-4 fw-bold text-dark mt-1"><?= $stats['total_sessions'] ?? 0 ?></div>
            <div class="text-muted extra-small">terdaftar di sesi</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body border-start border-success border-4 h-100">
            <div class="text-success extra-small fw-bold text-uppercase"><i class="bi bi-circle-fill fs-6 text-success me-1 animate-pulse"></i>Online</div>
            <div class="fs-4 fw-bold text-success mt-1"><?= $stats['online_count'] ?? 0 ?></div>
            <div class="text-muted extra-small">aktif &amp; terhubung</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body border-start border-secondary border-4 h-100">
            <div class="text-secondary extra-small fw-bold text-uppercase">Offline / Selesai</div>
            <div class="fs-4 fw-bold text-secondary mt-1"><?= $stats['offline_count'] ?? 0 ?></div>
            <div class="text-muted extra-small">tidak aktif saat ini</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body border-start border-primary border-4 h-100">
            <div class="text-primary extra-small fw-bold text-uppercase">Normal (Low)</div>
            <div class="fs-4 fw-bold text-primary mt-1"><?= $stats['normal_count'] ?? 0 ?></div>
            <div class="text-muted extra-small">Score 0–20 (Aman)</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body border-start border-warning border-4 h-100">
            <div class="text-warning-emphasis extra-small fw-bold text-uppercase">Warning</div>
            <div class="fs-4 fw-bold text-warning-emphasis mt-1"><?= $stats['warning_count'] ?? 0 ?></div>
            <div class="text-muted extra-small">Score 21–50 (Perhatian)</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body border-start border-danger border-4 h-100">
            <div class="text-danger extra-small fw-bold text-uppercase">High Risk</div>
            <div class="fs-4 fw-bold text-danger mt-1"><?= $stats['high_risk_count'] ?? 0 ?></div>
            <div class="text-muted extra-small">Score 51+ (Reviu)</div>
        </div>
    </div>
</div>

<!-- FILTER & SEARCH BAR -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= base_url('guru/monitoring/' . $exam['id']) ?>" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-secondary">Cari Peserta Didik / NIS</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Ketik nama atau NIS..." value="<?= e($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Filter Kategori Risiko</label>
                <select name="risk_level" class="form-select bg-light">
                    <option value="">Semua Kategori Risiko</option>
                    <option value="HIGH" <?= ($filters['risk_level'] ?? '') === 'HIGH' ? 'selected' : '' ?>>Tinggi / HIGH (Score &gt; 50)</option>
                    <option value="MEDIUM" <?= ($filters['risk_level'] ?? '') === 'MEDIUM' ? 'selected' : '' ?>>Sedang / MEDIUM (Score 21-50)</option>
                    <option value="LOW" <?= ($filters['risk_level'] ?? '') === 'LOW' ? 'selected' : '' ?>>Rendah / LOW (Score 0-20)</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Saring</button>
                <?php if (!empty($filters['search']) || !empty($filters['risk_level'])): ?>
                    <a href="<?= base_url('guru/monitoring/' . $exam['id']) ?>" class="btn btn-outline-secondary" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- LIVE CAMERA MONITORING GRID (GURU PROCTOR CAM + STUDENT LIVE CAMS) -->
<div class="row g-3 mb-4">
    <!-- GURU PROCTOR CAMERA PANEL -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 small text-dark"><i class="bi bi-person-video2 text-success me-1"></i> Kamera Pengawas (Guru)</h6>
                <span id="guruProctorStatusBadge" class="badge bg-secondary extra-small">Off</span>
            </div>
            <div class="card-body p-3 d-flex flex-column">
                <div class="bg-dark rounded-3 overflow-hidden position-relative mb-2 d-flex align-items-center justify-content-center" style="height: 165px;">
                    <video id="guruProctorVideo" autoplay playsinline muted class="w-100 h-100 object-fit-cover"></video>
                    <div id="guruProctorPh" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white-50 p-2 text-center bg-dark">
                        <i class="bi bi-webcam fs-2 mb-1"></i>
                        <small class="extra-small">Nyalakan kamera pengawas agar peserta melihat kehadiran pengawas secara live.</small>
                    </div>
                </div>
                <div class="d-flex gap-2 mb-2">
                    <select id="guruCamSelect" class="form-select form-select-sm extra-small bg-light">
                        <option value="">Deteksi Kamera Guru...</option>
                    </select>
                </div>
                <div class="d-flex gap-2 mt-auto">
                    <button type="button" id="btnStartGuruCam" class="btn btn-sm btn-success flex-grow-1 fw-semibold" onclick="startGuruProctorCamera()">
                        <i class="bi bi-camera-video-fill me-1"></i> Aktifkan Kamera Pengawas
                    </button>
                    <button type="button" id="btnStopGuruCam" class="btn btn-sm btn-outline-danger d-none" onclick="stopGuruProctorCamera()">
                        <i class="bi bi-stop-circle"></i> Matikan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- LIVE STUDENT CAMERAS GRID -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="fw-bold mb-0 small text-dark"><i class="bi bi-grid-fill text-primary me-1"></i> Pantauan Langsung Kamera Murid (Live Grid)</h6>
                <span class="badge bg-light text-muted border extra-small" id="liveGridSyncBadge">Sinkronisasi otomatis setiap 5 detik</span>
            </div>
            <div class="card-body p-3">
                <?php if (empty($sessions)): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-camera-video-off fs-2 d-block mb-1 opacity-50"></i>
                        Belum ada peserta yang memulai ujian.
                    </div>
                <?php else: ?>
                    <div class="row g-2" id="studentCamerasGrid">
                        <?php foreach ($sessions as $s): ?>
                            <div class="col-sm-6 col-md-4">
                                <div class="border rounded-3 overflow-hidden bg-light h-100 d-flex flex-column" id="camCard_<?= (int)$s['session_id'] ?>">
                                    <div class="px-2 py-1 bg-white border-bottom d-flex justify-content-between align-items-center">
                                        <span class="fw-bold extra-small text-truncate" style="max-width: 120px;"><?= e($s['student_name']) ?></span>
                                        <span class="badge bg-<?= $s['is_online'] ? 'success' : 'secondary' ?> extra-small" style="font-size: 0.65rem;" id="gridStatus_<?= (int)$s['session_id'] ?>">
                                            <?= $s['is_online'] ? 'ONLINE' : strtoupper(e($s['status'])) ?>
                                        </span>
                                    </div>
                                    <div class="bg-dark position-relative d-flex align-items-center justify-content-center" style="height: 115px;">
                                        <img id="gridCam1_<?= (int)$s['session_id'] ?>"
                                             src="<?= base_url('guru/monitoring/camera-frame/' . (int)$exam['id'] . '/' . (int)$s['session_id'] . '/camera_1?t=' . time()) ?>"
                                             alt="Cam <?= e($s['student_name']) ?>"
                                             class="w-100 h-100 object-fit-cover <?= $s['cam1_age'] !== null ? '' : 'd-none' ?>"
                                             onload="this.classList.remove('d-none'); document.getElementById('gridPh_<?= (int)$s['session_id'] ?>').classList.add('d-none');"
                                             onerror="this.classList.add('d-none'); document.getElementById('gridPh_<?= (int)$s['session_id'] ?>').classList.remove('d-none');">
                                        <div id="gridPh_<?= (int)$s['session_id'] ?>" class="text-white-50 extra-small text-center p-2 <?= $s['cam1_age'] !== null ? 'd-none' : '' ?>">
                                            <i class="bi bi-person-video d-block mb-0.5"></i>Kamera Belum Aktif
                                        </div>
                                        <span class="position-absolute bottom-0 end-0 m-1 badge bg-<?= $s['risk_color'] ?>" style="font-size: 0.65rem;" id="gridRisk_<?= (int)$s['session_id'] ?>">
                                            Risk: <?= $s['risk_score'] ?>
                                        </span>
                                    </div>
                                    <div class="p-1.5 bg-white border-top d-flex justify-content-between align-items-center">
                                        <small class="text-muted extra-small">NIS: <?= e($s['nis'] ?? '-') ?></small>
                                        <div class="d-flex align-items-center gap-1">
                                            <a href="<?= base_url('guru/monitoring/' . (int)$exam['id'] . '/' . (int)$s['session_id']) ?>" class="btn btn-outline-primary btn-sm py-0 px-2 extra-small">
                                                Cek Detail
                                            </a>
                                            <form action="<?= base_url('guru/monitoring/' . (int)$exam['id'] . '/session/' . (int)$s['session_id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Hapus sesi ujian peserta <?= e(addslashes($s['student_name'])) ?>? Seluruh riwayat jawaban dan rekaman kamera akan dihapus.')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1 extra-small" title="Hapus Sesi Peserta">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- SESSIONS MONITORING TABLE -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-3 pb-2 px-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-person-video3 me-2"></i>Tabel Pemantauan Peserta Ujian</h6>
        <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1"><?= count($sessions) ?> Peserta</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($sessions)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people fs-1 d-block mb-2 text-secondary opacity-50"></i>
                <p class="mb-0">Belum ada data sesi peserta atau tidak cocok dengan filter pencarian.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 40px;">No</th>
                            <th>Nama Peserta</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Camera 1 (Face)</th>
                            <th class="text-center">Camera 2 (Desk)</th>
                            <th class="text-center">Risk Score</th>
                            <th class="text-center">Warnings</th>
                            <th class="text-center">Last Activity</th>
                            <th class="pe-3 text-end" style="min-width: 220px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $idx => $s): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-muted"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= e($s['student_name']) ?></div>
                                    <small class="text-muted">NIS: <?= e($s['nis'] ?? '-') ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['status'] === 'finished'): ?>
                                        <span class="badge bg-success-subtle text-success px-2 py-1"><i class="bi bi-check-circle me-1"></i>Selesai</span>
                                    <?php elseif ($s['status'] === 'expired'): ?>
                                        <span class="badge bg-secondary-subtle text-secondary px-2 py-1"><i class="bi bi-stopwatch me-1"></i>Expired</span>
                                    <?php elseif ($s['is_online']): ?>
                                        <span class="badge bg-success px-2 py-1"><i class="bi bi-wifi me-1"></i>Online</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary px-2 py-1"><i class="bi bi-wifi-off me-1"></i>Offline</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['camera_1_status'] === 'connected'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" title="Kamera 1 Aktif & Terhubung">
                                            <i class="bi bi-camera-video-fill me-1"></i>Connected
                                        </span>
                                    <?php elseif ($s['camera_1_status'] === 'disconnected'): ?>
                                        <span class="badge bg-danger px-2 py-1" title="Kamera 1 Terputus">
                                            <i class="bi bi-camera-video-off-fill me-1"></i>Disconnected
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-2 py-1">
                                            <i class="bi bi-dash-circle me-1"></i><?= ucfirst(e($s['camera_1_status'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!$exam['dual_camera']): ?>
                                        <span class="badge bg-light text-muted border px-2 py-1" title="Mode Single Camera">
                                            N/A (Single Cam)
                                        </span>
                                    <?php elseif ($s['camera_2_status'] === 'connected'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" title="Kamera 2 Terhubung">
                                            <i class="bi bi-camera-video-fill me-1"></i>Connected
                                        </span>
                                    <?php elseif ($s['camera_2_status'] === 'single_camera_fallback'): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1" title="1 Kamera Terdeteksi (Fallback)">
                                            <i class="bi bi-info-circle me-1"></i>Fallback
                                        </span>
                                    <?php elseif ($s['camera_2_status'] === 'disconnected'): ?>
                                        <span class="badge bg-danger px-2 py-1" title="Kamera 2 Terputus">
                                            <i class="bi bi-camera-video-off-fill me-1"></i>Disconnected
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-2 py-1">
                                            <?= ucfirst(e($s['camera_2_status'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <span class="badge bg-<?= $s['risk_color'] ?> px-2 py-1 fs-6">
                                            <?= $s['risk_score'] ?>
                                        </span>
                                        <span class="badge bg-<?= $s['risk_color'] ?>-subtle text-<?= $s['risk_color'] ?> px-2 py-1">
                                            <?= $s['risk_label'] ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['warnings_count'] > 0): ?>
                                        <span class="badge bg-warning text-dark px-2 py-1 fw-bold fs-6">
                                            <i class="bi bi-exclamation-triangle-fill me-1 text-danger"></i><?= $s['warnings_count'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success px-2 py-1">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center small text-muted font-monospace">
                                    <?= e($s['last_active_formatted']) ?>
                                </td>
                                <td class="pe-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('guru/results/' . $exam['id'] . '/' . $s['student_id']) ?>" class="btn btn-outline-secondary" title="View Lembar Jawaban & Nilai">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <a href="<?= base_url('guru/monitoring/' . $exam['id'] . '/' . $s['session_id']) ?>" class="btn btn-primary" title="Live Monitoring Detail">
                                            <i class="bi bi-binoculars"></i> Monitoring
                                        </a>
                                        <a href="<?= base_url('guru/monitoring/' . $exam['id'] . '/' . $s['session_id']) ?>#reviewSection" class="btn btn-warning text-dark" title="Review Suspicious Events">
                                            <i class="bi bi-clipboard-check"></i> Review
                                        </a>
                                        <form action="<?= base_url('guru/monitoring/' . (int)$exam['id'] . '/session/' . (int)$s['session_id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Hapus sesi ujian peserta <?= e(addslashes($s['student_name'])) ?>? Seluruh riwayat jawaban dan rekaman kamera akan dihapus.')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus Sesi Peserta">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
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

<script>
const EXAM_MON_CFG = {
    examId: <?= (int)$exam['id'] ?>,
    liveDataUrl: '<?= base_url('guru/monitoring/live-data/' . (int)$exam['id']) ?>',
    camFrameBase: '<?= base_url('guru/monitoring/camera-frame/' . (int)$exam['id']) ?>',
    proctorCamUrl: '<?= base_url('guru/monitoring/proctor-camera') ?>',
    csrfToken: '<?= csrf_token() ?>'
};

let guruCamStream = null;
let guruCamInterval = null;
const guruCanvas = document.createElement('canvas');
guruCanvas.width = 480;
guruCanvas.height = 360;

async function startGuruProctorCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Browser tidak mendukung akses kamera WebRTC.');
        return;
    }

    try {
        const selectedId = document.getElementById('guruCamSelect')?.value || '';
        const stream = await navigator.mediaDevices.getUserMedia({
            video: selectedId ? { deviceId: { exact: selectedId } } : { facingMode: 'user' },
            audio: false
        });
        guruCamStream = stream;

        // Populate camera devices list
        const devs = (await navigator.mediaDevices.enumerateDevices()).filter(d => d.kind === 'videoinput');
        const sel = document.getElementById('guruCamSelect');
        if (sel && devs.length > 0) {
            const cur = sel.value;
            sel.innerHTML = '';
            devs.forEach((d, idx) => sel.add(new Option(d.label || `Kamera Guru ${idx + 1}`, d.deviceId)));
            if (cur) sel.value = cur;
        }

        const vid = document.getElementById('guruProctorVideo');
        vid.srcObject = stream;
        await vid.play().catch(() => {});

        document.getElementById('guruProctorPh').classList.add('d-none');
        const b = document.getElementById('guruProctorStatusBadge');
        b.className = 'badge bg-success extra-small';
        b.textContent = 'LIVE';
        document.getElementById('btnStartGuruCam').classList.add('d-none');
        document.getElementById('btnStopGuruCam').classList.remove('d-none');

        await sendGuruProctorFrame();
        if (guruCamInterval) clearInterval(guruCamInterval);
        guruCamInterval = setInterval(sendGuruProctorFrame, 5000);
    } catch (err) {
        alert('Gagal mengaktifkan kamera guru: ' + err.message);
    }
}

async function sendGuruProctorFrame() {
    const vid = document.getElementById('guruProctorVideo');
    if (!guruCamStream || !vid || vid.readyState < 2) return;

    try {
        const ctx = guruCanvas.getContext('2d');
        ctx.drawImage(vid, 0, 0, guruCanvas.width, guruCanvas.height);
        const imageBase64 = guruCanvas.toDataURL('image/jpeg', 0.65);

        await fetch(EXAM_MON_CFG.proctorCamUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                _csrf_token: EXAM_MON_CFG.csrfToken,
                exam_id: EXAM_MON_CFG.examId,
                action: 'stream',
                image_base64: imageBase64
            })
        });
    } catch (e) {}
}

async function stopGuruProctorCamera() {
    if (guruCamInterval) {
        clearInterval(guruCamInterval);
        guruCamInterval = null;
    }
    if (guruCamStream) {
        guruCamStream.getTracks().forEach(t => t.stop());
        guruCamStream = null;
    }
    const vid = document.getElementById('guruProctorVideo');
    if (vid) vid.srcObject = null;
    document.getElementById('guruProctorPh').classList.remove('d-none');

    const b = document.getElementById('guruProctorStatusBadge');
    b.className = 'badge bg-secondary extra-small';
    b.textContent = 'Off';
    document.getElementById('btnStopGuruCam').classList.add('d-none');
    document.getElementById('btnStartGuruCam').classList.remove('d-none');

    try {
        await fetch(EXAM_MON_CFG.proctorCamUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                _csrf_token: EXAM_MON_CFG.csrfToken,
                exam_id: EXAM_MON_CFG.examId,
                action: 'stop'
            })
        });
    } catch (e) {}
}

// Live Polling Student Cameras Grid every 5 seconds
async function pollStudentCamerasGrid() {
    try {
        const resp = await fetch(EXAM_MON_CFG.liveDataUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await resp.json();
        if (!data || data.status !== 'success') return;

        const ts = Date.now();
        (data.participants || []).forEach(p => {
            const img = document.getElementById(`gridCam1_${p.session_id}`);
            if (img && p.cam1_has_frame) {
                img.src = `${EXAM_MON_CFG.camFrameBase}/${p.session_id}/camera_1?t=${ts}`;
            }
            const st = document.getElementById(`gridStatus_${p.session_id}`);
            if (st) {
                st.className = `badge bg-${p.is_online ? 'success' : 'secondary'} extra-small`;
                st.textContent = p.is_online ? 'ONLINE' : String(p.status).toUpperCase();
            }
            const rk = document.getElementById(`gridRisk_${p.session_id}`);
            if (rk) {
                rk.className = `position-absolute bottom-0 end-0 m-1 badge bg-${p.risk_color}`;
                rk.textContent = `Risk: ${p.risk_score}`;
            }
        });

        const badge = document.getElementById('liveGridSyncBadge');
        if (badge) badge.textContent = 'Live Sync: ' + (data.timestamp || '');
    } catch (e) {}
}

setInterval(pollStudentCamerasGrid, 5000);
window.addEventListener('beforeunload', () => {
    if (guruCamStream) stopGuruProctorCamera();
});
</script>
