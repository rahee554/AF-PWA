// AF-PWA Service Worker v{{ config('app.version', '1.0') }}
// Generated: {{ now() }}

const CACHE_NAME = '{{ $cacheName }}';
const CACHE_STRATEGY = '{{ $cacheStrategy }}';
const DEBUG = {{ config('af-pwa.development.debug', false) ? 'true' : 'false' }};

// Allowed routes for PWA
const ALLOWED_ROUTES = @json($allowedRoutes);

// Offline pages configuration
const OFFLINE_PAGES = @json($offlinePages);

// Static assets to cache
const STATIC_ASSETS = @json($staticAssets);

// Additional configuration
const CONFIG = @json($config);

// Install event - cache static assets
self.addEventListener('install', (event) => {
    if (DEBUG) console.log('AF-PWA SW: Installing service worker');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(async (cache) => {
                if (DEBUG) console.log('AF-PWA SW: Caching static assets');
                
                // Cache essential assets
                const essentialAssets = [
                    '/',
                    '/manifest.json',
                    ...Object.values(OFFLINE_PAGES).map(page => page.url || `/offline-${page}.html`)
                ];
                
                // Add static assets if configured
                if (CONFIG.cache && CONFIG.cache.static_assets) {
                    essentialAssets.push(...STATIC_ASSETS.slice(0, 20)); // Limit initial cache
                }
                
                // Filter out undefined/null values
                const validAssets = essentialAssets.filter(Boolean);
                
                // Cache assets individually to handle 404s gracefully
                const cachePromises = validAssets.map(async (asset) => {
                    try {
                        const response = await fetch(asset);
                        if (response.ok) {
                            await cache.put(asset, response);
                            if (DEBUG) console.log('AF-PWA SW: Cached:', asset);
                        } else {
                            if (DEBUG) console.warn('AF-PWA SW: Failed to cache (status ' + response.status + '):', asset);
                        }
                    } catch (error) {
                        if (DEBUG) console.warn('AF-PWA SW: Failed to fetch for cache:', asset, error.message);
                    }
                });
                
                await Promise.allSettled(cachePromises);
                if (DEBUG) console.log('AF-PWA SW: Cache installation completed');
            })
            .catch((error) => {
                if (DEBUG) console.error('AF-PWA SW: Cache installation failed:', error);
            })
    );
    
    // Force activation of new service worker
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    if (DEBUG) console.log('AF-PWA SW: Activating service worker');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => cacheName !== CACHE_NAME)
                        .map((cacheName) => {
                            if (DEBUG) console.log('AF-PWA SW: Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        })
                );
            })
            .then(() => {
                // Take control of all pages immediately
                return self.clients.claim();
            })
    );
});

// Fetch event - handle network requests
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip external requests
    if (url.origin !== location.origin) {
        return;
    }
    
    // Check if route is allowed in PWA
    const path = url.pathname;
    const isAllowedRoute = isRouteAllowed(path);
    
    if (!isAllowedRoute) {
        // Redirect to external browser for disallowed routes
        event.respondWith(handleDisallowedRoute(request));
        return;
    }
    
    // Handle different types of requests
    if (isStaticAsset(request)) {
        event.respondWith(handleStaticAsset(request));
    } else if (isAPIRequest(request)) {
        event.respondWith(handleAPIRequest(request));
    } else if (isPageRequest(request)) {
        event.respondWith(handlePageRequest(request));
    }
});

// Message event - handle messages from client
self.addEventListener('message', (event) => {
    if (DEBUG) console.log('AF-PWA SW: Received message:', event.data);
    
    const { type, payload } = event.data;
    
    switch (type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;
            
        case 'GET_VERSION':
            event.ports[0].postMessage({
                type: 'VERSION',
                version: CACHE_NAME
            });
            break;
            
        case 'CACHE_URLS':
            if (payload && payload.urls) {
                cacheURLs(payload.urls);
            }
            break;
            
        case 'CLEAR_CACHE':
            clearCache();
            break;
            
        default:
            if (DEBUG) console.warn('AF-PWA SW: Unknown message type:', type);
    }
});

