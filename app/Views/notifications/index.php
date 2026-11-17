<?php
$title = $title ?? 'Pusat Notifikasi';
$unreadCount = $unreadCount ?? 0;
$filter = $filter ?? 'all';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <h4 class="fw-bold text-dark mb-1 d-flex align-items-center">
            <i class="bi bi-bell-fill text-primary me-2"></i>Pusat Notifikasi
            <?php if ($unreadCount > 0): ?>
                <span class="badge bg-danger rounded-pill fs-6 ms-2"><?= $unreadCount ?> Belum Dibaca</span>
            <?php endif; ?>
        </h4>
        <p class="text-muted small mb-0">Semua pemberitahuan penting terkait ujian, hasil penilaian, dan operasional akun Anda.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2 flex-wrap">
        <?php if ($unreadCount > 0): ?>
            <form action="<?= base_url('notifications/mark-all-read') ?>" method="POST" class="d-inline">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="btn btn-outline-primary btn-sm shadow-sm d-inline-flex align-items-center">
                    <i class="bi bi-check2-all me-1.5"></i>Tandai Semua Dibaca
                </button>
            </form>
        <?php endif; ?>
        <a href="<?= base_url('dashboard') ?>" class="btn btn-light btn-sm border shadow-sm d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-1.5"></i>Kembali ke Dashboard
        </a>
    </div>
