<?php

namespace ArtflowStudio\AfPwa;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AfPwaManager
{
    /**
     * Configuration array
     */
    protected array $config;

    /**
     * Create a new AfPwaManager instance.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Render complete PWA setup (simplified single directive)
     */
    public function renderComplete($options = []): string
    {
        $options = is_string($options) ? json_decode($options, true) : $options;
        $options = array_merge($this->config, $options ?: []);

        $html = '';
        
        // Head elements (meta tags, manifest, icons, CSS)
        $html .= $this->renderHead($options);
        
        // Scripts (service worker, PWA logic, event handlers)
        $html .= $this->renderScripts($options);
        
        // Optional UI components
        if ($options['show_install_prompt'] ?? true) {
            $html .= $this->renderInstallButton();
        }
        
        if ($options['show_network_status'] ?? true) {
            $html .= $this->renderNetworkStatus();
        }

        return $html;
    }

    /**
     * Render complete PWA setup
     */
    public function render($options = []): string
    {
        return $this->renderComplete($options);
    }

    /**
     * Render head elements (meta tags, manifest, icons)
     */
    public function renderHead($options = []): string
    {
        $options = is_string($options) ? json_decode($options, true) : $options;
        $options = array_merge($this->config, $options ?: []);

        return view('af-pwa::components.head', compact('options'))->render();
    }

    /**
     * Render JavaScript files and initialization
     */
    public function renderScripts($options = []): string
    {
        $options = is_string($options) ? json_decode($options, true) : $options;
        $options = array_merge($this->config, $options ?: []);

        return view('af-pwa::components.scripts', compact('options'))->render();
    }

    /**
     * Render manifest link
     */
    public function renderManifest(): string
    {
        $manifestUrl = route('af-pwa.manifest');
        return '<link rel="manifest" href="' . $manifestUrl . '">';
    }

    /**
     * Render service worker registration
     */
    public function renderServiceWorker($options = []): string
    {
        $options = is_string($options) ? json_decode($options, true) : $options;
        $options = array_merge($this->config, $options ?: []);

        $swUrl = route('af-pwa.service-worker');
        
        return view('af-pwa::components.service-worker', [
            'swUrl' => $swUrl,
            'options' => $options
        ])->render();
    }

    /**
     * Render install button
     */
    public function renderInstallButton($text = 'Install App'): string
    {
        $text = is_string($text) ? trim($text, '"\'') : 'Install App';
        
        return view('af-pwa::components.install-button', [
            'text' => $text
        ])->render();
    }

    /**
     * Render network status indicator
     */
    public function renderNetworkStatus(): string
    {
        return view('af-pwa::components.network-status')->render();
    }

    /**
     * Render offline page
     */
    public function renderOfflinePage($options = []): string
    {
        $options = is_string($options) ? json_decode($options, true) : $options;
        $options = array_merge($this->config['offline'] ?? [], $options ?: []);

        return view('af-pwa::offline.default', compact('options'))->render();
    }

    /**
     * Generate manifest.json content
     */
    public function generateManifest(): array
    {
        $manifest = [
            'name' => $this->config['name'] ?? config('app.name'),
            'short_name' => $this->config['short_name'] ?? Str::limit(config('app.name'), 12),
            'description' => $this->config['description'] ?? 'A Progressive Web Application',
            'start_url' => $this->config['start_url'] ?? '/',
            'display' => $this->config['display'] ?? 'standalone',
            'background_color' => $this->config['background_color'] ?? '#ffffff',
            'theme_color' => $this->config['theme_color'] ?? '#000000',
            'orientation' => $this->config['orientation'] ?? 'portrait-primary',
            'scope' => $this->config['scope'] ?? '/',
            'lang' => $this->config['lang'] ?? 'en',
            'dir' => $this->config['dir'] ?? 'ltr',
            'categories' => $this->config['categories'] ?? ['business', 'productivity'],
            'prefer_related_applications' => false,
            // Mobile-specific features for better PWA support
            'id' => $this->config['id'] ?? '/',
            'display_override' => ['window-controls-overlay', 'standalone'],
            'edge_side_panel' => [
                'preferred_width' => 400
            ],
        ];

        // Add icons
        if (isset($this->config['icons']) && is_array($this->config['icons'])) {
            $manifest['icons'] = $this->config['icons'];
        } else {
            $manifest['icons'] = $this->getDefaultIcons();
        }

        // Add shortcuts
        if (isset($this->config['shortcuts']) && is_array($this->config['shortcuts'])) {
            $manifest['shortcuts'] = $this->config['shortcuts'];
        }

        // Add screenshots for mobile app stores
        if (isset($this->config['screenshots']) && is_array($this->config['screenshots'])) {
            $manifest['screenshots'] = $this->config['screenshots'];
        } else {
            // Add default screenshots to help with mobile installation
            $manifest['screenshots'] = $this->generateDefaultScreenshots();
        }

        return $manifest;
    }