// Sync event - handle background sync
self.addEventListener('sync', (event) => {
    if (DEBUG) console.log('AF-PWA SW: Background sync:', event.tag);
    
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

// Push event - handle push notifications
self.addEventListener('push', (event) => {
    if (DEBUG) console.log('AF-PWA SW: Push received');
    
    if (event.data) {
        const data = event.data.json();
        event.waitUntil(showNotification(data));
    }
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    if (DEBUG) console.log('AF-PWA SW: Notification clicked');
    
    event.notification.close();
    
    if (event.notification.data && event.notification.data.url) {
        event.waitUntil(
            clients.openWindow(event.notification.data.url)
        );
    }
});

// Helper Functions

function isRouteAllowed(path) {
    if (ALLOWED_ROUTES.length === 0) return true;
    
    return ALLOWED_ROUTES.some(pattern => {
        // Simple wildcard matching
        const regex = new RegExp('^' + pattern.replace(/\*/g, '.*') + '$');
        return regex.test(path);
    });
}

function isStaticAsset(request) {
    const url = new URL(request.url);
    // Comprehensive list of cacheable file extensions
    return /\.(css|js|mjs|jsx|ts|tsx|png|jpg|jpeg|gif|svg|ico|webp|avif|bmp|tiff|woff|woff2|ttf|eot|otf|json|xml|pdf|txt|csv|webm|mp4|ogg|mp3|wav|flac|zip|gz|tar|rar|7z|html|htm)$/i.test(url.pathname);
}

function isAPIRequest(request) {
    const url = new URL(request.url);
    return url.pathname.startsWith('/api/') || 
           url.pathname.startsWith('/livewire/') ||
           request.headers.get('X-Requested-With') === 'XMLHttpRequest';
}

function isPageRequest(request) {
    return request.headers.get('Accept')?.includes('text/html');
}

async function handleDisallowedRoute(request) {
    // Return a response that triggers external browser opening
    return new Response(
        `<!DOCTYPE html>
        <html>
        <head>
            <title>Opening in Browser...</title>
            <meta http-equiv="refresh" content="0;url=${request.url}">
        </head>
        <body>
            <script>
                window.open('${request.url}', '_system');
            </script>
            <p>Redirecting to browser...</p>
        </body>
        </html>`,
        {
            headers: { 'Content-Type': 'text/html' },
            status: 200
        }
    );
}

async function handleStaticAsset(request) {
    const cacheStrategy = CONFIG.cache?.static_strategy || CACHE_STRATEGY;
    
    switch (cacheStrategy) {
        case 'cache_first':
            return cacheFirst(request);
        case 'network_first':
            return networkFirst(request);
        case 'stale_while_revalidate':
            return staleWhileRevalidate(request);
        default:
            return cacheFirst(request);
    }
}

async function handleAPIRequest(request) {
    try {
        // Always try network first for API requests
        const response = await fetch(request);
        
        // Cache successful GET requests
        if (response.ok && request.method === 'GET') {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        if (DEBUG) console.log('AF-PWA SW: API request failed, checking cache');
        
        // Try to return cached response
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline response for API requests
        return new Response(
            JSON.stringify({
                error: 'Network unavailable',
                offline: true,
                cached: false
            }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

async function handlePageRequest(request) {
    try {
        // Try network first for pages
        const response = await fetch(request);
        
        if (response.ok) {
            // Cache successful page responses
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        if (DEBUG) console.log('AF-PWA SW: Page request failed, checking cache');
        
        // Try cached version
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return appropriate offline page
        return getOfflinePage(request);
    }
}

async function cacheFirst(request) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        if (DEBUG) console.error('AF-PWA SW: Cache first failed:', error);
        throw error;
    }
}

async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        if (DEBUG) console.log('AF-PWA SW: Network first fallback to cache');
        
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        throw error;
    }
}

async function staleWhileRevalidate(request) {
    const cachedResponse = await caches.match(request);
    
    const fetchPromise = fetch(request)
        .then(response => {
            if (response.ok) {
                const cache = caches.open(CACHE_NAME);
                cache.then(c => c.put(request, response.clone()));
            }
            return response;
        })
        .catch(error => {
            if (DEBUG) console.error('AF-PWA SW: Stale while revalidate fetch failed:', error);
        });
    
    return cachedResponse || fetchPromise;
}

async function getOfflinePage(request) {
    const url = new URL(request.url);
    const path = url.pathname;
    
    // Determine which offline page to serve
    let offlinePage = 'default';
    
    if (path.startsWith('/admin')) {
        offlinePage = 'admin';
    } else if (path.startsWith('/member')) {
        offlinePage = 'member';
    }
    
    // Try to get the specific offline page
    const offlineUrl = `/offline-${offlinePage}.html`;
    const cachedOffline = await caches.match(offlineUrl);
    
    if (cachedOffline) {
        return cachedOffline;
    }
    
    // Fallback to default offline page
    const defaultOffline = await caches.match('/offline-default.html');
    if (defaultOffline) {
        return defaultOffline;
    }
    
    // Last resort - simple offline message
    return new Response(
        `<!DOCTYPE html>
        <html>
        <head>
            <title>Offline - ${CONFIG.manifest?.name || 'PWA'}</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                .offline { color: #666; }
            </style>
        </head>
        <body>
            <div class="offline">
                <h1>You're Offline</h1>
                <p>This page is not available offline. Please check your internet connection.</p>
            </div>
        </body>
        </html>`,
        {
            headers: { 'Content-Type': 'text/html' },
            status: 200
        }
    );
}

async function cacheURLs(urls) {
    if (DEBUG) console.log('AF-PWA SW: Caching URLs:', urls);
    
    try {
        const cache = await caches.open(CACHE_NAME);
        await cache.addAll(urls);
    } catch (error) {
        if (DEBUG) console.error('AF-PWA SW: Failed to cache URLs:', error);
    }
}

async function clearCache() {
    if (DEBUG) console.log('AF-PWA SW: Clearing cache');
    
    try {
        await caches.delete(CACHE_NAME);
    } catch (error) {
        if (DEBUG) console.error('AF-PWA SW: Failed to clear cache:', error);
    }
}

async function doBackgroundSync() {
    if (DEBUG) console.log('AF-PWA SW: Performing background sync');
    
    // Implement background sync logic here
    // This could include uploading offline data, syncing user preferences, etc.
}

async function showNotification(data) {
    const options = {
        body: data.body || 'New notification',
        icon: data.icon || '/icons/icon-192x192.png',
        badge: data.badge || '/icons/icon-72x72.png',
        data: data.data || {},
        actions: data.actions || [],
        tag: data.tag || 'default',
        renotify: data.renotify || false
    };
    
    return self.registration.showNotification(data.title || 'Notification', options);
}
