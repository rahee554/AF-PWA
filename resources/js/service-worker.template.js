/**
 * AF-PWA Service Worker Template
 * Dynamic service worker for Laravel PWA applications
 */

// Configuration will be injected by Laravel - this is a placeholder
const CONFIG = __AF_PWA_CONFIG__;

const CACHE_VERSION = CONFIG.cache_version || 'v1';
const CACHE_NAME = `${CONFIG.app_name}-${CACHE_VERSION}`;
const ASSETS_CACHE = `${CONFIG.app_name}-assets-${CACHE_VERSION}`;
const API_CACHE = `${CONFIG.app_name}-api-${CACHE_VERSION}`;

// Routes that should be handled by PWA
const PWA_ROUTES = CONFIG.pwa_routes || [];
const STATIC_ASSETS = CONFIG.static_assets || [];
const CACHE_STRATEGIES = CONFIG.cache_strategies || {};

/**
 * Check if URL should be handled by PWA
 */
function isPWARoute(url) {
    const pathname = new URL(url).pathname;
    return PWA_ROUTES.some(route => routeMatches(url, route));
}

/**
 * Check if request is for static asset
 */
function isStaticAsset(url) {
    const pathname = new URL(url).pathname;
    const extension = pathname.split('.').pop()?.toLowerCase();
    
    const staticExtensions = ['js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'webp', 'woff', 'woff2', 'ttf'];
    
    return staticExtensions.includes(extension) || 
           CONFIG.asset_patterns?.some(pattern => pathname.includes(pattern));
}

/**
 * Check if request is API call
 */
function isApiRequest(url) {
    const pathname = new URL(url).pathname;
    return CONFIG.api_patterns?.some(pattern => pathname.includes(pattern)) || false;
}

/**
 * Install event - cache static assets
 */
self.addEventListener('install', event => {
    console.log('[SW] Installing...');
    
    event.waitUntil(
        Promise.all([
            // Cache PWA routes
            caches.open(CACHE_NAME).then(cache => {
                const routesToCache = PWA_ROUTES.filter(route => !route.includes('*'));
                return cache.addAll([
                    ...routesToCache,
                    ...(CONFIG.offline_pages || [])
                ]);
            }),
            // Cache static assets
            caches.open(ASSETS_CACHE).then(cache => {
                return cache.addAll(STATIC_ASSETS.map(asset => {
                    return new Request(asset, { cache: 'reload' });
                }));
            })
        ]).then(() => {
            console.log('[SW] Installation complete');
            return self.skipWaiting();
        }).catch(error => {
            console.error('[SW] Installation failed:', error);
        })
    );
});

/**
 * Activate event - clean old caches
 */
self.addEventListener('activate', event => {
    console.log('[SW] Activating...');
    
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (!cacheName.includes(CACHE_VERSION)) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('[SW] Activation complete');
            return self.clients.claim();
        })
    );
});

/**
 * Fetch event - handle requests with configured strategies
 */
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    // Only handle same-origin requests
    if (url.origin !== location.origin) {
        return;
    }

    // Skip non-PWA routes unless they're assets
    if (!isPWARoute(request.url) && !isStaticAsset(request.url)) {
        return;
    }

    // Handle different types of requests
    if (request.method === 'GET') {
        if (isStaticAsset(request.url)) {
            event.respondWith(handleStaticAsset(request));
        } else if (isApiRequest(request.url)) {
            event.respondWith(handleApiRequest(request));
        } else {
            event.respondWith(handlePageRequest(request));
        }
    } else if (request.method === 'POST') {
        event.respondWith(handlePostRequest(request));
    }
});

/**
 * Handle static assets with cache-first strategy
 */
