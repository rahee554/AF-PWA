<?php

namespace ArtflowStudio\AfPwa;

use ArtflowStudio\AfPwa\Console\Commands\GeneratePWA;
use ArtflowStudio\AfPwa\Console\Commands\InstallPWA;
use ArtflowStudio\AfPwa\Http\Middleware\PWAMiddleware;
use ArtflowStudio\AfPwa\Services\PWAService;
use ArtflowStudio\AfPwa\Services\SessionManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PWAServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/af-pwa.php',
            'af-pwa'
        );

        // Register services
        $this->app->singleton(PWAService::class);
        $this->app->singleton(SessionManager::class);

        // Register facade
        $this->app->bind('af-pwa', function ($app) {
            return $app->make(PWAService::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/af-pwa.php' => config_path('af-pwa.php'),
        ], 'af-pwa-config');

        // Publish assets
        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('vendor/af-pwa'),
        ], 'af-pwa-assets');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/af-pwa'),
        ], 'af-pwa-views');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'af-pwa');

        // Register routes
        $this->registerRoutes();

        // Register middleware
        $this->registerMiddleware();

        // Register Blade directives
        $this->registerBladeDirectives();

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallPWA::class,
                GeneratePWA::class,
            ]);
        }
    }

    /**
     * Register package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => config('af-pwa.route_prefix', 'af-pwa'),
            'middleware' => config('af-pwa.route_middleware', ['web']),
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    /**
     * Register middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('af-pwa', PWAMiddleware::class);
    }

    /**
     * Register Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        // PWA Meta Tags
        Blade::directive('pwaMeta', function () {
            return "<?php echo app('af-pwa')->renderMetaTags(); ?>";
        });

        // PWA Manifest Link
        Blade::directive('pwaManifest', function () {
            return "<?php echo app('af-pwa')->renderManifestLink(); ?>";
        });

        // Service Worker Registration
        Blade::directive('pwaServiceWorker', function ($expression) {
            return "<?php echo app('af-pwa')->renderServiceWorkerScript({$expression}); ?>";
        });

        // PWA Install Button
        Blade::directive('pwaInstallButton', function ($expression) {
            $expression = $expression ?: '[]';
            return "<?php echo view('af-pwa::install-button', {$expression})->render(); ?>";
        });

        // PWA Status Indicator
        Blade::directive('pwaStatus', function ($expression) {
            $expression = $expression ?: '[]';
            return "<?php echo view('af-pwa::status-indicator', {$expression})->render(); ?>";
        });

        // Session Manager Script
        Blade::directive('pwaSessionManager', function ($expression) {
            return "<?php echo app('af-pwa')->renderSessionManagerScript({$expression}); ?>";
        });
    }
}
