<script>
// AF-PWA Session Manager
(function() {
    'use strict';
    
    const config = @json($options);
    const debug = {{ $debug ? 'true' : 'false' }};
    let sessionData = {
        csrf: '{{ $csrfToken }}',
        lifetime: {{ $sessionLifetime }},
        lastActivity: Date.now(),
        warningShown: false,
        refreshTimer: null,
        timeoutTimer: null
    };

    // Session management functions
    const SessionManager = {
        init() {
            if (debug) console.log('AF-PWA: Initializing session manager');
            
            this.updateActivity();
            this.startTimers();
            this.bindEvents();
            
            // Listen for Livewire events
            if (typeof Livewire !== 'undefined') {
                this.bindLivewireEvents();
            }
        },

        updateActivity() {
            sessionData.lastActivity = Date.now();
            sessionData.warningShown = false;
            
            // Send activity update to server
            if (config.track_activity !== false) {
                this.sendActivityUpdate();
            }
        },

        startTimers() {
            this.clearTimers();
            
            const warningTime = (config.timeout_warning || 300) * 1000; // Convert to ms
            const sessionLifetime = sessionData.lifetime * 60 * 1000; // Convert to ms
            
            // Set warning timer
            this.timeoutTimer = setTimeout(() => {
                this.showTimeoutWarning();
            }, sessionLifetime - warningTime);
            
            // Set auto-refresh timer if enabled
            if (config.auto_refresh !== false) {
                const refreshInterval = (config.refresh_interval || 1800) * 1000;
                this.refreshTimer = setTimeout(() => {
                    this.refreshSession();
                }, refreshInterval);
            }
        },

        clearTimers() {
            if (this.timeoutTimer) {
                clearTimeout(this.timeoutTimer);
                this.timeoutTimer = null;
            }
            if (this.refreshTimer) {
                clearTimeout(this.refreshTimer);
                this.refreshTimer = null;
            }
        },

        bindEvents() {
            // Track user activity
            const events = ['click', 'keypress', 'scroll', 'mousemove', 'touchstart'];
            const throttledUpdate = this.throttle(() => this.updateActivity(), 30000); // Max once per 30 seconds
            
            events.forEach(event => {
                document.addEventListener(event, throttledUpdate, { passive: true });
            });

            // Handle page visibility changes
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.checkSessionStatus();
                }
            });

            // Handle online/offline events
            window.addEventListener('online', () => {
                this.checkSessionStatus();
            });
        },

        bindLivewireEvents() {
            // Update session data for Livewire requests
            document.addEventListener('livewire:init', () => {
                if (debug) console.log('AF-PWA: Binding Livewire session events');
                
                // Intercept Livewire requests to include fresh session data
                Livewire.hook('request', ({ options, payload, respond, succeed, fail }) => {
                    // Update CSRF token if refreshed
                    if (sessionData.csrf) {
                        payload.fingerprint.csrf = sessionData.csrf;
                    }
                });

                // Handle Livewire errors that might indicate session issues
                Livewire.hook('request.exception', ({ status, content, preventDefault }) => {
                    if (status === 419 || status === 401) { // CSRF or auth errors
                        preventDefault();
                        this.handleSessionExpired();
                    }
                });
            });
        },

        async refreshSession() {
            if (debug) console.log('AF-PWA: Refreshing session');
            
            try {
                const response = await fetch('/af-pwa/session/refresh', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': sessionData.csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                const data = await response.json();
                
                if (data.success) {
                    sessionData.csrf = data.csrf_token;
                    sessionData.lastActivity = Date.now();
                    
                    // Update CSRF token in meta tag
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (csrfMeta) {
                        csrfMeta.setAttribute('content', data.csrf_token);
                    }
                    
                    // Update Livewire if available
                    if (typeof Livewire !== 'undefined' && Livewire.directive) {
                        Livewire.directive('csrf', data.csrf_token);
                    }
                    
                    this.startTimers(); // Restart timers
                    
                    if (debug) console.log('AF-PWA: Session refreshed successfully');
                    
                    // Dispatch custom event
                    window.dispatchEvent(new CustomEvent('pwa-session-refreshed', {
                        detail: { csrf: data.csrf_token }
                    }));
                    
                } else {
                    this.handleSessionExpired(data.redirect);
                }
                
            } catch (error) {
                if (debug) console.error('AF-PWA: Session refresh failed:', error);
                this.handleRefreshError();
            }
        },

        async sendActivityUpdate() {
            try {
                await fetch('/af-pwa/session/activity', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': sessionData.csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        timestamp: Date.now()
                    }),
                    credentials: 'same-origin'
                });
            } catch (error) {
                if (debug) console.error('AF-PWA: Activity update failed:', error);
            }
        },

        async checkSessionStatus() {
            try {
                const response = await fetch('/af-pwa/session/status', {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': sessionData.csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                const data = await response.json();
                
                if (data.expired) {
                    this.handleSessionExpired(data.redirect);
                } else if (data.warning) {
                    this.showTimeoutWarning();
                }
                
            } catch (error) {
                if (debug) console.error('AF-PWA: Session status check failed:', error);
            }
        },

        showTimeoutWarning() {
            if (sessionData.warningShown) return;
            
            sessionData.warningShown = true;
            
            const warning = document.createElement('div');
            warning.className = 'pwa-session-warning';
            warning.innerHTML = `
                <div class="pwa-session-warning-content">
                    <h4>Session Expiring Soon</h4>
                    <p>Your session will expire in a few minutes. Would you like to extend it?</p>
                    <div class="pwa-session-warning-actions">
                        <button class="pwa-extend-session">Extend Session</button>
                        <button class="pwa-logout">Logout</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(warning);
            
            // Handle extend session
            warning.querySelector('.pwa-extend-session').addEventListener('click', () => {
                this.refreshSession();
                warning.remove();
            });
            
            // Handle logout
            warning.querySelector('.pwa-logout').addEventListener('click', () => {
                this.logout();
                warning.remove();
            });
            
            // Auto-remove after 60 seconds
            setTimeout(() => {
                if (warning.parentNode) {
                    warning.remove();
                    this.handleSessionExpired();
                }
            }, 60000);
        },

        handleSessionExpired(redirectUrl = null) {
            this.clearTimers();
            
            // Dispatch event
            window.dispatchEvent(new CustomEvent('pwa-session-expired'));
            
            // Show expiration message
            const message = document.createElement('div');
            message.className = 'pwa-session-expired';
            message.innerHTML = `
                <div class="pwa-session-expired-content">
                    <h4>Session Expired</h4>
                    <p>Your session has expired. Please log in again.</p>
                    <button class="pwa-relogin">Login Again</button>
                </div>
            `;
            
            document.body.appendChild(message);
            
            // Handle relogin
            message.querySelector('.pwa-relogin').addEventListener('click', () => {
                const loginUrl = redirectUrl || config.login_url || '/login';
                window.location.href = loginUrl;
            });
            
            // Auto-redirect after 5 seconds
            setTimeout(() => {
                const loginUrl = redirectUrl || config.login_url || '/login';
                window.location.href = loginUrl;
            }, 5000);
        },

        handleRefreshError() {
            // Show offline-like message
            const error = document.createElement('div');
            error.className = 'pwa-session-error';
            error.innerHTML = `
                <div class="pwa-session-error-content">
                    <h4>Connection Issue</h4>
                    <p>Unable to refresh your session. Please check your connection.</p>
                    <button class="pwa-retry">Retry</button>
                </div>
            `;
            
            document.body.appendChild(error);
            
            // Handle retry
            error.querySelector('.pwa-retry').addEventListener('click', () => {
                this.refreshSession();
                error.remove();
            });
            
            // Auto-remove after 10 seconds
            setTimeout(() => {
                if (error.parentNode) {
                    error.remove();
                }
            }, 10000);
        },

        async logout() {
            try {
                await fetch('/af-pwa/session/logout', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': sessionData.csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                window.location.href = config.login_url || '/login';
                
            } catch (error) {
                if (debug) console.error('AF-PWA: Logout failed:', error);
                window.location.href = config.login_url || '/login';
            }
        },

        throttle(func, delay) {
            let timeoutId;
            let lastExecTime = 0;
            
            return function (...args) {
                const currentTime = Date.now();
                
                if (currentTime - lastExecTime > delay) {
                    func.apply(this, args);
                    lastExecTime = currentTime;
                } else {
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(() => {
                        func.apply(this, args);
                        lastExecTime = Date.now();
                    }, delay - (currentTime - lastExecTime));
                }
            };
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => SessionManager.init());
    } else {
        SessionManager.init();
    }

    // Export to global scope
    window.AFPWASession = SessionManager;

})();
</script>

@if(config('af-pwa.ui.styles.inject_css', true))
<style>
/* AF-PWA Session Manager Styles */
.pwa-session-warning,
.pwa-session-expired,
.pwa-session-error {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    animation: fadeIn 0.3s ease;
}

.pwa-session-warning-content,
.pwa-session-expired-content,
.pwa-session-error-content {
    background: white;
    border-radius: 12px;
    padding: 24px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.pwa-session-warning-content h4,
.pwa-session-expired-content h4,
.pwa-session-error-content h4 {
    margin: 0 0 12px 0;
    color: var(--pwa-text-color, #333);
    font-size: 18px;
    font-weight: 600;
}

.pwa-session-warning-content p,
.pwa-session-expired-content p,
.pwa-session-error-content p {
    margin: 0 0 20px 0;
    color: var(--pwa-text-secondary, #666);
    line-height: 1.5;
}

.pwa-session-warning-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.pwa-extend-session,
.pwa-logout,
.pwa-relogin,
.pwa-retry {
    padding: 10px 20px;
    border-radius: 6px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.pwa-extend-session,
.pwa-relogin,
.pwa-retry {
    background: var(--pwa-primary-color, #007bff);
    color: white;
}

.pwa-extend-session:hover,
.pwa-relogin:hover,
.pwa-retry:hover {
    background: var(--pwa-primary-hover, #0056b3);
}

.pwa-logout {
    background: var(--pwa-secondary-color, #6c757d);
    color: white;
}

.pwa-logout:hover {
    background: var(--pwa-secondary-hover, #545b62);
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
</style>
@endif
