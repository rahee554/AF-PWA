<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | The name of your PWA application. This will be used in the manifest.json
    | and displayed when users install the app on their devices.
    |
    */
    'name' => env('PWA_NAME', config('app.name')),

    /*
    |--------------------------------------------------------------------------
    | Short Name
    |--------------------------------------------------------------------------
    |
    | A shorter version of your app name, used when there's limited space
    | to display the full name (e.g., on device home screens).
    |
    */
    'short_name' => env('PWA_SHORT_NAME', 'PWA'),

    /*
    |--------------------------------------------------------------------------
    | Application Description
    |--------------------------------------------------------------------------
    |
    | A brief description of your PWA application that will appear in
    | the manifest.json and app stores.
    |
    */
    'description' => env('PWA_DESCRIPTION', 'A powerful Progressive Web Application'),

    /*
    |--------------------------------------------------------------------------
    | PWA Routes
    |--------------------------------------------------------------------------
    |
    | Define which routes should be handled by the PWA. Only these routes
    | will be cached and available offline. Other routes will open in browser.
    | 
    | Supports wildcards (*) for route patterns:
    | - '/admin*' matches /admin, /admin/dashboard, /admin/users, etc.
    | - '/user/*' matches /user/profile, /user/settings, etc.
    | - '/' matches homepage only
    |
    | Leave empty to auto-discover routes from your application
    |
    */
    'pwa_routes' => [
        '/', // Homepage
        // Add your application-specific routes here
        // Examples:
        // '/dashboard*',
        // '/profile*', 
        // '/settings*',
        // '/auth/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Discover Routes
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will automatically discover and cache
    | common Laravel routes from your application.
    |
    */
    'auto_discover_routes' => env('PWA_AUTO_DISCOVER_ROUTES', true),

    /*
    |--------------------------------------------------------------------------
    | Route Discovery Patterns
    |--------------------------------------------------------------------------
    |
    | Patterns to automatically discover routes when auto_discover_routes is enabled.
    | These are common Laravel application patterns.
    |
    */
    'route_discovery_patterns' => [
        '/dashboard*',
        '/profile*',
        '/settings*',
        '/home*',
        '/auth/*',
        '/login*',
        '/register*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Start URL
    |--------------------------------------------------------------------------
    |
    | The URL that loads when a user launches your PWA from their device.
    | This should typically be the main entry point of your application.
    |
    */
    'start_url' => '/',

    /*
    |--------------------------------------------------------------------------
    | Display Mode
    |--------------------------------------------------------------------------
    |
    | How the PWA should be displayed when launched. Options:
    | 'fullscreen', 'standalone', 'minimal-ui', 'browser'
    |
    */
    'display' => 'standalone',

    /*
    |--------------------------------------------------------------------------
    | Orientation
    |--------------------------------------------------------------------------
    |
    | The default orientation for your PWA. Options:
    | 'any', 'natural', 'landscape', 'portrait', 'portrait-primary', etc.
    |
    */
    'orientation' => 'portrait-primary',

    /*
    |--------------------------------------------------------------------------
    | Theme Color
    |--------------------------------------------------------------------------
    |
    | The theme color affects how the OS displays your app.
    | This includes the color of the title bar, status bar, etc.
    |
    */
    'theme_color' => '#000000',

    /*
    |--------------------------------------------------------------------------
    | Background Color
    |--------------------------------------------------------------------------
    |
    | The background color shown behind your app's content before
    | the stylesheet loads. Should match your app's background.
    |
    */
    'background_color' => '#ffffff',

    /*
    |--------------------------------------------------------------------------
    | Application Scope
    |--------------------------------------------------------------------------
    |
    | Defines the navigation scope of your PWA. URLs outside this scope
    | will open in the browser instead of the PWA.
    |
    */
    'scope' => '/',

    /*
    |--------------------------------------------------------------------------
    | Language and Direction
    |--------------------------------------------------------------------------
    |
    | The primary language and text direction of your PWA.
    |
    */
    'lang' => 'en',
    'dir' => 'ltr',

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    |
    | Categories that describe your PWA for app stores and catalogs.
    |
    */
    'categories' => ['business', 'productivity'],

    /*
    |--------------------------------------------------------------------------
    | Icons
    |--------------------------------------------------------------------------
    |
    | Define the icons for your PWA. These will be used on home screens,
    | app launchers, and in various contexts across different platforms.
    |
    */
    'icons' => [
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
        ],
        [
            'src' => '/vendor/artflow-studio/pwa/icons/icon-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => '/vendor/artflow-studio/pwa/icons/icon-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => '/vendor/artflow-studio/pwa/icons/maskable-icon-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'maskable'
        ],
        [
            'src' => '/vendor/artflow-studio/pwa/icons/maskable-icon-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'maskable'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Shortcuts
    |--------------------------------------------------------------------------
    |
    | App shortcuts that appear in context menus when users right-click
    | or long-press your PWA icon.
    |
    */
    'shortcuts' => [
        // Add application-specific shortcuts here
        // Example:
        // [
        //     'name' => 'Dashboard',
        //     'short_name' => 'Dashboard',
        //     'description' => 'Access your dashboard',
        //     'url' => '/dashboard',
        //     'icons' => [
        //         [
        //             'src' => '/favicon.svg',
        //             'sizes' => 'any',
        //             'type' => 'image/svg+xml'
        //         ]
        //     ]
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching strategies and cache versioning for your PWA.
    |
    */
    'cache_version' => env('PWA_CACHE_VERSION', 'v1'),

    'cache_strategies' => [
        'assets' => 'cache-first',    // Static assets (JS, CSS, images)
        'pages' => 'network-first',   // HTML pages
        'api' => 'network-first',     // API requests
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Headers
    |--------------------------------------------------------------------------
    |
    | Configure HTTP cache headers for PWA files. These headers control
    | how browsers and CDNs cache your PWA assets.
    |
    */
    'cache_headers' => [
        'manifest' => [
            'Cache-Control' => 'public, max-age=86400, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ],
        'service_worker' => [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Service-Worker-Allowed' => '/',
        ],
        'icons' => [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ],
        'assets' => [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Network Timeouts
    |--------------------------------------------------------------------------
    |
    | Configure timeout values for different types of network requests.
    | Values are in milliseconds.
    |
    */
    'network_timeout' => 10000,  // 10 seconds
    'api_timeout' => 10000,      // 10 seconds  
    'page_timeout' => 15000,     // 15 seconds
    'post_timeout' => 30000,     // 30 seconds

    /*
    |--------------------------------------------------------------------------
    | Asset Patterns
    |--------------------------------------------------------------------------
    |
    | URL patterns that should be treated as static assets and cached
    | with the assets cache strategy.
    |
    */
    'asset_patterns' => [
        '/assets/',
        '/build/',
        '/vendor/',
        '/css/',
        '/js/',
        '/images/',
        '/fonts/',
        '/media/',
        '/storage/',
        '/uploads/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cacheable File Extensions
    |--------------------------------------------------------------------------
    |
    | File extensions that should be cached by the service worker.
    | These files will be stored locally for offline access.
    |
    */
    'cacheable_extensions' => [
        // Stylesheets
        'css',
        
        // JavaScript
        'js', 'mjs', 'ts',
        
        // Images
        'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp', 'tiff',
        
        // Fonts
        'woff', 'woff2', 'ttf', 'otf', 'eot',
        
        // Documents
        'pdf', 'doc', 'docx', 'txt',
        
        // Audio/Video (smaller files only)
        'mp3', 'wav', 'mp4', 'webm',
        
        // Data formats
        'json', 'xml', 'csv',
        
        // Web manifest and service worker
        'manifest', 'webmanifest',
        
        // Templates
        'html', 'htm',
    ],

    /*
    |--------------------------------------------------------------------------
    | Non-Cacheable Extensions
    |--------------------------------------------------------------------------
    |
    | File extensions that should NOT be cached (override cacheable_extensions).
    | Useful for large files or frequently changing content.
    |
    */
    'non_cacheable_extensions' => [
        // Large video files
        'avi', 'mkv', 'mov', 'wmv', 'flv',
        
        // Large audio files
        'flac', 'aac', 'm4a',
        
        // Archives (can be large)
        'zip', 'rar', '7z', 'tar', 'gz',
        
        // Executables
        'exe', 'msi', 'dmg', 'deb', 'rpm',
        
        // Database files
        'db', 'sqlite', 'sql',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Patterns
    |--------------------------------------------------------------------------
    |
    | URL patterns that should be treated as API requests and cached
    | with the API cache strategy.
    |
    */
    'api_patterns' => [
        '/api/',
        '/livewire/',
        '/graphql/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Assets to Cache
    |--------------------------------------------------------------------------
    |
    | Specific assets that should be cached immediately when the service
    | worker is installed. Include critical assets for offline functionality.
    |
    */
    'cache_assets' => [
        '/',
        '/css/app.css',
        '/js/app.js',
        '/manifest.json',
        '/offline.html',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Size Limits
    |--------------------------------------------------------------------------
    |
    | Configure maximum cache sizes to prevent excessive storage usage.
    | Values in bytes (default: 50MB for assets, 10MB for pages).
    |
    */
    'cache_limits' => [
        'assets_max_size' => env('PWA_ASSETS_CACHE_MAX_SIZE', 52428800), // 50MB
        'pages_max_size' => env('PWA_PAGES_CACHE_MAX_SIZE', 10485760),   // 10MB
        'max_entries' => env('PWA_CACHE_MAX_ENTRIES', 100),              // Maximum cached items
        'max_age_seconds' => env('PWA_CACHE_MAX_AGE', 86400 * 30),       // 30 days
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimizations
    |--------------------------------------------------------------------------
    |
    | Enable various performance optimizations for the PWA.
    |
    */
    'performance' => [
        'preload_critical_assets' => env('PWA_PRELOAD_CRITICAL', true),
        'lazy_load_images' => env('PWA_LAZY_LOAD_IMAGES', true),
        'compress_responses' => env('PWA_COMPRESS_RESPONSES', true),
        'minify_html' => env('PWA_MINIFY_HTML', false),
        'prefetch_links' => env('PWA_PREFETCH_LINKS', true),
        'resource_hints' => env('PWA_RESOURCE_HINTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific PWA features.
    |
    */
    'enable_notifications' => env('PWA_ENABLE_NOTIFICATIONS', false),
    'enable_background_sync' => env('PWA_ENABLE_BACKGROUND_SYNC', false),
    'show_install_prompt' => env('PWA_SHOW_INSTALL_PROMPT', false),
    'show_network_status' => env('PWA_SHOW_NETWORK_STATUS', true),
    'auto_refresh_on_update' => env('PWA_AUTO_REFRESH_ON_UPDATE', true),

    /*
    |--------------------------------------------------------------------------
    | Mobile PWA Features
    |--------------------------------------------------------------------------
    |
    | Features specifically for mobile PWA installation and usage.
    |
    */
    'mobile_features' => [
        'fullscreen' => env('PWA_MOBILE_FULLSCREEN', true),
        'orientation_lock' => env('PWA_ORIENTATION_LOCK', false),
        'status_bar_style' => env('PWA_STATUS_BAR_STYLE', 'black-translucent'),
        'splash_screen' => env('PWA_SPLASH_SCREEN', true),
        'home_screen_icon' => env('PWA_HOME_SCREEN_ICON', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Update Check Interval
    |--------------------------------------------------------------------------
    |
    | How often to check for service worker updates (in milliseconds).
    | Default is 1 hour (3600000 ms).
    |
    */
    'update_check_interval' => env('PWA_UPDATE_CHECK_INTERVAL', 3600000),

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the routes used by the PWA package.
    |
    */
    'route_prefix' => 'af-pwa',
    'route_middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Configure session handling for PWA requests.
    |
    */
    'session_refresh_threshold' => env('PWA_SESSION_REFRESH_THRESHOLD', 300000), // 5 minutes
    'csrf_refresh_threshold' => env('PWA_CSRF_REFRESH_THRESHOLD', 600000), // 10 minutes

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configure how the PWA handles different types of errors.
    |
    */
    'error_handling' => [
        'csrf_error' => [
            'auto_refresh' => true,           // Auto-refresh CSRF token on 419 errors
            'max_retries' => 3,              // Maximum retry attempts
            'show_notification' => true,      // Show user notification
            'message' => 'Session expired. Refreshing...',
        ],
        'session_expired' => [
            'redirect_to_login' => true,      // Redirect to login on session expiry
            'login_route' => '/login',        // Login route (auto-detected if null)
            'show_notification' => true,      // Show user notification
            'message' => 'Your session has expired. Please log in again.',
        ],
        'network_error' => [
            'show_offline_page' => true,      // Show offline page on network errors
            'retry_button' => true,           // Show retry button
            'auto_retry' => false,            // Auto-retry failed requests
            'message' => 'Connection lost. Please check your internet.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Offline Configuration
    |--------------------------------------------------------------------------
    |
    | Configure offline page behavior and messaging.
    |
    */
    'offline' => [
        'title' => 'You\'re Offline',
        'message' => 'It looks like you\'re offline. Some features may not be available.',
        'button_text' => 'Try Again',
        'show_network_status' => true,
        'show_retry_button' => true,
        'custom_css' => null,
        'custom_js' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where PWA assets are served from. By default, assets are
    | served from the vendor directory for easy maintenance.
    |
    */
    'asset_url' => env('PWA_ASSET_URL', '/vendor/artflow-studio/pwa'),
    'use_vendor_assets' => env('PWA_USE_VENDOR_ASSETS', true),
    'asset_version' => env('PWA_ASSET_VERSION', '1.0.0'),
    
    /*
    |--------------------------------------------------------------------------
    | Icon Configuration
    |--------------------------------------------------------------------------
    |
    | Configure icon generation and serving. Icons can be served from
    | vendor directory or public directory.
    |
    */
    'icon_config' => [
        'serve_from_vendor' => true,          // Serve icons from vendor directory
        'fallback_to_public' => true,        // Fallback to public directory if vendor icons not found
        'auto_generate' => true,              // Auto-generate missing icons
        'source_priority' => [               // Priority order for source icons
            'public/logo.svg',
            'public/logo.png', 
            'public/favicon.svg',
            'public/favicon.png',
            'public/favicon.ico',
            'public/icon.svg',
            'public/icon.png',
        ],
    ],
    'debug' => env('PWA_DEBUG', false),
    'skip_waiting' => env('PWA_SKIP_WAITING', false),
    'claim_clients' => env('PWA_CLAIM_CLIENTS', true),
];
