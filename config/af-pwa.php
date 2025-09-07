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
        [
            'name' => 'Admin Dashboard',
            'short_name' => 'Admin',
            'description' => 'Access the admin dashboard',
            'url' => '/admin',
            'icons' => [
                [
                    'src' => '/favicon.svg',
                    'sizes' => 'any',
                    'type' => 'image/svg+xml'
                ]
            ]
        ],
        [
            'name' => 'Member Area',
            'short_name' => 'Member',
            'description' => 'Access the member area',
            'url' => '/member',
            'icons' => [
                [
                    'src' => '/favicon.svg',
                    'sizes' => 'any',
                    'type' => 'image/svg+xml'
                ]
            ]
        ],
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
    'auto_refresh_on_update' => env('PWA_AUTO_REFRESH_ON_UPDATE', false),

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
