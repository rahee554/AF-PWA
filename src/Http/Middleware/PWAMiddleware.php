<?php

namespace ArtflowStudio\AfPwa\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use ArtflowStudio\AfPwa\Services\PWAService;
use ArtflowStudio\AfPwa\Services\SessionManager;

class PWAMiddleware
{
    protected PWAService $pwaService;
    protected SessionManager $sessionManager;
    protected array $config;

    public function __construct(PWAService $pwaService, SessionManager $sessionManager)
    {
        $this->pwaService = $pwaService;
        $this->sessionManager = $sessionManager;
        $this->config = config('af-pwa', []);
    }

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if PWA is enabled
        if (!($this->config['enabled'] ?? true)) {
            return $next($request);
        }

        // Handle PWA route filtering
        if (!$this->pwaService->isRouteAllowed($request->path())) {
            return $this->handleDisallowedRoute($request);
        }

        // Handle PWA-specific headers
        $response = $next($request);
        $this->addPWAHeaders($response, $request);

        // Handle session management for PWA
        if ($this->shouldManageSession($request)) {
            $this->handleSessionManagement($request, $response);
        }

        // Add PWA assets to view if HTML response
        if ($this->isHtmlResponse($response)) {
            $this->injectPWAAssets($response, $request);
        }

        return $response;
    }

    /**
     * Handle routes that are not allowed in PWA
     */
    protected function handleDisallowedRoute(Request $request)
    {
        // If it's an AJAX request or API call, return JSON
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'pwa_redirect' => true,
                'redirect_url' => $this->config['external_redirect_url'] ?? config('app.url'),
                'message' => 'This page should be opened in browser',
            ], 200);
        }

        // For regular requests, redirect to external browser
        $redirectUrl = $this->config['external_redirect_url'] ?? config('app.url') . $request->getRequestUri();
        
        return response()->view('af-pwa::redirect', [
            'redirectUrl' => $redirectUrl,
            'message' => 'Redirecting to browser...',
            'appName' => config('app.name'),
        ], 200);
    }

    /**
     * Add PWA-specific headers
     */
    protected function addPWAHeaders(Response $response, Request $request): void
    {
        // Security headers for PWA
        $response->headers->set('X-PWA-Enabled', 'true');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Service Worker headers
        if ($request->is('sw.js')) {
            $response->headers->set('Content-Type', 'application/javascript');
            $response->headers->set('Service-Worker-Allowed', '/');
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        }

        // Manifest headers
        if ($request->is('manifest.json')) {
            $response->headers->set('Content-Type', 'application/manifest+json');
            $response->headers->set('Cache-Control', 'public, max-age=3600');
        }

        // PWA detection headers
        if ($this->isPWARequest($request)) {
            $response->headers->set('X-PWA-Mode', 'standalone');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
    }

    /**
     * Handle session management for PWA
     */
    protected function handleSessionManagement(Request $request, Response $response): void
    {
        // Update activity timestamp
        $this->sessionManager->updateActivity();

        // Add session data to response for JavaScript
        if ($this->isHtmlResponse($response)) {
            $sessionData = $this->sessionManager->initialize();
            $timeoutInfo = $this->sessionManager->getTimeoutInfo();

            View::share('pwaSessionData', $sessionData);
            View::share('pwaTimeoutInfo', $timeoutInfo);
        }

        // Handle session timeout
        if (!$this->sessionManager->isValid()) {
            $timeoutData = $this->sessionManager->handleTimeout();
            
            if ($request->expectsJson() || $request->ajax()) {
                $response->setContent(json_encode($timeoutData));
                $response->headers->set('Content-Type', 'application/json');
            }
        }
    }

    /**
     * Inject PWA assets into HTML response
     */
    protected function injectPWAAssets(Response $response, Request $request): void
    {
        $content = $response->getContent();
        
        if (!$content || !str_contains($content, '</head>')) {
            return;
        }

        // Get PWA meta tags
        $metaTags = $this->pwaService->getMetaTags();
        $metaHtml = '';
        
        foreach ($metaTags as $name => $content) {
            if ($name === 'viewport') {
                $metaHtml .= "<meta name=\"viewport\" content=\"{$content}\">\n";
            } else {
                $metaHtml .= "<meta name=\"{$name}\" content=\"{$content}\">\n";
            }
        }

        // Add manifest link
        $metaHtml .= '<link rel="manifest" href="' . asset('manifest.json') . '">' . "\n";

        // Add apple touch icons
        $metaHtml .= '<link rel="apple-touch-icon" href="' . asset('icons/icon-192x192.png') . '">' . "\n";
        $metaHtml .= '<link rel="apple-touch-icon" sizes="152x152" href="' . asset('icons/icon-152x152.png') . '">' . "\n";
        $metaHtml .= '<link rel="apple-touch-icon" sizes="180x180" href="' . asset('icons/icon-192x192.png') . '">' . "\n";

        // Add favicon
        $metaHtml .= '<link rel="icon" type="image/png" sizes="32x32" href="' . asset('icons/icon-32x32.png') . '">' . "\n";
        $metaHtml .= '<link rel="icon" type="image/png" sizes="16x16" href="' . asset('icons/icon-16x16.png') . '">' . "\n";

        // Inject before closing head tag
        $content = str_replace('</head>', $metaHtml . '</head>', $content);
        
        // Add service worker registration and session management before closing body
        if (str_contains($content, '</body>')) {
            $scripts = $this->pwaService->renderServiceWorkerScript();
            $scripts .= $this->pwaService->renderSessionManagerScript();
            $content = str_replace('</body>', $scripts . '</body>', $content);
        }

        $response->setContent($content);
    }

    /**
     * Check if request should have session management
     */
    protected function shouldManageSession(Request $request): bool
    {
        // Skip session management for static assets
        if ($request->is('*.css', '*.js', '*.png', '*.jpg', '*.gif', '*.svg', '*.ico', '*.woff', '*.woff2')) {
            return false;
        }

        // Skip for service worker and manifest
        if ($request->is('sw.js', 'manifest.json')) {
            return false;
        }

        // Enable for allowed PWA routes
        return $this->pwaService->isRouteAllowed($request->path());
    }

    /**
     * Check if response is HTML
     */
    protected function isHtmlResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        return str_contains($contentType, 'text/html') || 
               (empty($contentType) && str_contains($response->getContent(), '<!DOCTYPE html>'));
    }

    /**
     * Check if request is from PWA
     */
    protected function isPWARequest(Request $request): bool
    {
        // Check for PWA display mode
        $displayMode = $request->header('X-Requested-With');
        if ($displayMode === 'PWA') {
            return true;
        }

        // Check for standalone mode
        $userAgent = $request->header('User-Agent', '');
        if (str_contains($userAgent, 'PWA') || str_contains($userAgent, 'standalone')) {
            return true;
        }

        // Check for PWA-specific headers
        return $request->hasHeader('X-PWA-Mode') || $request->hasHeader('X-Standalone');
    }

    /**
     * Handle PWA offline detection
     */
    public function handleOffline(Request $request)
    {
        $path = $request->path();
        $offlinePage = 'default';

        // Determine which offline page to show
        if (str_starts_with($path, 'admin')) {
            $offlinePage = 'admin';
        } elseif (str_starts_with($path, 'member')) {
            $offlinePage = 'member';
        }

        // Check if custom offline page exists
        $offlineFile = public_path("offline-{$offlinePage}.html");
        if (file_exists($offlineFile)) {
            return response()->file($offlineFile);
        }

        // Fallback to default offline page
        return response()->view('af-pwa::offline.default', [
            'appName' => config('app.name'),
            'message' => 'You are currently offline. Please check your internet connection.',
        ]);
    }

    /**
     * Handle PWA installation prompt
     */
    public function handleInstallPrompt(Request $request)
    {
        $canInstall = $this->canShowInstallPrompt($request);
        
        return response()->json([
            'can_install' => $canInstall,
            'prompt_config' => $this->config['install_prompt'] ?? [],
            'app_name' => config('app.name'),
        ]);
    }

    /**
     * Check if install prompt can be shown
     */
    protected function canShowInstallPrompt(Request $request): bool
    {
        // Don't show if already installed
        if ($this->isPWARequest($request)) {
            return false;
        }

        // Check if prompt is enabled
        if (!($this->config['install_prompt']['enabled'] ?? true)) {
            return false;
        }

        // Check if route is allowed for installation
        return $this->pwaService->isRouteAllowed($request->path());
    }

    /**
     * Terminate middleware - cleanup tasks
     */
    public function terminate(Request $request, Response $response): void
    {
        // Log PWA usage statistics
        if ($this->config['analytics']['enabled'] ?? false) {
            $this->logPWAUsage($request, $response);
        }

        // Cleanup expired sessions periodically
        if (random_int(1, 100) <= ($this->config['session']['cleanup_probability'] ?? 1)) {
            $this->sessionManager->cleanup();
        }
    }

    /**
     * Log PWA usage for analytics
     */
    protected function logPWAUsage(Request $request, Response $response): void
    {
        $data = [
            'route' => $request->path(),
            'method' => $request->method(),
            'is_pwa' => $this->isPWARequest($request),
            'user_agent' => $request->header('User-Agent'),
            'timestamp' => now()->timestamp,
            'session_id' => session()->getId(),
            'user_id' => Auth::id(),
        ];

        // Store in cache or send to analytics service
        cache()->put(
            'pwa_analytics_' . session()->getId() . '_' . now()->timestamp,
            $data,
            now()->addDays(7)
        );
    }
}
