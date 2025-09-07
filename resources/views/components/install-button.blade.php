@php
    $config = config('af-pwa.install_prompt', []);
    $showInstallPrompt = config('af-pwa.show_install_prompt', false);
    $text = $config['text'] ?? 'Install App';
    $class = $config['class'] ?? 'pwa-install-btn';
    $style = $config['style'] ?? '';
@endphp

@if($showInstallPrompt)
<button 
    class="{{ $class }}" 
    style="{{ $style }}"
    data-pwa-install-button
    title="Install this app on your device"
>
    @if($config['icon'] ?? true)
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;">
            <path d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z" />
        </svg>
    @endif
    {{ $text }}
</button>

@push('scripts')
<script>
document.addEventListener('pwa-ready', function() {
    const installBtn = document.querySelector('[data-pwa-install-button]');
    
    if (installBtn && window.AFPWA) {
        // Show button only if app can be installed
        if (window.AFPWA.canInstall()) {
            installBtn.style.display = 'block';
        }
        
        // Handle click
        installBtn.addEventListener('click', function() {
            window.AFPWA.install();
        });
        
        // Listen for install events
        window.addEventListener('pwa-install-available', function() {
            installBtn.style.display = 'block';
        });
        
        window.addEventListener('pwa-installed', function() {
            installBtn.style.display = 'none';
        });
    }
});
</script>
@endpush
@endif
