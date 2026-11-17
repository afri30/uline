<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-camera-video-fill text-primary me-2"></i>Cek & Uji Kamera Peserta</h4>
        <p class="text-muted small mb-0">Pastikan Kamera Wajah (dan Kamera Meja untuk ujian Dual Camera) berfungsi baik sebelum memasuki ruang ujian</p>
    </div>
    <div>
        <a href="<?= base_url('peserta/exams') ?>" class="btn btn-outline-primary btn-sm rounded-3">
            <i class="bi bi-card-checklist me-1"></i> Lihat Ujian Online
        </a>
    </div>
</div>

<?php
$verifyUrl = base_url('peserta/camera-check/verify');
$snapshotUrl = base_url('peserta/camera-check/snapshot');
$accentColor = 'primary';
$camWidgetTitle = 'Uji Kamera Peserta Ujian (Kamera 1 Wajah & Kamera 2 Meja)';
require dirname(__DIR__, 2) . '/layouts/partials/camera_checker_widget.php';
?>

<!-- Available Exams Quick Launch -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-card-checklist text-primary me-2"></i>Ujian Aktif untuk Kelas Anda</h6>
        <span class="badge bg-primary-subtle text-primary rounded-pill"><?= count($availableExams ?? []) ?> Ujian</span>
    </div>
    <div class="card-body p-4">
        <?php if (empty($availableExams)): ?>
            <div class="text-center py-4 text-muted small">
                <i class="bi bi-calendar-check fs-2 d-block mb-2 text-secondary opacity-50"></i>
                Tidak ada ujian aktif untuk kelas Anda saat ini.
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($availableExams as $ex): ?>
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 bg-light d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-bold text-dark"><?= e($ex['title']) ?></div>
                                <small class="text-muted">
                                    <?= e($ex['subject_name']) ?> • <?= (int)$ex['duration'] ?> Menit
                                    <?php if (!empty($ex['dual_camera'])): ?>
                                        • <span class="text-primary fw-semibold">Dual Camera</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <a href="<?= base_url('peserta/exams/detail?id=' . (int)$ex['id']) ?>" class="btn btn-sm btn-primary rounded-3 flex-shrink-0">
                                Masuk Ujian <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

