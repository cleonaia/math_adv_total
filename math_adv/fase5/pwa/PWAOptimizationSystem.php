<?php
/**
 * Math Advantage - Sistema PWA y Optimización
 * Fase 5: Progressive Web App Implementation
 */

require_once '../../php/classes/Database.php';

class PWAOptimizationSystem {
    private $pdo;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection();
    }
    
    /**
     * Generate PWA manifest
     */
    public function generateManifest() {
        return [
            "name" => "Math Advantage - Portal Educatiu",
            "short_name" => "MathAdvantage",
            "description" => "Plataforma educativa avançada per a l'aprenentatge de matemàtiques",
            "start_url" => "/",
            "display" => "standalone",
            "background_color" => "#2563eb",
            "theme_color" => "#2563eb",
            "orientation" => "portrait-primary",
            "scope" => "/",
            "lang" => "ca",
            "dir" => "ltr",
            "categories" => ["education", "productivity"],
            "icons" => [
                [
                    "src" => "/img/icons/icon-72x72.png",
                    "sizes" => "72x72",
                    "type" => "image/png",
                    "purpose" => "maskable any"
                ],
                [
                    "src" => "/img/icons/icon-96x96.png",
                    "sizes" => "96x96",
                    "type" => "image/png",
                    "purpose" => "maskable any"
                ],
                [
                    "src" => "/img/icons/icon-128x128.png",
                    "sizes" => "128x128",
                    "type" => "image/png",
                    "purpose" => "maskable any"
                ],
                [
                    "src" => "/img/icons/icon-144x144.png",
                    "sizes" => "144x144",
                    "type" => "image/png",
                    "purpose" => "maskable any"
                ],
                [
                    "src" => "/img/icons/icon-152x152.png",
                    "sizes" => "152x152",
                    "type" => "image/png",
                    "purpose" => "maskable any"
                ],
                [
                    "src" => "/img/icons/icon-192x192.png",
                    "sizes" => "192x192",
                    "type" => "image/png",
                    "purpose" => "maskable any"
                ],
                [
                    "src" => "/img/icons/icon-384x384.png",
                    "sizes" => "384x384",
                    "type" => "image/png",
                    "purpose" => "maskable any"
                ],
                [
                    "src" => "/img/icons/icon-512x512.png",
                    "sizes" => "512x512",
                    "type" => "image/png",
                    "purpose" => "maskable any"
                ]
            ],
            "shortcuts" => [
                [
                    "name" => "Portal Estudiants",
                    "short_name" => "Estudiants",
                    "description" => "Accés directe al portal d'estudiants",
                    "url" => "/portal/student/dashboard.php",
                    "icons" => [
                        [
                            "src" => "/img/icons/student-icon.png",
                            "sizes" => "96x96"
                        ]
                    ]
                ],
                [
                    "name" => "Portal Professors",
                    "short_name" => "Professors",
                    "description" => "Accés directe al portal de professors",
                    "url" => "/portal/teacher/dashboard.php",
                    "icons" => [
                        [
                            "src" => "/img/icons/teacher-icon.png",
                            "sizes" => "96x96"
                        ]
                    ]
                ],
                [
                    "name" => "Evaluacions",
                    "short_name" => "Exàmens",
                    "description" => "Accés directe a les evaluacions",
                    "url" => "/fase4/evaluaciones/evaluaciones.html",
                    "icons" => [
                        [
                            "src" => "/img/icons/exam-icon.png",
                            "sizes" => "96x96"
                        ]
                    ]
                ],
                [
                    "name" => "Chat",
                    "short_name" => "Xat",
                    "description" => "Accés directe al sistema de chat",
                    "url" => "/fase4/chat/chat.html",
                    "icons" => [
                        [
                            "src" => "/img/icons/chat-icon.png",
                            "sizes" => "96x96"
                        ]
                    ]
                ]
            ],
            "screenshots" => [
                [
                    "src" => "/img/screenshots/desktop-1.png",
                    "sizes" => "1280x720",
                    "type" => "image/png",
                    "form_factor" => "wide",
                    "label" => "Dashboard principal"
                ],
                [
                    "src" => "/img/screenshots/mobile-1.png",
                    "sizes" => "390x844",
                    "type" => "image/png",
                    "form_factor" => "narrow",
                    "label" => "Vista mòbil"
                ]
            ]
        ];
    }
    
    /**
     * Generate Service Worker
     */
    public function generateServiceWorker() {
        $swContent = "
const CACHE_NAME = 'math-advantage-v" . time() . "';
const STATIC_ASSETS = [
    '/',
    '/index.html',
    '/assets/css/styles.css',
    '/assets/js/main.js',
    '/portal/',
    '/fase4/evaluaciones/evaluaciones.html',
    '/fase4/chat/chat.html',
    '/fase4/videollamadas/videollamadas.html',
    '/fase4/calendario/calendario.html',
    '/fase4/gamificacion/gamificacion.html',
    '/fase4/notificaciones/notificaciones.html',
    '/fase5/analytics/dashboard.html',
    // External dependencies
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap'
];

const DYNAMIC_CACHE = 'math-advantage-dynamic-v" . time() . "';

// Install event - cache static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => cacheName !== CACHE_NAME && cacheName !== DYNAMIC_CACHE)
                    .map(cacheName => caches.delete(cacheName))
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Handle navigation requests
    if (request.mode === 'navigate') {
        event.respondWith(
            caches.match(request)
                .then(response => {
                    if (response) {
                        return response;
                    }
                    return fetch(request)
                        .then(fetchResponse => {
                            // Cache successful responses
                            if (fetchResponse.status === 200) {
                                const responseClone = fetchResponse.clone();
                                caches.open(DYNAMIC_CACHE)
                                    .then(cache => cache.put(request, responseClone));
                            }
                            return fetchResponse;
                        })
                        .catch(() => {
                            // Return offline page
                            return caches.match('/offline.html');
                        });
                })
        );
        return;
    }
    
    // Handle API requests
    if (url.pathname.includes('/php/api.php') || url.pathname.includes('/fase4/')) {
        event.respondWith(
            fetch(request)
                .then(response => {
                    // Cache successful API responses for short time
                    if (response.status === 200 && request.method === 'GET') {
                        const responseClone = response.clone();
                        caches.open(DYNAMIC_CACHE)
                            .then(cache => {
                                // Set short expiration for API responses
                                cache.put(request, responseClone);
                                setTimeout(() => {
                                    cache.delete(request);
                                }, 300000); // 5 minutes
                            });
                    }
                    return response;
                })
                .catch(() => {
                    // Return cached response if available
                    return caches.match(request);
                })
        );
        return;
    }
    
    // Handle static assets
    event.respondWith(
        caches.match(request)
            .then(response => {
                if (response) {
                    return response;
                }
                
                return fetch(request)
                    .then(fetchResponse => {
                        // Don't cache non-successful responses
                        if (!fetchResponse || fetchResponse.status !== 200 || fetchResponse.type !== 'basic') {
                            return fetchResponse;
                        }
                        
                        const responseClone = fetchResponse.clone();
                        caches.open(DYNAMIC_CACHE)
                            .then(cache => cache.put(request, responseClone));
                        
                        return fetchResponse;
                    });
            })
    );
});

