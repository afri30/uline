<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Manajemen Pengguna</h4>
        <p class="text-muted small mb-0">Kelola akun Super Admin, Guru Penguji, dan Peserta Didik</p>
    </div>
    <button type="button" class="btn btn-primary px-3 py-2 rounded-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Pengguna
    </button>
</div>

<!-- Statistics Metric Cards (Tampilan Ringkasan seperti Dashboard) -->
<div class="row g-3 mb-4">
    <!-- Stat 1: Total Pengguna -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="<?= base_url('admin/users') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-card <?= (empty($role) && empty($status)) ? 'border-primary border-2 shadow' : '' ?>">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <span class="badge bg-primary-subtle text-primary rounded-pill">Semua</span>
                    </div>
                    <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Total Pengguna</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['total'] ?? 0) ?></h3>
                </div>
            </div>
        </a>
    </div>

    <!-- Stat 2: Super Admin -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="<?= base_url('admin/users?role=SUPER_ADMIN') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-card <?= ($role === 'SUPER_ADMIN') ? 'border-danger border-2 shadow' : '' ?>">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-danger-subtle text-danger rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-shield-fill-check fs-4"></i>
                        </div>
                        <span class="badge bg-danger-subtle text-danger rounded-pill">Admin</span>
                    </div>
                    <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Super Admin</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['admin'] ?? 0) ?></h3>
                </div>
            </div>
        </a>
    </div>

    <!-- Stat 3: Guru -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="<?= base_url('admin/users?role=GURU') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-card <?= ($role === 'GURU') ? 'border-success border-2 shadow' : '' ?>">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-person-badge-fill fs-4"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success rounded-pill">Guru</span>
                    </div>
                    <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Guru Penguji</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['guru'] ?? 0) ?></h3>
                </div>
            </div>
        </a>
    </div>

    <!-- Stat 4: Peserta -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="<?= base_url('admin/users?role=PESERTA') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-card <?= ($role === 'PESERTA') ? 'border-info border-2 shadow' : '' ?>">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-info-subtle text-info rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-mortarboard-fill fs-4"></i>
                        </div>
                        <span class="badge bg-info-subtle text-info rounded-pill">Peserta</span>
                    </div>
                    <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Peserta Didik</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['peserta'] ?? 0) ?></h3>
                </div>
            </div>
        </a>
    </div>

    <!-- Stat 5: Status Akun -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="<?= base_url('admin/users?status=active') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-card <?= ($status === 'active') ? 'border-success border-2 shadow' : '' ?>">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-toggle-on fs-4"></i>
                        </div>
                        <span class="badge bg-secondary-subtle text-muted rounded-pill"><?= (int)($stats['inactive'] ?? 0) ?> Non-Aktif</span>
                    </div>
                    <div class="text-muted extra-small fw-semibold text-uppercase tracking-wider">Akun Aktif</div>
                    <h3 class="fw-bold mb-0 text-dark"><?= (int)($stats['active'] ?? 0) ?></h3>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Filter & Search Box -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3 p-md-4">
        <form action="<?= base_url('admin/users') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control bg-light border-start-0 ps-0" name="q" value="<?= e($search ?? '') ?>" placeholder="Cari nama, username, atau email...">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="role" class="form-select bg-light">
                    <option value="">Semua Role</option>
                    <option value="SUPER_ADMIN" <?= ($role === 'SUPER_ADMIN') ? 'selected' : '' ?>>SUPER_ADMIN (Admin)</option>
                    <option value="GURU" <?= ($role === 'GURU') ? 'selected' : '' ?>>GURU (Guru Penguji)</option>
                    <option value="PESERTA" <?= ($role === 'PESERTA') ? 'selected' : '' ?>>PESERTA (Siswa)</option>
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
                <?php if (!empty($search) || !empty($role) || !empty($status)): ?>
                    <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Nama & Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Tgl Dibuat</th>
                        <th class="pe-4 text-end" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Tidak ada data pengguna yang ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $currentUser = auth(); ?>
                        <?php $no = ($currentPage - 1) * $perPage + 1; ?>
                        <?php foreach ($users as $u): ?>
                            <?php $isSelf = ((int)$u['id'] === (int)($currentUser['id'] ?? 0)); ?>
                            <tr class="<?= ($u['role'] === 'SUPER_ADMIN') ? 'bg-danger bg-opacity-10 border-start border-danger border-3' : '' ?>">
                                <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="fw-semibold text-dark"><?= e($u['name']) ?></div>
                                        <?php if ($isSelf): ?>
                                            <span class="badge bg-dark extra-small"><i class="bi bi-person-fill me-1"></i>Anda</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted font-monospace">@<?= e($u['username']) ?></small>
                                </td>
                                <td><small class="text-dark"><?= e($u['email']) ?></small></td>
                                <td>
                                    <?php if ($u['role'] === 'SUPER_ADMIN'): ?>
                                        <span class="badge bg-danger text-white px-2.5 py-1 rounded-pill font-monospace extra-small">
                                            <i class="bi bi-shield-fill-check me-1"></i>SUPER_ADMIN
                                        </span>
                                    <?php elseif ($u['role'] === 'GURU'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill font-monospace extra-small">
                                            <i class="bi bi-person-badge-fill me-1"></i>GURU
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill font-monospace extra-small">
                                            <i class="bi bi-mortarboard-fill me-1"></i>PESERTA
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="badge bg-success px-2 py-1"><i class="bi bi-check-circle me-1"></i>Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary px-2 py-1"><i class="bi bi-x-circle me-1"></i>Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted font-monospace"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></small></td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Toggle Status -->
                                        <?php if ($isSelf): ?>
                                            <button class="btn btn-outline-secondary opacity-50 disabled" title="Tidak dapat menonaktifkan akun sendiri" disabled>
                                                <i class="bi bi-toggle-on text-success fs-6"></i>
                                            </button>
                                        <?php else: ?>
                                            <a href="<?= base_url('admin/users/toggle-status?id=' . $u['id']) ?>" class="btn btn-outline-secondary" title="Ubah Status Aktif/Nonaktif">
                                                <i class="bi <?= ($u['status'] === 'active') ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?> fs-6"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Edit Modal Trigger -->
                                        <button type="button" class="btn btn-outline-primary btn-edit-user" 
                                                data-id="<?= $u['id'] ?>"
                                                data-name="<?= e($u['name']) ?>"
                                                data-username="<?= e($u['username']) ?>"
                                                data-email="<?= e($u['email']) ?>"
                                                data-role="<?= e($u['role']) ?>"
                                                data-status="<?= e($u['status']) ?>"
                                                title="Edit Data">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Delete Modal Trigger -->
                                        <?php if ($isSelf): ?>
                                            <button type="button" class="btn btn-outline-secondary opacity-50 disabled" title="Tidak dapat menghapus akun sendiri" disabled>
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-danger btn-delete-user"
                                                    data-id="<?= $u['id'] ?>"
                                                    data-name="<?= e($u['name']) ?>"
                                                    title="Hapus Pengguna">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        <?php endif; ?>
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
            <?= paginate_links($totalItems, $perPage, $currentPage, 'admin/users', [
                'q' => $search,
                'role' => $role,
                'status' => $status
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Add User -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="addUserModalLabel"><i class="bi bi-person-plus me-2 text-primary"></i>Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/users/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Andi Pratama" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" placeholder="username_unik" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="SUPER_ADMIN">SUPER_ADMIN</option>
                                <option value="GURU">GURU</option>
                                <option value="PESERTA" selected>PESERTA</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="editUserModalLabel"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Data Pengguna</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/users/update') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="edit_user_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_user_name" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="edit_user_username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="edit_user_email" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Password Baru <small class="text-muted">(Kosongkan jika tidak diubah)</small></label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Role <span class="text-danger">*</span></label>
                            <select name="role" id="edit_user_role" class="form-select" required>
                                <option value="SUPER_ADMIN">SUPER_ADMIN</option>
                                <option value="GURU">GURU</option>
                                <option value="PESERTA">PESERTA</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Status <span class="text-danger">*</span></label>
                            <select name="status" id="edit_user_status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
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

<!-- Modal: Delete Confirmation -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-body p-4 text-center">
                <div class="avatar-circle mx-auto mb-3 bg-danger-subtle text-danger fs-3">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h5 class="fw-bold mb-1">Hapus Pengguna?</h5>
                <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus akun <strong id="delete_user_name" class="text-dark"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
                <form action="<?= base_url('admin/users/delete') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="delete_user_id">
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
    // Edit User Modal
    const editButtons = document.querySelectorAll('.btn-edit-user');
    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));

    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_user_id').value = btn.dataset.id;
            document.getElementById('edit_user_name').value = btn.dataset.name;
            document.getElementById('edit_user_username').value = btn.dataset.username;
            document.getElementById('edit_user_email').value = btn.dataset.email;
            document.getElementById('edit_user_role').value = btn.dataset.role;
            document.getElementById('edit_user_status').value = btn.dataset.status;
            editModal.show();
        });
    });

    // Delete User Modal
    const deleteButtons = document.querySelectorAll('.btn-delete-user');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));

    deleteButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('delete_user_id').value = btn.dataset.id;
            document.getElementById('delete_user_name').textContent = btn.dataset.name;
            deleteModal.show();
        });
    });
});
</script>
