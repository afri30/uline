<?php
$currentUser = auth();
$currentRole = $currentUser['role'] ?? 'GUEST';
$notifCount = 0;
if ($currentUser && !empty($currentUser['id'])) {
    try {
        $notifModel = new \App\Models\Notification();
        $notifCount = $notifModel->getUnreadCount((int)$currentUser['id']);
    } catch (\Throwable $e) {
        $notifCount = 0;
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-top bg-white border-bottom sticky-top px-3 py-2">
    <div class="container-fluid px-0">
        <!-- Sidebar Toggle Button (Mobile & Desktop) -->
        <button class="btn btn-outline-secondary btn-sm me-3 d-inline-flex align-items-center justify-content-center shadow-none" id="sidebarToggle" type="button" title="Tutup / Geser Menu Sidebar (Ctrl+B)" aria-label="Toggle navigation">
            <i class="bi bi-list fs-5" id="sidebarToggleIcon"></i>
        </button>

        <!-- Brand/Page Title in Navbar -->
        <div class="d-none d-md-flex align-items-center">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 me-2 rounded-pill font-monospace small">
                <i class="bi bi-shield-lock me-1"></i>PROCTORING ENGINE
            </span>
            <span class="text-secondary small">AI Online Examination System</span>
        </div>

        <!-- Right Side User & Quick Info -->
        <ul class="navbar-nav ms-auto align-items-center gap-2">
            <!-- Role Badge -->
            <li class="nav-item d-none d-sm-block">
                <?php if ($currentRole === 'SUPER_ADMIN'): ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill">
                        <i class="bi bi-shield-fill-check me-1"></i>SUPER_ADMIN
                    </span>
                <?php elseif ($currentRole === 'GURU'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill">
                        <i class="bi bi-person-badge-fill me-1"></i>GURU PENGUJI
                    </span>
                <?php else: ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 rounded-pill">
                        <i class="bi bi-mortarboard-fill me-1"></i>PESERTA
                    </span>
                <?php endif; ?>
            </li>

            <!-- Notification Dropdown Menu -->
            <li class="nav-item dropdown position-relative me-1">
                <a class="nav-link text-dark p-2 rounded-circle position-relative d-flex align-items-center justify-content-center" 
                   href="#" 
                   id="notificationDropdownToggle" 
                   role="button" 
                   data-bs-toggle="dropdown" 
                   data-bs-auto-close="outside"
                   aria-expanded="false" 
                   title="Notifikasi">
                    <i class="bi bi-bell fs-5 text-secondary"></i>
                    <span id="navbarNotifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $notifCount > 0 ? '' : 'd-none' ?>" style="font-size: 0.65rem; padding: 0.3em 0.5em;">
                        <?= $notifCount > 99 ? '99+' : $notifCount ?>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-0" id="notificationDropdownMenu" style="width: 330px; max-width: 92vw; z-index: 1060;">
                    <div class="px-3 py-2.5 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <div class="fw-bold small text-dark d-flex align-items-center">
                            <i class="bi bi-bell-fill text-primary me-1.5"></i>Notifikasi
                            <span id="dropdownUnreadBadge" class="badge bg-danger rounded-pill extra-small ms-1.5 <?= $notifCount > 0 ? '' : 'd-none' ?>">
                                <?= $notifCount ?>
                            </span>
                        </div>
                        <button type="button" class="btn btn-link p-0 extra-small text-decoration-none text-primary fw-medium" id="btnMarkAllReadNav">
                            Tandai semua dibaca
                        </button>
                    </div>
                    <div id="notificationDropdownList" class="overflow-auto" style="max-height: 320px;">
                        <div class="text-center py-4 text-muted small" id="notificationLoadingState">
                            <div class="spinner-border spinner-border-sm text-primary mb-1" role="status"></div>
                            <div>Memuat notifikasi...</div>
                        </div>
                    </div>
                    <div class="p-2 border-top text-center bg-white">
                        <a href="<?= base_url('notifications') ?>" class="btn btn-sm btn-light w-100 extra-small fw-semibold text-primary">
                            Lihat Semua Notifikasi <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </li>

            <!-- User Dropdown Menu -->
            <li class="nav-item dropdown ms-2">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 text-dark p-0" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar-circle bg-primary text-white fw-bold">
                        <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="d-none d-lg-block text-start lh-sm">
                        <div class="fw-semibold small text-truncate" style="max-width: 140px;"><?= e($currentUser['name'] ?? 'Pengguna') ?></div>
                        <div class="text-muted extra-small font-monospace">@<?= e($currentUser['username'] ?? '') ?></div>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold small"><?= e($currentUser['name'] ?? '') ?></div>
                        <div class="text-muted extra-small"><?= e($currentUser['email'] ?? '') ?></div>
                        <div class="mt-1">
                            <span class="badge bg-secondary extra-small"><?= e($currentRole) ?></span>
                        </div>
                    </li>
                    <li><a class="dropdown-item py-2 small" href="<?= base_url('dashboard') ?>"><i class="bi bi-speedometer2 me-2 text-muted"></i>Dashboard</a></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item py-2 small text-danger" href="<?= base_url('logout') ?>">
                            <i class="bi bi-box-arrow-right me-2"></i>Keluar (Logout)
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