// Background sync
self.addEventListener('sync', event => {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

async function doBackgroundSync() {
    try {
        // Sync offline actions stored in IndexedDB
        const offlineActions = await getOfflineActions();
        
        for (const action of offlineActions) {
            try {
                await fetch(action.url, {
                    method: action.method,
                    headers: action.headers,
                    body: action.body
                });
                
                // Remove successful action
                await removeOfflineAction(action.id);
                
            } catch (error) {
                console.error('Failed to sync action:', error);
            }
        }
        
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

// Push notifications
self.addEventListener('push', event => {
    let data = {};
    
    if (event.data) {
        data = event.data.json();
    }
    
    const options = {
        body: data.body || 'Tens una nova notificació de Math Advantage',
        icon: '/img/icons/icon-192x192.png',
        badge: '/img/icons/badge-72x72.png',
        image: data.image,
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: data.primaryKey || 1,
            url: data.url || '/'
        },
        actions: [
            {
                action: 'explore',
                title: 'Veure més',
                icon: '/img/icons/checkmark.png'
            },
            {
                action: 'close',
                title: 'Tancar',
                icon: '/img/icons/xmark.png'
            }
        ],
        tag: data.tag || 'notification',
        renotify: true,
        requireInteraction: data.requireInteraction || false
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'Math Advantage', options)
    );
});

// Notification click
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow(event.notification.data.url)
        );
    } else if (event.action === 'close') {
        // Just close the notification
        return;
    } else {
        // Default action - open app
        event.waitUntil(
            clients.matchAll({ type: 'window' }).then(clientList => {
                for (const client of clientList) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        return client.focus();
                    }
                }
                if (clients.openWindow) {
                    return clients.openWindow(event.notification.data.url);
                }
            })
        );
    }
});

