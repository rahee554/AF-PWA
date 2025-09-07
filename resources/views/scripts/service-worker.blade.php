<script>
// AF-PWA Service Worker Registration
(function() {
    'use strict';
    
    const config = @json($options);
    const debug = {{ $debug ? 'true' : 'false' }};
    const swPath = '{{ $swPath }}';
    
    // Check for service worker support
    if (!('serviceWorker' in navigator)) {
        if (debug) console.warn('AF-PWA: Service Worker not supported');
        return;
    }

    // PWA Installation
    let deferredPrompt;
    let isInstalled = false;

    // Check if app is already installed
    if (window.matchMedia('(display-mode: standalone)').matches || 
        window.navigator.standalone === true) {
        isInstalled = true;
        document.documentElement.classList.add('pwa-installed');
    }

    // Listen for beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', (e) => {
        if (debug) console.log('AF-PWA: Install prompt available');
        
        // Prevent Chrome 67 and earlier from automatically showing the prompt
        e.preventDefault();
        
        // Stash the event so it can be triggered later
        deferredPrompt = e;
        
        // Show install button if configured
        showInstallButton();
        
        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('pwa-install-available', {
            detail: { prompt: deferredPrompt }
        }));
    });

    // Listen for app installed event
    window.addEventListener('appinstalled', (e) => {
        if (debug) console.log('AF-PWA: App installed successfully');
        
        isInstalled = true;
        deferredPrompt = null;
        
        document.documentElement.classList.add('pwa-installed');
        hideInstallButton();
        
        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('pwa-installed'));
        
        // Track installation if analytics enabled
        if (config.analytics && typeof gtag !== 'undefined') {
            gtag('event', 'pwa_install', {
                event_category: 'PWA',
                event_label: 'App Installed'
            });
        }
    });

    // Register service worker
    navigator.serviceWorker.register(swPath, {
        scope: config.scope || '/'
    })
    .then(function(registration) {
        if (debug) console.log('AF-PWA: Service Worker registered successfully');
        
        // Handle updates
        registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            
            newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    // New content is available, show update notification
                    showUpdateNotification();
                }
            });
        });
        
        // Check for updates periodically
        if (config.checkForUpdates !== false) {
            setInterval(() => {
                registration.update();
            }, config.updateInterval || 60000); // Default 1 minute
        }
    })
    .catch(function(error) {
        if (debug) console.error('AF-PWA: Service Worker registration failed:', error);
    });

    // PWA Install Functions
    function showInstallButton() {
        const installBtn = document.querySelector('.pwa-install-btn');
        if (installBtn) {
            installBtn.style.display = 'block';
            installBtn.addEventListener('click', installApp);
        }
        
        // Dispatch event for custom install UI
        window.dispatchEvent(new CustomEvent('pwa-show-install'));
    }

    function hideInstallButton() {
        const installBtn = document.querySelector('.pwa-install-btn');
        if (installBtn) {
            installBtn.style.display = 'none';
        }
        
        // Dispatch event for custom install UI
        window.dispatchEvent(new CustomEvent('pwa-hide-install'));
    }

    function installApp() {
        if (!deferredPrompt) return;
        
        // Show the install prompt
        deferredPrompt.prompt();
        
        // Wait for the user to respond to the prompt
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                if (debug) console.log('AF-PWA: User accepted the install prompt');
            } else {
                if (debug) console.log('AF-PWA: User dismissed the install prompt');
            }
            deferredPrompt = null;
        });
    }

    function showUpdateNotification() {
        // Show update notification
        const updateNotification = document.createElement('div');
        updateNotification.className = 'pwa-update-notification';
        updateNotification.innerHTML = `
            <div class="pwa-update-content">
                <span>New version available!</span>
                <button class="pwa-update-btn">Update</button>
                <button class="pwa-dismiss-btn">×</button>
            </div>
        `;
        
        document.body.appendChild(updateNotification);
        
        // Handle update button
        updateNotification.querySelector('.pwa-update-btn').addEventListener('click', () => {
            window.location.reload();
        });
        
        // Handle dismiss button
        updateNotification.querySelector('.pwa-dismiss-btn').addEventListener('click', () => {
            updateNotification.remove();
        });
        
        // Auto dismiss after 10 seconds
        setTimeout(() => {
            if (updateNotification.parentNode) {
                updateNotification.remove();
            }
        }, 10000);
    }

    // Global PWA utilities
    window.AFPWA = {
        isInstalled: () => isInstalled,
        canInstall: () => !!deferredPrompt,
        install: installApp,
        checkForUpdates: () => {
            navigator.serviceWorker.getRegistration().then(reg => {
                if (reg) reg.update();
            });
        },
        getInstallPrompt: () => deferredPrompt
    };

    // Dispatch ready event
    document.addEventListener('DOMContentLoaded', () => {
        window.dispatchEvent(new CustomEvent('pwa-ready', {
            detail: {
                isInstalled: isInstalled,
                canInstall: !!deferredPrompt,
                serviceWorker: 'serviceWorker' in navigator
            }
        }));
    });

})();
</script>

@if(config('af-pwa.ui.styles.inject_css', true))
<style>
/* AF-PWA Default Styles */
.pwa-install-btn {
    display: none;
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: var(--pwa-primary-color, #007bff);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    z-index: 1000;
    transition: all 0.3s ease;
}

.pwa-install-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4);
}

.pwa-update-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--pwa-update-bg, #28a745);
    color: white;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1001;
    animation: slideIn 0.3s ease;
}

.pwa-update-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.pwa-update-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 4px;
    padding: 6px 12px;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.pwa-update-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.pwa-dismiss-btn {
    background: none;
    color: white;
    border: none;
    font-size: 18px;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Hide install button when PWA is installed */
.pwa-installed .pwa-install-btn {
    display: none !important;
}

/* PWA-specific styles when in standalone mode */
@media (display-mode: standalone) {
    body {
        -webkit-user-select: none;
        -webkit-touch-callout: none;
        -webkit-tap-highlight-color: transparent;
    }
}
</style>
@endif
