/**
 * AI Online Examination & Proctoring System - Vanilla JavaScript App Helper
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Sidebar Toggle System (Desktop Collapse + Mobile Drawer)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    function isDesktop() {
        return window.innerWidth >= 992;
    }

    function updateToggleTitle() {
        if (!sidebarToggle) return;
        const isCollapsed = document.documentElement.classList.contains('sidebar-collapsed') || document.body.classList.contains('sidebar-collapsed');
        if (isDesktop()) {
            sidebarToggle.title = isCollapsed ? 'Buka Menu Sidebar (Klik untuk menampilkan menu)' : 'Tutup / Geser Menu Sidebar (Ctrl+B)';
            sidebarToggle.setAttribute('aria-expanded', !isCollapsed);
        } else {
            const isOpen = sidebar && sidebar.classList.contains('show');
            sidebarToggle.title = isOpen ? 'Tutup Menu' : 'Buka Menu';
            sidebarToggle.setAttribute('aria-expanded', isOpen);
        }
    }

    function toggleDesktopSidebar(force = null) {
        const currentlyCollapsed = document.body.classList.contains('sidebar-collapsed') || document.documentElement.classList.contains('sidebar-collapsed');
        const shouldCollapse = force !== null ? force : !currentlyCollapsed;
        
        if (shouldCollapse) {
            document.documentElement.classList.add('sidebar-collapsed');
            document.body.classList.add('sidebar-collapsed');
            try { localStorage.setItem('uline_sidebar_collapsed', 'true'); } catch (e) {}
        } else {
            document.documentElement.classList.remove('sidebar-collapsed');
            document.body.classList.remove('sidebar-collapsed');
            try { localStorage.setItem('uline_sidebar_collapsed', 'false'); } catch (e) {}
        }
        updateToggleTitle();
    }

    function openMobileSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('show');
        document.body.classList.add('sidebar-mobile-open');
        updateToggleTitle();
    }

    function closeMobileSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('show');
        document.body.classList.remove('sidebar-mobile-open');
        updateToggleTitle();
    }

    function toggleSidebar() {
        if (isDesktop()) {
            toggleDesktopSidebar();
        } else {
            if (sidebar && sidebar.classList.contains('show')) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        }
    }

    // Restore desktop preference on DOM load
    try {
        const savedCollapsed = localStorage.getItem('uline_sidebar_collapsed');
        if (savedCollapsed === 'true' && isDesktop()) {
            document.documentElement.classList.add('sidebar-collapsed');
            document.body.classList.add('sidebar-collapsed');
        }
    } catch (e) {}

    updateToggleTitle();

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleSidebar();
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', (e) => {
            e.stopPropagation();
            if (isDesktop()) {
                toggleDesktopSidebar(true);
            } else {
                closeMobileSidebar();
            }
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', () => {
            closeMobileSidebar();
        });
    }

    // Keyboard shortcuts: ESC to close on mobile, Ctrl+B to toggle on desktop
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !isDesktop()) {
            closeMobileSidebar();
        }
        if ((e.ctrlKey || e.metaKey) && (e.key === 'b' || e.key === 'B')) {
            e.preventDefault();
            toggleSidebar();
        }
    });

    // Close mobile drawer on resize to desktop
    window.addEventListener('resize', () => {
        if (isDesktop()) {
            closeMobileSidebar();
            try {
                const saved = localStorage.getItem('uline_sidebar_collapsed');
                if (saved === 'true') {
                    document.documentElement.classList.add('sidebar-collapsed');
                    document.body.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        }
        updateToggleTitle();
    });

    // 2. Role Selector Switcher & Auto-Fill Credentials
    const roleTabs = document.querySelectorAll('.btn-role-tab');
    const identifierInput = document.getElementById('identifier');
    const passwordInput = document.getElementById('password');
    const activeRoleBadge = document.getElementById('activeRoleBadge');

    function applyRole(btn, save = true) {
        if (!btn) return;
        const username = btn.getAttribute('data-username');
        const password = btn.getAttribute('data-password');
        const role = btn.getAttribute('data-role');
        const label = btn.getAttribute('data-label');

        roleTabs.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        if (identifierInput) identifierInput.value = username;
        if (passwordInput) passwordInput.value = password;

        if (activeRoleBadge) {
            activeRoleBadge.innerHTML = `<i class="bi bi-check-circle me-1"></i>Role: ${label}`;
        }

        if (save) {
            try {
                localStorage.setItem('uline_login_role', role);
            } catch (e) {}
        }

        // Visual flash feedback
        if (identifierInput && passwordInput) {
            identifierInput.classList.add('is-valid');
            passwordInput.classList.add('is-valid');
            setTimeout(() => {
                identifierInput.classList.remove('is-valid');
                passwordInput.classList.remove('is-valid');
            }, 600);
        }
    }

    if (roleTabs.length > 0) {
        // Restore last chosen role if available
        let savedRole = null;
        try {
            savedRole = localStorage.getItem('uline_login_role');
        } catch (e) {}

        if (savedRole) {
            const savedBtn = document.querySelector(`.btn-role-tab[data-role="${savedRole}"]`);
            if (savedBtn) {
                applyRole(savedBtn, false);
            }
        }

        roleTabs.forEach(btn => {
            btn.addEventListener('click', () => {
                applyRole(btn, true);
            });
        });
    }

    // Also support .btn-quick-login if present
    const quickLoginButtons = document.querySelectorAll('.btn-quick-login');
    quickLoginButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const user = btn.getAttribute('data-user');
            const pass = btn.getAttribute('data-pass');
            if (identifierInput && passwordInput) {
                identifierInput.value = user;
                passwordInput.value = pass;
            }
        });
    });

    // 3. Toggle Password Visibility
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    if (togglePasswordBtn && passwordInput && togglePasswordIcon) {
        togglePasswordBtn.addEventListener('click', () => {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                togglePasswordIcon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                togglePasswordIcon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    }

    // 4. Auto-dismiss flash alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });

    // 5. Notification Dropdown & AJAX Polling Engine
    const notifToggle = document.getElementById('notificationDropdownToggle');
    const notifBadge = document.getElementById('navbarNotifBadge');
    const dropdownBadge = document.getElementById('dropdownUnreadBadge');
    const notifList = document.getElementById('notificationDropdownList');
    const btnMarkAll = document.getElementById('btnMarkAllReadNav');

    function updateNotificationBadges(count) {
        const countNum = parseInt(count, 10) || 0;
        if (notifBadge) {
            notifBadge.textContent = countNum > 99 ? '99+' : countNum;
            if (countNum > 0) {
                notifBadge.classList.remove('d-none');
            } else {
                notifBadge.classList.add('d-none');
            }
        }
        if (dropdownBadge) {
            dropdownBadge.textContent = countNum;
            if (countNum > 0) {
                dropdownBadge.classList.remove('d-none');
            } else {
                dropdownBadge.classList.add('d-none');
            }
        }
    }

    async function loadRecentNotifications() {
        if (!notifList) return;
        try {
            const res = await fetchApi('api/notifications/recent');
            if (res && res.status === 'success') {
                updateNotificationBadges(res.unread_count);
                renderDropdownNotifications(res.notifications || []);
            }
        } catch (err) {
            notifList.innerHTML = `
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-exclamation-circle text-warning fs-5 d-block mb-1"></i>
                    Gagal memuat notifikasi.
                </div>
            `;
        }
    }

    function renderDropdownNotifications(items) {
        if (!notifList) return;
        if (!items || items.length === 0) {
            notifList.innerHTML = `
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-bell-slash fs-4 d-block mb-1"></i>
                    Belum ada notifikasi baru.
                </div>
            `;
            return;
        }

        let html = '<div class="list-group list-group-flush">';
        items.forEach(item => {
            const isUnread = !item.is_read;
            const bgClass = isUnread ? 'bg-primary-subtle bg-opacity-25' : '';
            const unreadDot = isUnread ? '<span class="badge bg-danger rounded-circle p-1" style="width: 8px; height: 8px;"></span>' : '';
            
            let icon = 'bi-bell text-primary';
            if (item.type === 'exam') icon = 'bi-journal-check text-primary';
            else if (item.type === 'result') icon = 'bi-trophy-fill text-success';
            else if (item.type === 'proctoring' || item.type === 'danger') icon = 'bi-shield-exclamation text-danger';
            else if (item.type === 'warning') icon = 'bi-exclamation-triangle-fill text-warning';
            else if (item.type === 'system') icon = 'bi-gear-fill text-secondary';

            html += `
                <div class="list-group-item list-group-item-action px-3 py-2.5 ${bgClass} position-relative cursor-pointer notif-item" data-id="${item.id}" data-link="${item.link || ''}">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi ${icon} fs-6 mt-1 flex-shrink-0"></i>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between align-items-center mb-0.5">
                                <div class="fw-semibold small text-truncate ${isUnread ? 'text-dark' : 'text-muted'}" style="max-width: 210px;">
                                    ${item.title}
                                </div>
                                ${unreadDot}
                            </div>
                            <div class="text-secondary extra-small text-truncate-2 lh-sm mb-1">
                                ${item.message}
                            </div>
                            <div class="text-muted font-monospace" style="font-size: 0.7rem;">
                                <i class="bi bi-clock me-0.5"></i>${item.time_ago}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        notifList.innerHTML = html;

        // Bind clicks on notification items
        notifList.querySelectorAll('.notif-item').forEach(el => {
            el.addEventListener('click', async (e) => {
                const id = el.dataset.id;
                const link = el.dataset.link;
                try {
                    await fetchApi(`api/notifications/${id}/read`, { method: 'POST' });
                } catch (err) {}
                if (link) {
                    window.location.href = link;
                } else {
                    loadRecentNotifications();
                }
            });
        });
    }

    if (notifToggle) {
        notifToggle.addEventListener('show.bs.dropdown', () => {
            loadRecentNotifications();
        });
    }

    if (btnMarkAll) {
        btnMarkAll.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            try {
                btnMarkAll.disabled = true;
                await fetchApi('api/notifications/mark-all-read', { method: 'POST' });
                updateNotificationBadges(0);
                loadRecentNotifications();
            } catch (err) {
                console.error('Error marking all as read:', err);
            } finally {
                btnMarkAll.disabled = false;
            }
        });
    }

    // 6. Periodic AJAX Notification Polling (Every 30 seconds)
    if (window.APP_URL && document.getElementById('navbarNotifBadge')) {
        setInterval(async () => {
            try {
                const res = await fetchApi('api/notifications/unread-count');
                if (res && res.status === 'success') {
                    updateNotificationBadges(res.unread_count);
                }
            } catch (err) {
                // Ignore silent polling network errors
            }
        }, 30000);
    }

    // 7. Web Push Notification Manager (Push API & Service Worker)
    const pushToggle = document.getElementById('pushNotificationToggle');
    const pushBadge = document.getElementById('pushStatusBadge');
    const pushLabel = document.getElementById('pushToggleLabel');
    const pushDesc = document.getElementById('pushStatusDesc');

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    async function initWebPushManager() {
        if (!pushToggle) return;

        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            pushToggle.disabled = true;
            if (pushBadge) {
                pushBadge.className = 'badge bg-secondary-subtle text-secondary border extra-small';
                pushBadge.textContent = 'Tidak Didukung';
            }
            if (pushLabel) pushLabel.textContent = 'Tidak Didukung';
            if (pushDesc) pushDesc.textContent = 'Browser ini belum mendukung Web Push Notifications.';
            return;
        }

        try {
            const swUrl = (window.APP_URL ? window.APP_URL + '/' : '/') + 'service-worker.js';
            const registration = await navigator.serviceWorker.register(swUrl);
            const existingSubscription = await registration.pushManager.getSubscription();

            if (Notification.permission === 'denied') {
                pushToggle.checked = false;
                pushToggle.disabled = true;
                if (pushBadge) {
                    pushBadge.className = 'badge bg-danger-subtle text-danger border extra-small';
                    pushBadge.textContent = 'Izin Diblokir';
                }
                if (pushLabel) pushLabel.textContent = 'Diblokir';
                if (pushDesc) pushDesc.textContent = 'Izin notifikasi diblokir di pengaturan browser. Buka setelan browser untuk mengaktifkannya kembali.';
                return;
            }

            if (existingSubscription && Notification.permission === 'granted') {
                pushToggle.checked = true;
                if (pushBadge) {
                    pushBadge.className = 'badge bg-success-subtle text-success border extra-small';
                    pushBadge.textContent = 'Aktif';
                }
                if (pushLabel) pushLabel.textContent = 'Aktif';
            } else {
                pushToggle.checked = false;
                if (pushBadge) {
                    pushBadge.className = 'badge bg-secondary-subtle text-secondary border extra-small';
                    pushBadge.textContent = 'Non-Aktif';
                }
                if (pushLabel) pushLabel.textContent = 'Non-Aktif';
            }

            pushToggle.disabled = false;

            // Handle user toggle click
            pushToggle.addEventListener('change', async () => {
                pushToggle.disabled = true;

                if (pushToggle.checked) {
                    try {
                        const permission = await Notification.requestPermission();
                        if (permission !== 'granted') {
                            pushToggle.checked = false;
                            if (pushBadge) {
                                pushBadge.className = 'badge bg-danger-subtle text-danger border extra-small';
                                pushBadge.textContent = 'Izin Ditolak';
                            }
                            if (pushLabel) pushLabel.textContent = 'Ditolak';
                            pushToggle.disabled = false;
                            return;
                        }

                        const keyRes = await fetchApi('api/push/vapid-public-key');
                        if (!keyRes || !keyRes.public_key) {
                            throw new Error('VAPID public key tidak tersedia');
                        }

                        const sub = await registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlBase64ToUint8Array(keyRes.public_key)
                        });

                        const subJson = sub.toJSON();
                        await fetchApi('api/push/subscribe', {
                            method: 'POST',
                            body: {
                                endpoint: sub.endpoint,
                                keys: {
                                    p256dh: subJson.keys ? subJson.keys.p256dh : '',
                                    auth: subJson.keys ? subJson.keys.auth : ''
                                }
                            }
                        });

                        if (pushBadge) {
                            pushBadge.className = 'badge bg-success-subtle text-success border extra-small';
                            pushBadge.textContent = 'Aktif';
                        }
                        if (pushLabel) pushLabel.textContent = 'Aktif';
                    } catch (err) {
                        console.error('Web Push Subscription error:', err);
                        pushToggle.checked = false;
                        if (pushBadge) {
                            pushBadge.className = 'badge bg-warning-subtle text-warning border extra-small';
                            pushBadge.textContent = 'Gagal Mengaktifkan';
                        }
                    }
                } else {
                    try {
                        const sub = await registration.pushManager.getSubscription();
                        if (sub) {
                            const endpoint = sub.endpoint;
                            await sub.unsubscribe();
                            await fetchApi('api/push/unsubscribe', {
                                method: 'POST',
                                body: { endpoint: endpoint }
                            });
                        }
                        if (pushBadge) {
                            pushBadge.className = 'badge bg-secondary-subtle text-secondary border extra-small';
                            pushBadge.textContent = 'Non-Aktif';
                        }
                        if (pushLabel) pushLabel.textContent = 'Non-Aktif';
                    } catch (err) {
                        console.error('Web Push Unsubscribe error:', err);
                    }
                }

                pushToggle.disabled = false;
            });

        } catch (err) {
            console.warn('Web Push initialization warning:', err);
            pushToggle.disabled = true;
            if (pushBadge) {
                pushBadge.className = 'badge bg-secondary-subtle text-secondary border extra-small';
                pushBadge.textContent = 'Non-Aktif';
            }
        }
    }

    initWebPushManager();
});

/**
 * Global AJAX / Fetch API Wrapper for Asynchronous Backend Communication
 *
 * @param {string} url Endpoint URL
 * @param {object} options Fetch options (method, body, headers)
 * @returns {Promise<any>}
 */
async function fetchApi(url, options = {}) {
    const defaultHeaders = {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    };

    if (window.CSRF_TOKEN) {
        defaultHeaders['X-CSRF-Token'] = window.CSRF_TOKEN;
    }

    // If body is an object and not FormData, stringify to JSON
    let body = options.body;
    if (body && typeof body === 'object' && !(body instanceof FormData)) {
        defaultHeaders['Content-Type'] = 'application/json';
        body = JSON.stringify(body);
    }

    const config = {
        ...options,
        headers: {
            ...defaultHeaders,
            ...options.headers
        },
        body: body
    };

    const targetUrl = url.startsWith('http') ? url : (window.APP_URL ? window.APP_URL + '/' + url.replace(/^\//, '') : url);

    try {
        const response = await fetch(targetUrl, config);
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || `HTTP Error ${response.status}`);
        }
        return data;
    } catch (error) {
        console.error('Fetch API Error:', error);
        throw error;
    }
}