// Utility functions for IndexedDB
async function getOfflineActions() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('MathAdvantageDB', 1);
        
        request.onerror = () => reject(request.error);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['offlineActions'], 'readonly');
            const store = transaction.objectStore('offlineActions');
            const getRequest = store.getAll();
            
            getRequest.onsuccess = () => resolve(getRequest.result);
            getRequest.onerror = () => reject(getRequest.error);
        };
        
        request.onupgradeneeded = () => {
            const db = request.result;
            db.createObjectStore('offlineActions', { keyPath: 'id', autoIncrement: true });
        };
    });
}

async function removeOfflineAction(id) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('MathAdvantageDB', 1);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['offlineActions'], 'readwrite');
            const store = transaction.objectStore('offlineActions');
            const deleteRequest = store.delete(id);
            
            deleteRequest.onsuccess = () => resolve();
            deleteRequest.onerror = () => reject(deleteRequest.error);
        };
    });
}

// Periodic sync (if supported)
self.addEventListener('periodicsync', event => {
    if (event.tag === 'content-sync') {
        event.waitUntil(syncContent());
    }
});

async function syncContent() {
    try {
        // Sync latest content, notifications, etc.
        const response = await fetch('/php/api.php?action=sync_content');
        const data = await response.json();
        
        if (data.success) {
            // Cache updated content
            const cache = await caches.open(DYNAMIC_CACHE);
            await cache.put('/sync-data', new Response(JSON.stringify(data.data)));
        }
        
    } catch (error) {
        console.error('Content sync failed:', error);
    }
}
        ";
        
        return $swContent;
    }
    
    /**
     * Generate offline page
     */
    public function generateOfflinePage() {
        return '
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sense connexió - Math Advantage</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            text-align: center;
            padding: 2rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .offline-container {
            max-width: 500px;
        }
        .offline-icon {
            font-size: 4rem;
            margin-bottom: 2rem;
        }
        .offline-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .offline-message {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .retry-button {
            background: white;
            color: #2563eb;
            border: none;
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .retry-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .features-list {
            margin-top: 3rem;
            text-align: left;
        }
        .feature-item {
            padding: 0.5rem 0;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">📡</div>
        <h1 class="offline-title">Sense connexió a Internet</h1>
        <p class="offline-message">
            No es pot connectar amb Math Advantage en aquest moment. 
            Comprova la teva connexió a Internet i torna-ho a intentar.
        </p>
        
        <button class="retry-button" onclick="window.location.reload()">
            Tornar a intentar
        </button>
        
        <div class="features-list">
            <h3>Què pots fer offline:</h3>
            <div class="feature-item">• Revisar contingut descarregat prèviament</div>
            <div class="feature-item">• Preparar respostes que s\'enviaran automàticament</div>
            <div class="feature-item">• Accedir als materials guardats</div>
        </div>
    </div>
    
    <script>
        // Auto-retry when connection is restored
        window.addEventListener("online", () => {
            window.location.reload();
        });
        
        // Check connection periodically
        setInterval(() => {
            if (navigator.onLine) {
                fetch("/").then(() => {
                    window.location.reload();
                }).catch(() => {
                    // Still offline
                });
            }
        }, 5000);
    </script>
</body>
</html>';
    }
    
    /**
     * Optimize images for PWA
     */
    public function optimizeImages() {
        $sizes = [72, 96, 128, 144, 152, 192, 384, 512];
        $sourceImage = '../../img/logo_math-advantatge.png';
        
        if (!file_exists($sourceImage)) {
            return false;
        }
        
        $iconDir = '../../img/icons/';
        if (!is_dir($iconDir)) {
            mkdir($iconDir, 0755, true);
        }
        
        foreach ($sizes as $size) {
            $this->createIcon($sourceImage, $iconDir . "icon-{$size}x{$size}.png", $size);
        }
        
        return true;
    }
    
    /**
     * Create icon of specific size
     */
    private function createIcon($source, $destination, $size) {
        $imageInfo = getimagesize($source);
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        
        // Create source image resource
        switch ($imageInfo[2]) {
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($source);
                break;
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($source);
                break;
            default:
                return false;
        }
        
        // Create destination image
        $destImage = imagecreatetruecolor($size, $size);
        
        // Preserve transparency
        imagealphablending($destImage, false);
        imagesavealpha($destImage, true);
        $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
        imagefilledrectangle($destImage, 0, 0, $size, $size, $transparent);
        
        // Calculate dimensions to maintain aspect ratio
        $aspectRatio = $sourceWidth / $sourceHeight;
        if ($aspectRatio > 1) {
            $newWidth = $size;
            $newHeight = $size / $aspectRatio;
        } else {
            $newWidth = $size * $aspectRatio;
            $newHeight = $size;
        }
        
        $x = ($size - $newWidth) / 2;
        $y = ($size - $newHeight) / 2;
        
        // Resize and copy
        imagecopyresampled($destImage, $sourceImage, $x, $y, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
        
        // Save
        imagepng($destImage, $destination);
        
        // Cleanup
        imagedestroy($sourceImage);
        imagedestroy($destImage);
        
        return true;
    }
    
    /**
     * Generate meta tags for PWA
     */
    public function getPWAMetaTags() {
        return [
            '<meta name="theme-color" content="#2563eb">',
            '<meta name="background-color" content="#2563eb">',
            '<meta name="display" content="standalone">',
            '<meta name="orientation" content="portrait-primary">',
            '<meta name="apple-mobile-web-app-capable" content="yes">',
            '<meta name="apple-mobile-web-app-status-bar-style" content="default">',
            '<meta name="apple-mobile-web-app-title" content="Math Advantage">',
            '<meta name="msapplication-TileColor" content="#2563eb">',
            '<meta name="msapplication-config" content="/browserconfig.xml">',
            '<link rel="apple-touch-icon" sizes="180x180" href="/img/icons/icon-192x192.png">',
            '<link rel="icon" type="image/png" sizes="32x32" href="/img/icons/icon-32x32.png">',
            '<link rel="icon" type="image/png" sizes="16x16" href="/img/icons/icon-16x16.png">',
            '<link rel="mask-icon" href="/img/icons/safari-pinned-tab.svg" color="#2563eb">'
        ];
    }
    
    /**
     * Generate performance optimizations
     */
    public function getPerformanceOptimizations() {
        return [
            'preload' => [
                '<link rel="preload" href="/assets/css/styles.css" as="style">',
                '<link rel="preload" href="/assets/js/main.js" as="script">',
                '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" as="style">'
            ],
            'prefetch' => [
                '<link rel="prefetch" href="/portal/">',
                '<link rel="prefetch" href="/fase4/evaluaciones/evaluaciones.html">',
                '<link rel="prefetch" href="/fase4/chat/chat.html">'
            ],
            'dns-prefetch' => [
                '<link rel="dns-prefetch" href="//cdn.jsdelivr.net">',
                '<link rel="dns-prefetch" href="//cdnjs.cloudflare.com">',
                '<link rel="dns-prefetch" href="//fonts.googleapis.com">',
                '<link rel="dns-prefetch" href="//fonts.gstatic.com">'
            ],
            'preconnect' => [
                '<link rel="preconnect" href="https://fonts.googleapis.com">',
                '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
            ]
        ];
    }
    
    /**
     * Install PWA prompt
     */
    public function getPWAInstallPrompt() {
        return '
<div id="pwaInstallBanner" class="pwa-install-banner" style="display: none;">
    <div class="pwa-banner-content">
        <div class="pwa-banner-icon">
            <img src="/img/icons/icon-96x96.png" alt="Math Advantage Icon" width="48" height="48">
        </div>
        <div class="pwa-banner-text">
            <h4>Instal·la Math Advantage</h4>
            <p>Accés ràpid i funcions offline</p>
        </div>
        <div class="pwa-banner-actions">
            <button id="pwaInstallBtn" class="btn btn-primary btn-sm">Instal·lar</button>
            <button id="pwaCloseBtn" class="btn btn-secondary btn-sm">Més tard</button>
        </div>
    </div>
</div>

<style>
.pwa-install-banner {
    position: fixed;
    bottom: 20px;
    left: 20px;
    right: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    z-index: 1000;
    padding: 1rem;
    border: 1px solid #e5e7eb;
}

.pwa-banner-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.pwa-banner-text h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.pwa-banner-text p {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
}

.pwa-banner-actions {
    margin-left: auto;
    display: flex;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .pwa-install-banner {
        left: 10px;
        right: 10px;
    }
    
    .pwa-banner-content {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }
    
    .pwa-banner-actions {
        margin-left: 0;
    }
}
</style>

<script>
let deferredPrompt;
let installSource = null;

// Track install prompt
window.addEventListener("beforeinstallprompt", (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Show custom install banner
    const banner = document.getElementById("pwaInstallBanner");
    const installBtn = document.getElementById("pwaInstallBtn");
    const closeBtn = document.getElementById("pwaCloseBtn");
    
    // Check if user has dismissed recently
    const dismissed = localStorage.getItem("pwa-install-dismissed");
    const dismissedTime = dismissed ? parseInt(dismissed) : 0;
    const daysSinceDismissed = (Date.now() - dismissedTime) / (1000 * 60 * 60 * 24);
    
    if (daysSinceDismissed > 7) { // Show again after 7 days
        banner.style.display = "block";
    }
    
    installBtn.addEventListener("click", async () => {
        banner.style.display = "none";
        
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const result = await deferredPrompt.userChoice;
            
            // Track installation
            if (result.outcome === "accepted") {
                console.log("PWA installed");
                gtag && gtag("event", "pwa_install", {
                    event_category: "engagement",
                    event_label: "user_accepted"
                });
            } else {
                console.log("PWA installation dismissed");
                localStorage.setItem("pwa-install-dismissed", Date.now().toString());
            }
            
            deferredPrompt = null;
        }
    });
    
    closeBtn.addEventListener("click", () => {
        banner.style.display = "none";
        localStorage.setItem("pwa-install-dismissed", Date.now().toString());
        
        gtag && gtag("event", "pwa_install", {
            event_category: "engagement",
            event_label: "user_dismissed"
        });
    });
});

// Track successful installation
window.addEventListener("appinstalled", (evt) => {
    console.log("PWA successfully installed");
    
    gtag && gtag("event", "pwa_install", {
        event_category: "engagement",
        event_label: "successful_install"
    });
    
    // Hide banner if still visible
    const banner = document.getElementById("pwaInstallBanner");
    if (banner) {
        banner.style.display = "none";
    }
});

// Detect if running as PWA
function isPWA() {
    return window.matchMedia("(display-mode: standalone)").matches || 
           window.navigator.standalone || 
           document.referrer.includes("android-app://");
}

// Track PWA usage
if (isPWA()) {
    gtag && gtag("event", "pwa_usage", {
        event_category: "engagement",
        event_label: "standalone_mode"
    });
    
    // Add PWA-specific styles or functionality
    document.body.classList.add("pwa-mode");
}
</script>';
    }
}
?>