<?php

namespace ArtflowStudio\AfPwa\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class SessionManager
{
    protected array $config;
    protected string $cachePrefix = 'af_pwa_session_';

    public function __construct()
    {
        $this->config = config('af-pwa.session', []);
    }

    /**
     * Initialize session management for PWA
     */
    public function initialize(): array
    {
        $sessionData = [
            'csrf_token' => csrf_token(),
            'session_id' => session()->getId(),
            'user_id' => Auth::id(),
            'is_authenticated' => Auth::check(),
            'lifetime' => $this->getSessionLifetime(),
            'timeout_warning' => $this->config['timeout_warning'] ?? 300, // 5 minutes before expiry
            'auto_refresh' => $this->config['auto_refresh'] ?? true,
            'refresh_interval' => $this->config['refresh_interval'] ?? 1800, // 30 minutes
            'last_activity' => now()->timestamp,
        ];

        // Store session metadata in cache
        $this->storeSessionMetadata($sessionData);

        return $sessionData;
    }

    /**
     * Refresh session and CSRF token
     */
    public function refresh(): array
    {
        try {
            // Regenerate CSRF token
            session()->regenerateToken();
            
            // Update session metadata
            $sessionData = [
                'csrf_token' => csrf_token(),
                'session_id' => session()->getId(),
                'user_id' => Auth::id(),
                'is_authenticated' => Auth::check(),
                'last_activity' => now()->timestamp,
                'refreshed_at' => now()->timestamp,
            ];

            $this->storeSessionMetadata($sessionData);

            return [
                'success' => true,
                'csrf_token' => $sessionData['csrf_token'],
                'session_id' => $sessionData['session_id'],
                'timestamp' => $sessionData['last_activity'],
            ];
        } catch (\Exception $e) {
            Log::error('PWA Session refresh failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Session refresh failed',
                'redirect' => $this->config['login_url'] ?? '/login',
            ];
        }
    }

    /**
     * Check if session is valid and active
     */
    public function isValid(): bool
    {
        if (!Session::has('_token')) {
            return false;
        }

        $metadata = $this->getSessionMetadata();
        if (!$metadata) {
            return false;
        }

        $lifetime = $this->getSessionLifetime();
        $lastActivity = $metadata['last_activity'] ?? 0;
        $currentTime = now()->timestamp;

        return ($currentTime - $lastActivity) < $lifetime;
    }

    /**
     * Get session timeout information
     */
    public function getTimeoutInfo(): array
    {
        $metadata = $this->getSessionMetadata();
        if (!$metadata) {
            return [
                'expired' => true,
                'time_remaining' => 0,
                'warning' => true,
            ];
        }

        $lifetime = $this->getSessionLifetime();
        $lastActivity = $metadata['last_activity'] ?? 0;
        $currentTime = now()->timestamp;
        $timeElapsed = $currentTime - $lastActivity;
        $timeRemaining = $lifetime - $timeElapsed;
        $warningTime = $this->config['timeout_warning'] ?? 300;

        return [
            'expired' => $timeRemaining <= 0,
            'time_remaining' => max(0, $timeRemaining),
            'warning' => $timeRemaining <= $warningTime && $timeRemaining > 0,
            'auto_refresh_enabled' => $this->config['auto_refresh'] ?? true,
            'next_refresh' => $this->getNextRefreshTime(),
        ];
    }

    /**
     * Handle session timeout
     */
    public function handleTimeout(): array
    {
        $this->clearSessionMetadata();
        
        // Determine redirect URL based on user type
        $redirectUrl = $this->getTimeoutRedirectUrl();
        
        return [
            'expired' => true,
            'redirect' => $redirectUrl,
            'message' => 'Your session has expired. Please log in again.',
        ];
    }

