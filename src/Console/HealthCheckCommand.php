<?php

namespace ArtflowStudio\AfPwa\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'af-pwa:health 
                          {--url= : Test specific URL}
                          {--detailed : Show detailed health information}
                          {--json : Output results as JSON}';

    /**
     * The console command description.
     */
    protected $description = 'Check PWA health and performance';

    protected array $healthData = [];
    protected array $recommendations = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('json')) {
            return $this->handleJsonOutput();
        }

        $this->displayHeader();

        try {
            $this->checkSystemHealth();
            $this->checkPWAFiles();
            $this->checkPerformance();
            $this->checkAccessibility();
            
            if ($this->option('detailed')) {
                $this->showDetailedHealth();
            }

            $this->displayHealthSummary();
            $this->showRecommendations();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Health check failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Handle JSON output
     */
    protected function handleJsonOutput(): int
    {
        $this->checkSystemHealth();
        $this->checkPWAFiles();
        $this->checkPerformance();
        $this->checkAccessibility();

        $output = [
            'timestamp' => now()->toISOString(),
            'health' => $this->healthData,
            'recommendations' => $this->recommendations,
            'score' => $this->calculateHealthScore(),
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));
        return self::SUCCESS;
    }

    /**
     * Display header
     */
    protected function displayHeader(): void
    {
        $this->info('');
        $this->info('🏥 AF-PWA Health Check');
        $this->info('=======================');
        $this->info('Analyzing your Progressive Web Application...');
        $this->info('');
    }

    /**
     * Check system health
     */
    protected function checkSystemHealth(): void
    {
        $this->info('🔍 Checking system health...');

        // Laravel version
        $laravelVersion = app()->version();
        $this->healthData['system']['laravel_version'] = $laravelVersion;
        $this->line("  • Laravel version: {$laravelVersion}");

        // PHP version
        $phpVersion = PHP_VERSION;
        $this->healthData['system']['php_version'] = $phpVersion;
        $this->line("  • PHP version: {$phpVersion}");

        // Extension availability
        $extensions = ['json', 'mbstring', 'openssl', 'curl'];
        $loadedExtensions = [];
        
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $loadedExtensions[$ext] = $loaded;
            $status = $loaded ? '✅' : '❌';
            $this->line("  • {$ext} extension: {$status}");
        }
        
        $this->healthData['system']['extensions'] = $loadedExtensions;

        // Disk space
        $diskSpace = disk_free_space(public_path());
        $this->healthData['system']['disk_space'] = $diskSpace;
        $this->line("  • Available disk space: " . $this->formatBytes($diskSpace));

        // Memory limit
        $memoryLimit = ini_get('memory_limit');
        $this->healthData['system']['memory_limit'] = $memoryLimit;
        $this->line("  • Memory limit: {$memoryLimit}");
    }

    /**
     * Check PWA files
     */
    protected function checkPWAFiles(): void
    {
        $this->info('');
        $this->info('📱 Checking PWA files...');

        // Determine the base URL - try different ports or use config
        $baseUrl = $this->option('url') ?: $this->detectServerUrl();

        // Check PWA files via routes (recommended approach)
        $files = [
            'manifest.json' => $baseUrl . '/manifest.json',
            'service_worker' => $baseUrl . '/sw.js',
            'offline_page' => $baseUrl . '/offline.html',
        ];

        foreach ($files as $type => $url) {
            try {
                $response = Http::timeout(5)->get($url);
                $exists = $response->successful();
                $size = $exists ? strlen($response->body()) : 0;
                
                $this->healthData['files'][$type] = [
                    'exists' => $exists,
                    'url' => $url,
                    'size' => $size,
                    'status_code' => $response->status(),
                ];

                if ($exists) {
                    $sizeFormatted = $this->formatBytes($size);
                    $this->line("  • {$type}: ✅ ({$sizeFormatted})");
                } else {
                    $this->line("  • {$type}: ❌ Missing (Status: {$response->status()})");
                    $this->recommendations[] = "Check PWA routes and configuration";
                }
            } catch (\Exception $e) {
                $this->line("  • {$type}: ❌ Missing (Error: {$e->getMessage()})");
                $this->recommendations[] = "Start the Laravel server and check PWA routes";
            }
        }

        // Check icons
        $this->checkIcons();

        // Check assets
        $this->checkAssets();
    }

    /**
     * Check icons
     */
    protected function checkIcons(): void
    {
        $iconDir = public_path('vendor/artflow-studio/pwa/icons');
        $iconCount = 0;
        $totalSize = 0;

        if (File::isDirectory($iconDir)) {
            $icons = File::files($iconDir);
            $iconCount = count($icons);
            
            foreach ($icons as $icon) {
                $totalSize += $icon->getSize();
            }
        }

        $this->healthData['files']['icons'] = [
            'directory_exists' => File::isDirectory($iconDir),
            'count' => $iconCount,
            'total_size' => $totalSize,
        ];

        if ($iconCount > 0) {
            $this->line("  • Icons: ✅ {$iconCount} files (" . $this->formatBytes($totalSize) . ")");
        } else {
            $this->line("  • Icons: ❌ No icons found");
            $this->recommendations[] = "Generate icons with: af-pwa:generate --icons";
        }

        // Check required icon sizes
        $requiredSizes = [192, 512];
        $missingSizes = [];

        foreach ($requiredSizes as $size) {
            $iconPath = $iconDir . "/icon-{$size}x{$size}.png";
            if (!File::exists($iconPath)) {
                $missingSizes[] = $size;
            }
        }

        if (!empty($missingSizes)) {
            $this->line("  • Missing required icon sizes: " . implode(', ', $missingSizes));
            $this->recommendations[] = "Generate missing icon sizes with: af-pwa:generate --icons";
        }
    }

    /**
     * Check assets
     */
    protected function checkAssets(): void
    {
        $assetDir = public_path('vendor/artflow-studio/pwa');
        $assetExists = File::isDirectory($assetDir);

        $this->healthData['files']['assets'] = [
            'directory_exists' => $assetExists,
            'published' => $assetExists,
        ];

        if ($assetExists) {
            $this->line("  • Assets: ✅ Published");
        } else {
            $this->line("  • Assets: ❌ Not published");
            $this->recommendations[] = "Publish assets with: php artisan vendor:publish --tag=af-pwa-assets";
        }
    }

    /**
     * Check performance
     */
    protected function checkPerformance(): void
    {
        $this->info('');
        $this->info('⚡ Checking performance...');

        // Manifest size (from route response)
        if (isset($this->healthData['files']['manifest.json']['exists']) && 
            $this->healthData['files']['manifest.json']['exists']) {
            $manifestSize = $this->healthData['files']['manifest.json']['size'];
            $this->healthData['performance']['manifest_size'] = $manifestSize;
            
            if ($manifestSize > 8192) { // 8KB
                $this->line("  • Manifest size: ⚠️  Large (" . $this->formatBytes($manifestSize) . ")");
                $this->recommendations[] = "Consider optimizing manifest.json";
            } else {
                $this->line("  • Manifest size: ✅ Good (" . $this->formatBytes($manifestSize) . ")");
            }
        }

        // Service worker size (from route response)
        if (isset($this->healthData['files']['service_worker']['exists']) && 
            $this->healthData['files']['service_worker']['exists']) {
            $swSize = $this->healthData['files']['service_worker']['size'];
            $this->healthData['performance']['service_worker_size'] = $swSize;
            
            if ($swSize > 51200) { // 50KB
                $this->line("  • Service worker size: ⚠️  Large (" . $this->formatBytes($swSize) . ")");
                $this->recommendations[] = "Consider optimizing service worker";
            } else {
                $this->line("  • Service worker size: ✅ Good (" . $this->formatBytes($swSize) . ")");
            }
        }

        // Icon sizes
        $iconDir = public_path('vendor/artflow-studio/pwa/icons');
        if (File::isDirectory($iconDir)) {
            $icons = File::files($iconDir);
            $largeIcons = [];
            
            foreach ($icons as $icon) {
                if ($icon->getSize() > 524288) { // 512KB
                    $largeIcons[] = $icon->getFilename();
                }
            }

            $this->healthData['performance']['large_icons'] = $largeIcons;
            
            if (!empty($largeIcons)) {
                $this->line("  • Large icons: ⚠️  " . count($largeIcons) . " icons > 512KB");
                $this->recommendations[] = "Optimize large icon files";
            } else {
                $this->line("  • Icon sizes: ✅ All icons optimized");
            }
        }

        // Check caching headers
        $this->checkCachingHeaders();
    }

    /**
     * Check caching headers
     */
    protected function checkCachingHeaders(): void
    {
        // Determine the base URL
        $baseUrl = $this->option('url') ?: $this->detectServerUrl();
        
        // Test cache headers on PWA files
        $testUrls = [
            $baseUrl . '/manifest.json',
            $baseUrl . '/sw.js',
        ];
        
        $cachingScore = 0;
        $totalTests = 0;
        
        foreach ($testUrls as $testUrl) {
            try {
                $response = Http::timeout(5)->get($testUrl);
                if ($response->successful()) {
                    $totalTests++;
                    
                    $headers = $response->headers();
                    $cacheControl = $headers['cache-control'][0] ?? null;
                    $etag = isset($headers['etag']);
                    
                    if ($cacheControl) {
                        $cachingScore++;
                    }
                    
                    $this->healthData['performance']['caching'][basename($testUrl)] = [
                        'cache_control' => $cacheControl,
                        'etag' => $etag,
                        'last_modified' => isset($headers['last-modified']),
                    ];
                }
            } catch (\Exception $e) {
                // Continue with other tests
            }
        }

        if ($totalTests > 0 && $cachingScore > 0) {
            $this->line("  • Cache headers: ✅ Present");
        } elseif ($totalTests > 0) {
            $this->line("  • Cache headers: ⚠️  Missing");
            $this->recommendations[] = "Configure caching headers for better performance";
        } else {
            $this->line("  • Cache headers: ❓ Cannot test (app not running)");
        }
    }

    /**
     * Check accessibility
     */
    protected function checkAccessibility(): void
    {
        $this->info('');
        $this->info('♿ Checking accessibility...');

        // Determine the base URL
        $baseUrl = $this->option('url') ?: $this->detectServerUrl();

        // Check manifest accessibility features via route
        try {
            $response = Http::timeout(5)->get($baseUrl . '/manifest.json');
            if ($response->successful()) {
                $manifest = $response->json();
                
                $a11yFeatures = [
                    'name' => 'App name provided',
                    'short_name' => 'Short name provided',
                    'description' => 'Description provided',
                    'theme_color' => 'Theme color specified',
                    'background_color' => 'Background color specified',
                ];

                $this->healthData['accessibility']['manifest'] = [];
                
                foreach ($a11yFeatures as $feature => $description) {
                    $present = !empty($manifest[$feature]);
                    $this->healthData['accessibility']['manifest'][$feature] = $present;
                    
                    $status = $present ? '✅' : '⚠️';
                    $this->line("  • {$description}: {$status}");
                }

                // Check for shortcuts with descriptions
                if (isset($manifest['shortcuts']) && is_array($manifest['shortcuts'])) {
                    $shortcutsWithDesc = 0;
                    foreach ($manifest['shortcuts'] as $shortcut) {
                        if (!empty($shortcut['description'])) {
                            $shortcutsWithDesc++;
                        }
                    }
                    
                    $this->healthData['accessibility']['shortcuts_with_descriptions'] = $shortcutsWithDesc;
                    $this->line("  • Shortcuts with descriptions: ✅ {$shortcutsWithDesc}");
                }

                // Check icons for accessibility
                $this->checkIconAccessibility($manifest);
            }
        } catch (\Exception $e) {
            $this->line("  • Manifest accessibility: ❓ Cannot test (app not running)");
        }
    }

    /**
     * Check icon accessibility
     */
    protected function checkIconAccessibility($manifest = null): void
    {
        if (!$manifest) {
            return;
        }

        $icons = $manifest['icons'] ?? [];

        $maskableIcons = 0;
        $purposeSpecified = 0;

        foreach ($icons as $icon) {
            if (isset($icon['purpose'])) {
                $purposeSpecified++;
                if (str_contains($icon['purpose'], 'maskable')) {
                    $maskableIcons++;
                }
            }
        }

        $this->healthData['accessibility']['icons'] = [
            'total' => count($icons),
            'with_purpose' => $purposeSpecified,
            'maskable' => $maskableIcons,
        ];

        if ($maskableIcons > 0) {
            $this->line("  • Maskable icons: ✅ {$maskableIcons} available");
        } else {
            $this->line("  • Maskable icons: ⚠️  None found");
            $this->recommendations[] = "Add maskable icons for better platform integration";
        }
    }

    /**
     * Show detailed health information
     */
    protected function showDetailedHealth(): void
    {
        $this->info('');
        $this->info('📊 Detailed Health Information');
        $this->info('===============================');

        // Configuration details
        $this->info('');
        $this->info('⚙️  Configuration:');
        $config = config('af-pwa');
        $this->line("  • PWA Routes: " . count($config['pwa_routes'] ?? []));
        $this->line("  • Cache Version: " . ($config['cache_version'] ?? 'Not set'));
        $this->line("  • Theme Color: " . ($config['theme_color'] ?? 'Not set'));
        $this->line("  • Background Color: " . ($config['background_color'] ?? 'Not set'));

        // Environment details
        $this->info('');
        $this->info('🌍 Environment:');
        $this->line("  • App Environment: " . app()->environment());
        $this->line("  • Debug Mode: " . (config('app.debug') ? 'Enabled' : 'Disabled'));
        $this->line("  • URL: " . config('app.url'));

        // File details
        $this->info('');
        $this->info('📁 File Details:');
        foreach ($this->healthData['files'] ?? [] as $type => $data) {
            if (is_array($data) && isset($data['exists']) && $data['exists']) {
                $this->line("  • {$type}: " . $this->formatBytes($data['size'] ?? 0));
            }
        }
    }

    /**
     * Display health summary
     */
    protected function displayHealthSummary(): void
    {
        $score = $this->calculateHealthScore();
        
        $this->info('');
        $this->info('🎯 Health Score: ' . $score . '%');
        
        if ($score >= 90) {
            $this->info('🎉 Excellent! Your PWA is in great shape!');
        } elseif ($score >= 70) {
            $this->info('👍 Good! Your PWA is working well with minor improvements needed.');
        } elseif ($score >= 50) {
            $this->warn('⚠️  Fair. Your PWA needs some attention.');
        } else {
            $this->error('❌ Poor. Your PWA needs significant improvements.');
        }
    }

    /**
     * Calculate health score
     */
    protected function calculateHealthScore(): int
    {
        $score = 0;
        $maxScore = 0;

        // System health (20 points)
        $maxScore += 20;
        $extensions = $this->healthData['system']['extensions'] ?? [];
        $loadedCount = count(array_filter($extensions));
        $totalCount = count($extensions);
        if ($totalCount > 0) {
            $score += (int) (($loadedCount / $totalCount) * 20);
        }

        // Files (40 points)
        $maxScore += 40;
        $files = $this->healthData['files'] ?? [];
        
        // Essential files
        $essentialFiles = ['manifest.json', 'service_worker'];
        foreach ($essentialFiles as $file) {
            if (isset($files[$file]['exists']) && $files[$file]['exists']) {
                $score += 15;
            }
        }
        
        // Icons
        if (isset($files['icons']['count']) && $files['icons']['count'] > 0) {
            $score += 10;
        }

        // Performance (25 points)
        $maxScore += 25;
        $performance = $this->healthData['performance'] ?? [];
        
        if (isset($performance['manifest_size']) && $performance['manifest_size'] <= 8192) {
            $score += 5;
        }
        
        if (isset($performance['service_worker_size']) && $performance['service_worker_size'] <= 51200) {
            $score += 10;
        }
        
        if (empty($performance['large_icons'] ?? [])) {
            $score += 10;
        }

        // Accessibility (15 points)
        $maxScore += 15;
        $accessibility = $this->healthData['accessibility'] ?? [];
        
        if (isset($accessibility['manifest'])) {
            $a11yFeatures = array_filter($accessibility['manifest']);
            $score += min(10, count($a11yFeatures) * 2);
        }
        
        if (isset($accessibility['icons']['maskable']) && $accessibility['icons']['maskable'] > 0) {
            $score += 5;
        }

        return min(100, (int) (($score / $maxScore) * 100));
    }

    /**
     * Show recommendations
     */
    protected function showRecommendations(): void
    {
        if (empty($this->recommendations)) {
            $this->info('');
            $this->info('✨ No recommendations - your PWA is optimized!');
            return;
        }

        $this->info('');
        $this->info('💡 Recommendations:');
        $this->info('==================');
        
        foreach ($this->recommendations as $i => $recommendation) {
            $this->line(($i + 1) . ". {$recommendation}");
        }

        $this->info('');
        $this->line('Run these commands to improve your PWA health score.');
    }

    /**
     * Format bytes for display
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        
        return $bytes . ' bytes';
    }

    /**
     * Detect the server URL by trying common development server ports
     */
    protected function detectServerUrl(): string
    {
        $commonPorts = [7978, 8000, 8080, 80];
        $baseHost = 'http://localhost';

        foreach ($commonPorts as $port) {
            $testUrl = $port === 80 ? $baseHost : "{$baseHost}:{$port}";
            
            try {
                $response = Http::timeout(2)->get($testUrl);
                if ($response->successful()) {
                    return $testUrl;
                }
            } catch (\Exception $e) {
                // Continue trying other ports
            }
        }

        // Fallback to config URL
        return config('app.url', 'http://localhost:8000');
    }
}
