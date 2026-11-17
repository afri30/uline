<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-mortarboard-fill text-primary me-2"></i>Manajemen Peserta Didik</h4>
        <p class="text-muted small mb-0">Kelola akun peserta ujian, nomor induk (NIS/NISN), dan penempatan kelas</p>
    </div>
    <button type="button" class="btn btn-primary px-3 py-2 rounded-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Peserta Didik
    </button>
</div>

<!-- Statistics Metric Cards (Tampilan Ringkasan seperti Dashboard) -->
<div class="row g-3 mb-4">
    <!-- Stat 1: Total Peserta -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="<?= base_url('admin/students') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-card <?= (empty($classId) && empty($status)) ? 'border-primary border-2 shadow' : '' ?>">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-mortarboard-fill fs-4"></i>
                        </div>
                        <span class="badge bg-primary-subtle text-primary rounded-pill">Semua</span>
                    </div>
                    <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Total Siswa</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['total'] ?? 0) ?></h3>
                </div>
            </div>
        </a>
    </div>

    <!-- Stat 2: Memiliki Rombel -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="card border-0 shadow-sm rounded-4 h-100 hover-card">
            <div class="card-body p-3.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="stat-icon bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-building-check fs-4"></i>
                    </div>
                    <span class="badge bg-success-subtle text-success rounded-pill">Terdaftar</span>
                </div>
                <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Ada Kelas / Rombel</div>
                <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['with_class'] ?? 0) ?></h3>
            </div>
        </div>
    </div>

    <!-- Stat 3: Belum Ada Kelas -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="card border-0 shadow-sm rounded-4 h-100 hover-card">
            <div class="card-body p-3.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="stat-icon bg-warning-subtle text-warning rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-building-exclamation fs-4"></i>
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">Perlu Kelas</span>
                </div>
                <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Tanpa Kelas</div>
                <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['without_class'] ?? 0) ?></h3>
            </div>
        </div>
    </div>

    <!-- Stat 4: Akun Aktif -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="<?= base_url('admin/students?status=active') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-card <?= ($status === 'active') ? 'border-success border-2 shadow' : '' ?>">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-person-check-fill fs-4"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success rounded-pill">Aktif</span>
                    </div>
                    <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Siswa Aktif</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['active'] ?? 0) ?></h3>
                </div>
            </div>
        </a>
    </div>

    <!-- Stat 5: Akun Nonaktif -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="<?= base_url('admin/students?status=inactive') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-card <?= ($status === 'inactive') ? 'border-secondary border-2 shadow' : '' ?>">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-secondary-subtle text-muted rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-person-x-fill fs-4"></i>
                        </div>
                        <span class="badge bg-secondary-subtle text-muted rounded-pill">Nonaktif</span>
                    </div>
                    <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Siswa Non-Aktif</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['inactive'] ?? 0) ?></h3>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3 p-md-4">
        <form action="<?= base_url('admin/students') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control bg-light border-start-0 ps-0" name="q" value="<?= e($search ?? '') ?>" placeholder="Cari nama siswa, NIS, NISN, username...">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="class_id" class="form-select bg-light">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($classId == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?> (Tingkat <?= e($c['grade']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select bg-light">
                    <option value="">Semua Status</option>
                    <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($status === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="bi bi-filter me-1"></i> Filter</button>
                <?php if (!empty($search) || !empty($classId) || !empty($status)): ?>
                    <a href="<?= base_url('admin/students') ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Students Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Nama & Username</th>
                        <th>NIS / NISN</th>
                        <th>Kelas / Rombel</th>
                        <th>Email & Kontak</th>
                        <th>Status</th>
                        <th class="pe-4 text-end" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Tidak ada data peserta didik yang ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = ($currentPage - 1) * $perPage + 1; ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($s['name']) ?></div>
                                    <small class="text-muted font-monospace">@<?= e($s['username']) ?> • <?= ($s['gender'] === 'L') ? 'Laki-laki' : 'Perempuan' ?></small>
                                </td>
                                <td>
                                    <div class="font-monospace fw-semibold text-dark small">NIS: <?= e($s['nis']) ?></div>
                                    <small class="text-muted font-monospace">NISN: <?= e($s['nisn'] ?: '-') ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($s['class_name'])): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">
                                            <?= e($s['class_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-muted">Belum ada kelas</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small text-dark"><?= e($s['email']) ?></div>
                                    <small class="text-muted"><?= e($s['phone'] ?: '-') ?></small>
                                </td>
                                <td>
                                    <?php if ($s['status'] === 'active'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Toggle Status -->
                                        <a href="<?= base_url('admin/students/toggle-status?id=' . $s['id']) ?>" class="btn btn-outline-secondary" title="Ubah Status Aktif/Nonaktif">
                                            <i class="bi <?= ($s['status'] === 'active') ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?> fs-6"></i>
                                        </a>

                                        <!-- Edit Modal Trigger -->
                                        <button type="button" class="btn btn-outline-primary btn-edit-student"
                                                data-id="<?= $s['id'] ?>"
                                                data-name="<?= e($s['name']) ?>"
                                                data-username="<?= e($s['username']) ?>"
                                                data-email="<?= e($s['email']) ?>"
                                                data-class-id="<?= e($s['class_id'] ?? '') ?>"
                                                data-nis="<?= e($s['nis']) ?>"
                                                data-nisn="<?= e($s['nisn'] ?? '') ?>"
                                                data-phone="<?= e($s['phone'] ?? '') ?>"
                                                data-gender="<?= e($s['gender'] ?? 'L') ?>"
                                                data-status="<?= e($s['status']) ?>"
                                                title="Edit Data Siswa">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Delete Modal Trigger -->
                                        <button type="button" class="btn btn-outline-danger btn-delete-student"
                                                data-id="<?= $s['id'] ?>"
                                                data-name="<?= e($s['name']) ?>"
                                                title="Hapus Siswa">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalItems > $perPage): ?>
        <div class="card-footer bg-white border-top py-3 px-4">
            <?= paginate_links($totalItems, $perPage, $currentPage, 'admin/students', [
                'q' => $search,
                'class_id' => $classId,
                'status' => $status
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Add Student -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="addStudentModalLabel"><i class="bi bi-person-plus me-2 text-primary"></i>Tambah Peserta Didik Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/students/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Ahmad Pratama" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">NIS <span class="text-danger">*</span></label>
                            <input type="text" name="nis" class="form-control" placeholder="2026001" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">NISN</label>
                            <input type="text" name="nisn" class="form-control" placeholder="0058912341">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Kelas / Rombel</label>
                            <select name="class_id" class="form-select">
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (Tingkat <?= e($c['grade']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Username Akun <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" placeholder="siswa_ahmad" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Email Siswa <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="siswa@email.com" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" value="password123" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">No. Handphone/WA</label>
                            <input type="text" name="phone" class="form-control" placeholder="085711223344">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Status Akun <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active" selected>Active (Aktif)</option>
                            <option value="inactive">Inactive (Non-Aktif)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Peserta Didik</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Student -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="editStudentModalLabel"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Data Peserta Didik</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/students/update') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="edit_student_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_student_name" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">NIS <span class="text-danger">*</span></label>
                            <input type="text" name="nis" id="edit_student_nis" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">NISN</label>
                            <input type="text" name="nisn" id="edit_student_nisn" class="form-control">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Kelas / Rombel</label>
                            <select name="class_id" id="edit_student_class_id" class="form-select">
                                <option value="">-- Tanpa Kelas --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (Tingkat <?= e($c['grade']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="gender" id="edit_student_gender" class="form-select" required>
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Username Akun <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="edit_student_username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Email Siswa <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="edit_student_email" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Password Baru <small class="text-muted">(Kosongkan jika tetap)</small></label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">No. Handphone/WA</label>
                            <input type="text" name="phone" id="edit_student_phone" class="form-control">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Status Akun <span class="text-danger">*</span></label>
                        <select name="status" id="edit_student_status" class="form-select" required>
                            <option value="active">Active (Aktif)</option>
                            <option value="inactive">Inactive (Non-Aktif)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Delete Student -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-body p-4 text-center">
                <div class="avatar-circle mx-auto mb-3 bg-danger-subtle text-danger fs-3">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h5 class="fw-bold mb-1">Hapus Siswa?</h5>
                <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus data dan akun peserta didik <strong id="delete_student_name" class="text-dark"></strong>?</p>
                <form action="<?= base_url('admin/students/delete') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="delete_student_id">
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
document.addEventListener('DOMContentLoaded', () => {
    // Edit Student Modal
    const editButtons = document.querySelectorAll('.btn-edit-student');
    const editModal = new bootstrap.Modal(document.getElementById('editStudentModal'));

    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_student_id').value = btn.dataset.id;
            document.getElementById('edit_student_name').value = btn.dataset.name;
            document.getElementById('edit_student_username').value = btn.dataset.username;
            document.getElementById('edit_student_email').value = btn.dataset.email;
            document.getElementById('edit_student_class_id').value = btn.dataset.classId;
            document.getElementById('edit_student_nis').value = btn.dataset.nis;
            document.getElementById('edit_student_nisn').value = btn.dataset.nisn;
            document.getElementById('edit_student_phone').value = btn.dataset.phone;
            document.getElementById('edit_student_gender').value = btn.dataset.gender;
            document.getElementById('edit_student_status').value = btn.dataset.status;
            editModal.show();
        });
    });

    // Delete Student Modal
    const deleteButtons = document.querySelectorAll('.btn-delete-student');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteStudentModal'));

    deleteButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('delete_student_id').value = btn.dataset.id;
            document.getElementById('delete_student_name').textContent = btn.dataset.name;
            deleteModal.show();
        });
    });
});
</script>
