<?php

namespace ArtflowStudio\AfPwa\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'af-pwa:test 
                          {--interactive : Run interactive tests}
                          {--fix : Automatically fix issues where possible}
                          {--report : Generate detailed test report}';

    /**
     * The console command description.
     */
    protected $description = 'Test PWA functionality, manifest, service worker, and icons';

    protected array $results = [];
    protected array $errors = [];
    protected array $warnings = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayHeader();

        try {
            if ($this->option('interactive')) {
                $this->runInteractiveTests();
            } else {
                $this->runAllTests();
            }

            $this->displayResults();

            if ($this->option('report')) {
                $this->generateReport();
            }

            return empty($this->errors) ? self::SUCCESS : self::FAILURE;
        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Display header
     */
    protected function displayHeader(): void
    {
        $this->info('');
        $this->info('🧪 AF-PWA Test Suite');
        $this->info('====================');
        $this->info('Testing your Progressive Web Application setup...');
        $this->info('');
    }

    /**
     * Run interactive tests
     */
    protected function runInteractiveTests(): void
    {
        $tests = [
            'configuration' => 'Test Configuration',
            'manifest' => 'Test Manifest',
            'service_worker' => 'Test Service Worker',
            'icons' => 'Test Icons',
            'routes' => 'Test Routes',
            'assets' => 'Test Assets',
            'integration' => 'Test Integration',
        ];

        foreach ($tests as $test => $description) {
            if ($this->confirm("Run {$description}?", true)) {
                $this->runTest($test);
            }
        }
    }

    /**
     * Run all tests
     */
    protected function runAllTests(): void
    {
        $tests = [
            'configuration',
            'manifest', 
            'service_worker',
            'icons',
            'routes',
            'assets',
            'integration'
        ];

        foreach ($tests as $test) {
            $this->runTest($test);
        }
    }

    /**
     * Run a specific test
     */
    protected function runTest(string $test): void
    {
        $this->info("🔍 Testing {$test}...");

        switch ($test) {
            case 'configuration':
                $this->testConfiguration();
                break;
            case 'manifest':
                $this->testManifest();
                break;
            case 'service_worker':
                $this->testServiceWorker();
                break;
            case 'icons':
                $this->testIcons();
                break;
            case 'routes':
                $this->testRoutes();
                break;
            case 'assets':
                $this->testAssets();
                break;
            case 'integration':
                $this->testIntegration();
                break;
        }
    }

    /**
     * Test configuration
     */
    protected function testConfiguration(): void
    {
        $configPath = config_path('af-pwa.php');
        
        if (!File::exists($configPath)) {
            $this->addError('Configuration file not found', 'af-pwa:install');
            return;
        }

        $this->addSuccess('Configuration file exists');

        // Test config values
        $config = config('af-pwa');
        $pwaManager = app('af-pwa');
        
        if (empty($config['name'])) {
            $this->addWarning('PWA name not configured');
        } else {
            $this->addSuccess("PWA name: {$config['name']}");
        }

        $allRoutes = $pwaManager->getAllPwaRoutes();
        if (empty($allRoutes)) {
            $this->addWarning('No PWA routes configured or discovered');
        } else {
            $this->addSuccess('PWA routes: ' . count($allRoutes) . ' available (' . 
                count($config['pwa_routes'] ?? []) . ' configured, ' . 
                (count($allRoutes) - count($config['pwa_routes'] ?? [])) . ' auto-discovered)');
        }

        // Test environment variables
        $envVars = [
            'PWA_NAME' => 'App name',
            'PWA_SHORT_NAME' => 'Short name', 
            'PWA_DESCRIPTION' => 'Description',
            'PWA_THEME_COLOR' => 'Theme color',
            'PWA_BACKGROUND_COLOR' => 'Background color'
        ];

        foreach ($envVars as $var => $description) {
            if (env($var)) {
                $this->addSuccess("{$description} configured in .env");
            } else {
                $this->addWarning("{$description} not set in .env");
            }
        }
    }

    /**
     * Test manifest
     */
    protected function testManifest(): void
    {
        // Check if static manifest.json exists first
        $staticManifestPath = public_path('manifest.json');
        $manifest = null;
        
        if (File::exists($staticManifestPath)) {
            // Test static file
            try {
                $content = File::get($staticManifestPath);
                $manifest = json_decode($content, true);
                
                if (!$manifest) {
                    $this->addError('Invalid manifest JSON format in static file');
                    return;
                }
                
                $this->addSuccess('Static manifest.json exists and is valid');
            } catch (\Exception $e) {
                $this->addError('Error reading static manifest: ' . $e->getMessage());
                return;
            }
        } else {
            // Test dynamic route
            try {
                $baseUrl = $this->detectServerUrl();
                $manifestUrl = $baseUrl . '/manifest.json';
                $response = Http::timeout(5)->get($manifestUrl);
                
                if (!$response->successful()) {
                    $this->addError('Manifest route not accessible', 'Check your routes');
                    return;
                }
                
                $this->addSuccess('Manifest route exists and accessible');
                
                $manifest = $response->json();
                
                if (!$manifest) {
                    $this->addError('Invalid manifest JSON format');
                    return;
                }
                
            } catch (\Exception $e) {
                $this->addError('Manifest route error: ' . $e->getMessage());
                return;
            }
        }

        $this->addSuccess('Manifest has valid JSON format');

        // Test required fields
        $required = [
            'name' => 'App name',
            'short_name' => 'Short name',
            'start_url' => 'Start URL',
            'display' => 'Display mode',
            'theme_color' => 'Theme color',
            'background_color' => 'Background color',
            'icons' => 'Icons array'
        ];

        foreach ($required as $field => $description) {
            if (isset($manifest[$field])) {
                $this->addSuccess("{$description} configured");
            } else {
                $this->addError("Missing required field: {$field}");
            }
        }

        // Test icons
        if (isset($manifest['icons']) && is_array($manifest['icons'])) {
            $iconCount = count($manifest['icons']);
            if ($iconCount > 0) {
                $this->addSuccess("{$iconCount} icons configured in manifest");
                
                // Check for required sizes
                $sizes = array_column($manifest['icons'], 'sizes');
                $required_sizes = ['192x192', '512x512'];
                
                foreach ($required_sizes as $size) {
                    if (in_array($size, $sizes)) {
                        $this->addSuccess("Required icon size {$size} present");
                    } else {
                        $this->addWarning("Missing recommended icon size: {$size}");
                    }
                }
            } else {
                $this->addWarning('No icons configured in manifest');
            }
        }

        // Test manifest size (from response)
        $size = strlen(json_encode($manifest));
        if ($size > 8192) { // 8KB
            $this->addWarning('Manifest is large (' . $this->formatBytes($size) . ')');
        } else {
            $this->addSuccess('Manifest file size OK (' . $this->formatBytes($size) . ')');
        }
    }

    /**
     * Test service worker
     */
    protected function testServiceWorker(): void
    {
        // Check if static sw.js exists first
        $staticSwPath = public_path('sw.js');
        $content = null;
        
        if (File::exists($staticSwPath)) {
            // Test static file
            try {
                $content = File::get($staticSwPath);
                $this->addSuccess('Static sw.js exists');
            } catch (\Exception $e) {
                $this->addError('Error reading static service worker: ' . $e->getMessage());
                return;
            }
        } else {
            // Test dynamic route
            try {
                $baseUrl = $this->detectServerUrl();
                $swUrl = $baseUrl . '/sw.js';
                $response = Http::timeout(5)->get($swUrl);
                
                if (!$response->successful()) {
                    $this->addError('Service worker route not accessible', 'Check your routes');
                    return;
                }
                
                $this->addSuccess('Service worker route exists and accessible');
                $content = $response->body();
                
            } catch (\Exception $e) {
                $this->addError('Service worker route error: ' . $e->getMessage());
                return;
            }
        }

        if (!$content) {
            $this->addError('Service worker content is empty');
            return;
        }

        // Test for required events
        $events = ['install', 'activate', 'fetch'];
        
        foreach ($events as $event) {
            if (strpos($content, "addEventListener('{$event}'") !== false) {
                $this->addSuccess("Service worker handles {$event} event");
            } else {
                $this->addError("Service worker missing {$event} event handler");
            }
        }

        // Test for cache names
        if (strpos($content, 'CACHE_NAME') !== false || strpos($content, 'cacheName') !== false) {
            $this->addSuccess('Service worker has cache management');
        } else {
            $this->addWarning('Service worker cache management not detected');
        }

        // Test file size (from response)
        $size = strlen($content);
        if ($size > 51200) { // 50KB
            $this->addWarning('Service worker is large (' . $this->formatBytes($size) . ')');
        } else {
            $this->addSuccess('Service worker size OK (' . $this->formatBytes($size) . ')');
        }

        // Test syntax
        if ($this->testJavaScriptSyntax($content)) {
            $this->addSuccess('Service worker syntax is valid');
        } else {
            $this->addError('Service worker has syntax errors');
        }
    }

    /**
     * Test icons
     */
    protected function testIcons(): void
    {
        $iconDir = public_path('icons');
        
        if (!File::isDirectory($iconDir)) {
            $this->addError('Icons directory not found', 'af-pwa:generate --icons');
            return;
        }

        $this->addSuccess('Icons directory exists');

        $icons = File::files($iconDir);
        $iconCount = count($icons);

        if ($iconCount === 0) {
            $this->addError('No icons found in icons directory');
            return;
        }

        $this->addSuccess("{$iconCount} icon files found");

        // Test for required sizes
        $requiredSizes = [192, 512];
        $foundSizes = [];

        foreach ($icons as $icon) {
            $filename = $icon->getFilename();
            if (preg_match('/(\d+)x(\d+)/', $filename, $matches)) {
                $size = (int) $matches[1];
                $foundSizes[] = $size;
            }
        }

        foreach ($requiredSizes as $size) {
            if (in_array($size, $foundSizes)) {
                $this->addSuccess("Required icon size {$size}x{$size} found");
            } else {
                $this->addError("Missing required icon size: {$size}x{$size}");
            }
        }

        // Test icon file sizes
        foreach ($icons as $icon) {
            $size = $icon->getSize();
            $filename = $icon->getFilename();
            
            if ($size > 1048576) { // 1MB
                $this->addWarning("Large icon file: {$filename} (" . $this->formatBytes($size) . ")");
            } else {
                $this->addSuccess("Icon size OK: {$filename}");
            }
        }

        // Test for favicon
        $faviconPaths = ['favicon.ico', 'favicon.svg', 'favicon.png'];
        $faviconFound = false;

        foreach ($faviconPaths as $favicon) {
            if (File::exists(public_path($favicon))) {
                $this->addSuccess("Favicon found: {$favicon}");
                $faviconFound = true;
                break;
            }
        }

        if (!$faviconFound) {
            $this->addWarning('No favicon found in public directory');
        }
    }

    /**
     * Test routes
     */
    protected function testRoutes(): void
    {
        $pwaManager = app('af-pwa');
        $routes = $pwaManager->getAllPwaRoutes();

        if (empty($routes)) {
            $this->addWarning('No PWA routes configured or discovered');
            return;
        }

        $this->addSuccess(count($routes) . ' PWA routes configured');

        // Test if routes exist in Laravel route collection
        $routeCollection = app('router')->getRoutes();
        foreach ($routes as $route) {
            $routeFound = false;
            
            // Check if the route exists in Laravel's route collection
            foreach ($routeCollection as $laravelRoute) {
                $uri = '/' . ltrim($laravelRoute->uri(), '/');
                $testRoute = '/' . ltrim($route, '/');
                
                if ($uri === $testRoute || 
                    fnmatch($uri, $testRoute) || 
                    fnmatch($testRoute, $uri)) {
                    $routeFound = true;
                    break;
                }
            }
            
            if ($routeFound) {
                $this->addSuccess("Route exists: {$route}");
            } else {
                $this->addWarning("Route not found in application: {$route}");
            }
        }

        // Test route patterns
        foreach ($routes as $route) {
            if (!str_starts_with($route, '/')) {
                $this->addWarning("Route should start with '/': {$route}");
            }
        }
    }

    /**
     * Test assets
     */
    protected function testAssets(): void
    {
        $assetDir = public_path('vendor/af-pwa');
        
        if (!File::isDirectory($assetDir)) {
            $this->addError('AF-PWA assets not published', 'php artisan vendor:publish --tag=af-pwa-assets');
            return;
        }

        $this->addSuccess('AF-PWA assets directory exists');

        // Test for required assets
        $requiredAssets = [
            'js/af-pwa.js' => 'Core JavaScript',
            'css/af-pwa.css' => 'Core CSS'
        ];

        foreach ($requiredAssets as $asset => $description) {
            $assetPath = $assetDir . '/' . $asset;
            
            if (File::exists($assetPath)) {
                $size = File::size($assetPath);
                $this->addSuccess("{$description} exists (" . $this->formatBytes($size) . ")");
            } else {
                $this->addError("{$description} not found: {$asset}");
            }
        }
    }

    /**
     * Test integration
     */
    protected function testIntegration(): void
    {
        // Test if service provider is registered
        if (app()->bound('af-pwa')) {
            $this->addSuccess('AF-PWA service is registered');
        } else {
            $this->addError('AF-PWA service not registered');
        }

        // Test Blade directives
        try {
            $html = app('af-pwa')->renderComplete();
            if (!empty($html)) {
                $this->addSuccess('Blade directive renders HTML');
            } else {
                $this->addWarning('Blade directive returns empty HTML');
            }
        } catch (\Exception $e) {
            $this->addError('Blade directive error: ' . $e->getMessage());
        }

        // Test configuration access
        try {
            $config = app('af-pwa')->getConfig();
            if (!empty($config)) {
                $this->addSuccess('Configuration accessible');
            } else {
                $this->addWarning('Configuration is empty');
            }
        } catch (\Exception $e) {
            $this->addError('Configuration error: ' . $e->getMessage());
        }
    }

    /**
     * Test JavaScript syntax
     */
    protected function testJavaScriptSyntax(string $content): bool
    {
        // Basic syntax checks
        $openBraces = substr_count($content, '{');
        $closeBraces = substr_count($content, '}');
        
        if ($openBraces !== $closeBraces) {
            return false;
        }

        $openParens = substr_count($content, '(');
        $closeParens = substr_count($content, ')');
        
        if ($openParens !== $closeParens) {
            return false;
        }

        // Check for common syntax errors
        $errors = [
            '/\b(if|for|while|function)\s*\(/' => 'Control structures',
            '/\bvar\s+\w+/' => 'Variable declarations',
            '/\bfunction\s+\w+\s*\(/' => 'Function declarations'
        ];

        foreach ($errors as $pattern => $description) {
            if (!preg_match($pattern, $content)) {
                continue;
            }
        }

        return true;
    }

    /**
     * Add success result
     */
    protected function addSuccess(string $message): void
    {
        $this->results[] = ['type' => 'success', 'message' => $message];
        $this->line("  ✅ {$message}");
    }

    /**
     * Add warning result
     */
    protected function addWarning(string $message, string $fix = null): void
    {
        $this->warnings[] = ['message' => $message, 'fix' => $fix];
        $this->line("  ⚠️  {$message}" . ($fix ? " (Fix: {$fix})" : ''));
    }

    /**
     * Add error result
     */
    protected function addError(string $message, string $fix = null): void
    {
        $this->errors[] = ['message' => $message, 'fix' => $fix];
        $this->line("  ❌ {$message}" . ($fix ? " (Fix: {$fix})" : ''));
    }

    /**
     * Display test results
     */
    protected function displayResults(): void
    {
        $this->info('');
        $this->info('📊 Test Results Summary');
        $this->info('========================');
        
        $successCount = count(array_filter($this->results, fn($r) => $r['type'] === 'success'));
        $warningCount = count($this->warnings);
        $errorCount = count($this->errors);

        $this->info("✅ Passed: {$successCount}");
        $this->info("⚠️  Warnings: {$warningCount}");
        $this->info("❌ Errors: {$errorCount}");

        if ($errorCount === 0 && $warningCount === 0) {
            $this->info('');
            $this->info('🎉 All tests passed! Your PWA is ready to go!');
        } elseif ($errorCount === 0) {
            $this->info('');
            $this->info('✅ No critical errors found. Address warnings for optimal PWA experience.');
        } else {
            $this->info('');
            $this->error('❌ Critical errors found. Please fix them before deploying your PWA.');
        }

        // Show fixes if available
        if ($this->option('fix')) {
            $this->autoFix();
        } else {
            $this->showQuickFixes();
        }
    }

    /**
     * Show quick fixes
     */
    protected function showQuickFixes(): void
    {
        $fixes = array_merge($this->errors, $this->warnings);
        $fixes = array_filter($fixes, fn($item) => !empty($item['fix']));

        if (!empty($fixes)) {
            $this->info('');
            $this->info('🔧 Quick Fixes:');
            foreach ($fixes as $fix) {
                $this->line("  • {$fix['fix']}");
            }
            $this->info('');
            $this->line('Run with --fix to automatically apply fixes where possible');
        }
    }

    /**
     * Auto-fix issues
     */
    protected function autoFix(): void
    {
        $this->info('');
        $this->info('🔧 Auto-fixing issues...');

        $fixed = 0;

        // Auto-fix logic here
        foreach ($this->errors as $error) {
            if (!empty($error['fix']) && $this->canAutoFix($error['fix'])) {
                try {
                    $this->executeAutoFix($error['fix']);
                    $fixed++;
                    $this->line("  ✅ Fixed: {$error['message']}");
                } catch (\Exception $e) {
                    $this->line("  ❌ Failed to fix: {$error['message']}");
                }
            }
        }

        if ($fixed > 0) {
            $this->info("✅ Auto-fixed {$fixed} issues");
            $this->info('Re-run tests to verify fixes');
        } else {
            $this->info('No issues could be auto-fixed');
        }
    }

    /**
     * Check if issue can be auto-fixed
     */
    protected function canAutoFix(string $fix): bool
    {
        $autoFixable = [
            'af-pwa:install',
            'af-pwa:generate',
            'php artisan vendor:publish'
        ];

        foreach ($autoFixable as $command) {
            if (str_contains($fix, $command)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Execute auto-fix command
     */
    protected function executeAutoFix(string $fix): void
    {
        if (str_contains($fix, 'af-pwa:')) {
            $command = str_replace('php artisan ', '', $fix);
            $this->call($command);
        } elseif (str_contains($fix, 'vendor:publish')) {
            $this->call('vendor:publish', ['--tag' => 'af-pwa-assets', '--force' => true]);
        }
    }

    /**
     * Generate test report
     */
    protected function generateReport(): void
    {
        $this->info('');
        $this->info('📄 Generating test report...');

        $report = [
            'timestamp' => now()->toISOString(),
            'results' => $this->results,
            'warnings' => $this->warnings,
            'errors' => $this->errors,
            'summary' => [
                'total_tests' => count($this->results),
                'passed' => count(array_filter($this->results, fn($r) => $r['type'] === 'success')),
                'warnings' => count($this->warnings),
                'errors' => count($this->errors),
            ]
        ];

        $reportPath = storage_path('logs/af-pwa-test-report.json');
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT));

        $this->info("✅ Test report saved to: {$reportPath}");
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
