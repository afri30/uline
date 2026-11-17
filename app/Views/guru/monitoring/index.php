<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-shield-check text-primary me-2"></i>Monitoring & AI Proctoring</h4>
        <p class="text-muted small mb-0">Pantau event mencurigakan, tingkat risiko peserta, deteksi kecurangan sistemik, dan verifikasi linimasa sesi ujian</p>
    </div>
</div>

<!-- STATS CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-primary-subtle text-primary me-3">
                    <i class="bi bi-camera-video-fill fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $stats['total_proctored_exams'] ?? 0 ?></div>
                    <div class="text-muted small">Ujian Ber-Proctoring</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-danger-subtle text-danger me-3">
                    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-danger"><?= $stats['total_high_risk'] ?? 0 ?></div>
                    <div class="text-muted small">Peserta Risiko Tinggi (Score &gt;50)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-warning-subtle text-warning me-3">
                    <i class="bi bi-activity fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $stats['total_events'] ?? 0 ?></div>
                    <div class="text-muted small">Total Suspicious Events</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-info-subtle text-info me-3">
                    <i class="bi bi-clipboard-check-fill fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $stats['total_unreviewed'] ?? 0 ?></div>
                    <div class="text-muted small">Event Belum Direviu</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FILTER & SEARCH BAR -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= base_url('guru/monitoring') ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Cari Judul Ujian</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Ketik kata kunci ujian..." value="<?= e($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Mata Pelajaran</label>
                <select name="subject_id" class="form-select bg-light">
                    <option value="">Semua Mata Pelajaran</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ((int)($filters['subject_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
                            <?= e($s['name']) ?> (<?= e($s['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Kelas</label>
                <select name="class_id" class="form-select bg-light">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ((int)($filters['class_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                <?php if (!empty($filters['search']) || !empty($filters['subject_id']) || !empty($filters['class_id'])): ?>
                    <a href="<?= base_url('guru/monitoring') ?>" class="btn btn-outline-secondary" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- EXAM MONITORING LIST TABLE -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-3 pb-2 px-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-display me-2"></i>Daftar Ujian Terpantau</h6>
        <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1"><?= count($exams) ?> Ujian</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($exams)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-shield-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                <p class="mb-0">Belum ada ujian yang memiliki modul monitoring proctoring aktif atau sesuai filter.</p>
                <a href="<?= base_url('guru/monitoring') ?>" class="btn btn-sm btn-outline-primary mt-2">Reset Filter</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 50px;">No</th>
                            <th>Ujian & Mata Pelajaran</th>
                            <th>Kelas</th>
                            <th class="text-center">Mode Kamera</th>
                            <th class="text-center">Peserta</th>
                            <th class="text-center">Total Events</th>
                            <th class="text-center">Status Risiko</th>
                            <th class="pe-3 text-end" style="min-width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exams as $idx => $exam): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-muted"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= e($exam['title']) ?></div>
                                    <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                                        <span class="badge bg-light text-dark border"><?= e($exam['subject_name']) ?></span>
                                        <span><i class="bi bi-clock me-1"></i><?= $exam['duration'] ?> Menit</span>
                                        <span class="badge <?= $exam['status'] === 'active' ? 'bg-success' : ($exam['status'] === 'published' ? 'bg-primary' : 'bg-secondary') ?> px-2 py-0">
                                            <?= ucfirst(e($exam['status'])) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info fw-semibold px-2 py-1">
                                        <i class="bi bi-building me-1"></i><?= e($exam['class_name']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($exam['dual_camera']): ?>
                                        <span class="badge bg-purple-subtle text-primary border border-primary-subtle px-2 py-1" title="Dual Camera: Face + Environment">
                                            <i class="bi bi-camera-video me-1"></i>Dual Cam
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border px-2 py-1">
                                            <i class="bi bi-webcam me-1"></i>Single Cam
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold text-dark"><?= $exam['total_participants'] ?></div>
                                    <small class="text-muted extra-small">peserta sesi</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $exam['total_events'] > 0 ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted' ?> px-2 py-1 fs-6">
                                        <?= $exam['total_events'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($exam['high_risk_participants'] > 0): ?>
                                        <span class="badge bg-danger px-2 py-1 animate-pulse">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i><?= $exam['high_risk_participants'] ?> Perlu Reviu
                                        </span>
                                    <?php elseif ($exam['total_events'] > 0): ?>
                                        <span class="badge bg-warning text-dark px-2 py-1">
                                            <i class="bi bi-info-circle me-1"></i>Dalam Pemantauan
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success px-2 py-1">
                                            <i class="bi bi-check-circle me-1"></i>Normal / Aman
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-3 text-end">
                                    <a href="<?= base_url('guru/monitoring/' . $exam['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-binoculars-fill me-1"></i> Pantau Sesi
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
