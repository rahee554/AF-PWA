<?php

namespace ArtflowStudio\AfPwa;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AfPwaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/af-pwa.php', 'af-pwa'
        );

        $this->app->singleton('af-pwa', function ($app) {
            return new AfPwaManager($app['config']['af-pwa']);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/af-pwa.php' => config_path('af-pwa.php'),
        ], 'af-pwa-config');

        // Publish assets - only icons to vendor directory
        $this->publishes([
            __DIR__.'/../resources/js' => public_path('vendor/artflow-studio/pwa/js'),
            __DIR__.'/../public/css' => public_path('vendor/artflow-studio/pwa/css'),
            __DIR__.'/../public/js' => public_path('vendor/artflow-studio/pwa/js'),
        ], 'af-pwa-assets');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/af-pwa'),
        ], 'af-pwa-views');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'af-pwa');

        // Register Blade directives
        $this->registerBladeDirectives();

        // Register routes
        $this->registerRoutes();

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\InstallCommand::class,
                Console\GenerateCommand::class,
                Console\TestCommand::class,
                Console\HealthCheckCommand::class,
                Console\RefreshCommand::class,
            ]);
        }
    }

    /**
     * Register Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        // Main PWA directive - handles everything in one directive
        Blade::directive('AFpwa', function ($expression) {
            $options = $expression ?: '[]';
            return "<?php echo app('af-pwa')->renderComplete({$options}); ?>";
        });

        // Individual components (for advanced users)
        Blade::directive('AFpwaHead', function ($expression) {
            $options = $expression ?: '[]';
            return "<?php echo app('af-pwa')->renderHead({$options}); ?>";
        });

        Blade::directive('AFpwaScripts', function ($expression) {
            $options = $expression ?: '[]';
            return "<?php echo app('af-pwa')->renderScripts({$options}); ?>";
        });

        Blade::directive('AFpwaManifest', function ($expression) {
            return "<?php echo app('af-pwa')->renderManifest(); ?>";
        });

        Blade::directive('AFpwaStyles', function ($expression) {
            return "<?php echo '<link rel=\"stylesheet\" href=\"' . asset('vendor/artflow-studio/pwa/css/af-pwa.css') . '\">'; ?>";
        });
    }

    /**
     * Register package routes.
     */
    protected function registerRoutes(): void
    {
        // Register core PWA routes at root level (required for PWA functionality)
        Route::group([
            'middleware' => config('af-pwa.route_middleware', ['web']),
        ], function () {
            // Manifest route at root (required for PWA)
            Route::get('/manifest.json', function () {
                $manifest = app('af-pwa')->generateManifest();
                $etag = md5(json_encode($manifest));
                
                return response()->json($manifest)
                    ->header('Content-Type', 'application/manifest+json')
                    ->header('Cache-Control', 'public, max-age=86400, must-revalidate')
                    ->header('ETag', $etag)
                    ->header('Vary', 'Accept-Encoding')
                    ->header('X-Content-Type-Options', 'nosniff');
            })->name('af-pwa.manifest');

            // Service Worker route at root (required for PWA)
            Route::get('/sw.js', function () {
                $content = app('af-pwa')->generateServiceWorker();
                $etag = md5($content);
                
                return response($content)
                    ->header('Content-Type', 'application/javascript; charset=utf-8')
                    ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0')
                    ->header('ETag', $etag)
                    ->header('Service-Worker-Allowed', '/')
                    ->header('X-Content-Type-Options', 'nosniff');
            })->name('af-pwa.service-worker');

            // Offline page at root
            Route::get('/offline.html', function () {
                $content = app('af-pwa')->generateOfflinePage();
                $etag = md5($content);
                
                return response($content)
                    ->header('Content-Type', 'text/html; charset=utf-8')
                    ->header('Cache-Control', 'public, max-age=86400, must-revalidate')
                    ->header('ETag', $etag)
                    ->header('Vary', 'Accept-Encoding')
                    ->header('X-Content-Type-Options', 'nosniff');
            })->name('af-pwa.offline');
        });

        // Register vendor asset routes with proper cache headers
        Route::group([
            'prefix' => 'vendor/artflow-studio/pwa',
            'middleware' => config('af-pwa.route_middleware', ['web']),
        ], function () {
            // Icons with long-term caching
            Route::get('/icons/{filename}', function ($filename) {
                $path = public_path('vendor/artflow-studio/pwa/icons/' . $filename);
                
                if (!file_exists($path)) {
                    abort(404);
                }
                
                $mimeType = mime_content_type($path);
                $etag = md5_file($path);
                $lastModified = filemtime($path);
                
                return response()->file($path, [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'public, max-age=31536000, immutable',
                    'ETag' => $etag,
                    'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
                ]);
            })->name('af-pwa.icons');

            // CSS/JS assets with long-term caching  
            Route::get('/{type}/{filename}', function ($type, $filename) {
                if (!in_array($type, ['css', 'js'])) {
                    abort(404);
                }
                
                $path = public_path("vendor/artflow-studio/pwa/{$type}/" . $filename);
                
                if (!file_exists($path)) {
                    abort(404);
                }
                
                $mimeTypes = [
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                ];
                
                $etag = md5_file($path);
                $lastModified = filemtime($path);
                
                return response()->file($path, [
                    'Content-Type' => $mimeTypes[$type],
                    'Cache-Control' => 'public, max-age=31536000, immutable',
                    'ETag' => $etag,
                    'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
                ]);
            })->name('af-pwa.assets');
        });

        // Register additional routes under prefix for admin/management
        Route::group([
            'prefix' => config('af-pwa.route_prefix', 'af-pwa'),
            'middleware' => config('af-pwa.route_middleware', ['web']),
        ], function () {
            // Admin routes for viewing PWA info
            Route::get('/info', function () {
                return response()->json([
                    'name' => config('af-pwa.name'),
                    'version' => config('af-pwa.version', '1.0.0'),
                    'routes' => app('af-pwa')->getAllPwaRoutes(),
                    'health' => 'OK'
                ]);
            })->name('af-pwa.info');

            // Offline page variants
            Route::get('/offline/{type}', function ($type) {
                $offlinePage = app('af-pwa')->generateOfflinePage();
                return response($offlinePage)
                    ->header('Content-Type', 'text/html')
                    ->header('Cache-Control', 'public, max-age=3600');
            })->name('af-pwa.offline.type');
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['af-pwa'];
    }
}
