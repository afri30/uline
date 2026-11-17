<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-webcam-fill text-success me-2"></i>Cek & Monitor Kamera Guru</h4>
        <p class="text-muted small mb-0">Uji kesiapan kamera Pengawas dan pantau langsung kamera peserta didik pada ujian Anda</p>
    </div>
    <div>
        <a href="<?= base_url('guru/monitoring') ?>" class="btn btn-outline-primary btn-sm rounded-3">
            <i class="bi bi-display me-1"></i> Dashboard Live Monitoring
        </a>
    </div>
</div>

<?php
$verifyUrl = base_url('guru/camera-check/verify');
$snapshotUrl = base_url('guru/camera-check/snapshot');
$accentColor = 'success';
$camWidgetTitle = 'Pengecekan Kamera Guru Pengawas';
require dirname(__DIR__, 2) . '/layouts/partials/camera_checker_widget.php';
?>

<!-- Exam Camera Monitoring Directory for Guru -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-people-fill text-primary me-2"></i>Pantau Kamera Peserta Didik Per Ujian</h6>
        <span class="badge bg-light text-dark border"><?= count($exams ?? []) ?> Ujian</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($exams)): ?>
            <div class="text-center py-5 text-muted small">
                <i class="bi bi-camera-video-off fs-2 d-block mb-2 opacity-50"></i>
                Belum ada ujian yang Anda buat.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Judul Ujian</th>
                            <th>Kelas</th>
                            <th class="text-center">Mode Kamera Murid</th>
                            <th class="text-center">Peserta Online</th>
                            <th class="text-center">Kamera Guru</th>
                            <th class="pe-4 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exams as $ex): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= e($ex['title']) ?></div>
                                    <small class="text-muted"><?= e($ex['subject_name']) ?> • Status: <?= strtoupper(e($ex['status'])) ?></small>
                                </td>
                                <td><span class="badge bg-info-subtle text-info"><?= e($ex['class_name']) ?></span></td>
                                <td class="text-center">
                                    <?php if (!empty($ex['dual_camera'])): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-camera-video me-1"></i>Dual Camera</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border"><i class="bi bi-webcam me-1"></i>Single Camera</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= ($ex['online_count'] ?? 0) > 0 ? 'bg-success' : 'bg-secondary-subtle text-secondary' ?> px-2.5 py-1">
                                        <?= (int)($ex['online_count'] ?? 0) ?> / <?= (int)($ex['total_participants'] ?? 0) ?> Online
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($ex['proctor_cam_live'])): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-broadcast me-1"></i>Live Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Standby</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="<?= base_url('guru/monitoring/' . (int)$ex['id']) ?>" class="btn btn-sm btn-primary rounded-3">
                                        <i class="bi bi-camera-video-fill me-1"></i> Buka Kamera & Pantau
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

