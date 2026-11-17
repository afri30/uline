<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= base_url('peserta/dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('peserta/exams') ?>" class="text-decoration-none">Daftar Ujian</a></li>
                <li class="breadcrumb-item active" aria-current="page">Konfirmasi Ujian</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0"><i class="bi bi-info-circle text-primary me-2"></i>Konfirmasi & Petunjuk Ujian</h4>
    </div>
    <div>
        <a href="<?= base_url('peserta/exams') ?>" class="btn btn-light border rounded-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

<div class="row g-4 justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-primary text-white p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-white text-primary px-3 py-1.5 rounded-pill font-monospace small">
                        [<?= htmlspecialchars($exam['subject_code']) ?>] <?= htmlspecialchars($exam['subject_name']) ?>
                    </span>
                    <span class="badge bg-white bg-opacity-25 text-white px-3 py-1.5 rounded-pill font-monospace small">
                        Kelas <?= htmlspecialchars($exam['class_name']) ?>
                    </span>
                </div>
                <h3 class="fw-bold mb-1"><?= htmlspecialchars($exam['title']) ?></h3>
                <p class="text-white-50 small mb-0">Guru Pengampu: <strong><?= htmlspecialchars($exam['teacher_name']) ?></strong></p>
            </div>

            <div class="card-body p-4 p-lg-5">
                <!-- EXAM METADATA GRID -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3 text-center">
                            <div class="text-muted extra-small fw-semibold text-uppercase">Durasi</div>
                            <div class="fs-4 fw-bold text-primary"><?= $exam['duration'] ?></div>
                            <small class="text-muted">Menit</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3 text-center">
                            <div class="text-muted extra-small fw-semibold text-uppercase">Jumlah Soal</div>
                            <div class="fs-4 fw-bold text-dark"><?= $questionsCount ?></div>
                            <small class="text-muted">Butir Pertanyaan</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3 text-center">
                            <div class="text-muted extra-small fw-semibold text-uppercase">Total Bobot</div>
                            <div class="fs-4 fw-bold text-success"><?= $exam['total_score'] ?></div>
                            <small class="text-muted">Poin Maksimal</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3 text-center">
                            <div class="text-muted extra-small fw-semibold text-uppercase">Proctoring</div>
                            <div class="fs-4 fw-bold text-danger"><?= $exam['ai_proctoring'] ? 'AKTIF' : 'OFF' ?></div>
                            <small class="text-muted"><?= $exam['dual_camera'] ? '+ Dual Cam' : 'Webcam' ?></small>
                        </div>
                    </div>
                </div>

                <!-- DESCRIPTION & INSTRUCTIONS -->
                <?php if (!empty($exam['description'])): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-card-text text-primary me-2"></i>Deskripsi & Pesan Penguji:</h6>
                        <div class="bg-light p-3 rounded-3 text-muted small border-start border-3 border-primary">
                            <?= nl2br(htmlspecialchars($exam['description'])) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- RULES & TERMS -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-exclamation text-warning me-2"></i>Tata Tertib & Petunjuk Pengerjaan:</h6>
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item px-0 py-2 border-0 d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-success mt-0.5"></i>
                            <div><strong>Waktu Berjalan Otomatis</strong>: Countdown timer akan langsung dihitung saat Anda mengklik tombol "Mulai Ujian". Waktu tidak akan berhenti meskipun halaman di-refresh.</div>
                        </li>
                        <li class="list-group-item px-0 py-2 border-0 d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-success mt-0.5"></i>
                            <div><strong>Penyimpanan Jawaban Otomatis (Auto-Save)</strong>: Pilihan jawaban Anda disimpan secara real-time ke server setiap kali Anda memilih opsi.</div>
                        </li>
                        <li class="list-group-item px-0 py-2 border-0 d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-success mt-0.5"></i>
                            <div><strong>Navigasi Soal Bebas</strong>: Anda dapat berpindah ke nomor soal berapa pun melalui tombol Sebelumnya/Berikutnya atau langsung mengklik nomor pada kisi-kisi soal.</div>
                        </li>
                        <li class="list-group-item px-0 py-2 border-0 d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-success mt-0.5"></i>
                            <div><strong>Pengumpulan Akhir</strong>: Klik tombol "Selesai & Kumpulkan Ujian" setelah selesai, atau sistem akan mengumpulkan jawaban secara otomatis saat waktu habis.</div>
                        </li>
                    </ul>
                </div>

                <!-- PRE-EXAM CAMERA CHECK PANEL -->
                <div class="mb-4 p-3 p-md-4 bg-light rounded-4 border" id="preExamCameraPanel">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div>
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-camera-video-fill text-primary me-2"></i>Pengecekan Kamera Sebelum Ujian</h6>
                            <small class="text-muted">Uji kamera Anda terlebih dahulu sebelum timer ujian dimulai.</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span id="preCamBadge" class="badge bg-secondary-subtle text-secondary border px-2.5 py-1 rounded-pill extra-small">Belum Diuji</span>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-3 fw-semibold" id="btnTestPreCam" onclick="runPreExamCameraTest()">
                                <i class="bi bi-webcam me-1"></i> Cek Kamera Sekarang
                            </button>
                        </div>
                    </div>

                    <div id="preCamAlert" class="alert d-none small py-2 px-3 rounded-3 mb-3"></div>

                    <div class="row g-3">
                        <div class="<?= !empty($exam['dual_camera']) ? 'col-md-6' : 'col-12' ?>">
                            <div class="bg-white border rounded-3 overflow-hidden">
                                <div class="px-2.5 py-1.5 border-bottom d-flex justify-content-between align-items-center">
                                    <small class="fw-bold text-dark">Kamera 1 (Wajah)</small>
                                    <span id="preCam1Status" class="badge bg-secondary extra-small">Off</span>
                                </div>
                                <div class="bg-dark position-relative d-flex align-items-center justify-content-center" style="height: 150px;">
                                    <video id="preVideo1" autoplay playsinline muted class="w-100 h-100 object-fit-cover"></video>
                                    <div id="preCam1Ph" class="position-absolute text-center text-white-50 p-2">
                                        <i class="bi bi-person-video fs-2 d-block mb-1"></i>
                                        <span class="extra-small">Klik "Cek Kamera Sekarang"</span>
                                    </div>
                                </div>
                                <div class="p-2 border-top">
                                    <select id="preSelect1" class="form-select form-select-sm extra-small bg-light" onchange="switchPreCam(1, this.value)">
                                        <option value="">Perangkat Kamera 1...</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($exam['dual_camera'])): ?>
                        <div class="col-md-6">
                            <div class="bg-white border rounded-3 overflow-hidden">
                                <div class="px-2.5 py-1.5 border-bottom d-flex justify-content-between align-items-center">
                                    <small class="fw-bold text-dark">Kamera 2 (Meja / Sekitar)</small>
                                    <span id="preCam2Status" class="badge bg-secondary extra-small">Off</span>
                                </div>
                                <div class="bg-dark position-relative d-flex align-items-center justify-content-center" style="height: 150px;">
                                    <video id="preVideo2" autoplay playsinline muted class="w-100 h-100 object-fit-cover"></video>
                                    <div id="preCam2Ph" class="position-absolute text-center text-white-50 p-2">
                                        <i class="bi bi-camera-video fs-2 d-block mb-1"></i>
                                        <span class="extra-small" id="preCam2PhText">Opsional / Fallback 1 Kamera</span>
                                    </div>
                                </div>
                                <div class="p-2 border-top">
                                    <select id="preSelect2" class="form-select form-select-sm extra-small bg-light" onchange="switchPreCam(2, this.value)">
                                        <option value="">Perangkat Kamera 2...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- START EXAM ACTION -->
                <div class="d-grid gap-2 pt-3 border-top">
                    <form action="<?= base_url('peserta/exams/start') ?>" method="POST" onsubmit="stopPreExamStreams()">
                        <?= csrf_field() ?>
                        <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">

                        <?php if ($session && $session['status'] === 'started'): ?>
                            <button type="submit" class="btn btn-warning w-100 py-3 rounded-3 fw-bold fs-6 shadow-sm">
                                <i class="bi bi-play-circle-fill me-2"></i> Lanjutkan Ujian yang Sedang Berjalan
                            </button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold fs-6 shadow-sm">
                                <i class="bi bi-pencil-square me-2"></i> Saya Siap, Mulai Ujian Sekarang
                            </button>
                        <?php endif; ?>
                    </form>
                    <a href="<?= base_url('peserta/exams') ?>" class="btn btn-light border py-2 rounded-3 text-muted">
                        Batal dan Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const PRE_CAM = { stream1: null, stream2: null, devices: [] };

