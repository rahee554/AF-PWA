{{-- PWA Network Status Component --}}

<div id="af-pwa-network-status" class="af-pwa-network-status" style="display: none;">
    <div class="af-pwa-network-indicator"></div>
    <span id="af-pwa-network-text">Online</span>
</div>

<script>
    document.addEventListener('af-pwa:online', function(e) {
        const networkStatus = document.getElementById('af-pwa-network-status');
        const networkText = document.getElementById('af-pwa-network-text');
        
        if (networkStatus && networkText) {
            networkStatus.className = 'af-pwa-network-status af-pwa-online af-pwa-show';
            networkText.textContent = 'Online';
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                networkStatus.classList.remove('af-pwa-show');
            }, 3000);
        }
    });
    
    document.addEventListener('af-pwa:offline', function(e) {
        const networkStatus = document.getElementById('af-pwa-network-status');
        const networkText = document.getElementById('af-pwa-network-text');
        
        if (networkStatus && networkText) {
            networkStatus.className = 'af-pwa-network-status af-pwa-offline af-pwa-show';
            networkText.textContent = 'Offline';
            // Don't auto-hide when offline
        }
    });
</script>
