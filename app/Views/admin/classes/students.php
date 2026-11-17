<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= base_url('admin/classes') ?>" class="text-decoration-none">Manajemen Kelas</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($class['name']) ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Daftar Siswa Kelas <?= e($class['name']) ?></h4>
        <p class="text-muted small mb-0">Tingkat <?= e($class['grade']) ?> • Tahun Ajaran <?= e($class['academic_year']) ?></p>
    </div>
    <div>
        <a href="<?= base_url('admin/classes') ?>" class="btn btn-outline-secondary px-3 py-2 rounded-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Data Kelas
        </a>
    </div>
</div>

<!-- Class Students Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 fs-6">Siswa Terdaftar (<?= count($students) ?> Siswa)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Nama Siswa</th>
                        <th>NIS</th>
                        <th>NISN</th>
                        <th>Gender</th>
                        <th>Email & Kontak</th>
                        <th class="pe-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Belum ada siswa yang terdaftar di kelas <strong><?= e($class['name']) ?></strong>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($s['name']) ?></div>
                                    <small class="text-muted font-monospace">@<?= e($s['username']) ?></small>
                                </td>
                                <td><span class="font-monospace fw-semibold text-dark"><?= e($s['nis']) ?></span></td>
                                <td><span class="font-monospace text-muted"><?= e($s['nisn'] ?: '-') ?></span></td>
                                <td><?= ($s['gender'] === 'L') ? 'Laki-laki' : 'Perempuan' ?></td>
                                <td>
                                    <div class="small text-dark"><?= e($s['email']) ?></div>
                                    <small class="text-muted"><?= e($s['phone'] ?: '-') ?></small>
                                </td>
                                <td class="pe-4">
                                    <?php if ($s['status'] === 'active'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Inactive</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