<!-- Web Push Notification Status & Permission Toggle Banner -->
<div class="card border-0 shadow-sm mb-4 bg-primary-subtle bg-opacity-25">
    <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="avatar-circle rounded-circle bg-white text-primary shadow-sm border p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                <i class="bi bi-broadcast-pin fs-5"></i>
            </div>
            <div>
                <div class="fw-bold text-dark small d-flex align-items-center gap-2">
                    <span>Notifikasi Browser (Web Push)</span>
                    <span id="pushStatusBadge" class="badge bg-secondary-subtle text-secondary border extra-small">Memeriksa...</span>
                </div>
                <div class="text-muted extra-small" id="pushStatusDesc">
                    Dapatkan pemberitahuan instan tentang jadwal ujian dan hasil penilaian langsung di browser Anda, bahkan saat tab tidak aktif.
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="form-check form-switch m-0 fs-5">
                <input class="form-check-input cursor-pointer" type="checkbox" role="switch" id="pushNotificationToggle" disabled>
            </div>
            <label class="form-check-label small fw-semibold text-secondary cursor-pointer" for="pushNotificationToggle" id="pushToggleLabel">
                Memuat...
            </label>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-2 p-md-3">
        <ul class="nav nav-pills gap-1 flex-wrap">
            <li class="nav-item">
                <a class="nav-link py-1.5 px-3 small <?= $filter === 'all' ? 'active' : '' ?>" href="<?= base_url('notifications?filter=all') ?>">
                    <i class="bi bi-collection me-1"></i>Semua
                    <span class="badge bg-light text-dark ms-1"><?= $total ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-1.5 px-3 small <?= $filter === 'unread' ? 'active' : '' ?>" href="<?= base_url('notifications?filter=unread') ?>">
                    <i class="bi bi-envelope me-1"></i>Belum Dibaca
                    <?php if ($unreadCount > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-1.5 px-3 small <?= $filter === 'read' ? 'active' : '' ?>" href="<?= base_url('notifications?filter=read') ?>">
                    <i class="bi bi-envelope-open me-1"></i>Sudah Dibaca
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Notification List -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($items)): ?>
            <div class="text-center py-5">
                <div class="avatar-circle mx-auto mb-3 bg-light text-muted" style="width: 64px; height: 64px; font-size: 28px;">
                    <i class="bi bi-bell-slash"></i>
                </div>
                <h6 class="fw-bold text-dark">Tidak ada notifikasi</h6>
                <p class="text-muted small mb-0">Belum ada pemberitahuan pada kategori ini.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($items as $item): ?>
                    <?php
                        $isUnread = !(bool)$item['is_read'];
                        $type = $item['type'] ?? 'info';
                        
                        $iconClass = 'bi-info-circle text-primary';
                        $bgBadge = 'bg-primary-subtle text-primary border-primary-subtle';
                        $typeLabel = 'Informasi';

                        switch ($type) {
                            case 'exam':
                                $iconClass = 'bi-journal-check text-primary';
                                $bgBadge = 'bg-primary-subtle text-primary border-primary-subtle';
                                $typeLabel = 'Ujian';
                                break;
                            case 'result':
                                $iconClass = 'bi-trophy-fill text-success';
                                $bgBadge = 'bg-success-subtle text-success border-success-subtle';
                                $typeLabel = 'Hasil Nilai';
                                break;
                            case 'proctoring':
                            case 'danger':
                                $iconClass = 'bi-shield-exclamation text-danger';
                                $bgBadge = 'bg-danger-subtle text-danger border-danger-subtle';
                                $typeLabel = 'Proctoring';
                                break;
                            case 'warning':
                                $iconClass = 'bi-exclamation-triangle-fill text-warning';
                                $bgBadge = 'bg-warning-subtle text-warning border-warning-subtle';
                                $typeLabel = 'Peringatan';
                                break;
                            case 'system':
                                $iconClass = 'bi-gear-wide-connected text-secondary';
                                $bgBadge = 'bg-secondary-subtle text-secondary border-secondary-subtle';
                                $typeLabel = 'Sistem';
                                break;
                        }

                        $cardBg = $isUnread ? 'bg-primary-subtle bg-opacity-10' : '';
                        $borderStyle = $isUnread ? 'border-start border-4 border-primary' : '';
                    ?>
                    <div class="list-group-item p-3 p-md-4 <?= $cardBg ?> <?= $borderStyle ?> transition-all">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <div class="avatar-circle rounded-3 p-2 bg-white shadow-sm border d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                    <i class="bi <?= $iconClass ?> fs-5"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-1">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge border <?= $bgBadge ?> extra-small font-monospace"><?= $typeLabel ?></span>
                                        <h6 class="mb-0 fw-bold <?= $isUnread ? 'text-dark' : 'text-secondary' ?>">
                                            <?= e($item['title']) ?>
                                        </h6>
                                        <?php if ($isUnread): ?>
                                            <span class="badge bg-danger rounded-pill extra-small">Baru</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-muted extra-small font-monospace">
                                        <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($item['created_at'])) ?> WIB
                                    </span>
                                </div>
                                <p class="text-secondary small mb-2 lh-base">
                                    <?= nl2br(e($item['message'])) ?>
                                </p>
                                <div class="d-flex align-items-center gap-2 pt-1 flex-wrap">
                                    <?php if (!empty($item['link'])): ?>
                                        <a href="<?= base_url($item['link']) ?>" class="btn btn-sm btn-primary shadow-none py-1 px-2.5 extra-small">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>Buka Halaman
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($isUnread): ?>
                                        <form action="<?= base_url('notifications/read/' . $item['id']) ?>" method="POST" class="d-inline">
                                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="redirect" value="notifications?page=<?= $page ?>&filter=<?= $filter ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary py-1 px-2.5 extra-small">
                                                <i class="bi bi-check2 me-1"></i>Tandai Dibaca
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted extra-small d-inline-flex align-items-center">
                                            <i class="bi bi-check2-all text-success me-1"></i>Sudah dibaca
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination Footer -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-top py-3 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <span class="text-muted small">
                Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> (Total: <?= $total ?> notifikasi)
            </span>
            <nav aria-label="Navigasi Halaman Notifikasi">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= base_url('notifications?page=' . ($page - 1) . '&filter=' . $filter) ?>" aria-label="Sebelumnya">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= base_url('notifications?page=' . $p . '&filter=' . $filter) ?>">
                                <?= $p ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= base_url('notifications?page=' . ($page + 1) . '&filter=' . $filter) ?>" aria-label="Berikutnya">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
