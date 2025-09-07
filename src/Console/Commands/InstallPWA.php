<?php

namespace ArtflowStudio\AfPwa\Console\Commands;

use Illuminate\Console\Command;
use ArtflowStudio\AfPwa\Services\PWAService;
use ArtflowStudio\AfPwa\Services\SessionManager;

class InstallPWA extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'af-pwa:install 
                          {--force : Force installation even if files exist}
                          {--config-only : Only publish configuration files}
                          {--assets-only : Only install PWA assets}';

    /**
     * The console command description.
     */
    protected $description = 'Install PWA package assets and configuration';

    protected PWAService $pwaService;
    protected SessionManager $sessionManager;

    public function __construct(PWAService $pwaService, SessionManager $sessionManager)
    {
        parent::__construct();
        $this->pwaService = $pwaService;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Installing AF-PWA Package...');

        try {
            // Publish configuration if needed
            if ($this->option('config-only') || !$this->option('assets-only')) {
                $this->publishConfiguration();
            }

            // Install PWA assets if needed
            if ($this->option('assets-only') || !$this->option('config-only')) {
                $this->installAssets();
            }

            $this->info('✅ AF-PWA Package installed successfully!');
            $this->displayNextSteps();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Installation failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Publish configuration files
     */
    protected function publishConfiguration(): void
    {
        $this->info('📝 Publishing configuration...');

        $configExists = file_exists(config_path('af-pwa.php'));
        
        if ($configExists && !$this->option('force')) {
            if (!$this->confirm('Configuration file already exists. Do you want to overwrite it?')) {
                $this->warn('⚠️  Skipping configuration publishing.');
                return;
            }
        }

        $this->call('vendor:publish', [
            '--provider' => 'ArtflowStudio\AfPwa\PWAServiceProvider',
            '--tag' => 'af-pwa-config',
            '--force' => $this->option('force'),
        ]);

        $this->info('✅ Configuration published to config/af-pwa.php');
    }

    /**
     * Install PWA assets
     */
    protected function installAssets(): void
    {
        $this->info('🎨 Installing PWA assets...');

        $manifestExists = file_exists(public_path('manifest.json'));
        $swExists = file_exists(public_path('sw.js'));

        if (($manifestExists || $swExists) && !$this->option('force')) {
            if (!$this->confirm('PWA files already exist. Do you want to overwrite them?')) {
                $this->warn('⚠️  Skipping asset installation.');
                return;
            }
        }

        // Install PWA assets using service
        $results = $this->pwaService->install();

        foreach ($results as $type => $message) {
            $this->info("✅ {$message}");
        }

        // Publish view files
        $this->call('vendor:publish', [
            '--provider' => 'ArtflowStudio\AfPwa\PWAServiceProvider',
            '--tag' => 'af-pwa-views',
            '--force' => $this->option('force'),
        ]);

        // Publish assets
        $this->call('vendor:publish', [
            '--provider' => 'ArtflowStudio\AfPwa\PWAServiceProvider',
            '--tag' => 'af-pwa-assets',
            '--force' => $this->option('force'),
        ]);

        $this->createIconPlaceholders();
    }

    /**
     * Create icon placeholders
     */
    protected function createIconPlaceholders(): void
    {
        $this->info('🖼️  Creating icon placeholders...');

        $iconDir = public_path('icons');
        if (!is_dir($iconDir)) {
            mkdir($iconDir, 0755, true);
        }

        $sizes = [16, 32, 72, 96, 128, 144, 152, 192, 384, 512];
        
        foreach ($sizes as $size) {
            $iconPath = $iconDir . "/icon-{$size}x{$size}.png";
            
            if (!file_exists($iconPath)) {
                // Create a simple placeholder icon (you might want to use a proper image library)
                $this->createPlaceholderIcon($iconPath, $size);
            }
        }

        $this->info('✅ Icon placeholders created');
        $this->warn('⚠️  Please replace placeholder icons with your actual app icons');
    }

    /**
     * Create a placeholder icon
     */
    protected function createPlaceholderIcon(string $path, int $size): void
    {
        // Create a simple SVG placeholder and convert to PNG if needed
        // For now, just create an empty file as placeholder
        touch($path);
    }

    /**
     * Display next steps
     */
    protected function displayNextSteps(): void
    {
        $this->info('');
        $this->info('🎯 Next Steps:');
        $this->info('');
        $this->info('1. Update your app layout to include PWA meta tags:');
        $this->line('   Add @pwaMeta in your <head> section');
        $this->info('');
        $this->info('2. Add service worker registration:');
        $this->line('   Add @pwaServiceWorker before closing </body> tag');
        $this->info('');
        $this->info('3. Configure your routes in config/af-pwa.php:');
        $this->line('   Set allowed routes for PWA access');
        $this->info('');
        $this->info('4. Replace placeholder icons in public/icons/ with your app icons');
        $this->info('');
        $this->info('5. Test your PWA:');
        $this->line('   Run: php artisan af-pwa:test');
        $this->info('');
        $this->info('6. Generate optimized PWA files:');
        $this->line('   Run: php artisan af-pwa:generate');
        $this->info('');
        $this->info('📚 Documentation: https://github.com/artflow-studio/af-pwa');
    }
}
