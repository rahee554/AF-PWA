<?php

namespace ArtflowStudio\AfPwa\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class PWAService
{
    protected array $config;
    protected string $manifestPath;
    protected string $serviceWorkerPath;

    public function __construct()
    {
        $this->config = config('af-pwa', []);
        $this->manifestPath = public_path('manifest.json');
        $this->serviceWorkerPath = public_path('sw.js');
    }

    /**
     * Generate and cache the PWA manifest
     */
    public function generateManifest(): array
    {
        return Cache::remember('af_pwa_manifest', 3600, function () {
            $manifest = [
                'name' => $this->config['manifest']['name'] ?? config('app.name'),
                'short_name' => $this->config['manifest']['short_name'] ?? Str::limit(config('app.name'), 12),
                'description' => $this->config['manifest']['description'] ?? 'Progressive Web Application',
                'start_url' => $this->config['manifest']['start_url'] ?? '/',
                'display' => $this->config['manifest']['display'] ?? 'standalone',
                'theme_color' => $this->config['manifest']['theme_color'] ?? '#000000',
                'background_color' => $this->config['manifest']['background_color'] ?? '#ffffff',
                'orientation' => $this->config['manifest']['orientation'] ?? 'portrait',
                'scope' => $this->config['manifest']['scope'] ?? '/',
                'lang' => $this->config['manifest']['lang'] ?? 'en',
                'categories' => $this->config['manifest']['categories'] ?? ['productivity'],
                'prefer_related_applications' => false,
                'icons' => $this->generateIcons(),
                'shortcuts' => $this->generateShortcuts(),
                'screenshots' => $this->generateScreenshots(),
            ];

            // Add PWA-specific capabilities
            if ($this->config['features']['file_handler'] ?? false) {
                $manifest['file_handlers'] = $this->config['file_handlers'] ?? [];
            }

            if ($this->config['features']['protocol_handler'] ?? false) {
                $manifest['protocol_handlers'] = $this->config['protocol_handlers'] ?? [];
            }

            if ($this->config['features']['share_target'] ?? false) {
                $manifest['share_target'] = $this->config['share_target'] ?? [];
            }

            return $manifest;
        });
    }

    /**
     * Generate service worker content
     */
    public function generateServiceWorker(): string
    {
        $cacheName = $this->config['cache']['name'] ?? (config('app.name') . '_v' . config('app.version', '1.0'));
        $cacheStrategy = $this->config['cache']['strategy'] ?? 'cache_first';
        $allowedRoutes = $this->config['routes']['allowed'] ?? [];
        $offlinePages = $this->config['offline']['pages'] ?? [];

        $swContent = view('af-pwa::service-worker', [
            'cacheName' => $cacheName,
            'cacheStrategy' => $cacheStrategy,
            'allowedRoutes' => $allowedRoutes,
            'offlinePages' => $offlinePages,
            'staticAssets' => $this->getStaticAssets(),
            'config' => $this->config,
        ])->render();

        return $swContent;
    }

