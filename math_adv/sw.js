// Math Advantage - Service Worker para Push Notifications
const CACHE_NAME = 'math-advantage-v1';
const urlsToCache = [
    '/',
    '/portal/welcome.php',
    '/assets/css/styles.css',
    '/assets/js/main.js',
    '/img/logo_math-advantatge.png'
];

// Instalar Service Worker
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
    );
});

// Activar Service Worker
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Interceptar requests (para PWA)
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Cache hit - return response
                if (response) {
                    return response;
                }
                return fetch(event.request);
            })
    );
});

// Manejar Push Notifications
self.addEventListener('push', event => {
    console.log('Push notification received:', event);
    
    let notificationData = {};
    
    if (event.data) {
        try {
            notificationData = event.data.json();
        } catch (e) {
            notificationData = {
                title: 'Math Advantage',
                body: event.data.text() || 'Nueva notificación',
                icon: '/img/logo_math-advantatge.png'
            };
        }
    }
    
    // Opciones por defecto
    const options = {
        body: notificationData.body || 'Nueva notificación de Math Advantage',
        icon: notificationData.icon || '/img/logo_math-advantatge.png',
        badge: notificationData.badge || '/img/badge.png',
        image: notificationData.image,
        tag: notificationData.tag || 'math-advantage-notification',
        requireInteraction: notificationData.requireInteraction || false,
        actions: notificationData.actions || [
            {
                action: 'view',
                title: 'Ver',
                icon: '/img/view-icon.png'
            },
            {
                action: 'dismiss',
                title: 'Cerrar',
                icon: '/img/close-icon.png'
            }
        ],
        data: {
            url: notificationData.url || '/portal/welcome.php',
            notificationId: notificationData.id
        }
    };
    
    const title = notificationData.title || 'Math Advantage';
    
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Manejar clicks en notificaciones
self.addEventListener('notificationclick', event => {
    console.log('Notification clicked:', event);
    
    event.notification.close();
    
    const action = event.action;
    const notificationData = event.notification.data || {};
    
    if (action === 'dismiss') {
        // No hacer nada, solo cerrar
        return;
    }
    
    // Abrir la URL especificada o ir al portal
    const urlToOpen = notificationData.url || '/portal/welcome.php';
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(clientList => {
                // Buscar si ya hay una ventana abierta de Math Advantage
                for (let i = 0; i < clientList.length; i++) {
                    const client = clientList[i];
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        // Enfocar ventana existente y navegar a la URL
                        client.focus();
                        return client.navigate(urlToOpen);
                    }
                }
                
                // Si no hay ventana abierta, abrir una nueva
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
            .then(() => {
                // Marcar notificación como leída si es posible
                if (notificationData.notificationId) {
                    return fetch('/fase4/notificaciones/api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'mark_read',
                            notification_id: notificationData.notificationId
                        })
                    });
                }
            })
    );
});

// Manejar cierre de notificaciones
self.addEventListener('notificationclose', event => {
    console.log('Notification closed:', event);
    
    // Aquí podrías registrar estadísticas de notificaciones cerradas
    const notificationData = event.notification.data || {};
    
    if (notificationData.notificationId) {
        // Registrar que la notificación fue cerrada sin hacer click
        fetch('/fase4/notificaciones/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'notification_closed',
                notification_id: notificationData.notificationId
            })
        }).catch(err => console.log('Error registering notification close:', err));
    }
});

// Manejar mensajes desde la aplicación principal
self.addEventListener('message', event => {
    console.log('SW received message:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    // Responder con información del SW
    if (event.data && event.data.type === 'GET_VERSION') {
        event.ports[0].postMessage({
            version: CACHE_NAME,
            isUpdateAvailable: false
        });
    }
});

// Manejar sync en segundo plano (para notificaciones diferidas)
self.addEventListener('sync', event => {
    console.log('Background sync:', event.tag);
    
    if (event.tag === 'send-notifications') {
        event.waitUntil(
            // Enviar notificaciones pendientes
            fetch('/fase4/notificaciones/api.php?action=process_pending')
                .then(response => response.json())
                .then(data => {
                    console.log('Pending notifications processed:', data);
                })
                .catch(err => {
                    console.error('Error processing pending notifications:', err);
                })
        );
    }
});

// Utilidades para el Service Worker
function sendMessageToClient(client, message) {
    return new Promise((resolve, reject) => {
        const messageChannel = new MessageChannel();
        
        messageChannel.port1.onmessage = event => {
            if (event.data.error) {
                reject(event.data.error);
            } else {
                resolve(event.data);
            }
        };
        
        client.postMessage(message, [messageChannel.port2]);
    });
}

// Log de eventos para debugging
console.log('Math Advantage Service Worker loaded');