    /**
     * Update last activity timestamp
     */
    public function updateActivity(): bool
    {
        try {
            $metadata = $this->getSessionMetadata();
            if ($metadata) {
                $metadata['last_activity'] = now()->timestamp;
                $this->storeSessionMetadata($metadata);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('PWA Session activity update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get Livewire-compatible session data
     */
    public function getLivewireData(): array
    {
        return [
            'csrf' => csrf_token(),
            'session' => session()->getId(),
            'fingerprint' => $this->generateFingerprint(),
            'memo' => [
                'id' => session()->getId(),
                'name' => 'pwa-session',
                'path' => request()->path(),
                'method' => request()->method(),
            ],
        ];
    }

    /**
     * Validate Livewire request
     */
    public function validateLivewireRequest(array $data): bool
    {
        // Validate CSRF token
        if (!hash_equals(csrf_token(), $data['csrf'] ?? '')) {
            return false;
        }

        // Validate session
        if (session()->getId() !== ($data['session'] ?? '')) {
            return false;
        }

        // Validate fingerprint
        if ($this->generateFingerprint() !== ($data['fingerprint'] ?? '')) {
            return false;
        }

        return $this->isValid();
    }

    /**
     * Generate client fingerprint for security
     */
    public function generateFingerprint(): string
    {
        $components = [
            request()->ip(),
            request()->header('User-Agent'),
            Auth::id() ?? 'guest',
            session()->getId(),
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Store session metadata in cache
     */
    protected function storeSessionMetadata(array $data): void
    {
        $key = $this->cachePrefix . session()->getId();
        $ttl = $this->getSessionLifetime() + 300; // Add 5 minutes buffer
        
        Cache::put($key, $data, $ttl);
    }

    /**
     * Get session metadata from cache
     */
    protected function getSessionMetadata(): ?array
    {
        $key = $this->cachePrefix . session()->getId();
        return Cache::get($key);
    }

    /**
     * Clear session metadata from cache
     */
    protected function clearSessionMetadata(): void
    {
        $key = $this->cachePrefix . session()->getId();
        Cache::forget($key);
    }

    /**
     * Get session lifetime in seconds
     */
    protected function getSessionLifetime(): int
    {
        return config('session.lifetime') * 60; // Convert minutes to seconds
    }

    /**
     * Get next refresh time
     */
    protected function getNextRefreshTime(): int
    {
        $refreshInterval = $this->config['refresh_interval'] ?? 1800;
        $metadata = $this->getSessionMetadata();
        $lastRefresh = $metadata['refreshed_at'] ?? $metadata['last_activity'] ?? now()->timestamp;
        
        return $lastRefresh + $refreshInterval;
    }

    /**
     * Get timeout redirect URL based on context
     */
    protected function getTimeoutRedirectUrl(): string
    {
        $path = request()->path();
        
        // Check if it's an admin route
        if (str_starts_with($path, 'admin')) {
            return $this->config['admin_login_url'] ?? '/login';
        }
        
        // Check if it's a member route
        if (str_starts_with($path, 'member')) {
            return $this->config['member_login_url'] ?? '/login';
        }
        
        // Default login URL
        return $this->config['login_url'] ?? '/login';
    }

    /**
     * Clean up expired session metadata
     */
    public function cleanup(): int
    {
        $cleaned = 0;
        $pattern = $this->cachePrefix . '*';
        
        // This is a simplified cleanup - in production you might want to use a more efficient method
        try {
            $keys = Cache::getRedis()->keys($pattern);
            foreach ($keys as $key) {
                $data = Cache::get($key);
                if ($data && isset($data['last_activity'])) {
                    $age = now()->timestamp - $data['last_activity'];
                    if ($age > $this->getSessionLifetime()) {
                        Cache::forget($key);
                        $cleaned++;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('PWA Session cleanup failed: ' . $e->getMessage());
        }
        
        return $cleaned;
    }

    /**
     * Get session statistics
     */
    public function getStatistics(): array
    {
        try {
            $pattern = $this->cachePrefix . '*';
            $keys = Cache::getRedis()->keys($pattern);
            $total = count($keys);
            $active = 0;
            $expired = 0;
            
            foreach ($keys as $key) {
                $data = Cache::get($key);
                if ($data && isset($data['last_activity'])) {
                    $age = now()->timestamp - $data['last_activity'];
                    if ($age > $this->getSessionLifetime()) {
                        $expired++;
                    } else {
                        $active++;
                    }
                }
            }
            
            return [
                'total_sessions' => $total,
                'active_sessions' => $active,
                'expired_sessions' => $expired,
                'cleanup_needed' => $expired > 0,
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Could not retrieve statistics',
                'total_sessions' => 0,
                'active_sessions' => 0,
                'expired_sessions' => 0,
                'cleanup_needed' => false,
            ];
        }
    }

    /**
     * Force logout and clear all session data
     */
    public function forceLogout(): array
    {
        try {
            $this->clearSessionMetadata();
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            
            return [
                'success' => true,
                'redirect' => $this->getTimeoutRedirectUrl(),
                'message' => 'You have been logged out.',
            ];
        } catch (\Exception $e) {
            Log::error('PWA Force logout failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Logout failed',
                'redirect' => '/login',
            ];
        }
    }
}
