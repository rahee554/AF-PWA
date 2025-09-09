{{-- PWA Scripts Component --}}

{{-- Load AF-PWA Core JavaScript --}}
<script src="{{ asset('vendor/artflow-studio/pwa/js/af-pwa.js') }}"></script>

{{-- Complete PWA Script (Consolidated) --}}
<script>
    // Global PWA Configuration and Environment
    window.AF_PWA_CONFIG = @json(app('af-pwa')->getFrontendConfig());
    const isLocalEnv = @json(config('app.env') === 'local');
    
    // Console logging helper (environment-based)
    function pwaLog(message, type = 'log') {
        if (isLocalEnv) {
            console[type]('[AF-PWA] ' + message);
        }
    }
    
    // PWA Initialization
    document.addEventListener('DOMContentLoaded', function() {
        // Function to initialize PWA
        function initializePWA() {
            if (typeof window.AfPwa !== 'undefined') {
                window.afPwaInstance = new window.AfPwa(window.AF_PWA_CONFIG);
                
                // Expose to global scope for debugging in local environment
                if (window.AF_PWA_CONFIG.debug && isLocalEnv) {
                    window.afPwa = window.afPwaInstance;
                    pwaLog('Debug mode enabled. Instance available as window.afPwa');
                }
                
                pwaLog('PWA initialized and ready');
                return true;
            }
            return false;
        }
        
        // Try to initialize immediately
        if (!initializePWA()) {
            // If not loaded yet, wait for the script to load
            setTimeout(() => {
                if (!initializePWA()) {
                    pwaLog('Core JavaScript failed to load', 'error');
                }
            }, 500);
        }
    });
    
    // Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('{{ route('af-pwa.service-worker') }}', {
                scope: '{{ $options['scope'] ?? '/' }}'
            }).then(function(registration) {
                pwaLog('Service Worker registered successfully: ' + registration.scope);
                
                // Check for updates
                registration.addEventListener('updatefound', function() {
                    const newWorker = registration.installing;
                    if (newWorker) {
                        newWorker.addEventListener('statechange', function() {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                // New update available
                                if (window.afPwaInstance) {
                                    window.afPwaInstance.handleServiceWorkerUpdate(registration);
                                }
                            }
                        });
                    }
                });
                
                // Handle service worker messages
                navigator.serviceWorker.addEventListener('message', function(event) {
                    if (window.afPwaInstance) {
                        window.afPwaInstance.handleServiceWorkerMessage(event);
                    }
                });
                
            }).catch(function(error) {
                pwaLog('Service Worker registration failed: ' + error, 'error');
            });
        });
    } else {
        pwaLog('Service Worker not supported', 'warn');
    }
    
    // Install Prompt Handler
    @if(isset($options['show_install_prompt']) && $options['show_install_prompt'])
        // Custom install prompt enabled
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            deferredPrompt = e;
            
            if (window.afPwaInstance) {
                window.afPwaInstance.showInstallPrompt(deferredPrompt);
            }
        });
        
        window.addEventListener('appinstalled', function(e) {
            pwaLog('App installed successfully');
            if (window.afPwaInstance) {
                window.afPwaInstance.handleAppInstalled();
            }
        });
    @else
        // Native browser install prompt
        pwaLog('Install prompt disabled - using native browser installation');
        
        window.addEventListener('beforeinstallprompt', function(e) {
            // Don't prevent the default - let browser show native prompt
            pwaLog('Native install prompt available');
        });
        
        window.addEventListener('appinstalled', function(e) {
            pwaLog('App installed successfully via native prompt');
            if (window.afPwaInstance) {
                window.afPwaInstance.handleAppInstalled();
            }
        });
    @endif
    
    // Network Status Monitoring
    @if(isset($options['show_network_status']) && $options['show_network_status'])
        window.addEventListener('online', function() {
            if (window.afPwaInstance) {
                window.afPwaInstance.handleNetworkOnline();
            }
        });
        
        window.addEventListener('offline', function() {
            if (window.afPwaInstance) {
                window.afPwaInstance.handleNetworkOffline();
            }
        });
    @endif
    
    // Custom PWA Events
    document.addEventListener('af-pwa:ready', function(e) {
        pwaLog('PWA initialized and ready');
    });
    
    document.addEventListener('af-pwa:offline', function(e) {
        pwaLog('App went offline');
    });
    
    document.addEventListener('af-pwa:online', function(e) {
        pwaLog('App came back online');
    });
    
    document.addEventListener('af-pwa:update-available', function(e) {
        pwaLog('App update available');
    });
    
    document.addEventListener('af-pwa:installed', function(e) {
        pwaLog('App installed');
    });
</script>

{{-- Livewire Integration (if enabled) --}}
@if(isset($options['enable_livewire']) && $options['enable_livewire'])
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for Livewire to be available
        if (typeof Livewire !== 'undefined') {
            // Hook into Livewire error handling
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (window.afPwaInstance) {
                        window.afPwaInstance.handleLivewireError(status);
                        
                        // Handle CSRF token mismatch
                        if (status === 419) {
                            preventDefault();
                            window.afPwaInstance.refreshCsrfToken().then(() => {
                                // Retry the request
                                Livewire.rescan();
                            });
                        }
                    }
                });
            });
            
            // Handle successful requests
            Livewire.hook('request', ({ succeed }) => {
                succeed(() => {
                    if (window.afPwaInstance) {
                        window.afPwaInstance.handleLivewireSuccess();
                    }
                });
            });
            
            if (@json(config('app.env') === 'local')) {
                console.log('[AF-PWA] Livewire integration enabled');
            }
        } else {
            // Wait for Livewire to load
            const checkLivewire = setInterval(() => {
                if (typeof Livewire !== 'undefined') {
                    clearInterval(checkLivewire);
                    // Re-run Livewire integration
                    const event = new CustomEvent('af-pwa:livewire-ready');
                    document.dispatchEvent(event);
                }
            }, 100);
        }
    });
</script>
@endif
