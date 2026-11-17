<?php require __DIR__ . '/partials/header.php'; ?>

<div class="app-wrapper d-flex">
    <!-- Sidebar -->
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Main Content Area -->
    <div class="main-wrapper d-flex flex-column flex-grow-1 min-vh-100">
        <!-- Top Navbar -->
        <?php require __DIR__ . '/partials/navbar.php'; ?>

        <!-- Page Content -->
        <main class="page-content flex-grow-1 p-3 p-md-4">
            <div class="container-fluid px-0">
                <!-- Flash Messages -->
                <?php if (has_flash('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
                        <div class="flex-grow-1"><?= flash('success') ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (has_flash('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
                        <div class="flex-grow-1"><?= flash('error') ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (has_flash('info')): ?>
                    <div class="alert alert-info alert-dismissible fade show shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-info-circle-fill fs-5 me-2 text-info"></i>
                        <div class="flex-grow-1"><?= flash('info') ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- View Output -->
                <?= $content ?? '' ?>
            </div>
        </main>

        <!-- Main Layout Footer -->
        <footer class="footer bg-white border-top py-3 px-4 text-center text-md-start">
            <div class="container-fluid px-0 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <span class="text-muted small">&copy; <?= date('Y') ?> <strong>AI Online Examination & Proctoring System</strong>. Fondasi MVC PHP Native.</span>
                <span class="badge bg-light text-secondary border font-monospace small">PHP Native MVC • MySQL • WebRTC Ready</span>
            </div>
        </footer>
    </div>
</div>

<?php if (is_logged_in()): ?>
    <?php require __DIR__ . '/partials/floating_chatbot.php'; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
