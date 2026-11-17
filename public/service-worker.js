/**
 * U-Line AI Online Examination & Proctoring System
 * Service Worker: Web Push Notifications & Lifecycle Management
 */

self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

// Push Event: Handle incoming push notification payload
self.addEventListener('push', function (event) {
    let payload = {
        title: 'Pemberitahuan U-Line',
        body: 'Anda memiliki pemberitahuan baru di sistem ujian U-Line.',
        icon: '/uline/public/assets/images/logo.png',
        badge: '/uline/public/assets/images/badge.png',
        data: {
            url: '/uline/notifications'
        }
    };

    if (event.data) {
        try {
            const parsed = event.data.json();
            payload.title = parsed.title || payload.title;
            payload.body = parsed.body || payload.body;
            if (parsed.icon) payload.icon = parsed.icon;
            if (parsed.badge) payload.badge = parsed.badge;
            if (parsed.data && parsed.data.url) payload.data.url = parsed.data.url;
        } catch (e) {
            payload.body = event.data.text();
        }
    }

    const options = {
        body: payload.body,
        icon: payload.icon,
        badge: payload.badge,
        vibrate: [100, 50, 100],
        data: payload.data,
        actions: [
            {
                action: 'open_url',
                title: 'Buka Notifikasi'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(payload.title, options)
    );
});

// Notification Click Event: Focus existing tab or open new URL
self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const targetUrl = (event.notification.data && event.notification.data.url) 
        ? event.notification.data.url 
        : '/uline/notifications';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if ('focus' in client && client.url.includes('/uline/')) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
