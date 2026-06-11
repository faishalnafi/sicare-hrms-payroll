const CACHE_NAME = 'sicare-cache-v1';
const ASSETS_TO_CACHE = [
    '/manifest.php',
    '/images/icons/icon-72x72.png',
    '/images/icons/icon-96x96.png',
    '/images/icons/icon-128x128.png',
    '/images/icons/icon-144x144.png',
    '/images/icons/icon-152x152.png',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-384x384.png',
    '/images/icons/icon-512x512.png'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(ASSETS_TO_CACHE).catch(err => {
                console.warn('Failed to pre-cache some assets:', err);
            });
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cache => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    // We only intercept GET requests
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request)
            .then(response => {
                // If it's a valid response, we can dynamically cache static assets
                const url = new URL(event.request.url);
                if (response.ok && (
                    url.pathname.endsWith('.css') || 
                    url.pathname.endsWith('.js') || 
                    url.pathname.endsWith('.png') || 
                    url.pathname.endsWith('.jpg') || 
                    url.pathname.endsWith('.jpeg') || 
                    url.pathname.endsWith('.svg') || 
                    url.pathname.endsWith('.ico') ||
                    url.pathname.includes('/ringtones/')
                )) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                // If network fails, try to return from cache
                return caches.match(event.request).then(response => {
                    if (response) {
                        return response;
                    }
                    // Custom HTML fallback page when navigating
                    if (event.request.mode === 'navigate') {
                        return new Response(
                            `<!DOCTYPE html>
                            <html lang="id">
                            <head>
                                <meta charset="UTF-8">
                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                <title>Koneksi Terputus | siCare</title>
                                <style>
                                    body { 
                                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
                                        display: flex; 
                                        flex-direction: column; 
                                        align-items: center; 
                                        justify-content: center; 
                                        height: 100vh; 
                                        margin: 0; 
                                        background-color: #f8f9fa; 
                                        color: #191c1d; 
                                        text-align: center; 
                                        padding: 20px; 
                                    }
                                    .card {
                                        background: white;
                                        padding: 32px;
                                        border-radius: 20px;
                                        box-shadow: 0 10px 25px rgba(0, 6, 102, 0.05);
                                        border: 1px solid rgba(0, 6, 102, 0.08);
                                        max-width: 400px;
                                        width: 100%;
                                    }
                                    h1 { 
                                        font-size: 22px; 
                                        color: #000666; 
                                        margin-top: 0;
                                        margin-bottom: 12px; 
                                        font-weight: 800;
                                    }
                                    p { 
                                        font-size: 14px; 
                                        color: #454652; 
                                        margin-bottom: 24px; 
                                        line-height: 1.5;
                                    }
                                    .btn { 
                                        background-color: #000666; 
                                        color: white; 
                                        padding: 12px 24px; 
                                        border: none; 
                                        border-radius: 12px; 
                                        font-weight: 700; 
                                        cursor: pointer; 
                                        font-size: 14px;
                                        transition: background-color 0.2s;
                                        width: 100%;
                                        box-sizing: border-box;
                                    }
                                    .btn:hover {
                                        background-color: #000444;
                                    }
                                    .icon {
                                        font-size: 48px;
                                        color: #ba1a1a;
                                        margin-bottom: 16px;
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="card">
                                    <div class="icon">⚠️</div>
                                    <h1>Koneksi Terputus</h1>
                                    <p>Maaf, Anda sedang offline. Silakan periksa koneksi internet Anda untuk kembali mengakses siCare Portal Mandiri Karyawan.</p>
                                    <button class="btn" onclick="window.location.reload()">Coba Lagi</button>
                                </div>
                            </body>
                            </html>`,
                            {
                                status: 503,
                                statusText: "Offline",
                                headers: { "Content-Type": "text/html; charset=utf-8" }
                            }
                        );
                    }
                    return new Response("Koneksi internet terputus.", {
                        status: 503,
                        statusText: "Offline",
                        headers: { "Content-Type": "text/plain; charset=utf-8" }
                    });
                });
            })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
            let pageFocused = false;
            for (const client of clientList) {
                if (client.url.includes('/employee/attendance')) {
                    client.postMessage({ action: 'notification-clicked' });
                    if ('focus' in client) {
                        client.focus();
                        pageFocused = true;
                        break;
                    }
                }
            }
            if (!pageFocused && clients.openWindow) {
                return clients.openWindow('/employee/attendance');
            }
        })
    );
});
