@php
    $config = config('af-pwa.ui.status_indicator', []);
    $showOnline = $config['show_online'] ?? true;
    $showOffline = $config['show_offline'] ?? true;
    $showInstalled = $config['show_installed'] ?? true;
    $position = $config['position'] ?? 'top-right';
@endphp

<div class="pwa-status-indicator pwa-status-{{ $position }}" data-pwa-status>
    @if($showOnline)
    <div class="pwa-status-item pwa-status-online" data-status="online" style="display: none;">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z" />
        </svg>
        <span>Online</span>
    </div>
    @endif
    
    @if($showOffline)
    <div class="pwa-status-item pwa-status-offline" data-status="offline" style="display: none;">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
            <path d="M23,9V7H1V9H23M23,13V11H1V13H23M23,17V15H1V17H23Z" />
        </svg>
        <span>Offline</span>
    </div>
    @endif
    
    @if($showInstalled)
    <div class="pwa-status-item pwa-status-installed" data-status="installed" style="display: none;">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
            <path d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z" />
        </svg>
        <span>PWA</span>
    </div>
    @endif
</div>

@push('styles')
<style>
.pwa-status-indicator {
    position: fixed;
    z-index: 1000;
    font-size: 12px;
    font-weight: 500;
}

.pwa-status-top-right {
    top: 10px;
    right: 10px;
}

.pwa-status-top-left {
    top: 10px;
    left: 10px;
}

.pwa-status-bottom-right {
    bottom: 10px;
    right: 10px;
}

.pwa-status-bottom-left {
    bottom: 10px;
    left: 10px;
}

.pwa-status-item {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(0, 0, 0, 0.1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.pwa-status-online {
    color: #28a745;
    border-color: rgba(40, 167, 69, 0.3);
}

.pwa-status-offline {
    color: #dc3545;
    border-color: rgba(220, 53, 69, 0.3);
}

.pwa-status-installed {
    color: #007bff;
    border-color: rgba(0, 123, 255, 0.3);
}

/* Dark theme support */
@media (prefers-color-scheme: dark) {
    .pwa-status-item {
        background: rgba(0, 0, 0, 0.9);
        border-color: rgba(255, 255, 255, 0.1);
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusIndicator = document.querySelector('[data-pwa-status]');
    
    if (!statusIndicator) return;
    
    const onlineStatus = statusIndicator.querySelector('[data-status="online"]');
    const offlineStatus = statusIndicator.querySelector('[data-status="offline"]');
    const installedStatus = statusIndicator.querySelector('[data-status="installed"]');
    
    function updateOnlineStatus() {
        if (onlineStatus && offlineStatus) {
            if (navigator.onLine) {
                onlineStatus.style.display = 'flex';
                offlineStatus.style.display = 'none';
            } else {
                onlineStatus.style.display = 'none';
                offlineStatus.style.display = 'flex';
            }
        }
    }
    
    function updateInstalledStatus() {
        if (installedStatus && window.AFPWA) {
            if (window.AFPWA.isInstalled()) {
                installedStatus.style.display = 'flex';
            } else {
                installedStatus.style.display = 'none';
            }
        }
    }
    
    // Initial status update
    updateOnlineStatus();
    updateInstalledStatus();
    
    // Listen for online/offline events
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    
    // Listen for PWA events
    window.addEventListener('pwa-ready', updateInstalledStatus);
    window.addEventListener('pwa-installed', updateInstalledStatus);
});
</script>
@endpush
