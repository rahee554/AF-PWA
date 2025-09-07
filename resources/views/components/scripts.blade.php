{{-- PWA Scripts Component --}}

{{-- Load AF-PWA Core JavaScript --}}
<script src="{{ asset('vendor/af-pwa/js/af-pwa.js') }}" defer></script>

{{-- Initialize PWA with Configuration --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // PWA Configuration
        window.AF_PWA_CONFIG = @json(app('af-pwa')->getFrontendConfig());
        
        // Initialize AF-PWA
        if (typeof window.AfPwa !== 'undefined') {
            window.afPwaInstance = new window.AfPwa(window.AF_PWA_CONFIG);
            window.afPwaInstance.init();
            
            // Expose to global scope for debugging
            if (window.AF_PWA_CONFIG.debug) {
                window.afPwa = window.afPwaInstance;
                console.log('[AF-PWA] Debug mode enabled. Instance available as window.afPwa');
            }
        } else {
            console.error('[AF-PWA] Core JavaScript not loaded');
        }
    });
</script>

{{-- Service Worker Registration --}}
<script>
    // Register service worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('{{ route('af-pwa.service-worker') }}', {
                scope: '{{ $options['scope'] ?? '/' }}'
            }).then(function(registration) {
                console.log('[AF-PWA] Service Worker registered successfully:', registration.scope);
                
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
                console.error('[AF-PWA] Service Worker registration failed:', error);
            });
        });
    } else {
        console.warn('[AF-PWA] Service Worker not supported');
    }
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
            
            console.log('[AF-PWA] Livewire integration enabled');
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

{{-- Install Prompt (if enabled) --}}
@if(isset($options['show_install_prompt']) && $options['show_install_prompt'])
<script>
    // Handle install prompt
    let deferredPrompt;
    
    window.addEventListener('beforeinstallprompt', function(e) {
        // Prevent Chrome 67 and earlier from automatically showing the prompt
        e.preventDefault();
        
        // Stash the event so it can be triggered later
        deferredPrompt = e;
        
        if (window.afPwaInstance) {
            window.afPwaInstance.showInstallPrompt(deferredPrompt);
        }
    });
    
    window.addEventListener('appinstalled', function(e) {
        console.log('[AF-PWA] App installed successfully');
        if (window.afPwaInstance) {
            window.afPwaInstance.handleAppInstalled();
        }
    });
</script>
@else
<script>
    // Install prompt disabled - let browser handle native installation
    console.log('[AF-PWA] Install prompt disabled - using native browser installation');
    
    window.addEventListener('beforeinstallprompt', function(e) {
        // Don't prevent the default - let browser show native prompt
        console.log('[AF-PWA] Native install prompt available');
    });
    
    window.addEventListener('appinstalled', function(e) {
        console.log('[AF-PWA] App installed successfully via native prompt');
        if (window.afPwaInstance) {
            window.afPwaInstance.handleAppInstalled();
        }
    });
</script>
@endif

{{-- Network Status Monitoring (if enabled) --}}
@if(isset($options['show_network_status']) && $options['show_network_status'])
<script>
    // Monitor network status
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
</script>
@endif

{{-- Custom PWA Events --}}
<script>
    // Custom PWA event handlers
    document.addEventListener('af-pwa:ready', function(e) {
        console.log('[AF-PWA] PWA initialized and ready');
        // Custom initialization code can go here
    });
    
    document.addEventListener('af-pwa:offline', function(e) {
        console.log('[AF-PWA] App went offline');
        // Handle offline state
    });
    
    document.addEventListener('af-pwa:online', function(e) {
        console.log('[AF-PWA] App came back online');
        // Handle online state
    });
    
    document.addEventListener('af-pwa:update-available', function(e) {
        console.log('[AF-PWA] App update available');
        // Handle update notification
    });
    
    document.addEventListener('af-pwa:installed', function(e) {
        console.log('[AF-PWA] App installed');
        // Handle app installation
    });
</script>
