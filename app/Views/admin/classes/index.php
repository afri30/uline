<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-building text-warning me-2"></i>Manajemen Data Kelas</h4>
        <p class="text-muted small mb-0">Kelola rombongan belajar (rombel), tingkatan, dan tahun ajaran</p>
    </div>
    <button type="button" class="btn btn-warning text-dark px-3 py-2 rounded-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addClassModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Kelas Baru
    </button>
</div>

<!-- Search & Filter Box -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3 p-md-4">
        <form action="<?= base_url('admin/classes') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control bg-light border-start-0 ps-0" name="q" value="<?= e($search ?? '') ?>" placeholder="Cari nama kelas atau tahun ajaran...">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="grade" class="form-select bg-light">
                    <option value="">Semua Tingkat</option>
                    <option value="10" <?= ($grade === '10') ? 'selected' : '' ?>>Tingkat 10</option>
                    <option value="11" <?= ($grade === '11') ? 'selected' : '' ?>>Tingkat 11</option>
                    <option value="12" <?= ($grade === '12') ? 'selected' : '' ?>>Tingkat 12</option>
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="bi bi-filter me-1"></i> Filter</button>
                <?php if (!empty($search) || !empty($grade)): ?>
                    <a href="<?= base_url('admin/classes') ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Classes Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Nama Kelas</th>
                        <th>Tingkat</th>
                        <th>Tahun Ajaran</th>
                        <th>Total Siswa</th>
                        <th class="pe-4 text-end" style="width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classes)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Tidak ada data kelas yang ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($classes as $c): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= e($c['name']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-dark border font-monospace">
                                        Kelas <?= e($c['grade']) ?>
                                    </span>
                                </td>
                                <td><small class="font-monospace text-dark"><?= e($c['academic_year']) ?></small></td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 font-monospace">
                                        <i class="bi bi-people-fill me-1"></i><?= (int)($c['total_students'] ?? 0) ?> Siswa
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- View Students in Class -->
                                        <a href="<?= base_url('admin/classes/' . $c['id'] . '/students') ?>" class="btn btn-outline-primary" title="Lihat Daftar Siswa Kelas Ini">
                                            <i class="bi bi-eye"></i> Siswa
                                        </a>

                                        <!-- Edit Modal Trigger -->
                                        <button type="button" class="btn btn-outline-secondary btn-edit-class"
                                                data-id="<?= $c['id'] ?>"
                                                data-name="<?= e($c['name']) ?>"
                                                data-grade="<?= e($c['grade']) ?>"
                                                data-academic-year="<?= e($c['academic_year']) ?>"
                                                title="Edit Kelas">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Delete Modal Trigger -->
                                        <button type="button" class="btn btn-outline-danger btn-delete-class"
                                                data-id="<?= $c['id'] ?>"
                                                data-name="<?= e($c['name']) ?>"
                                                title="Hapus Kelas">
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
</div>

<!-- Modal: Add Class -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="addClassModalLabel"><i class="bi bi-plus-circle me-2 text-warning"></i>Tambah Kelas Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/classes/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Kelas / Rombel <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: XII MIPA 1" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Tingkat Kelas <span class="text-danger">*</span></label>
                            <select name="grade" class="form-select" required>
                                <option value="10">Tingkat 10 (X)</option>
                                <option value="11">Tingkat 11 (XI)</option>
                                <option value="12" selected>Tingkat 12 (XII)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Tahun Ajaran <span class="text-danger">*</span></label>
                            <input type="text" name="academic_year" class="form-control" value="2026/2027" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-dark fw-semibold"><i class="bi bi-save me-1"></i> Simpan Kelas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Class -->
<div class="modal fade" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="editClassModalLabel"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Data Kelas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/classes/update') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="edit_class_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Kelas / Rombel <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_class_name" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Tingkat Kelas <span class="text-danger">*</span></label>
                            <select name="grade" id="edit_class_grade" class="form-select" required>
                                <option value="10">Tingkat 10 (X)</option>
                                <option value="11">Tingkat 11 (XI)</option>
                                <option value="12">Tingkat 12 (XII)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Tahun Ajaran <span class="text-danger">*</span></label>
                            <input type="text" name="academic_year" id="edit_class_academic_year" class="form-control" required>
                        </div>
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

<!-- Modal: Delete Class -->
<div class="modal fade" id="deleteClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-body p-4 text-center">
                <div class="avatar-circle mx-auto mb-3 bg-danger-subtle text-danger fs-3">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h5 class="fw-bold mb-1">Hapus Kelas?</h5>
                <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus kelas <strong id="delete_class_name" class="text-dark"></strong>?</p>
                <form action="<?= base_url('admin/classes/delete') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="delete_class_id">
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
    // Edit Class Modal
    const editButtons = document.querySelectorAll('.btn-edit-class');
    const editModal = new bootstrap.Modal(document.getElementById('editClassModal'));

    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_class_id').value = btn.dataset.id;
            document.getElementById('edit_class_name').value = btn.dataset.name;
            document.getElementById('edit_class_grade').value = btn.dataset.grade;
            document.getElementById('edit_class_academic_year').value = btn.dataset.academicYear;
            editModal.show();
        });
    });

    // Delete Class Modal
    const deleteButtons = document.querySelectorAll('.btn-delete-class');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteClassModal'));

    deleteButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('delete_class_id').value = btn.dataset.id;
            document.getElementById('delete_class_name').textContent = btn.dataset.name;
            deleteModal.show();
        });
    });
});
</script>
