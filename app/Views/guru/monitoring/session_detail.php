<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= base_url('guru/monitoring') ?>">Monitoring</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('guru/monitoring/' . $exam['id']) ?>"><?= e($exam['title']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($session['student_name']) ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-workspace text-primary me-2"></i>Detail Monitoring Sesi: <?= e($session['student_name']) ?></h4>
        <p class="text-muted small mb-0">Rincian status perangkat, koneksi realtime, serta linimasa deteksi AI Proctoring untuk sesi ini.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('guru/results/' . $exam['id'] . '/' . $session['student_id']) ?>" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-text-fill me-1"></i> Lembar Jawaban
        </a>
        <a href="<?= base_url('guru/monitoring/' . $exam['id']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Peserta
        </a>
        <form action="<?= base_url('guru/monitoring/' . (int)$exam['id'] . '/session/' . (int)$session['id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh sesi peserta ini? Seluruh rekaman dan riwayat jawaban akan dihapus permanen.')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus Sesi">
                <i class="bi bi-trash me-1"></i> Hapus Sesi
            </button>
        </form>
    </div>
</div>

<div class="row g-4">
    <!-- LEFT COLUMN: PARTICIPANT, CONNECTION, CAMERAS, RISK & REVIEW -->
    <div class="col-lg-4">
        <!-- PARTICIPANT & RISK CARD -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
            <div class="card-header bg-primary text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle bg-white text-primary me-3 fw-bold fs-5 shadow-sm">
                            <?= strtoupper(substr($session['student_name'] ?? 'P', 0, 1)) ?>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-white"><?= e($session['student_name']) ?></h6>
                            <small class="text-white-50">NIS: <?= e($session['nis'] ?? '-') ?></small>
                        </div>
                    </div>
                    <div>
                        <?php if ($isOnline): ?>
                            <span class="badge bg-success border border-white d-inline-flex align-items-center gap-1">
                                <span class="spinner-grow spinner-grow-sm text-light" style="width: 0.5rem; height: 0.5rem;" role="status"></span>
                                ONLINE
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary border border-white">
                                <i class="bi bi-wifi-off me-1"></i>OFFLINE
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body p-3">
                <!-- RISK SCORE BANNER -->
                <div class="alert alert-<?= $risk['color'] ?> border-0 d-flex align-items-center justify-content-between p-3 rounded-3 mb-3">
                    <div>
                        <div class="extra-small fw-bold text-uppercase text-secondary">Tingkat Risiko</div>
                        <h4 class="fw-bold mb-0 text-<?= $risk['color'] ?>"><?= $risk['label'] ?></h4>
                        <small class="text-muted d-block mt-1"><?= $risk['description'] ?></small>
                    </div>
                    <div class="text-center">
                        <div class="display-6 fw-bold text-<?= $risk['color'] ?>"><?= $risk['score'] ?></div>
                        <span class="badge bg-<?= $risk['color'] ?>">Risk Score</span>
                    </div>
                </div>

                <!-- DUAL CAMERA LIVE FEED & STATUS WIDGET -->
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <div class="fw-bold small text-secondary mb-2 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-camera-video-fill text-primary me-1"></i> Live Kamera Murid</span>
                        <span class="extra-small text-muted" id="camFeedRefreshInfo">Auto-Refresh 4s</span>
                    </div>
                    <div class="row g-2">
                        <!-- Camera 1: Face -->
                        <div class="col-6">
                            <div class="p-2 rounded bg-white border text-center h-100 d-flex flex-column">
                                <div class="extra-small fw-bold text-muted mb-1"><i class="bi bi-person-video me-1"></i>Camera 1 (Wajah)</div>
                                <div class="bg-dark rounded overflow-hidden position-relative mb-1.5 d-flex align-items-center justify-content-center" style="height: 95px;">
                                    <img id="liveStudentCam1"
                                         src="<?= base_url('guru/monitoring/camera-frame/' . (int)$exam['id'] . '/' . (int)$session['id'] . '/camera_1?t=' . time()) ?>"
                                         alt="Cam 1"
                                         class="w-100 h-100 object-fit-cover <?= $cam1Age !== null ? '' : 'd-none' ?>"
                                         onload="this.classList.remove('d-none'); document.getElementById('liveStudentCam1Ph').classList.add('d-none');"
                                         onerror="this.classList.add('d-none'); document.getElementById('liveStudentCam1Ph').classList.remove('d-none');">
                                    <div id="liveStudentCam1Ph" class="text-white-50 extra-small p-1 <?= $cam1Age !== null ? 'd-none' : '' ?>">
                                        <i class="bi bi-camera-video-off d-block mb-1"></i>Belum ada frame
                                    </div>
                                </div>
                                <?php 
                                    $c1Status = $cameras['camera_1']['event_type'] ?? ($cameras['primary']['status'] ?? 'none');
                                    if ($c1Status === 'none' && $cam1Age !== null && $cam1Age <= 30) {
                                        $c1Status = 'connected';
                                    }
                                    $c1Badge = match($c1Status) {
                                        'connected', 'streaming', 'permission_granted' => ['bg' => 'bg-success', 'label' => 'Connected'],
                                        'disconnected', 'stopped', 'permission_denied', 'stream_error' => ['bg' => 'bg-danger', 'label' => 'Terputus'],
                                        default => ['bg' => 'bg-secondary', 'label' => 'Belum Aktif']
                                    };
                                ?>
                                <span class="badge <?= $c1Badge['bg'] ?> extra-small w-100 mb-1"><?= $c1Badge['label'] ?></span>
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-1.5 extra-small mt-auto" onclick="captureStudentEvidence('camera_1')">
                                    <i class="bi bi-camera me-1"></i>Cek & Simpan Foto
                                </button>
                            </div>
                        </div>
                        <!-- Camera 2: Environment / Desk -->
                        <div class="col-6">
                            <div class="p-2 rounded bg-white border text-center h-100 d-flex flex-column">
                                <div class="extra-small fw-bold text-muted mb-1"><i class="bi bi-camera-video me-1"></i>Camera 2 (Meja)</div>
                                <div class="bg-dark rounded overflow-hidden position-relative mb-1.5 d-flex align-items-center justify-content-center" style="height: 95px;">
                                    <img id="liveStudentCam2"
                                         src="<?= base_url('guru/monitoring/camera-frame/' . (int)$exam['id'] . '/' . (int)$session['id'] . '/camera_2?t=' . time()) ?>"
                                         alt="Cam 2"
                                         class="w-100 h-100 object-fit-cover <?= $cam2Age !== null ? '' : 'd-none' ?>"
                                         onload="this.classList.remove('d-none'); document.getElementById('liveStudentCam2Ph').classList.add('d-none');"
                                         onerror="this.classList.add('d-none'); document.getElementById('liveStudentCam2Ph').classList.remove('d-none');">
                                    <div id="liveStudentCam2Ph" class="text-white-50 extra-small p-1 <?= $cam2Age !== null ? 'd-none' : '' ?>">
                                        <i class="bi bi-webcam d-block mb-1"></i>Fallback / Off
                                    </div>
                                </div>
                                <?php 
                                    $c2Status = $cameras['camera_2']['event_type'] ?? ($cameras['secondary']['status'] ?? 'none');
                                    if ($c2Status === 'none' && $cam2Age !== null && $cam2Age <= 30) {
                                        $c2Status = 'connected';
                                    }
                                    $c2Badge = match($c2Status) {
                                        'connected', 'streaming' => ['bg' => 'bg-success', 'label' => 'Connected'],
                                        'single_camera_fallback' => ['bg' => 'bg-info', 'label' => 'Fallback 1 Cam'],
                                        'disconnected', 'stopped' => ['bg' => 'bg-danger', 'label' => 'Terputus'],
                                        default => ['bg' => 'bg-secondary', 'label' => 'Belum Aktif']
                                    };
                                ?>
                                <span class="badge <?= $c2Badge['bg'] ?> extra-small w-100 mb-1"><?= $c2Badge['label'] ?></span>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1.5 extra-small mt-auto" onclick="captureStudentEvidence('camera_2')">
                                    <i class="bi bi-camera me-1"></i>Cek & Simpan Foto
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="captureCheckAlert" class="alert d-none py-1.5 px-2 extra-small mt-2 mb-0"></div>
                </div>

                <!-- EXAM SESSION DETAILS -->
                <div class="list-group list-group-flush small">
                    <div class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Mata Pelajaran</span>
                        <span class="fw-semibold text-dark"><?= e($exam['subject_name']) ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Kelas</span>
                        <span class="fw-semibold text-dark"><?= e($exam['class_name']) ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Status Sesi</span>
                        <span class="fw-bold <?= $session['status'] === 'finished' ? 'text-success' : ($session['status'] === 'started' ? 'text-primary' : 'text-danger') ?>">
                            <?= strtoupper(e($session['status'])) ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Waktu Mulai</span>
                        <span class="fw-semibold text-dark"><?= $session['started_at'] ? date('H:i:s - d/m/Y', strtotime($session['started_at'])) : '-' ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Waktu Selesai</span>
                        <span class="fw-semibold text-dark"><?= $session['finished_at'] ? date('H:i:s - d/m/Y', strtotime($session['finished_at'])) : ($session['status'] === 'started' ? '<span class="badge bg-primary">Sedang Berlangsung</span>' : '-') ?></span>
                    </div>
                    <?php if ($session['status'] === 'started'): ?>
                    <div class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Sisa Waktu</span>
                        <span class="fw-bold text-danger">
                            <?= gmdate("H:i:s", max(0, (int)($remainingSeconds ?? 0))) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Aktivitas Terakhir</span>
                        <span class="text-muted"><?= !empty($session['last_activity_at']) ? date('H:i:s - d/m/Y', strtotime($session['last_activity_at'])) : '-' ?></span>
                    </div>
                </div>

                <hr class="my-3">

                <!-- EVENT BREAKDOWN COUNTER -->
                <h6 class="fw-bold small text-secondary mb-2">Ringkasan Kejadian (Events)</h6>
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="p-2 rounded bg-danger-subtle border border-danger-subtle">
                            <div class="fw-bold fs-5 text-danger"><?= $eventStats['high_count'] ?? 0 ?></div>
                            <div class="extra-small text-danger fw-semibold">HIGH</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded bg-warning-subtle border border-warning-subtle">
                            <div class="fw-bold fs-5 text-warning-emphasis"><?= $eventStats['warning_count'] ?? 0 ?></div>
                            <div class="extra-small text-warning-emphasis fw-semibold">WARNING</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded bg-info-subtle border border-info-subtle">
                            <div class="fw-bold fs-5 text-info"><?= $eventStats['info_count'] ?? 0 ?></div>
                            <div class="extra-small text-info fw-semibold">INFO</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TEACHER REVIEW FORM -->
        <div class="card border-0 shadow-sm rounded-3" id="reviewSection">
            <div class="card-header bg-transparent border-0 pt-3 pb-2 px-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i>Catatan & Verifikasi Pengawas</h6>
            </div>
            <div class="card-body p-3">
                <p class="text-muted small">
                    AI hanya bertindak sebagai pendeteksi anomali. Integritas dan keputusan final status ujian ditentukan sepenuhnya oleh Guru Pengawas.
                </p>
                <form action="<?= base_url('guru/monitoring/review') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
                    <input type="hidden" name="session_id" value="<?= $session['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Status Keputusan Reviu</label>
                        <?php 
                            $currentStatus = (!empty($events) && isset($events[0]['review_status'])) ? $events[0]['review_status'] : 'pending';
                        ?>
                        <select name="review_status" class="form-select form-select-sm" required>
                            <option value="pending" <?= $currentStatus === 'pending' ? 'selected' : '' ?>>⏳ Pending (Belum Direviu)</option>
                            <option value="reviewed" <?= $currentStatus === 'reviewed' ? 'selected' : '' ?>>✓ Reviewed (Telah Direviu - Wajar)</option>
                            <option value="confirmed" <?= $currentStatus === 'confirmed' ? 'selected' : '' ?>>⚠️ Confirmed (Terkonfirmasi Pelanggaran)</option>
                            <option value="dismissed" <?= $currentStatus === 'dismissed' ? 'selected' : '' ?>>✕ Dismissed (Abaikan / False Alarm)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Catatan Verifikasi Guru</label>
                        <textarea name="review_note" class="form-control" rows="3" placeholder="Contoh: Terkonfirmasi peserta menoleh karena instruksi teknis di ruangan, atau terindikasi kecurangan..."><?= e($events[0]['review_note'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Catatan Reviu Sesi
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: TIMELINE FEED -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-transparent border-0 pt-3 pb-2 px-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-activity me-2 text-primary"></i>Linimasa Kejadian AI Proctoring</h6>
                <span class="badge bg-light text-dark border"><?= count($events) ?> Kejadian Terdeteksi</span>
            </div>
            <div class="card-body px-4 py-3">
                <?php if (empty($events)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-shield-check fs-1 d-block mb-3 text-success"></i>
                        <h5 class="fw-bold text-dark">Tidak Ada Suspicious Event</h5>
                        <p class="small text-muted mb-0">Peserta menyelesaikan sesi ujian secara wajar tanpa adanya anomali atau peringatan yang tercatat oleh AI Proctoring.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline position-relative">
                        <?php foreach ($events as $idx => $ev): ?>
                            <div class="d-flex gap-3 mb-4 position-relative">
                                <!-- TIMELINE BADGE / ICON -->
                                <div class="flex-shrink-0">
                                    <div class="avatar-circle <?= $ev['severity_badge']['bg'] ?> <?= $ev['severity_badge']['text'] ?> shadow-sm">
                                        <i class="bi <?= $ev['event_icon'] ?> fs-5"></i>
                                    </div>
                                </div>

                                <!-- EVENT CONTENT CARD -->
                                <div class="card flex-grow-1 border-0 bg-light p-3 rounded-3 shadow-none">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                        <div>
                                            <span class="badge <?= $ev['severity_badge']['bg'] ?> <?= $ev['severity_badge']['text'] ?> me-2">
                                                <?= $ev['severity_badge']['label'] ?>
                                            </span>
                                            <span class="badge bg-dark-subtle text-dark me-2">
                                                <i class="bi <?= ($ev['camera_type'] === 'secondary') ? 'bi-camera-video' : 'bi-person-video' ?> me-1"></i>
                                                <?= ($ev['camera_type'] === 'secondary') ? 'Camera 2 (Meja)' : 'Camera 1 (Wajah)' ?>
                                            </span>
                                            <strong class="text-dark fs-6"><?= $ev['event_label'] ?></strong>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-clock me-1"></i><?= $ev['time_formatted'] ?>
                                        </div>
                                    </div>

                                    <!-- EVENT DESCRIPTION / REASON -->
                                    <p class="mb-2 text-secondary small">
                                        <?= e($ev['meta']['reason'] ?? 'Deteksi anomali pola aktivitas peserta didik selama sesi.') ?>
                                    </p>

                                    <!-- EVIDENCE IMAGE IF PRESENT -->
                                    <?php if (!empty($ev['evidence_path'])): ?>
                                        <div class="mb-2">
                                            <a href="<?= base_url(e($ev['evidence_path'])) ?>" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-2 extra-small">
                                                <i class="bi bi-image me-1"></i>Lihat Bukti Foto
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top border-secondary-subtle">
                                        <div class="small">
                                            <span class="text-muted">AI Confidence:</span>
                                            <strong class="text-dark"><?= $ev['confidence_pct'] ?>%</strong>
                                            <div class="progress mt-1" style="height: 4px; width: 100px;">
                                                <div class="progress-bar <?= $ev['confidence'] >= 0.8 ? 'bg-danger' : ($ev['confidence'] >= 0.5 ? 'bg-warning' : 'bg-info') ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= $ev['confidence_pct'] ?>%">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="small d-flex align-items-center gap-2">
                                            <?php 
                                                $revStatus = $ev['review_status'] ?? ($ev['reviewed'] ? 'reviewed' : 'pending');
                                                $statusBadge = match($revStatus) {
                                                    'confirmed' => ['bg' => 'bg-danger', 'text' => 'text-white', 'icon' => 'bi-exclamation-triangle-fill', 'label' => 'Confirmed Violation'],
                                                    'dismissed' => ['bg' => 'bg-secondary', 'text' => 'text-white', 'icon' => 'bi-x-circle', 'label' => 'Dismissed'],
                                                    'reviewed' => ['bg' => 'bg-success', 'text' => 'text-white', 'icon' => 'bi-check-circle-fill', 'label' => 'Reviewed'],
                                                    default => ['bg' => 'bg-warning', 'text' => 'text-dark', 'icon' => 'bi-hourglass-split', 'label' => 'Pending Review']
                                                };
                                            ?>
                                            <span class="badge <?= $statusBadge['bg'] ?> <?= $statusBadge['text'] ?>">
                                                <i class="bi <?= $statusBadge['icon'] ?> me-1"></i><?= $statusBadge['label'] ?>
                                            </span>
                                        </div>
                                    </div>

                    <?php if (!empty($ev['review_note'])): ?>
                                        <div class="mt-2 p-2 bg-white rounded border extra-small text-muted">
                                            <strong class="text-dark"><i class="bi bi-chat-left-text me-1 text-primary"></i>Catatan:</strong>
                                            <?= e($ev['review_note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CAMERA VERIFICATION SNAPSHOTS GALLERY -->
        <div class="card border-0 shadow-sm rounded-3 mt-4">
            <div class="card-header bg-transparent border-0 pt-3 pb-2 px-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-images me-2 text-success"></i>Riwayat Foto Pengecekan Kamera oleh Pengawas</h6>
                <span class="badge bg-success-subtle text-success"><?= count($verificationChecks ?? []) ?> Foto</span>
            </div>
            <div class="card-body px-4 py-3">
                <?php if (empty($verificationChecks)): ?>
                    <p class="text-muted small mb-0">Belum ada foto pengecekan manual yang disimpan pada sesi ini. Gunakan tombol <strong>Cek &amp; Simpan Foto</strong> di kolom kiri saat kamera peserta sedang live.</p>
                <?php else: ?>
                    <div class="row g-3" id="verificationGallery">
                        <?php foreach ($verificationChecks as $vc): ?>
                            <?php $vcMeta = $vc['metadata_parsed'] ?? []; ?>
                            <div class="col-sm-6 col-md-4">
                                <div class="border rounded-3 overflow-hidden bg-light">
                                    <?php if (!empty($vcMeta['evidence_url'])): ?>
                                        <a href="<?= base_url(e($vcMeta['evidence_url'])) ?>" target="_blank">
                                            <img src="<?= base_url(e($vcMeta['evidence_url'])) ?>" class="w-100 object-fit-cover" style="height: 120px;" alt="Bukti Pengecekan">
                                        </a>
                                    <?php endif; ?>
                                    <div class="p-2 extra-small bg-white">
                                        <div class="fw-bold text-dark"><?= $vc['camera_type'] === 'camera_2' ? 'Camera 2 (Meja)' : 'Camera 1 (Wajah)' ?></div>
                                        <div class="text-muted"><?= e($vcMeta['captured_at'] ?? $vc['timestamp']) ?></div>
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

<script>
const SESSION_CAM_CFG = {
    examId: <?= (int)$exam['id'] ?>,
    sessionId: <?= (int)$session['id'] ?>,
    cam1Url: '<?= base_url('guru/monitoring/camera-frame/' . (int)$exam['id'] . '/' . (int)$session['id'] . '/camera_1') ?>',
    cam2Url: '<?= base_url('guru/monitoring/camera-frame/' . (int)$exam['id'] . '/' . (int)$session['id'] . '/camera_2') ?>',
    captureUrl: '<?= base_url('guru/monitoring/capture-evidence') ?>',
    csrfToken: '<?= csrf_token() ?>'
};

setInterval(() => {
    const ts = Date.now();
    const img1 = document.getElementById('liveStudentCam1');
    const img2 = document.getElementById('liveStudentCam2');
    if (img1) img1.src = `${SESSION_CAM_CFG.cam1Url}?t=${ts}`;
    if (img2) img2.src = `${SESSION_CAM_CFG.cam2Url}?t=${ts}`;
    const info = document.getElementById('camFeedRefreshInfo');
    if (info) info.textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID');
}, 4000);

async function captureStudentEvidence(camType) {
    const alertBox = document.getElementById('captureCheckAlert');
    alertBox.className = 'alert alert-info py-1.5 px-2 extra-small mt-2 mb-0';
    alertBox.textContent = 'Menyimpan foto pengecekan...';
    alertBox.classList.remove('d-none');

    try {
        const resp = await fetch(SESSION_CAM_CFG.captureUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                _csrf_token: SESSION_CAM_CFG.csrfToken,
                exam_id: SESSION_CAM_CFG.examId,
                session_id: SESSION_CAM_CFG.sessionId,
                camera_type: camType
            })
        });
        const data = await resp.json();
        if (data.status === 'success') {
            alertBox.className = 'alert alert-success py-1.5 px-2 extra-small mt-2 mb-0';
            alertBox.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>Tersimpan (${data.captured_at}). <a href="${data.evidence_url}" target="_blank" class="fw-bold">Lihat</a>`;
        } else {
            alertBox.className = 'alert alert-warning py-1.5 px-2 extra-small mt-2 mb-0';
            alertBox.textContent = data.message || 'Gagal mengambil foto.';
        }
    } catch (e) {
        alertBox.className = 'alert alert-danger py-1.5 px-2 extra-small mt-2 mb-0';
        alertBox.textContent = 'Koneksi error saat menyimpan bukti.';
    }
}
</script>
