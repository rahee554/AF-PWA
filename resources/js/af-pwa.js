/**
 * AF-PWA Core JavaScript
 * Dynamic Progressive Web App functionality for Laravel
 * Version: 2.0
 */

class AfPwa {
    constructor(config = {}) {
        this.config = {
            app_name: 'Laravel',
            debug: false,
            autoRegister: true,
            scope: '/',
            updatePrompt: true,
            offlineMessage: true,
            sessionHandling: true,
            retryAttempts: 3,
            retryDelay: 1000,
            enable_notifications: true,
            enable_background_sync: true,
            update_check_interval: 3600000, // 1 hour
            show_install_prompt: false,
            show_network_status: true,
            auto_refresh_on_update: true,
            ...config
        };
        
        this.serviceWorker = null;
        this.isOnline = navigator.onLine;
        this.sessionTimeoutShown = false;
        this.installPrompt = null;
        this.isInitialized = false;
        
        // Initialize immediately
        this.init();
    }

    /**
     * Initialize PWA functionality
     */
    async init() {
        if (this.isInitialized) return;
        
        this.log('Initializing AF-PWA v2.0...');
        
        try {
            // Register service worker first
            if ('serviceWorker' in navigator) {
                await this.registerServiceWorker();
            }
            
            // Initialize session handling
            if (this.config.sessionHandling) {
                this.initSessionHandling();
            }
            
            // Initialize network handling
            this.initNetworkHandling();
            
            // Initialize install prompt handling
            this.initInstallPrompt();
            
            // Initialize notifications
            if (this.config.enable_notifications) {
                this.initNotifications();
            }
            
            // Setup error handlers
            this.setupErrorHandlers();
            
            this.isInitialized = true;
            this.log('AF-PWA initialized successfully');
            
            // Dispatch ready event
            this.dispatchEvent('af-pwa:ready', { config: this.config });
            
        } catch (error) {
            this.error('AF-PWA initialization failed:', error);
        }
    }

    /**
     * Register service worker with enhanced error handling
     */
    async registerServiceWorker() {
        try {
            const registration = await navigator.serviceWorker.register('/sw.js', {
                scope: this.config.scope,
                updateViaCache: 'none'
            });
            
            this.serviceWorker = registration;
            this.log('Service Worker registered:', registration.scope);
            
            // Handle updates
            registration.addEventListener('updatefound', () => {
                this.handleServiceWorkerUpdate(registration);
            });
            
            // Listen for controller change (new SW took control)
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                this.log('Service Worker controller changed');
                if (this.config.auto_refresh_on_update) {
                    window.location.reload();
                }
            });
            
            // Listen for messages from service worker
            navigator.serviceWorker.addEventListener('message', (event) => {
                this.handleServiceWorkerMessage(event);
            });
            
            // Check for existing service worker
            if (registration.active) {
                this.log('Service Worker is active');
            }
            