function showPreCamAlert(type, html) {
    const el = document.getElementById('preCamAlert');
    el.className = `alert alert-${type} small py-2 px-3 rounded-3 mb-3`;
    el.innerHTML = html;
    el.classList.remove('d-none');
}

async function runPreExamCameraTest() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showPreCamAlert('danger', 'Browser Anda tidak mendukung WebRTC kamera.');
        return;
    }
    try {
        const tmp = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        tmp.getTracks().forEach(t => t.stop());

        const all = await navigator.mediaDevices.enumerateDevices();
        PRE_CAM.devices = all.filter(d => d.kind === 'videoinput');

        const s1 = document.getElementById('preSelect1');
        const s2 = document.getElementById('preSelect2');
        s1.innerHTML = '';
        if (s2) s2.innerHTML = '';

        PRE_CAM.devices.forEach((dev, i) => {
            const lbl = dev.label || `Kamera ${i + 1}`;
            s1.add(new Option(`CAM 1: ${lbl}`, dev.deviceId));
            if (s2) s2.add(new Option(`CAM 2: ${lbl}`, dev.deviceId));
        });

        let cam1Id = sessionStorage.getItem('cam1DeviceId');
        if (!cam1Id || !PRE_CAM.devices.some(d => d.deviceId === cam1Id)) {
            cam1Id = PRE_CAM.devices[0]?.deviceId || '';
        }
        s1.value = cam1Id;
        await startPreStream(1, cam1Id);

        if (s2) {
            if (PRE_CAM.devices.length >= 2) {
                let cam2Id = sessionStorage.getItem('cam2DeviceId');
                if (!cam2Id || cam2Id === cam1Id || !PRE_CAM.devices.some(d => d.deviceId === cam2Id)) {
                    cam2Id = PRE_CAM.devices[1].deviceId;
                }
                s2.value = cam2Id;
                await startPreStream(2, cam2Id);
            } else {
                document.getElementById('preCam2Status').textContent = 'Fallback 1 Cam';
                document.getElementById('preCam2PhText').textContent = 'Hanya 1 kamera terdeteksi (Fallback aktif)';
            }
        }

        const badge = document.getElementById('preCamBadge');
        badge.className = 'badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill extra-small';
        badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Kamera Siap';
        showPreCamAlert('success', `Berhasil mendeteksi <strong>${PRE_CAM.devices.length} kamera</strong>. Pengaturan kamera telah disimpan otomatis untuk ruang ujian.`);
    } catch (err) {
        const badge = document.getElementById('preCamBadge');
        badge.className = 'badge bg-danger-subtle text-danger border px-2.5 py-1 rounded-pill extra-small';
        badge.textContent = 'Izin Ditolak';
        showPreCamAlert('warning', 'Akses kamera belum diizinkan atau sedang dipakai aplikasi lain. Silakan izinkan akses kamera pada browser.');
    }
}

