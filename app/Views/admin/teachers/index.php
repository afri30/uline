<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-badge-fill text-success me-2"></i>Manajemen Data Guru</h4>
        <p class="text-muted small mb-0">Kelola akun guru penguji, NIP, kontak, dan penugasan mata pelajaran</p>
    </div>
    <button type="button" class="btn btn-success px-3 py-2 rounded-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Guru Baru
    </button>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3 p-md-4">
        <form action="<?= base_url('admin/teachers') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control bg-light border-start-0 ps-0" name="q" value="<?= e($search ?? '') ?>" placeholder="Cari nama guru, NIP, username, atau email...">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="status" class="form-select bg-light">
                    <option value="">Semua Status</option>
                    <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($status === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="bi bi-filter me-1"></i> Filter</button>
                <?php if (!empty($search) || !empty($status)): ?>
                    <a href="<?= base_url('admin/teachers') ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Teachers Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Nama & Username</th>
                        <th>NIP & Gender</th>
                        <th>Email & Kontak</th>
                        <th>Mapel Diampu</th>
                        <th>Status</th>
                        <th class="pe-4 text-end" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teachers)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Tidak ada data guru yang ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = ($currentPage - 1) * $perPage + 1; ?>
                        <?php foreach ($teachers as $t): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($t['name']) ?></div>
                                    <small class="text-muted font-monospace">@<?= e($t['username']) ?></small>
                                </td>
                                <td>
                                    <div class="font-monospace fw-semibold text-dark small"><?= e($t['nip']) ?></div>
                                    <small class="text-muted"><?= ($t['gender'] === 'L') ? 'Laki-laki' : 'Perempuan' ?></small>
                                </td>
                                <td>
                                    <div class="small text-dark"><?= e($t['email']) ?></div>
                                    <small class="text-muted"><?= e($t['phone'] ?: '-') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        <?= (int)($t['total_subjects'] ?? 0) ?> Mapel
                                    </span>
                                </td>
                                <td>
                                    <?php if ($t['status'] === 'active'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Toggle Status -->
                                        <a href="<?= base_url('admin/teachers/toggle-status?id=' . $t['id']) ?>" class="btn btn-outline-secondary" title="Ubah Status Aktif/Nonaktif">
                                            <i class="bi <?= ($t['status'] === 'active') ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?> fs-6"></i>
                                        </a>

                                        <!-- Edit Modal Trigger -->
                                        <button type="button" class="btn btn-outline-primary btn-edit-teacher"
                                                data-id="<?= $t['id'] ?>"
                                                data-name="<?= e($t['name']) ?>"
                                                data-username="<?= e($t['username']) ?>"
                                                data-email="<?= e($t['email']) ?>"
                                                data-nip="<?= e($t['nip']) ?>"
                                                data-phone="<?= e($t['phone'] ?? '') ?>"
                                                data-gender="<?= e($t['gender'] ?? 'L') ?>"
                                                data-status="<?= e($t['status']) ?>"
                                                title="Edit Data Guru">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Delete Modal Trigger -->
                                        <button type="button" class="btn btn-outline-danger btn-delete-teacher"
                                                data-id="<?= $t['id'] ?>"
                                                data-name="<?= e($t['name']) ?>"
                                                title="Hapus Guru">
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
            <?= paginate_links($totalItems, $perPage, $currentPage, 'admin/teachers', [
                'q' => $search,
                'status' => $status
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Add Teacher -->
<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="addTeacherModalLabel"><i class="bi bi-person-plus me-2 text-success"></i>Tambah Data Guru Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/teachers/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Budi Santoso, M.Pd." required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">NIP <span class="text-danger">*</span></label>
                            <input type="text" name="nip" class="form-control" placeholder="198507122010..." required>
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
                            <input type="text" name="username" class="form-control" placeholder="guru_budi" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="guru@email.com" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" value="password123" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">No. Handphone/WhatsApp</label>
                            <input type="text" name="phone" class="form-control" placeholder="081234567890">
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
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i> Simpan Data Guru</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Teacher -->
<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="editTeacherModalLabel"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Data Guru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/teachers/update') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="edit_teacher_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_teacher_name" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">NIP <span class="text-danger">*</span></label>
                            <input type="text" name="nip" id="edit_teacher_nip" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="gender" id="edit_teacher_gender" class="form-select" required>
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="edit_teacher_username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="edit_teacher_email" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Password Baru <small class="text-muted">(Kosongkan jika tetap)</small></label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">No. Handphone/WhatsApp</label>
                            <input type="text" name="phone" id="edit_teacher_phone" class="form-control">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Status Akun <span class="text-danger">*</span></label>
                        <select name="status" id="edit_teacher_status" class="form-select" required>
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

<!-- Modal: Delete Teacher -->
<div class="modal fade" id="deleteTeacherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-body p-4 text-center">
                <div class="avatar-circle mx-auto mb-3 bg-danger-subtle text-danger fs-3">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h5 class="fw-bold mb-1">Hapus Data Guru?</h5>
                <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus data dan akun guru <strong id="delete_teacher_name" class="text-dark"></strong>?</p>
                <form action="<?= base_url('admin/teachers/delete') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="delete_teacher_id">
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
    // Edit Teacher Modal
    const editButtons = document.querySelectorAll('.btn-edit-teacher');
    const editModal = new bootstrap.Modal(document.getElementById('editTeacherModal'));

    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_teacher_id').value = btn.dataset.id;
            document.getElementById('edit_teacher_name').value = btn.dataset.name;
            document.getElementById('edit_teacher_username').value = btn.dataset.username;
            document.getElementById('edit_teacher_email').value = btn.dataset.email;
            document.getElementById('edit_teacher_nip').value = btn.dataset.nip;
            document.getElementById('edit_teacher_phone').value = btn.dataset.phone;
            document.getElementById('edit_teacher_gender').value = btn.dataset.gender;
            document.getElementById('edit_teacher_status').value = btn.dataset.status;
            editModal.show();
        });
    });

    // Delete Teacher Modal
    const deleteButtons = document.querySelectorAll('.btn-delete-teacher');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteTeacherModal'));

    deleteButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('delete_teacher_id').value = btn.dataset.id;
            document.getElementById('delete_teacher_name').textContent = btn.dataset.name;
            deleteModal.show();
        });
    });
});
</script>
