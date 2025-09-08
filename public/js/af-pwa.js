/**
 * AF-PWA JavaScript Library
 * Handles PWA functionality, CSRF errors, session management, and user interactions
 */

class AfPwa {
    constructor(config = {}) {
        this.config = {
            debug: false,
            auto_refresh_csrf: true,
            show_notifications: true,
            install_prompt_delay: 5000,
            ...config
        };
        
        this.deferredPrompt = null;
        this.isOnline = navigator.onLine;
        this.retryQueue = [];
        
        this.init();
    }

    /**
     * Initialize PWA functionality
     */
    init() {
        this.log('AF-PWA initializing...');
        
        // Register service worker
        this.registerServiceWorker();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Setup install prompt handling
        this.setupInstallPrompt();
        
        // Setup network status monitoring
        this.setupNetworkMonitoring();
        
        // Setup CSRF token management
        this.setupCSRFManagement();
        
        // Dispatch ready event
        this.dispatchEvent('af-pwa:ready', { config: this.config });
    }

    /**
     * Register service worker
     */
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                this.log('Service Worker registered successfully:', registration);
                
                // Listen for service worker messages
                navigator.serviceWorker.addEventListener('message', (event) => {
                    this.handleServiceWorkerMessage(event);
                });
                
                // Check for updates
                this.checkForUpdates(registration);
                
            } catch (error) {
                this.log('Service Worker registration failed:', error);
            }
        }
    }

    /**
     * Handle messages from service worker
     */
    handleServiceWorkerMessage(event) {
        const { type, data } = event.data;
        
        if (type === 'af-pwa-notification') {
            this.handleNotification(data);
        }
    }

    /**
     * Handle notifications from service worker
     */
    handleNotification(data) {
        this.log('Received notification:', data);
        
        switch (data.type) {
            case 'csrf_token_updated':
                this.updateCSRFToken(data.token);
                break;
            case 'session_expired':
                this.handleSessionExpired(data);
                break;
            case 'network_error':
                this.handleNetworkError(data);
                break;
        }
    }

    /**
     * Update CSRF token silently
     */
    updateCSRFToken(newToken) {
        // Update meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', newToken);
        }
        
        // Update all forms
        document.querySelectorAll('input[name="_token"]').forEach(input => {
            input.value = newToken;
        });
        
        this.log('CSRF token updated silently');
        this.dispatchEvent('af-pwa:csrf-updated', { token: newToken });
    }

    /**
     * Handle session expired
     */
    handleSessionExpired(data) {
        if (this.config.show_notifications) {
            this.showNotification(data.message || 'Your session has expired. Please log in again.', 'error');
        }
        
        if (data.redirect_to_login) {
            setTimeout(() => {
                window.location.href = data.login_route || '/login';
            }, 3000);
        }
        
        this.dispatchEvent('af-pwa:session-expired', data);
    }

    /**
     * Handle network errors
     */
    handleNetworkError(data) {
        if (this.config.show_notifications) {
            this.showNotification(data.message || 'Connection lost. Please check your internet.', 'error');
        }
        
        this.dispatchEvent('af-pwa:network-error', data);
    }

    /**
     * Refresh CSRF token
     */
    async refreshCSRFToken() {
        try {
            const response = await fetch('/', { method: 'GET' });
            const html = await response.text();
            const tokenMatch = html.match(/name="csrf-token" content="([^"]+)"/);
            
            if (tokenMatch) {
                const newToken = tokenMatch[1];
                
                // Update meta tag
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    metaTag.setAttribute('content', newToken);
                }
                
                // Update all forms
                document.querySelectorAll('input[name="_token"]').forEach(input => {
                    input.value = newToken;
                });
                
                this.log('CSRF token refreshed successfully');
                this.dispatchEvent('af-pwa:csrf-refreshed', { token: newToken });
                
                return newToken;
            }
        } catch (error) {
            this.log('Failed to refresh CSRF token:', error);
        }
        
        return null;
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Listen for app install events
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallButton();
        });

        // Listen for app installed event
        window.addEventListener('appinstalled', () => {
            this.log('PWA was installed');
            this.hideInstallButton();
            this.dispatchEvent('af-pwa:installed');
        });
    }

    /**
     * Setup install prompt handling
     */
    setupInstallPrompt() {
        // Only setup install prompt if enabled in config
        if (!this.config.show_install_prompt) {
            this.log('Install prompt disabled in configuration');
            return;
        }
        
        // Handle beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent Chrome 67 and earlier from automatically showing the prompt
            e.preventDefault();
            // Stash the event so it can be triggered later
            this.deferredPrompt = e;
            this.log('Install prompt available');
            
            // Show install button after delay if configured
            setTimeout(() => {
                if (this.deferredPrompt) {
                    this.showInstallButton();
                }
            }, this.config.install_prompt_delay);
        });
        
        // Handle app installed event
        window.addEventListener('appinstalled', (e) => {
            this.log('App installed successfully');
            this.hideInstallButton();
            this.dispatchEvent('af-pwa:installed');
        });
    }

    /**
     * Setup network monitoring
     */
    setupNetworkMonitoring() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.log('Network: Online');
            this.updateNetworkStatus();
            this.processRetryQueue();
            this.dispatchEvent('af-pwa:online');
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.log('Network: Offline');
            this.updateNetworkStatus();
            this.dispatchEvent('af-pwa:offline');
        });

        // Initial network status
        this.updateNetworkStatus();
    }

    /**
     * Setup CSRF token management
     */
    setupCSRFManagement() {
        // Auto-refresh CSRF token periodically
        if (this.config.auto_refresh_csrf) {
            setInterval(() => {
                this.refreshCSRFToken();
            }, this.config.csrf_refresh_interval || 600000); // 10 minutes
        }
    }

    /**
     * Show install button
     */
    showInstallButton() {
        let button = document.getElementById('af-pwa-install-btn');
        
        if (!button) {
            button = document.createElement('button');
            button.id = 'af-pwa-install-btn';
            button.className = 'af-pwa-install-button';
            button.innerHTML = '📱 Install App';
            button.onclick = () => this.promptInstall();
            document.body.appendChild(button);
        }
        
        button.style.display = 'block';
        this.dispatchEvent('af-pwa:install-available');
    }

    /**
     * Hide install button
     */
    hideInstallButton() {
        const button = document.getElementById('af-pwa-install-btn');
        if (button) {
            button.style.display = 'none';
        }
    }

    /**
     * Prompt user to install PWA
     */
    async promptInstall() {
        if (!this.deferredPrompt) {
            this.log('Install prompt not available');
            return;
        }

        this.deferredPrompt.prompt();
        const { outcome } = await this.deferredPrompt.userChoice;
        
        this.log('Install prompt outcome:', outcome);
        
        if (outcome === 'accepted') {
            this.hideInstallButton();
            this.dispatchEvent('af-pwa:install-accepted');
        } else {
            this.dispatchEvent('af-pwa:install-dismissed');
        }
        
        this.deferredPrompt = null;
    }

    /**
     * Update network status indicator
     */
    updateNetworkStatus() {
        let indicator = document.getElementById('af-pwa-network-status');
        
        if (!indicator && this.config.show_network_status) {
            indicator = document.createElement('div');
            indicator.id = 'af-pwa-network-status';
            indicator.className = 'af-pwa-network-indicator';
            document.body.appendChild(indicator);
        }
        
        if (indicator) {
            indicator.className = `af-pwa-network-indicator ${this.isOnline ? 'online' : 'offline'}`;
            indicator.innerHTML = this.isOnline ? '🟢 Online' : '🔴 Offline';
            
            // Auto-hide when online
            if (this.isOnline) {
                setTimeout(() => {
                    indicator.style.opacity = '0';
                }, 3000);
            } else {
                indicator.style.opacity = '1';
            }
        }
    }

    /**
     * Process retry queue when back online
     */
    processRetryQueue() {
        this.retryQueue.forEach(item => {
            this.log('Retrying request:', item.url);
            fetch(item.url, item.options)
                .then(response => {
                    if (response.ok) {
                        this.log('Retry successful:', item.url);
                    }
                })
                .catch(error => {
                    this.log('Retry failed:', item.url, error);
                });
        });
        
        this.retryQueue = [];
    }

    /**
     * Add request to retry queue
     */
    addToRetryQueue(url, options = {}) {
        this.retryQueue.push({ url, options, timestamp: Date.now() });
    }

    /**
     * Show notification to user
     */
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `af-pwa-notification af-pwa-notification-${type}`;
        notification.innerHTML = message;
        
        document.body.appendChild(notification);
        
        // Auto-remove after duration
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, duration);
        
        return notification;
    }

    /**
     * Check for service worker updates - auto-update silently
     */
    checkForUpdates(registration) {
        if (registration.waiting) {
            this.applyUpdateSilently(registration);
            return;
        }

        registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            
            newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    this.applyUpdateSilently(registration);
                }
            });
        });
    }

    /**
     * Apply update silently without user notification
     */
    applyUpdateSilently(registration) {
        this.log('New version available, updating silently...');
        
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({ type: 'SKIP_WAITING' });
            
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                this.log('Service worker updated, reloading page...');
                // Small delay to ensure proper cleanup
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            });
        }
        
        this.dispatchEvent('af-pwa:update-applied-silently', { registration });
    }

    /**
     * Dispatch custom event
     */
    dispatchEvent(name, detail = {}) {
        const event = new CustomEvent(name, { detail });
        document.dispatchEvent(event);
        this.log('Event dispatched:', name, detail);
    }

    /**
     * Log messages in debug mode
     */
    log(...args) {
        if (this.config.debug) {
            console.log('[AF-PWA]', ...args);
        }
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Get config from meta tag or use defaults
    const configMeta = document.querySelector('meta[name="af-pwa-config"]');
    const config = configMeta ? JSON.parse(configMeta.content) : {};
    
    // Initialize AF-PWA
    window.afPwa = new AfPwa(config);
});

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AfPwa;
}
