<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-book-half text-danger me-2"></i>Manajemen Mata Pelajaran</h4>
        <p class="text-muted small mb-0">Kelola master mata pelajaran kurikulum dan penugasan guru pengajar</p>
    </div>
    <button type="button" class="btn btn-danger px-3 py-2 rounded-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Mapel Baru
    </button>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3 p-md-4">
        <form action="<?= base_url('admin/subjects') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-10">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control bg-light border-start-0 ps-0" name="q" value="<?= e($search ?? '') ?>" placeholder="Cari kode mapel, nama pelajaran, atau guru pengampu...">
                </div>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="bi bi-search me-1"></i> Cari</button>
                <?php if (!empty($search)): ?>
                    <a href="<?= base_url('admin/subjects') ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Subjects Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Kode</th>
                        <th>Nama Mata Pelajaran</th>
                        <th>Deskripsi</th>
                        <th>Guru Pengampu</th>
                        <th class="pe-4 text-end" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Tidak ada data mata pelajaran yang ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($subjects as $s): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td>
                                    <span class="badge bg-dark font-monospace px-2.5 py-1.5 fs-7">
                                        <?= e($s['code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= e($s['name']) ?></div>
                                </td>
                                <td>
                                    <small class="text-muted"><?= e($s['description'] ?: '-') ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($s['teacher_name'])): ?>
                                        <div class="fw-semibold text-dark small"><i class="bi bi-person-badge me-1 text-success"></i><?= e($s['teacher_name']) ?></div>
                                        <small class="text-muted font-monospace">NIP: <?= e($s['nip'] ?: '-') ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-muted">Belum Ditentukan</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Edit Modal Trigger -->
                                        <button type="button" class="btn btn-outline-primary btn-edit-subject"
                                                data-id="<?= $s['id'] ?>"
                                                data-code="<?= e($s['code']) ?>"
                                                data-name="<?= e($s['name']) ?>"
                                                data-description="<?= e($s['description'] ?? '') ?>"
                                                data-teacher-id="<?= e($s['teacher_id'] ?? '') ?>"
                                                title="Edit Mapel">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Delete Modal Trigger -->
                                        <button type="button" class="btn btn-outline-danger btn-delete-subject"
                                                data-id="<?= $s['id'] ?>"
                                                data-name="<?= e($s['name']) ?>"
                                                title="Hapus Mapel">
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

<!-- Modal: Add Subject -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="addSubjectModalLabel"><i class="bi bi-plus-circle me-2 text-danger"></i>Tambah Mata Pelajaran Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/subjects/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-secondary">Kode Mapel <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control font-monospace" placeholder="MAT-12" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold text-secondary">Nama Mata Pelajaran <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Matematika Peminatan" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Guru Pengampu</label>
                        <select name="teacher_id" class="form-select">
                            <option value="">-- Pilih Guru Pengampu --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= e($t['name']) ?> (NIP: <?= e($t['nip']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Deskripsi Singkat</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi silabus atau materi ujian..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i> Simpan Mapel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Subject -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="editSubjectModalLabel"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Data Mata Pelajaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/subjects/update') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="edit_subject_id">
                <div class="modal-body p-4">
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-secondary">Kode Mapel <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="edit_subject_code" class="form-control font-monospace" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold text-secondary">Nama Mata Pelajaran <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_subject_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Guru Pengampu</label>
                        <select name="teacher_id" id="edit_subject_teacher_id" class="form-select">
                            <option value="">-- Tanpa Guru Pengampu --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= e($t['name']) ?> (NIP: <?= e($t['nip']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Deskripsi Singkat</label>
                        <textarea name="description" id="edit_subject_description" class="form-control" rows="3"></textarea>
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

<!-- Modal: Delete Subject -->
<div class="modal fade" id="deleteSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-body p-4 text-center">
                <div class="avatar-circle mx-auto mb-3 bg-danger-subtle text-danger fs-3">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h5 class="fw-bold mb-1">Hapus Mata Pelajaran?</h5>
                <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus mapel <strong id="delete_subject_name" class="text-dark"></strong>?</p>
                <form action="<?= base_url('admin/subjects/delete') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="delete_subject_id">
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
    // Edit Subject Modal
    const editButtons = document.querySelectorAll('.btn-edit-subject');
    const editModal = new bootstrap.Modal(document.getElementById('editSubjectModal'));

    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_subject_id').value = btn.dataset.id;
            document.getElementById('edit_subject_code').value = btn.dataset.code;
            document.getElementById('edit_subject_name').value = btn.dataset.name;
            document.getElementById('edit_subject_description').value = btn.dataset.description;
            document.getElementById('edit_subject_teacher_id').value = btn.dataset.teacherId;
            editModal.show();
        });
    });

    // Delete Subject Modal
    const deleteButtons = document.querySelectorAll('.btn-delete-subject');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteSubjectModal'));

    deleteButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('delete_subject_id').value = btn.dataset.id;
            document.getElementById('delete_subject_name').textContent = btn.dataset.name;
            deleteModal.show();
        });
    });
});
</script>
