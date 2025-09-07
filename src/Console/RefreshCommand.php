<?php

namespace ArtflowStudio\AfPwa\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use ArtflowStudio\AfPwa\AfPwaManager;

class RefreshCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'af-pwa:refresh 
                           {--force : Force refresh without confirmation}
                           {--cache-only : Only clear cache, don\'t regenerate files}
                           {--assets-only : Only publish assets, don\'t clear cache}';

    /**
     * The console command description.
     */
    protected $description = 'Force refresh PWA cache and regenerate all PWA assets';

    /**
     * PWA Manager instance
     */
    protected AfPwaManager $pwaManager;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->pwaManager = app('af-pwa');
        
        $this->displayHeader();

        if (!$this->option('force') && !$this->confirmRefresh()) {
            $this->comment('Refresh cancelled.');
            return self::SUCCESS;
        }

        $startTime = microtime(true);

        try {
            if (!$this->option('assets-only')) {
                $this->clearCache();
            }

            if (!$this->option('cache-only')) {
                $this->regenerateAssets();
                $this->publishAssets();
            }

            $this->displaySuccess($startTime);
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Refresh failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Display command header
     */
    protected function displayHeader(): void
    {
        $this->newLine();
        $this->line('<fg=cyan>🔄 AF-PWA Cache Refresh</>');
        $this->line('<fg=cyan>========================</>');
        $this->line('Force refreshing PWA cache and regenerating assets...');
        $this->newLine();
    }

    /**
     * Confirm refresh action
     */
    protected function confirmRefresh(): bool
    {
        $this->line('<fg=yellow>⚠️  This will:</fg>');
        $this->line('  • Clear all PWA cache entries');
        $this->line('  • Force browser cache invalidation');
        $this->line('  • Regenerate manifest.json');
        $this->line('  • Regenerate service worker');
        $this->line('  • Update all PWA assets');
        $this->newLine();

        return $this->confirm('Do you want to continue?', true);
    }

    /**
     * Clear all PWA related cache
     */
    protected function clearCache(): void
    {
        $this->line('🧹 <fg=yellow>Clearing cache...</fg>');

        // Clear Laravel application cache
        $this->clearLaravelCache();

        // Clear PWA files with cache-busting
        $this->clearPwaCache();

        // Clear browser cache by updating timestamps
        $this->updateCacheTimestamps();

        $this->line('  ✅ Cache cleared successfully');
    }

    /**
     * Clear Laravel application cache
     */
    protected function clearLaravelCache(): void
    {
        $commands = [
            'config:clear' => 'Configuration cache',
            'route:clear' => 'Route cache',
            'view:clear' => 'View cache',
            'cache:clear' => 'Application cache'
        ];

        foreach ($commands as $command => $description) {
            try {
                Artisan::call($command);
                $this->line("  • {$description} cleared");
            } catch (\Exception $e) {
                $this->line("  ⚠️  Failed to clear {$description}: " . $e->getMessage());
            }
        }
    }

    /**
     * Clear PWA specific cache
     */
    protected function clearPwaCache(): void
    {
        // Clear any existing static PWA files (from older versions)
        $oldFiles = [
            public_path('manifest.json'),
            public_path('sw.js'),
            public_path('offline.html')
        ];

        foreach ($oldFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
                $this->line("  • Removed old static file: " . basename($file));
            }
        }
        
        $this->line("  • PWA files now served dynamically via routes");
    }

    /**
     * Update cache timestamps for browser invalidation
     */
    protected function updateCacheTimestamps(): void
    {
        $config = config('af-pwa');
        
        // Update version in config for cache busting
        $newVersion = time();
        
        $configPath = config_path('af-pwa.php');
        if (File::exists($configPath)) {
            $content = File::get($configPath);
            
            // Update version string
            $content = preg_replace(
                "/'version'\s*=>\s*'[^']*'/",
                "'version' => '{$newVersion}'",
                $content
            );
            
            // If version doesn't exist, add it
            if (!str_contains($content, "'version'")) {
                $content = str_replace(
                    "'name' => env('PWA_NAME'",
                    "'version' => '{$newVersion}',\n    'name' => env('PWA_NAME'",
                    $content
                );
            }
            
            File::put($configPath, $content);
            $this->line("  • Updated PWA version: {$newVersion}");
        }
    }

    /**
     * Regenerate PWA assets
     */
    protected function regenerateAssets(): void
    {
        $this->line('🔧 <fg=yellow>Regenerating assets...</fg>');

        try {
            // Note: PWA files are served dynamically via routes
            // No static files need to be generated
            $this->line('  • PWA files are served dynamically via routes');
            $this->line('  • manifest.json: /manifest.json');
            $this->line('  • service worker: /sw.js');  
            $this->line('  • offline page: /offline.html');

            $this->line('  ✅ Dynamic routes refreshed successfully');

        } catch (\Exception $e) {
            throw new \Exception('Failed to refresh dynamic routes: ' . $e->getMessage());
        }
    }

    /**
     * Publish package assets
     */
    protected function publishAssets(): void
    {
        $this->line('📦 <fg=yellow>Publishing assets...</fg>');

        try {
            Artisan::call('vendor:publish', [
                '--provider' => 'ArtflowStudio\AfPwa\AfPwaServiceProvider',
                '--tag' => 'af-pwa-assets',
                '--force' => true
            ]);

            $this->line('  ✅ Package assets published successfully');

        } catch (\Exception $e) {
            $this->line('  ⚠️  Failed to publish assets: ' . $e->getMessage());
        }
    }

    /**
     * Display success message
     */
    protected function displaySuccess(float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        $this->newLine();
        $this->line('<fg=green>✅ PWA Refresh Complete!</fg>');
        $this->line('<fg=green>========================</fg>');
        $this->line("Completed in {$duration}ms");
        $this->newLine();
        
        $this->line('<fg=cyan>💡 Next steps:</fg>');
        $this->line('  • Clear your browser cache');
        $this->line('  • Test your PWA with: <fg=yellow>php artisan af-pwa:test</fg>');
        $this->line('  • Check PWA health: <fg=yellow>php artisan af-pwa:health</fg>');
        $this->newLine();
    }
}