    /**
     * Generate service worker content
     */
    public function generateServiceWorker(): string
    {
        // Check for custom template first, then use package template
        $customTemplate = resource_path('views/vendor/af-pwa/js/service-worker.template.js');
        $packageTemplate = __DIR__ . '/../resources/js/service-worker.template.js';
        
        $templatePath = File::exists($customTemplate) ? $customTemplate : $packageTemplate;
        $template = File::get($templatePath);
        
        $config = [
            'app_name' => $this->config['name'] ?? config('app.name'),
            'cache_version' => $this->config['cache_version'] ?? 'v1',
            'pwa_routes' => $this->config['pwa_routes'] ?? ['/admin', '/member', '/login'],
            'static_assets' => $this->getStaticAssets(),
            'cache_strategies' => $this->config['cache_strategies'] ?? [
                'assets' => 'cache-first',
                'pages' => 'network-first',
                'api' => 'network-first'
            ],
            'offline_pages' => $this->getOfflinePages(),
            'default_offline_page' => route('af-pwa.offline'),
            'network_timeout' => $this->config['network_timeout'] ?? 10000,
            'api_timeout' => $this->config['api_timeout'] ?? 10000,
            'page_timeout' => $this->config['page_timeout'] ?? 15000,
            'post_timeout' => $this->config['post_timeout'] ?? 30000,
            'asset_patterns' => $this->config['asset_patterns'] ?? ['/assets/', '/build/', '/vendor/'],
            'api_patterns' => $this->config['api_patterns'] ?? ['/api/', '/livewire/'],
            'cacheable_extensions' => $this->config['cacheable_extensions'] ?? [
                'js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'webp',
                'woff', 'woff2', 'ttf', 'otf', 'eot', 'json', 'html', 'htm'
            ],
            'non_cacheable_extensions' => $this->config['non_cacheable_extensions'] ?? [
                'avi', 'mkv', 'mov', 'wmv', 'flv', 'zip', 'rar', '7z', 'exe', 'msi'
            ],
            'error_handling' => $this->config['error_handling'] ?? []
        ];

        // Replace template placeholder with actual config
        $configJson = json_encode($config, JSON_PRETTY_PRINT);
        $serviceWorker = str_replace('__AF_PWA_CONFIG__', $configJson, $template);

        return $serviceWorker;
    }

    /**
     * Generate offline page HTML
     */
    public function generateOfflinePage(): string
    {
        $appName = $this->config['name'] ?? config('app.name');
        
        try {
            // Try to use custom offline template first
            if (view()->exists('vendor.artflow-studio.pwa.offline.default')) {
                return view('vendor.artflow-studio.pwa.offline.default', [
                    'appName' => $appName,
                    'message' => 'You are currently offline. Please check your internet connection and try again.'
                ])->render();
            }
            
            // Fallback to package template
            if (view()->exists('af-pwa::offline.default')) {
                return view('af-pwa::offline.default', [
                    'appName' => $appName,
                    'message' => 'You are currently offline. Please check your internet connection and try again.'
                ])->render();
            }
        } catch (\Exception $e) {
            // Continue to inline fallback
        }
        
        // Inline fallback if no templates found
        $themeColor = $this->config['theme_color'] ?? '#000000';
        $backgroundColor = $this->config['background_color'] ?? '#ffffff';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - {$appName}</title>
    <meta name="theme-color" content="{$themeColor}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: {$backgroundColor};
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            padding: 20px;
        }
        
        .offline-container {
            max-width: 400px;
            padding: 40px 20px;
        }
        
        .offline-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        .offline-title {
            font-size: 24px;
            margin-bottom: 16px;
            color: #333;
        }
        
        .offline-message {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 24px;
            color: #666;
        }
        