    /**
     * Install PWA assets and configuration
     */
    public function install(): array
    {
        $results = [];

        // Generate and save manifest
        $manifest = $this->generateManifest();
        File::put($this->manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
        $results['manifest'] = 'Generated manifest.json';

        // Generate and save service worker
        $serviceWorker = $this->generateServiceWorker();
        File::put($this->serviceWorkerPath, $serviceWorker);
        $results['service_worker'] = 'Generated sw.js';

        // Create offline pages
        $this->generateOfflinePages();
        $results['offline_pages'] = 'Generated offline pages';

        // Copy PWA assets
        $this->copyAssets();
        $results['assets'] = 'Copied PWA assets';

        // Clear cache
        Cache::forget('af_pwa_manifest');
        $results['cache'] = 'Cleared PWA cache';

        return $results;
    }

    /**
     * Check if current route is allowed in PWA
     */
    public function isRouteAllowed(string $route = null): bool
    {
        $route = $route ?? request()->path();
        $allowedRoutes = $this->config['routes']['allowed'] ?? [];

        foreach ($allowedRoutes as $pattern) {
            if (Str::is($pattern, $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get PWA meta tags
     */
    public function getMetaTags(): array
    {
        $manifest = $this->generateManifest();

        return [
            'viewport' => 'width=device-width, initial-scale=1, shrink-to-fit=no',
            'theme-color' => $manifest['theme_color'],
            'apple-mobile-web-app-capable' => 'yes',
            'apple-mobile-web-app-status-bar-style' => 'default',
            'apple-mobile-web-app-title' => $manifest['short_name'],
            'mobile-web-app-capable' => 'yes',
            'application-name' => $manifest['name'],
            'msapplication-TileColor' => $manifest['theme_color'],
            'msapplication-config' => asset('browserconfig.xml'),
        ];
    }

    /**
     * Render service worker registration script
     */
    public function renderServiceWorkerScript(array $options = []): string
    {
        return view('af-pwa::scripts.service-worker', [
            'options' => array_merge($this->config['service_worker']['options'] ?? [], $options),
            'swPath' => asset('sw.js'),
            'debug' => $this->config['development']['debug'] ?? false,
        ])->render();
    }

    /**
     * Render session manager script
     */
    public function renderSessionManagerScript(array $options = []): string
    {
        return view('af-pwa::scripts.session-manager', [
            'options' => array_merge($this->config['session'] ?? [], $options),
            'csrfToken' => csrf_token(),
            'sessionLifetime' => config('session.lifetime'),
            'debug' => $this->config['development']['debug'] ?? false,
        ])->render();
    }

    /**
     * Generate icons configuration
     */
    protected function generateIcons(): array
    {
        $icons = [];
        $baseIcon = $this->config['manifest']['icons']['base'] ?? '/favicon.ico';

        $sizes = [
            72, 96, 128, 144, 152, 192, 384, 512
        ];

        foreach ($sizes as $size) {
            $icons[] = [
                'src' => asset("icons/icon-{$size}x{$size}.png"),
                'sizes' => "{$size}x{$size}",
                'type' => 'image/png',
                'purpose' => $size >= 192 ? 'any maskable' : 'any',
            ];
        }

        // Add vector icon if available
        if (file_exists(public_path('favicon.svg'))) {
            $icons[] = [
                'src' => asset('favicon.svg'),
                'sizes' => 'any',
                'type' => 'image/svg+xml',
                'purpose' => 'any',
            ];
        }

        return $icons;
    }

    /**
     * Generate shortcuts configuration
     */
    protected function generateShortcuts(): array
    {
        $shortcuts = $this->config['manifest']['shortcuts'] ?? [];
        $defaultShortcuts = [];

        // Add admin shortcut if route exists
        if (Route::has('admin.dashboard')) {
            $defaultShortcuts[] = [
                'name' => 'Admin Dashboard',
                'short_name' => 'Admin',
                'description' => 'Access admin dashboard',
                'url' => route('admin.dashboard'),
                'icons' => [
                    [
                        'src' => asset('icons/admin-96x96.png'),
                        'sizes' => '96x96',
                        'type' => 'image/png',
                    ],
                ],
            ];
        }

        // Add member shortcut if route exists
        if (Route::has('member.dashboard')) {
            $defaultShortcuts[] = [
                'name' => 'Member Area',
                'short_name' => 'Member',
                'description' => 'Access member area',
                'url' => route('member.dashboard'),
                'icons' => [
                    [
                        'src' => asset('icons/member-96x96.png'),
                        'sizes' => '96x96',
                        'type' => 'image/png',
                    ],
                ],
            ];
        }

        return array_merge($defaultShortcuts, $shortcuts);
    }

    /**
     * Generate screenshots configuration
     */
    protected function generateScreenshots(): array
    {
        return $this->config['manifest']['screenshots'] ?? [];
    }

    /**
     * Get static assets for caching
     */
    protected function getStaticAssets(): array
    {
        $assets = [];
        $assetPaths = [
            'css' => public_path('build/assets'),
            'js' => public_path('build/assets'),
            'images' => public_path('images'),
            'icons' => public_path('icons'),
        ];

        foreach ($assetPaths as $type => $path) {
            if (File::exists($path)) {
                $files = File::allFiles($path);
                foreach ($files as $file) {
                    $relativePath = str_replace(public_path(), '', $file->getPathname());
                    $assets[] = asset(ltrim($relativePath, '/\\'));
                }
            }
        }

        return array_slice($assets, 0, 50); // Limit to 50 assets to avoid large cache
    }

    /**
     * Generate offline pages
     */
    protected function generateOfflinePages(): void
    {
        $offlinePages = $this->config['offline']['pages'] ?? [];

        foreach ($offlinePages as $page => $config) {
            $content = view('af-pwa::offline.' . $page, [
                'config' => $config,
                'appName' => config('app.name'),
            ])->render();

            File::put(public_path("offline-{$page}.html"), $content);
        }
    }

    /**
     * Copy PWA assets
     */
    protected function copyAssets(): void
    {
        $sourcePath = __DIR__ . '/../../resources/assets';
        $destPath = public_path('af-pwa');

        if (File::exists($sourcePath)) {
            File::copyDirectory($sourcePath, $destPath);
        }
    }

    /**
     * Get PWA status information
     */
    public function getStatus(): array
    {
        return [
            'installed' => File::exists($this->manifestPath) && File::exists($this->serviceWorkerPath),
            'manifest_exists' => File::exists($this->manifestPath),
            'service_worker_exists' => File::exists($this->serviceWorkerPath),
            'offline_pages' => $this->getOfflinePagesStatus(),
            'cache_status' => Cache::has('af_pwa_manifest'),
            'allowed_routes' => $this->config['routes']['allowed'] ?? [],
            'current_route_allowed' => $this->isRouteAllowed(),
        ];
    }

    /**
     * Get offline pages status
     */
    protected function getOfflinePagesStatus(): array
    {
        $pages = [];
        $offlineConfig = $this->config['offline']['pages'] ?? [];

        foreach ($offlineConfig as $page => $config) {
            $pages[$page] = File::exists(public_path("offline-{$page}.html"));
        }

        return $pages;
    }

    /**
     * Clear PWA cache
     */
    public function clearCache(): void
    {
        Cache::forget('af_pwa_manifest');
        Cache::forget('af_pwa_service_worker');
        Cache::forget('af_pwa_assets');
    }

    /**
     * Update PWA configuration
     */
    public function updateConfig(array $config): void
    {
        $configPath = config_path('af-pwa.php');
        $currentConfig = require $configPath;
        $newConfig = array_merge_recursive($currentConfig, $config);

        File::put($configPath, '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($newConfig, true) . ';');
        $this->clearCache();
    }
}
