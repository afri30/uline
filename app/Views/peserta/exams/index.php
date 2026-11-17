<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-card-checklist text-primary me-2"></i>Daftar Ujian Online</h4>
        <p class="text-muted small mb-0">Ujian terjadwal untuk Kelas <strong><?= htmlspecialchars($student['class_name'] ?? '-') ?></strong></p>
    </div>
    <div class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill font-monospace small">
        <i class="bi bi-person-circle me-1"></i> NIS: <?= htmlspecialchars($student['nis'] ?? '-') ?>
    </div>
</div>

<!-- AVAILABLE EXAMS LIST -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-1"><i class="bi bi-play-circle text-primary me-2"></i>Ujian yang Tersedia</h5>
            <p class="text-muted small mb-0">Pilih ujian untuk melihat petunjuk dan memulai pengerjaan</p>
        </div>
        <span class="badge bg-primary rounded-pill px-3 py-2"><?= count($availableExams) ?> Ujian</span>
    </div>
    <div class="card-body p-4">
        <?php if (empty($availableExams)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-calendar-x fs-1 opacity-50 d-block mb-2"></i>
                <div class="fw-semibold">Tidak ada ujian yang sedang aktif untuk kelas Anda saat ini.</div>
                <small>Silakan hubungi guru pengampu jika jadwal ujian belum muncul.</small>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($availableExams as $ex): ?>
                    <?php 
                        $hasSession = !empty($ex['session_id']);
                        $sessionStatus = $ex['session_status'] ?? null;
                        $isFinished = in_array($sessionStatus, ['finished', 'expired'], true);
                        $isStarted = ($sessionStatus === 'started');
                    ?>
                    <div class="col-12 col-lg-6">
                        <div class="card h-100 border rounded-4 p-3 shadow-none transition-hover <?= $isStarted ? 'border-warning bg-warning-subtle bg-opacity-10' : ($isFinished ? 'border-success bg-success-subtle bg-opacity-10' : 'bg-body') ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-primary-subtle text-primary fw-medium px-2.5 py-1.5 rounded-pill">
                                    [<?= htmlspecialchars($ex['subject_code']) ?>] <?= htmlspecialchars($ex['subject_name']) ?>
                                </span>
                                <?php if ($isFinished): ?>
                                    <span class="badge bg-success-subtle text-success px-2.5 py-1.5 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>
                                <?php elseif ($isStarted): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis px-2.5 py-1.5 rounded-pill"><i class="bi bi-clock-history me-1"></i>Sedang Berlangsung</span>
                                <?php else: ?>
                                    <span class="badge bg-info-subtle text-info-emphasis px-2.5 py-1.5 rounded-pill"><i class="bi bi-bell-fill me-1"></i>Siap Dikerjakan</span>
                                <?php endif; ?>
                            </div>

                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($ex['title']) ?></h5>
                            <p class="text-muted small mb-3 text-truncate-2">
                                <?= htmlspecialchars($ex['description'] ?: 'Tidak ada petunjuk tambahan.') ?>
                            </p>

                            <div class="row g-2 small text-muted mb-3 py-2 border-top border-bottom">
                                <div class="col-6">
                                    <i class="bi bi-person me-1"></i>Guru: <strong><?= htmlspecialchars($ex['teacher_name']) ?></strong>
                                </div>
                                <div class="col-6">
                                    <i class="bi bi-stopwatch me-1"></i>Durasi: <strong><?= $ex['duration'] ?> Menit</strong>
                                </div>
                                <div class="col-6">
                                    <i class="bi bi-journal-text me-1"></i>Jumlah: <strong><?= $ex['total_questions'] ?> Soal</strong>
                                </div>
                                <div class="col-6">
                                    <i class="bi bi-award me-1"></i>Total Poin: <strong><?= $ex['total_score'] ?> Pts</strong>
                                </div>
                            </div>

                            <!-- PROCTORING BADGES -->
                            <div class="d-flex flex-wrap gap-1 mb-3">
                                <?php if ($ex['ai_proctoring']): ?>
                                    <span class="badge bg-danger-subtle text-danger"><i class="bi bi-robot me-1"></i>AI Proctoring</span>
                                <?php endif; ?>
                                <?php if ($ex['dual_camera']): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-camera-video me-1"></i>Dual Cam</span>
                                <?php endif; ?>
                                <?php if ($ex['random_questions']): ?>
                                    <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-shuffle me-1"></i>Acak Soal</span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-auto pt-2 d-flex justify-content-between align-items-center">
                                <span class="text-muted extra-small">
                                    <i class="bi bi-calendar-event me-1"></i><?= date('d M Y, H:i', strtotime($ex['start_at'])) ?>
                                </span>

                                <?php if ($isFinished): ?>
                                    <a href="<?= base_url('peserta/exams/result?session_id=' . $ex['session_id']) ?>" class="btn btn-sm btn-outline-success px-3 rounded-pill">
                                        <i class="bi bi-trophy me-1"></i> Lihat Hasil
                                    </a>
                                <?php elseif ($isStarted): ?>
                                    <a href="<?= base_url('peserta/exams/room?session_id=' . $ex['session_id']) ?>" class="btn btn-sm btn-warning px-3 rounded-pill fw-medium">
                                        <i class="bi bi-play-fill me-1"></i> Lanjutkan Ujian
                                    </a>
                                <?php else: ?>
                                    <a href="<?= base_url('peserta/exams/detail?id=' . $ex['id']) ?>" class="btn btn-sm btn-primary px-3 rounded-pill">
                                        <i class="bi bi-arrow-right-circle me-1"></i> Mulai Ujian
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- HISTORY OF EXAMS -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
        <h5 class="fw-bold mb-1"><i class="bi bi-clock-history text-secondary me-2"></i>Riwayat Ujian Anda</h5>
        <p class="text-muted small mb-0">Daftar rekaman ujian yang telah Anda selesaikan</p>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Mata Pelajaran & Judul Ujian</th>
                    <th>Waktu Mulai</th>
                    <th>Waktu Selesai</th>
                    <th>Status</th>
                    <th>Nilai Akhir</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted small">
                            Belum ada riwayat pengerjaan ujian.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $h): ?>
                        <?php
                            $historyStatus = $h['session_status'] ?? ($h['status'] ?? '');
                            $earnedScore = (int)($h['earned_score'] ?? ($h['total_earned_score'] ?? 0));
                            $maxScore = (int)($h['max_score'] ?? 0);
                            $sessionId = (int)($h['session_id'] ?? ($h['id'] ?? 0));
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars((string)($h['exam_title'] ?? '')) ?></div>
                                <small class="text-muted">[<?= htmlspecialchars((string)($h['subject_code'] ?? '')) ?>] <?= htmlspecialchars((string)($h['subject_name'] ?? '')) ?></small>
                            </td>
                            <td class="small text-muted"><?= !empty($h['started_at']) ? date('d M Y, H:i', strtotime($h['started_at'])) : '-' ?></td>
                            <td class="small text-muted"><?= !empty($h['finished_at']) ? date('d M Y, H:i', strtotime($h['finished_at'])) : '-' ?></td>
                            <td>
                                <?php if ($historyStatus === 'finished'): ?>
                                    <span class="badge bg-success-subtle text-success">Selesai</span>
                                <?php elseif ($historyStatus === 'expired'): ?>
                                    <span class="badge bg-danger-subtle text-danger">Waktu Habis</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis">Berlangsung</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-bold text-primary fs-6"><?= $earnedScore ?></span>
                                <span class="text-muted small">/ <?= $maxScore ?> Pts</span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?= base_url('peserta/exams/result?session_id=' . $sessionId) ?>" class="btn btn-sm btn-light border rounded-pill">
                                    <i class="bi bi-eye text-primary me-1"></i> Rincian
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.transition-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.transition-hover:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important; }
.text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