            // Check for updates periodically
            if (this.config.update_check_interval > 0) {
                setInterval(() => {
                    registration.update();
                }, this.config.update_check_interval);
            }
            
        } catch (error) {
            this.error('Service Worker registration failed:', error);
            throw error;
        }
    }

    /**
     * Handle service worker updates
     */
    handleServiceWorkerUpdate(registration) {
        const newWorker = registration.installing;
        if (!newWorker) return;
        
        this.log('Service Worker update found');
        
        newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                this.log('New Service Worker installed');
                
                if (this.config.updatePrompt) {
                    this.showUpdatePrompt(newWorker);
                } else {
                    // Auto-update
                    newWorker.postMessage({ type: 'SKIP_WAITING' });
                }
                
                this.dispatchEvent('af-pwa:update-available', { newWorker });
            }
        });
    }

    /**
     * Handle messages from service worker
     */
    handleServiceWorkerMessage(event) {
        const { data } = event;
        
        if (data && data.type === 'af-pwa-notification') {
            this.handleNotification(data.data);
        }
    }

    /**
     * Handle notifications from service worker
     */
    handleNotification(data) {
        switch (data.type) {
            case 'csrf_error':
                if (data.auto_refresh) {
                    this.showMessage('Session refreshing...', 'info', 2000);
                    setTimeout(() => {
                        if (data.message) {
                            this.showMessage(data.message, 'success', 3000);
                        }
                    }, 2000);
                }
                break;
                
            case 'session_expired':
                if (data.redirect_to_login) {
                    this.showMessage(data.message || 'Session expired', 'warning', 5000);
                    setTimeout(() => {
                        window.location.href = data.login_route || '/login';
                    }, 3000);
                } else {
                    this.showMessage(data.message || 'Session expired', 'warning');
                }
                break;
                
            case 'network_error':
                this.showMessage(data.message || 'Network error occurred', 'error');
                break;
        }
    }

    /**
     * Show update prompt to user
     */
    showUpdatePrompt(newWorker) {
        const message = 'A new version is available! Update now?';
        
        if (this.config.enable_notifications && 'Notification' in window) {
            // Try to show browser notification
            if (Notification.permission === 'granted') {
                const notification = new Notification('App Update Available', {
                    body: message,
                    icon: '/favicon-192x192.png',
                    tag: 'app-update'
                });
                
                notification.onclick = () => {
                    newWorker.postMessage({ type: 'SKIP_WAITING' });
                    notification.close();
                };
                
                return;
            }
        }
        
        // Fallback to confirm dialog
        if (confirm(message)) {
            newWorker.postMessage({ type: 'SKIP_WAITING' });
        }
    }

    /**
     * Initialize comprehensive session handling for Laravel/Livewire
     */
    initSessionHandling() {
        // Enhanced CSRF token handling
        this.initCSRFHandling();
        
        // Livewire integration
        if (window.Livewire) {
            this.initLivewireHandling();
        } else {
            // Wait for Livewire to load
            this.waitForLivewire();
        }
        
        // General auth error handling
        this.initAuthErrorHandling();
        
        this.log('Session handling initialized');
    }

    /**
     * Wait for Livewire to be available
     */
    waitForLivewire() {
        const checkLivewire = () => {
            if (window.Livewire) {
                this.initLivewireHandling();
                this.log('Livewire integration activated');
            } else {
                setTimeout(checkLivewire, 100);
            }
        };
        
        setTimeout(checkLivewire, 100);
    }

    /**
     * Enhanced CSRF token handling
     */
    initCSRFHandling() {
        // Store original fetch for restoration if needed
        if (!window._afpwa_original_fetch) {
            window._afpwa_original_fetch = window.fetch;
        }
        
        // Intercept fetch requests
        window.fetch = async (...args) => {
            let [url, options = {}] = args;
            
            try {
                // Add CSRF token to POST requests if needed
                if (options.method === 'POST' || options.method === 'PUT' || options.method === 'DELETE') {
                    options = this.addCSRFToken(options);
                }
                
                const response = await window._afpwa_original_fetch(url, options);
                
                // Handle CSRF errors
                if (response.status === 419) {
                    this.log('CSRF error detected, refreshing token...');
                    const newToken = await this.refreshCSRFToken();
                    
                    if (newToken) {
                        // Retry with new token
                        options = this.addCSRFToken(options, newToken);
                        return window._afpwa_original_fetch(url, options);
                    }
                }
                
                return response;
            } catch (error) {
                this.handleNetworkError(error);
                throw error;
            }
        };
    }

    /**
     * Add CSRF token to request options
     */
    addCSRFToken(options, token = null) {
        const csrfToken = token || this.getCSRFToken();
        if (!csrfToken) return options;
        
        const newOptions = { ...options };
        
        // Add to headers
        newOptions.headers = {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            ...newOptions.headers
        };
        
        // Add to FormData if body is FormData
        if (newOptions.body instanceof FormData) {
            newOptions.body.set('_token', csrfToken);
        }
        
        return newOptions;
    }

    /**
     * Get current CSRF token
     */
    getCSRFToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : null;
    }

    /**
     * Enhanced Livewire error handling
     */
    initLivewireHandling() {
        if (!window.Livewire) return;
        
        // Hook into Livewire's error handling
        if (window.Livewire.hook) {
            window.Livewire.hook('request', ({ fail }) => {
                fail(({ status, content, preventDefault }) => {
                    this.log('Livewire request failed:', status);
                    
                    if (status === 419) {
                        // CSRF error
                        preventDefault();
                        this.handleLivewireCSRFError();
                    } else if (status === 401 || status === 403) {
                        // Auth error
                        this.handleSessionTimeout();
                    } else if (status === 0 || !this.isOnline) {
                        // Network error
                        this.handleNetworkError({ status });
                    }
                });
            });
            
            // Hook into successful requests
            window.Livewire.hook('request', ({ succeed }) => {
                succeed(() => {
                    this.handleLivewireSuccess();
                });
            });
        }
        
        // Listen for Livewire events
        document.addEventListener('livewire:error', (event) => {
            const error = event.detail;
            this.log('Livewire error event:', error);
            
            if (error.status === 419) {
                this.handleLivewireCSRFError();
            }
        });
    }

    /**
     * Handle Livewire CSRF errors
     */
    async handleLivewireCSRFError() {
        try {
            const newToken = await this.refreshCSRFToken();
            if (newToken) {
                this.showMessage('Session refreshed successfully', 'success', 2000);
                // Livewire will automatically retry
            } else {
                this.showMessage('Please refresh the page', 'warning');
            }
        } catch (error) {
            this.error('Failed to refresh CSRF token:', error);
            this.showMessage('Please refresh the page', 'error');
        }
    }

    /**
     * Handle successful Livewire requests
     */
    handleLivewireSuccess() {
        // Reset session timeout flag
        this.sessionTimeoutShown = false;
    }

    /**
     * Initialize comprehensive auth error handling
     */
    initAuthErrorHandling() {
        // Monitor XMLHttpRequest for auth errors
        const originalXHROpen = XMLHttpRequest.prototype.open;
        const originalXHRSend = XMLHttpRequest.prototype.send;
        
        XMLHttpRequest.prototype.open = function(...args) {
            this._afpwa_method = args[0];
            this._afpwa_url = args[1];
            return originalXHROpen.apply(this, args);
        };
        
        XMLHttpRequest.prototype.send = function(...args) {
            this.addEventListener('load', () => {
                if (this.status === 419) {
                    window.afPwaInstance?.refreshCSRFToken();
                } else if (this.status === 401 || this.status === 403) {
                    window.afPwaInstance?.handleSessionTimeout();
                }
            });
            
            return originalXHRSend.apply(this, args);
        };
    }

    /**
     * Enhanced CSRF token refresh
     */
    async refreshCSRFToken() {
        try {
            const response = await window._afpwa_original_fetch(window.location.pathname, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const text = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(text, 'text/html');
            const newToken = doc.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            if (newToken) {
                this.updateCSRFToken(newToken);
                this.log('CSRF token refreshed successfully');
                return newToken;
            } else {
                throw new Error('No CSRF token found in response');
            }
        } catch (error) {
            this.error('Failed to refresh CSRF token:', error);
            return null;
        }
    }

    /**
     * Update CSRF token in DOM
     */
    updateCSRFToken(newToken) {
        // Update meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', newToken);
        }
        
        // Update all form tokens
        document.querySelectorAll('input[name="_token"]').forEach(input => {
            input.value = newToken;
        });
        
        // Update Livewire token if available
        if (window.Livewire && window.Livewire.csrf) {
            window.Livewire.csrf = newToken;
        }
    }

    /**
     * Handle session timeout with better UX
     */
    handleSessionTimeout() {
        if (this.sessionTimeoutShown) return;
        
        this.sessionTimeoutShown = true;
        
        const message = 'Your session has expired. Please login again.';
        
        this.showMessage(message, 'warning', 0); // Persistent message
        
        // Auto-redirect after 5 seconds
        setTimeout(() => {
            window.location.href = '/login';
        }, 5000);
    }

    /**
     * Enhanced network status handling
     */
    initNetworkHandling() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.hideOfflineMessage();
            this.showMessage('🟢 Back online', 'success', 3000);
            this.dispatchEvent('af-pwa:online');
            this.log('Network: Online');
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            if (this.config.show_network_status) {
                this.showOfflineMessage();
            }
            this.dispatchEvent('af-pwa:offline');
            this.log('Network: Offline');
        });
    }

    /**
     * Enhanced install prompt handling
     */
    initInstallPrompt() {
        window.addEventListener('beforeinstallprompt', (e) => {
            if (this.config.show_install_prompt) {
                e.preventDefault();
                this.installPrompt = e;
                this.dispatchEvent('af-pwa:installprompt', { prompt: e });
            } else {
                // Let browser handle native prompt
                this.log('Native install prompt available');
            }
        });

        window.addEventListener('appinstalled', (e) => {
            this.log('App installed successfully');
            this.installPrompt = null;
            this.dispatchEvent('af-pwa:installed');
            this.handleAppInstalled();
        });
    }

    /**
     * Handle app installation
     */
    handleAppInstalled() {
        this.showMessage('🎉 App installed successfully!', 'success', 5000);
        
        // Clear install prompt
        this.installPrompt = null;
        
        // Optional: Clear caches to ensure fresh start
        if (this.config.clear_cache_on_install) {
            this.clearCaches();
        }
    }

    /**
     * Trigger install prompt
     */
    async triggerInstall() {
        if (!this.installPrompt) {
            this.showMessage('Install prompt not available', 'warning');
            return false;
        }
        
        try {
            this.installPrompt.prompt();
            const { outcome } = await this.installPrompt.userChoice;
            this.log('Install prompt outcome:', outcome);
            
            if (outcome === 'accepted') {
                this.installPrompt = null;
                return true;
            }
            
            return false;
        } catch (error) {
            this.error('Install prompt failed:', error);
            return false;
        }
    }

    /**
     * Initialize notifications
     */
    async initNotifications() {
        if (!('Notification' in window)) {
            this.log('Notifications not supported');
            return;
        }
        
        if (Notification.permission === 'default') {
            try {
                const permission = await Notification.requestPermission();
                this.log('Notification permission:', permission);
            } catch (error) {
                this.log('Notification permission request failed:', error);
            }
        }
    }

    /**
     * Setup global error handlers
     */
    setupErrorHandlers() {
        // Handle unhandled promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            this.log('Unhandled promise rejection:', event.reason);
            
            // Handle network-related promise rejections
            if (event.reason && event.reason.name === 'TypeError' && 
                event.reason.message.includes('fetch')) {
                this.handleNetworkError(event.reason);
            }
        });
        
        // Handle general JavaScript errors
        window.addEventListener('error', (event) => {
            this.log('JavaScript error:', event.error);
        });
    }

    /**
     * Handle network errors
     */
    handleNetworkError(error) {
        if (!this.isOnline && this.config.show_network_status) {
            this.showOfflineMessage();
        }
        
        this.dispatchEvent('af-pwa:network-error', { error });
    }

    /**
     * Show persistent offline message
     */
    showOfflineMessage() {
        this.removeMessage('afpwa-offline-message');
        
        const message = this.createMessage({
            id: 'afpwa-offline-message',
            content: `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="font-size: 20px;">⚠️</div>
                    <div>
                        <strong>You're offline</strong>
                        <div style="font-size: 13px; margin-top: 2px; opacity: 0.8;">
                            Some features may not work until you reconnect
                        </div>
                    </div>
                </div>
            `,
            type: 'warning',
            closable: true,
            persistent: true
        });
        
        document.body.appendChild(message);
    }

    /**
     * Hide offline message
     */
    hideOfflineMessage() {
        this.removeMessage('afpwa-offline-message');
    }

    /**
     * Show status message with enhanced styling
     */
    showMessage(content, type = 'info', duration = 5000) {
        // Remove existing status messages
        this.removeMessage('afpwa-status-message');
        
        const message = this.createMessage({
            id: 'afpwa-status-message',
            content,
            type,
            duration,
            closable: duration === 0
        });
        
        document.body.appendChild(message);
        
        if (duration > 0) {
            setTimeout(() => {
                this.removeMessage('afpwa-status-message');
            }, duration);
        }
    }

    /**
     * Create enhanced message element
     */
    createMessage({ id, content, type = 'info', closable = false, duration = 0, persistent = false }) {
        const colors = {
            success: { bg: '#d4edda', color: '#155724', border: '#c3e6cb', icon: '✅' },
            warning: { bg: '#fff3cd', color: '#856404', border: '#ffeaa7', icon: '⚠️' },
            error: { bg: '#f8d7da', color: '#721c24', border: '#f5c6cb', icon: '❌' },
            info: { bg: '#d1ecf1', color: '#0c5460', border: '#bee5eb', icon: 'ℹ️' }
        };

        const style = colors[type] || colors.info;
        
        const messageEl = document.createElement('div');
        messageEl.id = id;
        messageEl.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${style.bg};
            color: ${style.color};
            border: 1px solid ${style.border};
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            max-width: 350px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            padding: 15px 20px;
            transform: translateX(400px);
            transition: transform 0.3s ease-out;
        `;
        
        messageEl.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: 10px;">
                <div style="font-size: 16px; margin-top: 2px;">${style.icon}</div>
                <div style="flex: 1;">
                    ${content}
                </div>
                ${closable ? `
                    <button onclick="window.afPwaInstance.removeMessage('${id}')" 
                            style="background: none; border: none; font-size: 18px; cursor: pointer; 
                                   color: ${style.color}; padding: 0; margin-left: 10px; line-height: 1;">&times;</button>
                ` : ''}
            </div>
        `;
        
        // Animate in
        setTimeout(() => {
            messageEl.style.transform = 'translateX(0)';
        }, 10);
        
        return messageEl;
    }

    /**
     * Remove message by ID with animation
     */
    removeMessage(id) {
        const element = document.getElementById(id);
        if (element) {
            element.style.transform = 'translateX(400px)';
            setTimeout(() => {
                if (element.parentNode) {
                    element.remove();
                }
            }, 300);
        }
    }

    /**
     * Dispatch custom events
     */
    dispatchEvent(eventName, detail = {}) {
        const event = new CustomEvent(eventName, { 
            detail: { 
                ...detail, 
                timestamp: Date.now(),
                instance: this
            } 
        });
        document.dispatchEvent(event);
        window.dispatchEvent(event);
    }

    /**
     * Get PWA installation status
     */
    isInstalled() {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true ||
               document.referrer.includes('android-app://');
    }

    /**
     * Get comprehensive network status
     */
    getNetworkStatus() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        
        return {
            online: this.isOnline,
            effectiveType: connection?.effectiveType || 'unknown',
            downlink: connection?.downlink || 0,
            rtt: connection?.rtt || 0,
            saveData: connection?.saveData || false
        };
    }

    /**
     * Clear all caches
     */
    async clearCaches() {
        if ('caches' in window) {
            try {
                const cacheNames = await caches.keys();
                await Promise.all(cacheNames.map(name => caches.delete(name)));
                this.log('All caches cleared');
                this.showMessage('Cache cleared successfully', 'success');
            } catch (error) {
                this.error('Failed to clear caches:', error);
                this.showMessage('Failed to clear cache', 'error');
            }
        }
    }

    /**
     * Update service worker
     */
    async updateServiceWorker() {
        if (this.serviceWorker) {
            try {
                await this.serviceWorker.update();
                this.log('Service worker update triggered');
                this.showMessage('Checking for updates...', 'info', 3000);
            } catch (error) {
                this.error('Service worker update failed:', error);
            }
        }
    }

    /**
     * Get PWA information
     */
    getInfo() {
        return {
            version: '2.0',
            config: this.config,
            isInstalled: this.isInstalled(),
            isOnline: this.isOnline,
            networkStatus: this.getNetworkStatus(),
            serviceWorker: {
                registered: !!this.serviceWorker,
                scope: this.serviceWorker?.scope,
                active: !!this.serviceWorker?.active
            },
            installPrompt: !!this.installPrompt,
            isInitialized: this.isInitialized
        };
    }

    /**
     * Enhanced logging
     */
    log(...args) {
        if (this.config.debug) {
            console.log('%c[AF-PWA]', 'color: #007bff; font-weight: bold;', ...args);
        }
    }

    /**
     * Error logging (always enabled)
     */
    error(...args) {
        console.error('%c[AF-PWA]', 'color: #dc3545; font-weight: bold;', ...args);
    }
}

// Global exposure
if (typeof window !== 'undefined') {
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.AfPwa = AfPwa;
        });
    } else {
        window.AfPwa = AfPwa;
    }
}
