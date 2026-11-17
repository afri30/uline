<?php require __DIR__ . '/partials/header.php'; ?>

<div class="auth-layout d-flex align-items-center justify-content-center min-vh-100 py-5 bg-gradient-auth">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                
                <!-- Flash Messages for Auth Layout -->
                <?php if (has_flash('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div class="flex-grow-1 small"><?= flash('success') ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (has_flash('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div class="flex-grow-1 small"><?= flash('error') ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?= $content ?? '' ?>

                <div class="text-center mt-4 text-muted small">
                    &copy; <?= date('Y') ?> AI Online Examination & Proctoring System
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
