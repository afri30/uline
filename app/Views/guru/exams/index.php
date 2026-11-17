<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-card-checklist text-primary me-2"></i>Manajemen Ujian</h4>
        <p class="text-muted small mb-0">Kelola jadwal ujian, alokasi kelas, bank butir soal, dan konfigurasi AI Proctoring</p>
    </div>
    <div>
        <a href="<?= base_url('guru/exams/create') ?>" class="btn btn-primary shadow-sm rounded-3">
            <i class="bi bi-plus-circle me-1"></i> Buat Ujian Baru
        </a>
    </div>
</div>

<!-- STATS CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-primary-subtle text-primary me-3">
                    <i class="bi bi-journal-text fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $stats['total'] ?></div>
                    <div class="text-muted small">Total Ujian</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-secondary-subtle text-secondary me-3">
                    <i class="bi bi-pencil-square fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $stats['draft'] ?></div>
                    <div class="text-muted small">Draft (Belum Rilis)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-info-subtle text-info me-3">
                    <i class="bi bi-send-check fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $stats['published'] ?></div>
                    <div class="text-muted small">Terjadwal (Published)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-body">
            <div class="d-flex align-items-center">
                <div class="avatar-circle bg-success-subtle text-success me-3">
                    <i class="bi bi-play-circle fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-dark"><?= $stats['active'] + $stats['finished'] ?></div>
                    <div class="text-muted small">Aktif / Selesai</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FILTER & SEARCH BAR -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= base_url('guru/exams') ?>" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0 ps-0" 
                           placeholder="Cari judul atau deskripsi ujian..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="subject_id" class="form-select bg-light">
                    <option value="">-- Semua Mata Pelajaran --</option>
                    <?php foreach ($mySubjects as $subj): ?>
                        <option value="<?= $subj['id'] ?>" <?= ($filters['subject_id'] == $subj['id']) ? 'selected' : '' ?>>
                            [<?= htmlspecialchars($subj['code']) ?>] <?= htmlspecialchars($subj['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="class_id" class="form-select bg-light">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($allClasses as $cls): ?>
                        <option value="<?= $cls['id'] ?>" <?= ($filters['class_id'] == $cls['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cls['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select bg-light">
                    <option value="">-- Status --</option>
                    <option value="draft" <?= ($filters['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= ($filters['status'] === 'published') ? 'selected' : '' ?>>Published</option>
                    <option value="active" <?= ($filters['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="finished" <?= ($filters['status'] === 'finished') ? 'selected' : '' ?>>Finished</option>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button>
                <a href="<?= base_url('guru/exams') ?>" class="btn btn-light border" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- EXAM LIST TABLE -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Judul Ujian</th>
                    <th>Mata Pelajaran & Kelas</th>
                    <th>Jadwal Pelaksanaan</th>
                    <th>Durasi & Soal</th>
                    <th>Fitur AI & Acak</th>
                    <th>Status</th>
                    <th style="width: 140px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($exams)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <div class="fw-semibold">Belum ada jadwal ujian yang ditemukan.</div>
                            <small>Klik tombol "Buat Ujian Baru" untuk membuat jadwal ujian.</small>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1 + (($page - 1) * 10); ?>
                    <?php foreach ($exams as $e): ?>
                        <tr>
                            <td class="text-center text-muted small"><?= $no++ ?></td>
                            <td>
                                <a href="<?= base_url('guru/exams/show?id=' . $e['id']) ?>" class="fw-bold text-dark text-decoration-none">
                                    <?= htmlspecialchars($e['title']) ?>
                                </a>
                                <?php if (!empty($e['description'])): ?>
                                    <div class="text-muted small text-truncate" style="max-width: 250px;">
                                        <?= htmlspecialchars($e['description']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><span class="badge bg-primary-subtle text-primary fw-medium">[<?= htmlspecialchars($e['subject_code']) ?>]</span> <?= htmlspecialchars($e['subject_name']) ?></div>
                                <div class="text-muted small"><i class="bi bi-people me-1"></i>Kelas <?= htmlspecialchars($e['class_name']) ?></div>
                            </td>
                            <td>
                                <div class="small fw-semibold"><i class="bi bi-calendar-event me-1 text-muted"></i><?= date('d M Y, H:i', strtotime($e['start_at'])) ?></div>
                                <div class="text-muted small"><i class="bi bi-clock me-1"></i>s/d <?= date('d M Y, H:i', strtotime($e['end_at'])) ?></div>
                            </td>
                            <td>
                                <div><span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-stopwatch me-1"></i><?= $e['duration'] ?> Menit</span></div>
                                <div class="text-muted small mt-1">
                                    <strong><?= $e['total_questions'] ?></strong> Soal (<?= $e['total_score'] ?> Poin)
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php if ($e['ai_proctoring']): ?>
                                        <span class="badge bg-danger-subtle text-danger" title="AI Proctoring Aktif"><i class="bi bi-robot"></i> AI</span>
                                    <?php endif; ?>
                                    <?php if ($e['dual_camera']): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis" title="Dual Camera Aktif"><i class="bi bi-camera-video"></i> Dual Cam</span>
                                    <?php endif; ?>
                                    <?php if ($e['random_questions']): ?>
                                        <span class="badge bg-info-subtle text-info" title="Acak Soal"><i class="bi bi-shuffle"></i> Soal</span>
                                    <?php endif; ?>
                                    <?php if ($e['random_options']): ?>
                                        <span class="badge bg-light text-dark border" title="Acak Opsi"><i class="bi bi-shuffle"></i> Jawaban</span>
                                    <?php endif; ?>
                                    <?php if (!$e['ai_proctoring'] && !$e['dual_camera'] && !$e['random_questions'] && !$e['random_options']): ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($e['status'] === 'draft'): ?>
                                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1">Draft</span>
                                <?php elseif ($e['status'] === 'published'): ?>
                                    <span class="badge bg-primary-subtle text-primary px-2 py-1">Published</span>
                                <?php elseif ($e['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success px-2 py-1">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-dark-subtle text-dark px-2 py-1">Finished</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-gear me-1"></i> Aksi
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item" href="<?= base_url('guru/exams/show?id=' . $e['id']) ?>">
                                                <i class="bi bi-eye text-primary me-2"></i> Detail Ujian
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= base_url('guru/exams/edit?id=' . $e['id']) ?>">
                                                <i class="bi bi-pencil-square text-warning me-2"></i> Edit Ujian
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <!-- Quick Status Toggle -->
                                        <?php if ($e['status'] === 'draft'): ?>
                                            <li>
                                                <form action="<?= base_url('guru/exams/toggle-status') ?>" method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                                    <input type="hidden" name="status" value="published">
                                                    <button type="submit" class="dropdown-item text-primary">
                                                        <i class="bi bi-send-check me-2"></i> Publish Ujian
                                                    </button>
                                                </form>
                                            </li>
                                        <?php else: ?>
                                            <li>
                                                <form action="<?= base_url('guru/exams/toggle-status') ?>" method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                                    <input type="hidden" name="status" value="draft">
                                                    <button type="submit" class="dropdown-item text-secondary">
                                                        <i class="bi bi-pencil me-2"></i> Kembalikan ke Draft
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button" class="dropdown-item text-danger" 
                                                    onclick="confirmDelete(<?= $e['id'] ?>, '<?= htmlspecialchars(addslashes($e['title'])) ?>')">
                                                <i class="bi bi-trash me-2"></i> Hapus
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Total: <strong><?= $totalExams ?></strong> ujian</span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= base_url('guru/exams?' . http_build_query(array_merge($filters, ['page' => $page - 1]))) ?>">&laquo;</a>
                    </li>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= base_url('guru/exams?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= base_url('guru/exams?' . http_build_query(array_merge($filters, ['page' => $page + 1]))) ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL CONFIRM DELETE -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-body text-center p-4">
                <div class="avatar-circle bg-danger-subtle text-danger mx-auto mb-3" style="width: 60px; height: 60px; font-size: 28px;">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h5 class="fw-bold mb-2">Hapus Ujian?</h5>
                <p class="text-muted small mb-4">
                    Apakah Anda yakin ingin menghapus ujian <strong id="deleteExamTitle"></strong>? Tindakan ini tidak dapat dibatalkan.
                </p>
                <form action="<?= base_url('guru/exams/delete') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="deleteExamId">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light w-50" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger w-50">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, title) {
    document.getElementById('deleteExamId').value = id;
    document.getElementById('deleteExamTitle').textContent = title;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