async function startPreStream(camNum, deviceId) {
    if (camNum === 1 && PRE_CAM.stream1) PRE_CAM.stream1.getTracks().forEach(t => t.stop());
    if (camNum === 2 && PRE_CAM.stream2) PRE_CAM.stream2.getTracks().forEach(t => t.stop());

    const stream = await navigator.mediaDevices.getUserMedia({
        video: deviceId ? { deviceId: { exact: deviceId } } : true,
        audio: false
    });
    if (camNum === 1) {
        PRE_CAM.stream1 = stream;
        sessionStorage.setItem('cam1DeviceId', deviceId || '');
    } else {
        PRE_CAM.stream2 = stream;
        sessionStorage.setItem('cam2DeviceId', deviceId || '');
    }
    const vid = document.getElementById(`preVideo${camNum}`);
    vid.srcObject = stream;
    await vid.play().catch(() => {});
    document.getElementById(`preCam${camNum}Ph`).classList.add('d-none');
    const st = document.getElementById(`preCam${camNum}Status`);
    st.className = 'badge bg-success extra-small';
    st.textContent = 'Aktif';
}

async function switchPreCam(camNum, devId) {
    if (!devId) return;
    await startPreStream(camNum, devId);
}

function stopPreExamStreams() {
    [PRE_CAM.stream1, PRE_CAM.stream2].forEach(s => {
        if (s) s.getTracks().forEach(t => t.stop());
    });
}

window.addEventListener('beforeunload', stopPreExamStreams);
</script>