async function handleStaticAsset(request) {
    const strategy = CACHE_STRATEGIES.assets || 'cache-first';
    
    try {
        const cache = await caches.open(ASSETS_CACHE);
        
        if (strategy === 'cache-first') {
            const cachedResponse = await cache.match(request);
            
            if (cachedResponse) {
                // Update cache in background
                fetch(request).then(response => {
                    if (response.ok) {
                        cache.put(request, response.clone());
                    }
                }).catch(() => {});
                
                return cachedResponse;
            }
        }

        const networkResponse = await fetch(request, {
            signal: AbortSignal.timeout(CONFIG.network_timeout || 10000)
        });
        
        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.error('[SW] Static asset fetch failed:', error);
        
        // Try cache as fallback
        const cache = await caches.open(ASSETS_CACHE);
        const cachedResponse = await cache.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        return new Response('Asset not available offline', { status: 503 });
    }
}

/**
 * Handle API requests with network-first strategy
 */
async function handleApiRequest(request) {
    const strategy = CACHE_STRATEGIES.api || 'network-first';
    
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), CONFIG.api_timeout || 10000);

        const networkResponse = await fetch(request, {
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);

        // Cache successful GET responses
        if (networkResponse.ok && request.method === 'GET') {
            const cache = await caches.open(API_CACHE);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.log('[SW] API request failed:', error);
        
        // Try cache for GET requests
        if (request.method === 'GET' && strategy === 'network-first') {
            const cache = await caches.open(API_CACHE);
            const cachedResponse = await cache.match(request);
            if (cachedResponse) {
                return cachedResponse;
            }
        }

        // Return appropriate error response
        if (error.name === 'AbortError') {
            return new Response(JSON.stringify({
                message: 'Request timeout. Please check your connection.',
                offline: true,
                code: 'TIMEOUT'
            }), {
                status: 408,
                headers: { 'Content-Type': 'application/json' }
            });
        }

        return new Response(JSON.stringify({
            message: 'Service temporarily unavailable',
            offline: true,
            code: 'OFFLINE'
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

/**
 * Handle page requests with network-first strategy
 */
async function handlePageRequest(request) {
    const strategy = CACHE_STRATEGIES.pages || 'network-first';
    
    try {
        const networkResponse = await fetch(request, {
            signal: AbortSignal.timeout(CONFIG.page_timeout || 15000)
        });
        
        // Cache successful page responses
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.log('[SW] Page request failed:', error);
        
        // Try cache first
        const cache = await caches.open(CACHE_NAME);
        const cachedResponse = await cache.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        // Return appropriate offline page
        return getOfflinePage(request.url);
    }
}

/**
 * Handle POST requests - always try network with CSRF error handling
 */
async function handlePostRequest(request) {
    try {
        const networkResponse = await fetch(request, {
            signal: AbortSignal.timeout(CONFIG.post_timeout || 30000)
        });
        
        // Handle CSRF token errors (419)
        if (networkResponse.status === 419) {
            return handleCSRFError(request, networkResponse);
        }
        
        // Handle session expired errors (401/403)
        if (networkResponse.status === 401 || networkResponse.status === 403) {
            return handleSessionError(request, networkResponse);
        }

        return networkResponse;
    } catch (error) {
        console.error('[SW] POST request failed:', error);
        
        if (error.name === 'AbortError') {
            return new Response(JSON.stringify({
                message: 'Request timeout. Please try again.',
                code: 'TIMEOUT',
                offline: true
            }), {
                status: 408,
                headers: { 'Content-Type': 'application/json' }
            });
        }

        return new Response(JSON.stringify({
            message: 'Unable to complete request. Please check your connection.',
            code: 'NETWORK_ERROR',
            offline: true
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

/**
 * Handle CSRF token errors - silent auto-refresh
 */
async function handleCSRFError(originalRequest, response) {
    const errorConfig = CONFIG.error_handling?.csrf_error || {};
    
    console.log('[SW] CSRF error detected, silently refreshing token...');
    
    // Auto-refresh enabled by default, no user notification
    if (errorConfig.auto_refresh !== false) {
        try {
            // Try to refresh CSRF token by fetching a page
            const tokenResponse = await fetch('/', { method: 'GET' });
            if (tokenResponse.ok) {
                // Extract new CSRF token from response
                const html = await tokenResponse.text();
                const tokenMatch = html.match(/name="csrf-token" content="([^"]+)"/);
                
                if (tokenMatch) {
                    const newToken = tokenMatch[1];
                    
                    // Notify client to update CSRF token in DOM
                    notifyClients({
                        type: 'csrf_token_updated',
                        token: newToken,
                        silent: true
                    });
                    
                    // Clone the original request with new token
                    const formData = await originalRequest.formData();
                    formData.set('_token', newToken);
                    
                    const retryRequest = new Request(originalRequest.url, {
                        method: originalRequest.method,
                        headers: originalRequest.headers,
                        body: formData
                    });
                    
                    // Retry the request with new token
                    const retryResponse = await fetch(retryRequest);
                    
                    if (retryResponse.ok) {
                        console.log('[SW] CSRF token refreshed and request retried successfully');
                        return retryResponse;
                    }
                }
            }
        } catch (error) {
            console.error('[SW] CSRF token refresh failed:', error);
        }
    }
    
    // Return original response if refresh failed
    return response;
}

/**
 * Handle session expired errors
 */
async function handleSessionError(originalRequest, response) {
    const errorConfig = CONFIG.error_handling?.session_expired || {};
    
    console.log('[SW] Session expired detected');
    
    // Notify client about session expiry
    if (errorConfig.show_notification) {
        notifyClients({
            type: 'session_expired',
            message: errorConfig.message || 'Your session has expired. Please log in again.',
            redirect_to_login: errorConfig.redirect_to_login,
            login_route: errorConfig.login_route || '/login'
        });
    }
    
    return response;
}

/**
 * Notify all clients about errors or events
 */
async function notifyClients(data) {
    try {
        const clients = await self.clients.matchAll({
            includeUncontrolled: true,
            type: 'window'
        });
        
        clients.forEach(client => {
            client.postMessage({
                type: 'af-pwa-notification',
                data: data
            });
        });
    } catch (error) {
        console.error('[SW] Failed to notify clients:', error);
    }
}

/**
 * Check if route matches pattern (supports wildcards)
 */
function routeMatches(url, pattern) {
    const pathname = new URL(url).pathname;
    
    if (pattern.includes('*')) {
        const basePattern = pattern.replace('*', '');
        return pathname.startsWith(basePattern);
    }
    
    return pathname === pattern || pathname.startsWith(pattern + '/');
}
async function handlePostRequest(request) {
    try {
        return await fetch(request, {
            signal: AbortSignal.timeout(CONFIG.post_timeout || 30000)
        });
    } catch (error) {
        return new Response(JSON.stringify({
            message: 'Cannot perform this action offline',
            offline: true,
            code: 'POST_OFFLINE'
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

/**
 * Get appropriate offline page based on route
 */
async function getOfflinePage(url) {
    const pathname = new URL(url).pathname;
    
    // Find the most specific offline page
    const offlinePages = CONFIG.offline_pages || {};
    
    for (const [pattern, page] of Object.entries(offlinePages)) {
        if (pattern.includes('*')) {
            const prefix = pattern.replace('*', '');
            if (pathname.startsWith(prefix)) {
                return caches.match(page);
            }
        } else if (pathname.startsWith(pattern)) {
            return caches.match(page);
        }
    }
    
    // Default offline page
    return caches.match(CONFIG.default_offline_page || '/offline.html');
}

/**
 * Handle service worker messages
 */
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CACHE_CLEAR') {
        event.waitUntil(clearAllCaches());
    }
});

/**
 * Clear all caches
 */
async function clearAllCaches() {
    const cacheNames = await caches.keys();
    await Promise.all(cacheNames.map(name => caches.delete(name)));
    console.log('[SW] All caches cleared');
}

/**
 * Background sync for offline actions
 */
self.addEventListener('sync', event => {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

async function doBackgroundSync() {
    console.log('[SW] Background sync triggered');
    // Custom background sync logic can be implemented here
}