        .retry-button {
            background: {$themeColor};
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        
        .retry-button:hover {
            opacity: 0.9;
        }
        
        .retry-button:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">📡</div>
        <h1 class="offline-title">You're Offline</h1>
        <p class="offline-message">
            It looks like you've lost your internet connection. Please check your connection and try again.
        </p>
        <button class="retry-button" onclick="window.location.reload()">
            Try Again
        </button>
    </div>
    
    <script>
        // Auto-retry when connection is restored
        window.addEventListener('online', function() {
            window.location.reload();
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Get default icons
     */
    protected function getDefaultIcons(): array
    {
        $icons = [
            [
                'src' => '/favicon.ico',
                'sizes' => '16x16 32x32',
                'type' => 'image/x-icon'
            ],
            [
                'src' => '/favicon.svg',
                'sizes' => 'any',
                'type' => 'image/svg+xml'
            ],
            [
                'src' => '/apple-touch-icon.png',
                'sizes' => '180x180',
                'type' => 'image/png'
            ]
        ];

        // Add PWA icons if they exist
        $iconSizes = [
            '16x16', '32x32', '72x72', '96x96', '128x128', '144x144', '152x152', 
            '192x192', '384x384', '512x512'
        ];

        foreach ($iconSizes as $size) {
            $iconPath = "/vendor/artflow-studio/pwa/icons/icon-{$size}.png";
            if (file_exists(public_path("vendor/artflow-studio/pwa/icons/icon-{$size}.png"))) {
                $purpose = in_array($size, ['192x192', '512x512']) ? 'any' : null;
                $icon = [
                    'src' => $iconPath,
                    'sizes' => $size,
                    'type' => 'image/png'
                ];
                if ($purpose) {
                    $icon['purpose'] = $purpose;
                }
                $icons[] = $icon;
            }
        }

        // Add maskable icons if they exist
        $maskableSizes = ['192x192', '512x512'];
        foreach ($maskableSizes as $size) {
            $maskableIconPath = "/vendor/artflow-studio/pwa/icons/maskable-icon-{$size}.png";
            if (file_exists(public_path("vendor/artflow-studio/pwa/icons/maskable-icon-{$size}.png"))) {
                $icons[] = [
                    'src' => $maskableIconPath,
                    'sizes' => $size,
                    'type' => 'image/png',
                    'purpose' => 'maskable'
                ];
            }
        }

        return $icons;
    }

    /**
     * Get static assets to cache
     */
    protected function getStaticAssets(): array
    {
        $assets = [];
        
        // Add configured assets
        if (isset($this->config['cache_assets'])) {
            $assets = array_merge($assets, $this->config['cache_assets']);
        }

        // Add default assets with correct paths
        $defaultAssets = [
            '/vendor/artflow-studio/pwa/js/af-pwa.js',
            '/vendor/artflow-studio/pwa/css/af-pwa.css',
        ];

        return array_merge($assets, $defaultAssets);
    }

    /**
     * Get offline pages mapping
     */
    protected function getOfflinePages(): array
    {
        $pages = [];
        
        $routes = $this->config['pwa_routes'] ?? ['/'];
        
        foreach ($routes as $route) {
            try {
                // Use single default offline page for all routes
                $pages[$route . '*'] = route('af-pwa.offline');
            } catch (\Exception $e) {
                // Fallback to main offline page if route generation fails
                $pages[$route . '*'] = route('af-pwa.offline');
            }
        }

        return $pages;
    }

    /**
     * Check if current route should be handled by PWA
     */
    public function isPwaRoute(string $path = null): bool
    {
        $path = $path ?: request()->path();
        $routes = $this->config['pwa_routes'] ?? [];

        foreach ($routes as $route) {
            $route = trim($route, '/');
            if ($path === $route || str_starts_with($path, $route . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get PWA configuration
     */
    public function getConfig(string $key = null)
    {
        if ($key) {
            return $this->config[$key] ?? null;
        }

        return $this->config;
    }

    /**
     * Set configuration value
     */
    public function setConfig(string $key, $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * Generate CSRF-safe configuration for frontend
     */
    public function getFrontendConfig(): array
    {
        return [
            'app_name' => $this->config['name'] ?? config('app.name'),
            'pwa_routes' => $this->getAllPwaRoutes(),
            'enable_notifications' => $this->config['enable_notifications'] ?? true,
            'enable_background_sync' => $this->config['enable_background_sync'] ?? true,
            'update_check_interval' => $this->config['update_check_interval'] ?? 3600000,
            'show_install_prompt' => $this->config['show_install_prompt'] ?? false,
            'show_network_status' => $this->config['show_network_status'] ?? true,
            'auto_refresh_on_update' => $this->config['auto_refresh_on_update'] ?? true,
            'debug' => $this->config['debug'] ?? false,
            'theme_color' => $this->config['theme_color'] ?? '#000000',
            'csrf_token' => csrf_token(),
            'session_lifetime' => config('session.lifetime') * 60 * 1000, // Convert to milliseconds
            'error_handling' => [
                'csrf_error' => [
                    'auto_refresh' => $this->config['csrf_auto_refresh'] ?? true,
                    'max_retries' => $this->config['csrf_max_retries'] ?? 3,
                    'show_notification' => true,
                    'message' => 'Session expired. Refreshing...'
                ],
                'session_expired' => [
                    'redirect_to_login' => $this->config['redirect_on_session_expired'] ?? true,
                    'login_route' => $this->config['login_route'] ?? '/login',
                    'show_notification' => true,
                    'message' => 'Your session has expired. Please log in again.'
                ],
                'network_error' => [
                    'show_offline_page' => $this->config['show_offline_page'] ?? true,
                    'retry_button' => $this->config['offline_retry_button'] ?? true,
                    'auto_retry' => $this->config['offline_auto_retry'] ?? false,
                    'message' => 'Connection lost. Please check your internet.'
                ]
            ]
        ];
    }

    /**
     * Get PWA routes with auto-discovery
     */
    public function getDiscoveredRoutes(): array
    {
        $routes = $this->config['pwa_routes'] ?? [];
        
        // If auto-discovery is enabled and no routes configured, discover them
        if (($this->config['auto_discover_routes'] ?? true) && empty($routes)) {
            $routes = $this->discoverApplicationRoutes();
        }
        
        return array_unique($routes);
    }

    /**
     * Get all PWA routes (both configured and discovered)
     */
    public function getAllPwaRoutes(): array
    {
        return $this->getDiscoveredRoutes();
    }

    /**
     * Auto-discover application routes
     */
    protected function discoverApplicationRoutes(): array
    {
        $discoveredRoutes = [];
        $discoveryPatterns = $this->config['route_discovery_patterns'] ?? [];
        
        try {
            $allRoutes = Route::getRoutes()->getRoutes();
            $existingPaths = [];
            
            foreach ($allRoutes as $route) {
                $methods = $route->methods();
                
                // Only consider GET routes for PWA caching
                if (!in_array('GET', $methods)) {
                    continue;
                }
                
                $uri = $route->uri();
                
                // Skip API routes and special Laravel routes
                if ($this->shouldSkipRoute($uri)) {
                    continue;
                }
                
                // Normalize route for pattern matching
                $normalizedUri = '/' . ltrim($uri, '/');
                
                // Check if route matches any discovery pattern
                foreach ($discoveryPatterns as $pattern) {
                    if ($this->matchesPattern($normalizedUri, $pattern)) {
                        // Convert parameterized routes to wildcard patterns
                        $routePattern = $this->convertToWildcardPattern($normalizedUri);
                        if (!in_array($routePattern, $existingPaths)) {
                            $discoveredRoutes[] = $routePattern;
                            $existingPaths[] = $routePattern;
                        }
                    }
                }
            }
            
            // Always include homepage
            if (!in_array('/', $discoveredRoutes)) {
                array_unshift($discoveredRoutes, '/');
            }
            
        } catch (\Exception $e) {
            // Fallback to basic routes if auto-discovery fails
            $discoveredRoutes = ['/'];
        }
        
        return $discoveredRoutes;
    }

    /**
     * Check if route should be skipped during discovery
     */
    protected function shouldSkipRoute(string $uri): bool
    {
        $skipPatterns = [
            'api/',
            '_debugbar/',
            'telescope/',
            'horizon/',
            'livewire/',
            'fortify/',
            'sanctum/',
            '{',  // Skip parameterized routes for basic discovery
        ];
        
        foreach ($skipPatterns as $pattern) {
            if (Str::contains($uri, $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if URI matches a discovery pattern
     */
    protected function matchesPattern(string $uri, string $pattern): bool
    {
        // Remove trailing * for comparison
        $basePattern = rtrim($pattern, '*');
        
        if (Str::endsWith($pattern, '*')) {
            return Str::startsWith($uri, $basePattern);
        }
        
        return $uri === $pattern;
    }

    /**
     * Convert parameterized route to wildcard pattern
     */
    protected function convertToWildcardPattern(string $uri): string
    {
        // Convert Laravel route parameters to wildcards
        $pattern = preg_replace('/\{[^}]+\}/', '*', $uri);
        
        // Clean up multiple wildcards
        $pattern = preg_replace('/\/\*\/\*/', '/*', $pattern);
        
        // Ensure trailing wildcard if route has parameters
        if (Str::contains($uri, '{') && !Str::endsWith($pattern, '*')) {
            $pattern .= '*';
        }
        
        return $pattern;
    }

    /**
     * Get asset URL for PWA resources
     */
    public function getAssetUrl(string $path = ''): string
    {
        $baseUrl = $this->config['asset_url'] ?? '/vendor/af-pwa';
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Check if we should use vendor assets
     */
    public function useVendorAssets(): bool
    {
        return $this->config['use_vendor_assets'] ?? true;
    }

    /**
     * Generate default screenshots for mobile app stores
     */
    protected function generateDefaultScreenshots(): array
    {
        return [
            [
                'src' => url('/vendor/artflow-studio/pwa/icons/icon-512x512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'form_factor' => 'narrow',
                'label' => $this->config['name'] ?? config('app.name')
            ],
            [
                'src' => url('/vendor/artflow-studio/pwa/icons/icon-512x512.png'),
                'sizes' => '512x512', 
                'type' => 'image/png',
                'form_factor' => 'wide',
                'label' => $this->config['name'] ?? config('app.name')
            ]
        ];
    }
}
