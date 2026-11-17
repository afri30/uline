<?php
$currentUser = auth();
$currentRole = $currentUser['role'] ?? '';
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
?>
<aside class="sidebar bg-dark text-white d-flex flex-column flex-shrink-0" id="appSidebar">
    <!-- Brand Header -->
    <div class="sidebar-brand px-3 py-3 d-flex align-items-center justify-content-between border-bottom border-secondary border-opacity-25">
        <a href="<?= base_url('dashboard') ?>" class="text-white text-decoration-none d-flex align-items-center gap-2">
            <div class="brand-icon bg-gradient-primary rounded-3 d-flex align-items-center justify-content-center text-white shadow-sm">
                <i class="bi bi-shield-lock-fill fs-5"></i>
            </div>
            <div class="lh-1">
                <div class="fw-bold fs-6 tracking-wide text-white">AI PROCTOR</div>
                <small class="text-secondary extra-small">Online Exam System</small>
            </div>
        </a>
        <button class="btn btn-sm btn-outline-secondary border-0 text-white-50 p-1.5 rounded-2 d-flex align-items-center justify-content-center sidebar-toggle-btn" id="sidebarClose" title="Tutup / Geser Menu (Sembunyikan Sidebar)" aria-label="Tutup sidebar">
            <i class="bi bi-chevron-left fs-5 d-none d-lg-inline"></i>
            <i class="bi bi-x-lg fs-5 d-lg-none"></i>
        </button>
    </div>

    <!-- Navigation Links -->
    <div class="sidebar-menu p-3 flex-grow-1 overflow-auto">
        <div class="text-uppercase extra-small fw-bold text-secondary tracking-wider mb-2 px-2">Menu Utama</div>
        <ul class="nav nav-pills flex-column gap-1">
            <li class="nav-item">
                <a href="<?= base_url('dashboard') ?>" class="nav-link <?= (str_contains($currentUri, 'dashboard')) ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2 me-2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('notifications') ?>" class="nav-link <?= (str_contains($currentUri, 'notifications')) ? 'active' : '' ?>">
                    <i class="bi bi-bell-fill me-2"></i>
                    <span>Notifikasi</span>
                </a>
            </li>
        </ul>

        <!-- ROLE: SUPER_ADMIN -->
        <?php if ($currentRole === 'SUPER_ADMIN'): ?>
            <div class="text-uppercase extra-small fw-bold text-secondary tracking-wider mt-3 mb-2 px-2">Menu Super Admin</div>
            <ul class="nav nav-pills flex-column gap-1">
                <li class="nav-item">
                    <a href="<?= base_url('admin/dashboard') ?>" class="nav-link <?= (str_ends_with($currentUri, 'admin/dashboard')) ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2 me-2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/profile') ?>" class="nav-link <?= (str_contains($currentUri, 'admin/profile')) ? 'active' : '' ?>">
                        <i class="bi bi-person-badge-fill me-2 text-info"></i>
                        <span>Profil Admin</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/users') ?>" class="nav-link <?= (str_contains($currentUri, 'admin/users')) ? 'active' : '' ?>">
                        <i class="bi bi-people-fill me-2"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/teachers') ?>" class="nav-link <?= (str_contains($currentUri, 'admin/teachers')) ? 'active' : '' ?>">
                        <i class="bi bi-person-badge-fill me-2"></i>
                        <span>Guru</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/students') ?>" class="nav-link <?= (str_contains($currentUri, 'admin/students')) ? 'active' : '' ?>">
                        <i class="bi bi-mortarboard-fill me-2"></i>
                        <span>Peserta Didik</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/classes') ?>" class="nav-link <?= (str_contains($currentUri, 'admin/classes')) ? 'active' : '' ?>">
                        <i class="bi bi-building me-2"></i>
                        <span>Kelas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/subjects') ?>" class="nav-link <?= (str_contains($currentUri, 'admin/subjects')) ? 'active' : '' ?>">
                        <i class="bi bi-book-half me-2"></i>
                        <span>Mata Pelajaran</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/settings') ?>" class="nav-link <?= (str_contains($currentUri, 'admin/settings')) ? 'active' : '' ?>">
                        <i class="bi bi-gear-fill me-2"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/exam-config') ?>" class="nav-link <?= (str_contains($currentUri, 'exam-config') || str_contains($currentUri, 'konfigurasi-ujian')) ? 'active' : '' ?>">
                        <i class="bi bi-sliders2-vertical me-2"></i>
                        <span>Konfigurasi Ujian</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/ai-config') ?>" class="nav-link <?= (str_contains($currentUri, 'ai-config') || str_contains($currentUri, 'admin/ai')) ? 'active' : '' ?>">
                        <i class="bi bi-robot me-2 text-primary"></i>
                        <span>Konfigurasi AI</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/whatsapp') ?>" class="nav-link <?= (str_contains($currentUri, 'admin/whatsapp')) ? 'active' : '' ?>">
                        <i class="bi bi-whatsapp me-2 text-success"></i>
                        <span>WhatsApp Gateway</span>
                    </a>
                </li>
            </ul>
        <?php endif; ?>

        <!-- ROLE: GURU -->
        <?php if ($currentRole === 'GURU'): ?>
            <div class="text-uppercase extra-small fw-bold text-secondary tracking-wider mt-3 mb-2 px-2">Menu Guru Penguji</div>
            <ul class="nav nav-pills flex-column gap-1">
                <li class="nav-item">
                    <a href="<?= base_url('guru/dashboard') ?>" class="nav-link <?= (str_ends_with($currentUri, 'guru/dashboard')) ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2 me-2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('guru/profile') ?>" class="nav-link <?= (str_contains($currentUri, 'guru/profile')) ? 'active' : '' ?>">
                        <i class="bi bi-person-gear me-2 text-info"></i>
                        <span>Profil Guru</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('guru/camera-check') ?>" class="nav-link <?= (str_contains($currentUri, 'guru/camera-check')) ? 'active' : '' ?>">
                        <i class="bi bi-webcam-fill me-2 text-success"></i>
                        <span>Cek &amp; Monitor Kamera</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('admin/exam-config') ?>" class="nav-link <?= (str_contains($currentUri, 'exam-config') || str_contains($currentUri, 'konfigurasi-ujian')) ? 'active' : '' ?>">
                        <i class="bi bi-sliders2-vertical me-2"></i>
                        <span>Konfigurasi Ujian</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('guru/questions') ?>" class="nav-link <?= (str_contains($currentUri, 'guru/questions')) ? 'active' : '' ?>">
                        <i class="bi bi-journal-text me-2"></i>
                        <span>Bank Soal</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('guru/exams') ?>" class="nav-link <?= (str_contains($currentUri, 'guru/exams')) ? 'active' : '' ?>">
                        <i class="bi bi-card-checklist me-2"></i>
                        <span>Ujian</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('guru/students') ?>" class="nav-link <?= (str_contains($currentUri, 'guru/students')) ? 'active' : '' ?>">
                        <i class="bi bi-people-fill me-2"></i>
                        <span>Peserta Ujian</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('guru/results') ?>" class="nav-link <?= (str_contains($currentUri, 'guru/results')) ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>
                        <span>Hasil Ujian</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('guru/monitoring') ?>" class="nav-link <?= (str_contains($currentUri, 'guru/monitoring')) ? 'active' : '' ?>">
                        <i class="bi bi-camera-video-fill me-2 text-warning"></i>
                        <span>Monitoring</span>
                    </a>
                </li>
            </ul>
        <?php endif; ?>

        <!-- ROLE: PESERTA -->
        <?php if ($currentRole === 'PESERTA'): ?>
            <div class="text-uppercase extra-small fw-bold text-secondary tracking-wider mt-4 mb-2 px-2">Panel Peserta Ujian</div>
            <ul class="nav nav-pills flex-column gap-1">
                <li class="nav-item">
                    <a href="<?= base_url('peserta/dashboard') ?>" class="nav-link <?= (str_ends_with($currentUri, 'peserta/dashboard')) ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2 me-2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('peserta/profile') ?>" class="nav-link <?= (str_contains($currentUri, 'peserta/profile')) ? 'active' : '' ?>">
                        <i class="bi bi-person-gear me-2 text-info"></i>
                        <span>Profil Peserta</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('peserta/camera-check') ?>" class="nav-link <?= (str_contains($currentUri, 'peserta/camera-check')) ? 'active' : '' ?>">
                        <i class="bi bi-webcam-fill me-2 text-primary"></i>
                        <span>Cek Kamera</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('peserta/exams') ?>" class="nav-link <?= (str_contains($currentUri, 'peserta/exams')) ? 'active' : '' ?>">
                        <i class="bi bi-card-checklist me-2"></i>
                        <span>Ujian Online</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('peserta/results') ?>" class="nav-link <?= (str_contains($currentUri, 'peserta/results')) ? 'active' : '' ?>">
                        <i class="bi bi-award-fill me-2"></i>
                        <span>Hasil Ujian</span>
                    </a>
                </li>
            </ul>
        <?php endif; ?>

        <!-- RBAC Authorization Test Links -->
        <div class="text-uppercase extra-small fw-bold text-secondary tracking-wider mt-4 mb-2 px-2">Uji Isolasi Role (RBAC)</div>
        <ul class="nav nav-pills flex-column gap-1">
            <li class="nav-item">
                <a href="<?= base_url('admin/dashboard') ?>" class="nav-link text-danger-emphasis">
                    <i class="bi bi-shield-lock me-2 text-danger"></i>
                    <span>/admin/dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('guru/dashboard') ?>" class="nav-link text-success-emphasis">
                    <i class="bi bi-person-badge me-2 text-success"></i>
                    <span>/guru/dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('peserta/dashboard') ?>" class="nav-link text-primary-emphasis">
                    <i class="bi bi-mortarboard me-2 text-primary"></i>
                    <span>/peserta/dashboard</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- User Profile & Logout Bottom Section -->
    <div class="sidebar-footer p-3 border-top border-secondary border-opacity-25 bg-dark-subtle bg-opacity-10">
        <div class="d-flex align-items-center justify-content-between">
            <a href="<?= base_url('profile') ?>" class="d-flex align-items-center gap-2 overflow-hidden text-decoration-none" title="Lihat Profil">
                <div class="avatar-circle-sm bg-primary text-white fw-bold">
                    <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="lh-1 overflow-hidden">
                    <div class="text-white small fw-semibold text-truncate" style="max-width: 120px;">
                        <?= e($currentUser['name'] ?? 'Pengguna') ?>
                    </div>
                    <small class="text-secondary extra-small font-monospace"><?= e($currentRole) ?></small>
                </div>
            </a>
            <a href="<?= base_url('logout') ?>" class="btn btn-outline-danger btn-sm p-1 px-2" title="Keluar">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</aside>
