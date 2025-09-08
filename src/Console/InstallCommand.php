<?php

namespace ArtflowStudio\AfPwa\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'af-pwa:install 
                          {--force : Force installation even if files exist}
                          {--minimal : Install minimal configuration only}';

    /**
     * The console command description.
     */
    protected $description = 'Install AF-PWA package with interactive setup';

    /**
     * Whether to use auto-discovery for routes
     */
    protected bool $useAutoDiscovery = true;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayWelcome();

        try {
            if ($this->option('minimal')) {
                $this->installMinimal();
            } else {
                $this->installInteractive();
            }

            $this->displaySuccess();
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Installation failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Display welcome message
     */
    protected function displayWelcome(): void
    {
        $this->info('');
        $this->info('🚀 AF-PWA Installation Wizard');
        $this->info('================================');
        $this->info('Transform your Laravel app into a Progressive Web Application');
        $this->info('');
    }

    /**
     * Install with interactive prompts
     */
    protected function installInteractive(): void
    {
        // Step 1: Publish configuration
        $this->publishConfig();

        // Step 2: Setup basic configuration
        $this->setupConfiguration();

        // Step 3: Generate PWA assets
        $this->generateAssets();

        // Step 4: Create icons
        $this->setupIcons();

        // Step 5: Publish assets
        $this->publishAssets();

        // Step 6: Copy PWA files to root
        $this->copyPwaFilesToRoot();
    }

    /**
     * Install minimal version
     */
    protected function installMinimal(): void
    {
        $this->info('📦 Installing minimal AF-PWA...');

        $this->publishConfig();
        $this->publishAssets();
        $this->generateBasicAssets();
        $this->copyPwaFilesToRoot();

        $this->info('✅ Minimal installation complete');
    }

    /**
     * Publish configuration file
     */
    protected function publishConfig(): void
    {
        $this->info('📝 Publishing configuration...');

        $configExists = File::exists(config_path('af-pwa.php'));
        
        if ($configExists && !$this->option('force')) {
            if (!$this->confirm('Configuration file already exists. Overwrite?', false)) {
                $this->warn('⚠️  Skipping configuration publishing');
                return;
            }
        }

        Artisan::call('vendor:publish', [
            '--provider' => 'ArtflowStudio\AfPwa\AfPwaServiceProvider',
            '--tag' => 'af-pwa-config',
            '--force' => $this->option('force'),
        ]);

        $this->info('✅ Configuration published');
    }

    /**
     * Setup configuration interactively
     */
    protected function setupConfiguration(): void
    {
        $this->info('⚙️  Configuring your PWA...');

        $appName = $this->ask('App name for PWA', config('app.name'));
        $shortName = $this->ask('Short name (12 chars max)', substr($appName, 0, 12));
        $description = $this->ask('App description', 'A powerful Progressive Web Application');
        
        // Theme colors
        $themeColor = $this->ask('Theme color (hex)', '#000000');
        $backgroundColor = $this->ask('Background color (hex)', '#ffffff');
        
        // Route configuration with auto-discovery
        $routes = $this->setupRouteConfiguration();

        // Update .env file
        $this->updateEnvFile([
            'PWA_NAME' => $appName,
            'PWA_SHORT_NAME' => $shortName,
            'PWA_DESCRIPTION' => $description,
            'PWA_THEME_COLOR' => $themeColor,
            'PWA_BACKGROUND_COLOR' => $backgroundColor,
            'PWA_AUTO_DISCOVER_ROUTES' => $this->useAutoDiscovery ? 'true' : 'false',
        ]);

        // Update config file with routes
        $this->updateConfigFile($routes);

        $this->info('✅ Configuration updated');
    }

    /**
     * Setup route configuration with auto-discovery
     */
    protected function setupRouteConfiguration(): array
    {
        $this->info('');
        $this->info('📍 PWA Routes Configuration');
        
        // Ask about auto-discovery first
        $useAutoDiscovery = $this->confirm('Use auto-discovery to find routes in your application?', true);
        $this->useAutoDiscovery = $useAutoDiscovery;
        
        if ($useAutoDiscovery) {
            $this->info('✅ Auto-discovery enabled - routes will be automatically detected');
            $this->info('Common patterns like /dashboard*, /profile*, /auth/* will be discovered');
            
            // Ask if they want to add custom routes too
            $routes = [];
            if ($this->confirm('Add custom routes in addition to auto-discovery?', false)) {
                $routes = $this->getCustomRoutes();
            }
            
            return $routes;
        }
        
        // Manual route configuration
        $this->info('Manual route configuration - specify exact routes to cache:');
        $this->info('Support wildcards (*) for route patterns:');
        $this->info('- "/admin*" matches /admin, /admin/dashboard, /admin/users, etc.');
        $this->info('- "/user/*" matches /user/profile, /user/settings, etc.');
        $this->info('');
        
        return $this->getCustomRoutes();
    }

    /**
     * Get custom routes from user
     */
    protected function getCustomRoutes(): array
    {
        $routes = ['/'];  // Always include homepage
        
        // Suggest common routes based on Laravel conventions
        $suggestions = [
            '/dashboard*' => 'Dashboard and sub-pages',
            '/profile*' => 'User profile pages', 
            '/settings*' => 'Settings pages',
            '/auth/*' => 'Authentication pages',
            '/admin*' => 'Admin area (if applicable)',
            '/member*' => 'Member area (if applicable)',
        ];
        
        foreach ($suggestions as $route => $description) {
            if ($this->confirm("Include route: {$route} ({$description})?", false)) {
                $routes[] = $route;
            }
        }
        
        // Allow custom routes
        while ($this->confirm('Add custom route?', false)) {
            $customRoute = $this->ask('Enter route (e.g., /my-page* or /exact-path)');
            if ($customRoute && !in_array($customRoute, $routes)) {
                $routes[] = $customRoute;
            }
        }
        
        return $routes;
    }

    /**
     * Generate PWA assets
     */
    protected function generateAssets(): void
    {
        $this->info('🔧 Generating PWA assets...');

        Artisan::call('af-pwa:generate', [
            '--manifest' => true,
            '--service-worker' => true,
        ]);

        $this->info('✅ PWA assets generated');
    }

    /**
     * Setup icons
     */
    protected function setupIcons(): void
    {
        $this->info('🎨 Setting up PWA icons...');

        // Check for existing icons
        $hasIcons = File::exists(public_path('favicon.svg')) || 
                   File::exists(public_path('favicon.ico')) ||
                   File::exists(public_path('logo.png'));

        if ($hasIcons) {
            if ($this->confirm('Generate PWA icons from existing favicon/logo?', true)) {
                Artisan::call('af-pwa:generate', ['--icons' => true]);
                $this->info('✅ Icons generated from existing files');
            }
        } else {
            $this->warn('⚠️  No favicon or logo found');
            
            if ($this->confirm('Create placeholder icons?', true)) {
                $this->createPlaceholderIcons();
                $this->info('✅ Placeholder icons created');
                $this->warn('📝 Remember to replace placeholders with your actual icons');
            }
        }
    }

    /**
     * Copy PWA files to root level
     */
    protected function copyPwaFilesToRoot(): void
    {
        $this->info('📁 Copying PWA files to root level...');

        // Generate fresh PWA files
        $manager = app('af-pwa');
        
        // Copy manifest.json to root
        $manifestContent = $manager->generateManifest();
        File::put(public_path('manifest.json'), json_encode($manifestContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        // Copy service worker to root
        $swContent = $manager->generateServiceWorker();
        File::put(public_path('sw.js'), $swContent);
        
        // Copy offline page to root
        $offlineContent = $manager->generateOfflinePage();
        File::put(public_path('offline.html'), $offlineContent);

        $this->info('✅ PWA files copied to root:');
        $this->line('   • manifest.json');
        $this->line('   • sw.js');
        $this->line('   • offline.html');
    }

    /**
     * Publish package assets
     */
    protected function publishAssets(): void
    {
        $this->info('📦 Publishing package assets...');

        Artisan::call('vendor:publish', [
            '--provider' => 'ArtflowStudio\AfPwa\AfPwaServiceProvider',
            '--tag' => 'af-pwa-assets',
            '--force' => $this->option('force'),
        ]);

        $this->info('✅ Assets published');
    }

    /**
     * Generate basic assets for minimal install
     */
    protected function generateBasicAssets(): void
    {
        Artisan::call('af-pwa:generate');
        $this->createPlaceholderIcons();
    }

    /**
     * Create placeholder icons
     */
    protected function createPlaceholderIcons(): void
    {
        // Create icons directly in vendor publish directory
        $iconDir = public_path('vendor/artflow-studio/pwa/icons');
        
        if (!File::isDirectory($iconDir)) {
            File::makeDirectory($iconDir, 0755, true);
        }

        $sizes = [16, 32, 72, 96, 128, 144, 152, 192, 384, 512];
        
        foreach ($sizes as $size) {
            $iconPath = $iconDir . "/icon-{$size}x{$size}.png";
            $this->createPlaceholderIcon($iconPath, $size);
        }

        // Create maskable icons
        $this->createMaskableIcon($iconDir . "/maskable-icon-192x192.png", 192);
        $this->createMaskableIcon($iconDir . "/maskable-icon-512x512.png", 512);
        
        // Only create favicon.ico in public root
        $this->createFaviconIco();
    }

    /**
     * Create a single placeholder icon
     */
    protected function createPlaceholderIcon(string $path, int $size): void
    {
        $svg = "<?xml version='1.0' encoding='UTF-8'?>
<svg width='{$size}' height='{$size}' viewBox='0 0 {$size} {$size}' xmlns='http://www.w3.org/2000/svg'>
  <defs>
    <linearGradient id='grad' x1='0%' y1='0%' x2='100%' y2='100%'>
      <stop offset='0%' style='stop-color:#007bff;stop-opacity:1' />
      <stop offset='100%' style='stop-color:#0056b3;stop-opacity:1' />
    </linearGradient>
  </defs>
  <rect width='{$size}' height='{$size}' fill='url(#grad)' rx='" . ($size * 0.1) . "'/>
  <text x='50%' y='50%' font-family='Arial, sans-serif' font-size='" . ($size * 0.2) . "' font-weight='bold' fill='white' text-anchor='middle' dominant-baseline='middle'>PWA</text>
</svg>";

        File::put($path, $svg);
    }

    /**
     * Create maskable icon
     */
    protected function createMaskableIcon(string $path, int $size): void
    {
        $safeZone = $size * 0.8; // 80% safe zone for maskable icons
        $offset = ($size - $safeZone) / 2;
        
        $svg = "<?xml version='1.0' encoding='UTF-8'?>
<svg width='{$size}' height='{$size}' viewBox='0 0 {$size} {$size}' xmlns='http://www.w3.org/2000/svg'>
  <defs>
    <linearGradient id='maskableGrad' x1='0%' y1='0%' x2='100%' y2='100%'>
      <stop offset='0%' style='stop-color:#007bff;stop-opacity:1' />
      <stop offset='100%' style='stop-color:#0056b3;stop-opacity:1' />
    </linearGradient>
  </defs>
  <rect width='{$size}' height='{$size}' fill='url(#maskableGrad)'/>
  <rect x='{$offset}' y='{$offset}' width='{$safeZone}' height='{$safeZone}' fill='white' fill-opacity='0.9' rx='" . ($safeZone * 0.1) . "'/>
  <text x='50%' y='50%' font-family='Arial, sans-serif' font-size='" . ($safeZone * 0.2) . "' font-weight='bold' fill='#007bff' text-anchor='middle' dominant-baseline='middle'>PWA</text>
</svg>";

        File::put($path, $svg);
    }

    /**
     * Create favicon.ico in public root
     */
    protected function createFaviconIco(): void
    {
        $faviconPath = public_path('favicon.ico');
        
        // Only create if it doesn't exist
        if (!File::exists($faviconPath)) {
            // Create a simple SVG favicon
            $favicon = "<?xml version='1.0' encoding='UTF-8'?>
<svg width='32' height='32' viewBox='0 0 32 32' xmlns='http://www.w3.org/2000/svg'>
  <rect width='32' height='32' fill='#007bff' rx='3'/>
  <text x='50%' y='50%' font-family='Arial, sans-serif' font-size='12' font-weight='bold' fill='white' text-anchor='middle' dominant-baseline='middle'>P</text>
</svg>";
            
            File::put(public_path('favicon.svg'), $favicon);
            
            // Copy SVG as ICO for compatibility
            File::copy(public_path('favicon.svg'), $faviconPath);
        }
    }

    /**
     * Update .env file
     */
    protected function updateEnvFile(array $values): void
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            return;
        }

        $envContent = File::get($envPath);

        foreach ($values as $key => $value) {
            $value = is_string($value) ? '"' . $value . '"' : $value;
            
            if (strpos($envContent, $key . '=') !== false) {
                $envContent = preg_replace(
                    '/^' . preg_quote($key) . '=.*$/m',
                    $key . '=' . $value,
                    $envContent
                );
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);
    }

    /**
     * Update config file with routes
     */
    protected function updateConfigFile(array $routes): void
    {
        $configPath = config_path('af-pwa.php');
        
        if (!File::exists($configPath)) {
            return;
        }

        $configContent = File::get($configPath);
        
        $routesString = "[\n";
        foreach ($routes as $route) {
            $routesString .= "        '{$route}',\n";
        }
        $routesString .= "    ]";

        $configContent = preg_replace(
            "/('pwa_routes'\s*=>\s*)\[[^\]]*\]/s",
            "$1{$routesString}",
            $configContent
        );

        File::put($configPath, $configContent);
    }

    /**
     * Display success message
     */
    protected function displaySuccess(): void
    {
        $this->info('');
        $this->info('🎉 AF-PWA Installation Complete!');
        $this->info('');
        $this->info('📋 Implementation Guide:');
        $this->info('');
        $this->info('1. 🎯 Add PWA to your Blade template:');
        $this->line('   Add this directive to your main layout (app.blade.php):');
        $this->line('');
        $this->line('   <head>');
        $this->line('     <!-- Your existing head content -->');
        $this->info('     @AFpwa');
        $this->line('   </head>');
        $this->info('');
        $this->info('2. 🎨 Available Blade Directives:');
        $this->line('   @AFpwa              - Complete PWA setup (recommended)');
        $this->line('   @AFpwaStyles        - PWA CSS only');
        $this->line('   @AFpwaScripts       - PWA JavaScript only');
        $this->line('   @AFpwaManifest      - Manifest link only');
        $this->info('');
        $this->info('3. 📱 Files Created:');
        $this->line('   ✅ manifest.json    - PWA manifest (root level)');
        $this->line('   ✅ sw.js           - Service worker (root level)');
        $this->line('   ✅ offline.html    - Offline page (root level)');
        $this->line('   ✅ config/af-pwa.php - Configuration file');
        $this->info('');
        $this->info('4. 🧪 Test Your Setup:');
        $this->line('   php artisan af-pwa:test     - Run comprehensive tests');
        $this->line('   php artisan af-pwa:health   - Check PWA health');
        $this->info('');
        $this->info('5. 🔄 Manage Your PWA:');
        $this->line('   php artisan af-pwa:refresh  - Refresh PWA cache & files');
        $this->line('   php artisan af-pwa:generate - Regenerate PWA assets');
        $this->info('');
        $this->info('6. ⚙️  Customize Your PWA:');
        $this->line('   • Edit config/af-pwa.php for settings');
        $this->line('   • Update .env variables (PWA_NAME, PWA_THEME_COLOR, etc.)');
        $this->line('   • Replace icons in vendor/artflow-studio/af-pwa/public/icons/');
        $this->info('');
        $this->info('🚀 Quick Start:');
        $this->line('   1. Add @AFpwa to your layout');
        $this->line('   2. Run: php artisan af-pwa:test');
        $this->line('   3. Visit your site and install the PWA!');
        $this->info('');
        $this->info('� Documentation: vendor/artflow-studio/af-pwa/README.md');
        $this->info('');
    }
}
