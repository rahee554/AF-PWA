# AF-PWA - Laravel Progressive Web Application Package

[![Latest Stable Version](https://img.shields.io/packagist/v/artflow-studio/af-pwa.svg)](https://packagist.org/packages/artflow-studio/af-pwa)
[![License](https://img.shields.io/packagist/l/artflow-studio/af-pwa.svg)](https://packagist.org/packages/artflow-studio/af-pwa)
[![PHP Version](https://img.shields.io/packagist/php-v/artflow-studio/af-pwa.svg)](https://packagist.org/packages/artflow-studio/af-pwa)

Transform your Laravel application into a **Production-Ready Progressive Web Application** with zero configuration complexity. AF-PWA provides everything you need to create installable, offline-capable web applications that work seamlessly across all devices and platforms.

## 🚀 Key Features

### 🎯 **One-Line Integration**
```blade
<!-- Add this single directive to your layout head -->
@AFpwa
```
That's it! Everything is configured automatically.

### 📱 **Native Install Experience** 
- **No custom install buttons** - Uses browser's native PWA install prompt
- Works seamlessly on iOS, Android, Windows, macOS, and Linux
- Automatic install badge/prompt when PWA requirements are met

### ⚡ **Production-Ready Performance**
- **Smart Caching Strategies**: Cache-first for assets, Network-first for pages/API
- **Optimized Assets**: Minified CSS/JS with proper cache headers
- **Background Sync**: Offline form submissions with automatic retry
- **Service Worker**: Advanced caching with CSRF/session handling

### 🎨 **Complete Icon Suite**
- **Auto-Generated Icons**: Creates all required PWA icon sizes (16x16 to 512x512)
- **Maskable Icons**: Platform-adaptive icons for modern devices
- **Apple Touch Icons**: Perfect iOS integration
- **SVG Support**: Vector icons with PNG fallbacks

### 🔧 **Advanced Configuration**
- **Route Auto-Discovery**: Automatically detects and caches your app routes
- **Offline Pages**: Custom offline experiences for different user types
- **Error Handling**: Robust CSRF token refresh and session management
- **Network Status**: Real-time connectivity monitoring

### 🧪 **Testing & Monitoring**
- **Health Check System**: Comprehensive PWA validation
- **Test Suite**: Automated testing for all PWA features
- **Performance Monitoring**: Cache efficiency and load time metrics
- **Playwright Integration**: Automated browser testing

## 🛠 Installation

### Prerequisites
- PHP 8.2+
- Laravel 11+
- Node.js (for asset compilation)

### Quick Install

```bash
# Install the package
composer require artflow-studio/af-pwa

# Run the interactive installer
php artisan af-pwa:install
```

The installer will:
1. 📝 Publish configuration files
2. 🎨 Generate PWA icons from your existing favicon/logo
3. ⚙️ Configure PWA settings interactively
4. 📦 Publish assets to `public/vendor/artflow-studio/pwa/`
5. 🔧 Set up routes and service worker

### Manual Installation

```bash
# Publish config
php artisan vendor:publish --tag=af-pwa-config

# Publish assets
php artisan vendor:publish --tag=af-pwa-assets

# Generate PWA files
php artisan af-pwa:generate --icons --manifest --service-worker
```

## ⚡ Quick Start

### 1. Add PWA to Your Layout

```blade
<!-- resources/views/layouts/app.blade.php -->
<head>
    <!-- Your existing head content -->
    
    <!-- Complete PWA Setup (All-in-One) -->
    @AFpwa
    
    <!-- OR use individual components -->
    @AFpwaHead      <!-- Meta tags, manifest, icons -->
    @AFpwaScripts   <!-- Service worker, PWA logic -->
    @AFpwaStyles    <!-- PWA CSS -->
</head>
```

### 2. Configure Your PWA

```bash
# Interactive configuration
php artisan af-pwa:install

# Or edit config/af-pwa.php directly
```

### 3. Test Your Setup

```bash
# Run comprehensive tests
php artisan af-pwa:test

# Check PWA health
php artisan af-pwa:health

# Test with Playwright (if available)
npx playwright test test-pwa-install.spec.js
```

## 🎛 Configuration

### Environment Variables

```env
# Basic PWA Settings
PWA_NAME="Your App Name"
PWA_SHORT_NAME="App"
PWA_DESCRIPTION="A powerful Progressive Web Application"
PWA_THEME_COLOR="#000000"
PWA_BACKGROUND_COLOR="#ffffff"

# Features
PWA_SHOW_INSTALL_PROMPT=false          # Use native browser prompt
PWA_SHOW_NETWORK_STATUS=true           # Show connectivity status
PWA_ENABLE_NOTIFICATIONS=false         # Push notifications
PWA_ENABLE_BACKGROUND_SYNC=false       # Offline form sync
PWA_AUTO_REFRESH_ON_UPDATE=false       # Auto-update behavior

# Performance
PWA_CACHE_VERSION="v1"                 # Cache versioning
PWA_UPDATE_CHECK_INTERVAL=3600000      # 1 hour in milliseconds
PWA_ASSET_URL="/vendor/artflow-studio/pwa"  # Asset serving path
```

### Advanced Configuration

```php
// config/af-pwa.php
return [
    // App Identity
    'name' => env('PWA_NAME', config('app.name')),
    'short_name' => env('PWA_SHORT_NAME', 'PWA'),
    'description' => env('PWA_DESCRIPTION', 'A Progressive Web Application'),
    
    // Visual
    'theme_color' => env('PWA_THEME_COLOR', '#000000'),
    'background_color' => env('PWA_BACKGROUND_COLOR', '#ffffff'),
    'display' => 'standalone', // fullscreen, minimal-ui, browser
    
    // Icons (auto-generated)
    'icons' => [
        // Basic icons
        ['src' => '/favicon.ico', 'sizes' => '16x16 32x32', 'type' => 'image/x-icon'],
        ['src' => '/favicon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml'],
        ['src' => '/apple-touch-icon.png', 'sizes' => '180x180', 'type' => 'image/png'],
        
        // PWA icons (auto-generated)
        ['src' => '/vendor/artflow-studio/pwa/icons/icon-192x192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => '/vendor/artflow-studio/pwa/icons/icon-512x512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        
        // Maskable icons
        ['src' => '/vendor/artflow-studio/pwa/icons/maskable-icon-192x192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
        ['src' => '/vendor/artflow-studio/pwa/icons/maskable-icon-512x512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
    ],
    
    // Routes (auto-discovered)
    'pwa_routes' => ['/'],
    'auto_discover_routes' => true,
    'route_discovery_patterns' => [
        '/dashboard*', '/profile*', '/settings*', '/auth/*'
    ],
    
    // Caching Strategy
    'cache_strategies' => [
        'assets' => 'cache-first',    // JS, CSS, Images
        'pages' => 'network-first',   // HTML pages
        'api' => 'network-first',     // API requests
    ],
    
    // Error Handling
    'error_handling' => [
        'csrf_error' => [
            'auto_refresh' => true,
            'max_retries' => 3,
            'show_notification' => true,
        ],
        'session_expired' => [
            'redirect_to_login' => true,
            'login_route' => '/login',
            'show_notification' => true,
        ],
        'network_error' => [
            'show_offline_page' => true,
            'retry_button' => true,
        ],
    ],
];
```

## 🎨 Icon Management

### Auto-Generation
AF-PWA automatically generates all required icons from your existing favicon or logo:

```bash
# Generate from existing favicon/logo
php artisan af-pwa:generate --icons

# Use specific source image
php artisan af-pwa:generate --icons --source=public/logo.png
```

**Generated Icon Sizes:**
- Standard: 16x16, 32x32, 72x72, 96x96, 128x128, 144x144, 152x152, 192x192, 384x384, 512x512
- Apple Touch: 57x57, 60x60, 72x72, 76x76, 114x114, 120x120, 144x144, 152x152, 180x180
- Maskable: 192x192, 512x512 (with safe zone)

### Custom Icons
Replace auto-generated icons by placing your custom icons in:
```
public/vendor/artflow-studio/pwa/icons/
├── icon-192x192.png
├── icon-512x512.png
├── maskable-icon-192x192.png
└── maskable-icon-512x512.png
```

## 🔧 Artisan Commands

### Installation & Setup
```bash
php artisan af-pwa:install [--force] [--minimal]
# Interactive PWA setup with configuration wizard

php artisan af-pwa:generate [--icons] [--manifest] [--service-worker]
# Generate specific PWA components
```

### Maintenance
```bash
php artisan af-pwa:refresh
# Clear cache and regenerate all PWA files

php artisan af-pwa:health
# Comprehensive PWA health check (95% score target)

php artisan af-pwa:test
# Run full test suite for PWA compliance
```

### Development
```bash
php artisan af-pwa:test --fix
# Run tests and auto-fix common issues

php artisan af-pwa:generate --source=path/to/logo.png
# Generate icons from specific source image
```

## 📊 Testing & Validation

### Health Check
```bash
php artisan af-pwa:health
```
**Checks:**
- ✅ System requirements (PHP, Laravel, extensions)
- ✅ PWA files (manifest, service worker, offline page)
- ✅ Performance (file sizes, cache headers)
- ✅ Accessibility (app name, icons, colors)
- ✅ Icons (all sizes, maskable icons)

**Health Score Breakdown:**
- 100%: Perfect PWA implementation
- 95%+: Production ready
- 80%+: Good, minor improvements needed
- <80%: Needs attention

### Test Suite
```bash
php artisan af-pwa:test
```
**Validates:**
- Configuration completeness
- Manifest.json validity and accessibility
- Service worker functionality
- Icon availability and sizes
- Route configuration
- Asset publishing
- Integration status

### Browser Testing
```bash
# Install Playwright
npm install playwright

# Run PWA tests
npx playwright test test-pwa-install.spec.js
```
**Tests:**
- Manifest loading and parsing
- Service worker registration
- Icon availability
- Install prompt behavior
- Offline functionality
- Network status monitoring

## 🚀 Production Deployment

### 1. Pre-Deployment Checklist
```bash
# Health check
php artisan af-pwa:health

# Test suite
php artisan af-pwa:test

# Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Server Configuration

#### Nginx
```nginx
# PWA files with proper headers
location ~* \.(webmanifest|json)$ {
    add_header Cache-Control "public, max-age=86400, must-revalidate";
    add_header X-Content-Type-Options "nosniff";
}

location = /sw.js {
    add_header Cache-Control "no-cache, no-store, must-revalidate";
    add_header Pragma "no-cache";
    add_header Expires "0";
    add_header Service-Worker-Allowed "/";
}

# PWA icons
location /vendor/artflow-studio/pwa/icons/ {
    add_header Cache-Control "public, max-age=31536000, immutable";
}
```

#### Apache
```apache
# .htaccess in public directory
<IfModule mod_headers.c>
    # Manifest files
    <FilesMatch "\.(webmanifest|json)$">
        Header set Cache-Control "public, max-age=86400, must-revalidate"
        Header set X-Content-Type-Options "nosniff"
    </FilesMatch>
    
    # Service worker
    <Files "sw.js">
        Header set Cache-Control "no-cache, no-store, must-revalidate"
        Header set Pragma "no-cache"
        Header set Expires "0"
        Header set Service-Worker-Allowed "/"
    </Files>
    
    # PWA icons
    <LocationMatch "/vendor/artflow-studio/pwa/icons/">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </LocationMatch>
</IfModule>
```

### 3. SSL/HTTPS Requirement
PWAs require HTTPS in production. Ensure your application is served over HTTPS:

```env
# In production .env
APP_URL=https://yourdomain.com
FORCE_HTTPS=true
```

### 4. Performance Optimization
```bash
# Optimize assets
npm run build

# Optimize images
php artisan af-pwa:generate --icons --optimize

# Clear development caches
php artisan optimize:clear
php artisan optimize
```

---

**Ready to make your Laravel app installable?**

```bash
composer require artflow-studio/af-pwa
php artisan af-pwa:install
```

Transform your web application into a native-like experience in minutes, not days.
```  
🎯 **Single Directive** - Just add `@AFpwa` to your Blade template  
� **Auto-Discovery** - Automatically detects admin/*, member/*, dashboard/* routes  
�️ **Session-Safe** - Handles CSRF tokens, session expiration, and 419 errors  
📱 **Mobile-First** - Works perfectly on all devices and platforms  
🎨 **Customizable** - Extensive configuration options  
⚡ **Performance** - Intelligent caching and offline support  
🧪 **Testing Suite** - Built-in testing and health check commands

## 📋 Requirements

- PHP 8.0+
- Laravel 9.0+
- JSON PHP extension
- OpenSSL PHP extension (recommended)

## 🚀 Quick Start

### 1. Installation

Install the package via Composer:

```bash
composer require artflow-studio/af-pwa
```

### 2. Setup

Run the interactive installation:

```bash
php artisan af-pwa:install
```

### 3. Integration

Add the directive to your main layout file:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    {{-- AF-PWA Integration - Handles everything automatically --}}
    @AFpwa
</head>
<body>
    <!-- Your app content -->
</body>
</html>
```

### 4. Test Your PWA

```bash
php artisan af-pwa:test
```

That's it! Your Laravel app is now a Progressive Web Application! 🎉

## 📖 Detailed Installation Guide

### Step 1: Install Package

```bash
composer require artflow-studio/af-pwa
```

### Step 2: Run Installation Wizard

The installation wizard will guide you through the setup process:

```bash
php artisan af-pwa:install
```

**Interactive Setup Options:**
- App name and description
- Theme colors
- PWA routes configuration
- Icon generation
- Asset publishing

**Quick Installation (minimal setup):**
```bash
php artisan af-pwa:install --minimal
```

### Step 3: Configure Your PWA

Edit `config/af-pwa.php` to customize your PWA:

```php
return [
    'name' => 'My Amazing App',
    'short_name' => 'MyApp',
    'description' => 'A powerful Progressive Web Application',
    'theme_color' => '#007bff',
    'background_color' => '#ffffff',
    'pwa_routes' => [
        '/admin',
        '/member',
        '/dashboard',
        '/login',
    ],
    // ... more configuration options
];
```

### Step 4: Generate PWA Assets

Generate manifest, service worker, and icons:

```bash
php artisan af-pwa:generate
```

**Generate specific components:**
```bash
# Generate only manifest
php artisan af-pwa:generate --manifest

# Generate only service worker
php artisan af-pwa:generate --service-worker

# Generate only icons
php artisan af-pwa:generate --icons

# Generate and optimize all files
php artisan af-pwa:generate --optimize
```

## 🎨 Icon Management

AF-PWA automatically generates all required PWA icons from your source files.

### Source Icon Priority

The package looks for source icons in this order:
1. `public/logo.svg`
2. `public/logo.png`
3. `public/favicon.svg`
4. `public/favicon.png`
5. `public/favicon.ico`
6. `public/icon.svg`
7. `public/icon.png`

### Generated Icon Sizes

AF-PWA generates all required icon sizes:
- **Standard Icons**: 16x16, 32x32, 72x72, 96x96, 128x128, 144x144, 152x152, 192x192, 384x384, 512x512
- **Apple Touch Icons**: 57x57, 60x60, 72x72, 76x76, 114x114, 120x120, 144x144, 152x152, 180x180
- **Maskable Icons**: 192x192, 512x512 (with safe zone padding)

### Custom Icon Generation

```bash
# Generate icons from specific source
php artisan af-pwa:generate --icons

# The command will automatically detect and use your source icon
```

If no source icon is found, beautiful placeholder icons will be created with your app's theme colors.

## 🔧 Available Commands

### Installation Command
```bash
php artisan af-pwa:install [options]
```

**Options:**
- `--force` - Force installation even if files exist
- `--minimal` - Install minimal configuration only

### Generation Command
```bash
php artisan af-pwa:generate [options]
```

**Options:**
- `--manifest` - Generate only manifest.json
- `--service-worker` - Generate only service worker
- `--icons` - Generate only icons
- `--optimize` - Optimize generated files

### Test Command
```bash
php artisan af-pwa:test [options]
```

**Options:**
- `--interactive` - Run interactive tests
- `--fix` - Automatically fix issues where possible
- `--report` - Generate detailed test report

**Test Categories:**
- ✅ Configuration validation
- 📱 Manifest file testing
- ⚙️ Service worker validation
- 🎨 Icon completeness check
- 🌐 Route accessibility testing
- 📦 Asset verification
- 🔧 Integration testing

### Health Check Command
```bash
php artisan af-pwa:health [options]
```

**Options:**
- `--url=` - Test specific URL
- `--detailed` - Show detailed health information
- `--json` - Output results as JSON

**Health Checks:**
- 🏥 System health (PHP, Laravel, extensions)
- 📱 PWA files status
- ⚡ Performance metrics
- ♿ Accessibility compliance
- 📊 Overall health score

## ⚙️ Configuration

The `config/af-pwa.php` file contains all configuration options:

### Basic Configuration

```php
'name' => env('PWA_NAME', config('app.name')),
'short_name' => env('PWA_SHORT_NAME', 'PWA'),
'description' => env('PWA_DESCRIPTION', 'A powerful Progressive Web Application'),
'theme_color' => '#000000',
'background_color' => '#ffffff',
```

### Route Configuration

```php
'pwa_routes' => [
    '/admin',
    '/member',
    '/login',
    '/dashboard',
],
```

### Cache Strategies

```php
'cache_strategies' => [
    'assets' => 'cache-first',    // Static assets (JS, CSS, images)
    'pages' => 'network-first',   // HTML pages
    'api' => 'network-first',     // API requests
],
```

### Performance Settings

```php
'network_timeout' => 10000,  // 10 seconds
'api_timeout' => 10000,      // 10 seconds  
'page_timeout' => 15000,     // 15 seconds
'post_timeout' => 30000,     // 30 seconds
```

### Feature Toggles

```php
'enable_notifications' => false,
'enable_background_sync' => false,
'show_install_prompt' => true,
'show_network_status' => true,
'auto_refresh_on_update' => false,
```

## 🎯 Advanced Usage

### Individual Components

For advanced users who need granular control:

```blade
{{-- Head elements only --}}
@AFpwaHead

{{-- Scripts only --}}
@AFpwaScripts
```

### Custom Configuration

Pass custom options to the directive:

```blade
@AFpwa([
    'show_install_prompt' => false,
    'show_network_status' => true,
])
```

### Programmatic Access

Access AF-PWA functionality in your controllers:

```php
use ArtflowStudio\AfPwa\Facades\AfPwa;

class PWAController extends Controller
{
    public function manifest()
    {
        $manifest = app('af-pwa')->generateManifest();
        return response()->json($manifest);
    }
    
    public function checkPwaRoute($path)
    {
        return app('af-pwa')->isPwaRoute($path);
    }
}
```

## 🔒 Security Features

### CSRF Protection

AF-PWA includes automatic CSRF token management:
- Automatic token refresh
- Failed request retry
- Livewire integration
- Session timeout handling

### Route Protection

Only configured routes are cached and available offline:

```php
'pwa_routes' => [
    '/admin',    // Admin panel
    '/member',   // Member area
    '/login',    // Login page
],
```

All other routes will open in the browser, not the PWA.

### Session Management

Automatic session handling for PWA requests:
- Session refresh monitoring
- Automatic logout on expiry
- Livewire session sync
- Error recovery

## 🌐 Offline Support

### Automatic Offline Pages

AF-PWA creates route-specific offline pages:
- `/offline` - Default offline page
- `/offline/admin` - Admin-specific offline page
- `/offline/member` - Member-specific offline page

### Custom Offline Pages

Customize offline pages by publishing views:

```bash
php artisan vendor:publish --tag=af-pwa-views
```

Then edit the files in `resources/views/vendor/af-pwa/offline/`.

### Offline Configuration

```php
'offline' => [
    'title' => 'You\'re Offline',
    'message' => 'Some features may not be available.',
    'button_text' => 'Try Again',
    'show_network_status' => true,
    'show_retry_button' => true,
],
```

## 📊 Testing & Debugging

### Comprehensive Testing

```bash
# Run all tests
php artisan af-pwa:test

# Interactive testing
php artisan af-pwa:test --interactive

# Auto-fix issues
php artisan af-pwa:test --fix

# Generate test report
php artisan af-pwa:test --report
```

### Health Monitoring

```bash
# Basic health check
php artisan af-pwa:health

# Detailed health information
php artisan af-pwa:health --detailed

# JSON output for monitoring
php artisan af-pwa:health --json
```

### Debug Mode

Enable debug mode in your `.env`:

```env
PWA_DEBUG=true
```

This provides:
- Console logging
- Error details
- Performance metrics
- Development helpers

## 🔄 Updates & Maintenance

### Updating PWA Files

Regenerate PWA files after configuration changes:

```bash
php artisan af-pwa:generate
```

### Cache Versioning

AF-PWA automatically handles cache versioning. Update the cache version to force refresh:

```php
'cache_version' => 'v2',
```

### Performance Optimization

Optimize your PWA for production:

```bash
php artisan af-pwa:generate --optimize
```

This will:
- Minify service worker
- Optimize manifest
- Compress icons
- Remove debug code

## 🎨 Customization

### Custom Styling

Publish and customize the CSS:

```bash
php artisan vendor:publish --tag=af-pwa-assets
```

Edit `public/vendor/af-pwa/css/af-pwa.css` to customize:
- Install button styling
- Network status indicator
- Offline page design
- Loading animations

### Custom JavaScript

Add custom PWA functionality:

```javascript
document.addEventListener('af-pwa:ready', function(e) {
    console.log('PWA initialized');
    // Your custom code
});

document.addEventListener('af-pwa:offline', function(e) {
    console.log('App went offline');
    // Handle offline state
});

document.addEventListener('af-pwa:online', function(e) {
    console.log('App came back online');
    // Handle online state
});
```

### Custom Service Worker

Extend the service worker by publishing and modifying:

```bash
php artisan vendor:publish --tag=af-pwa-views
```

## 🐛 Troubleshooting

### Common Issues

**1. PWA not installing**
```bash
# Check if all files are generated
php artisan af-pwa:test

# Regenerate PWA files
php artisan af-pwa:generate
```

**2. Service worker not registering**
```bash
# Check for JavaScript errors in browser console
# Ensure service worker file exists
ls public/sw.js

# Regenerate service worker
php artisan af-pwa:generate --service-worker
```

**3. Icons not displaying**
```bash
# Check icon directory
ls public/icons/

# Regenerate icons
php artisan af-pwa:generate --icons
```

**4. Routes not caching**
- Verify routes are listed in `config/af-pwa.php`
- Check browser network tab for service worker activity
- Clear browser cache and reinstall PWA

### Debug Steps

1. **Check health status:**
   ```bash
   php artisan af-pwa:health --detailed
   ```

2. **Run tests:**
   ```bash
   php artisan af-pwa:test --interactive
   ```

3. **Check browser console** for JavaScript errors

4. **Verify files exist:**
   - `public/manifest.json`
   - `public/sw.js`
   - `public/icons/`

5. **Check configuration:**
   ```bash
   php artisan config:cache
   ```

## 📚 Browser Support

AF-PWA supports all modern browsers:

- ✅ Chrome 70+
- ✅ Firefox 65+
- ✅ Safari 12+
- ✅ Edge 79+
- ✅ Samsung Internet 10+
- ✅ Opera 57+

### Progressive Enhancement

The package uses progressive enhancement:
- Core functionality works in all browsers
- PWA features activate in supported browsers
- Graceful degradation for older browsers

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer test`
4. Run code style fixes: `composer fix-style`

## 📄 License

AF-PWA is open-sourced software licensed under the [MIT license](LICENSE).

## 🆘 Support

- 📖 [Documentation](https://github.com/artflow-studio/af-pwa/wiki)
- 🐛 [Issue Tracker](https://github.com/artflow-studio/af-pwa/issues)
- 💬 [Discussions](https://github.com/artflow-studio/af-pwa/discussions)
- 📧 [Email Support](mailto:support@artflow-studio.com)

## 🙏 Credits

AF-PWA is developed and maintained by [ArtFlow Studio](https://artflow-studio.com).

### Special Thanks

- Laravel community for the amazing framework
- PWA community for standards and best practices
- Contributors and testers

---

<p align="center">
<strong>Made with ❤️ by <a href="https://artflow-studio.com">ArtFlow Studio</a></strong>
</p>
