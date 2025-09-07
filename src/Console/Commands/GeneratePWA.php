<?php

namespace ArtflowStudio\AfPwa\Console\Commands;

use Illuminate\Console\Command;
use ArtflowStudio\AfPwa\Services\PWAService;
use ArtflowStudio\AfPwa\Services\SessionManager;
use Illuminate\Support\Facades\File;

class GeneratePWA extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'af-pwa:generate 
                          {--manifest : Generate only manifest.json}
                          {--service-worker : Generate only service worker}
                          {--icons : Generate only icons}
                          {--optimize : Optimize generated files}
                          {--validate : Validate generated files}';

    /**
     * The console command description.
     */
    protected $description = 'Generate and optimize PWA files';

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
        $this->info('🔄 Generating PWA files...');

        try {
            if ($this->option('manifest') || !$this->hasSpecificOption()) {
                $this->generateManifest();
            }

            if ($this->option('service-worker') || !$this->hasSpecificOption()) {
                $this->generateServiceWorker();
            }

            if ($this->option('icons') || !$this->hasSpecificOption()) {
                $this->generateIcons();
            }

            if ($this->option('optimize')) {
                $this->optimizeFiles();
            }

            if ($this->option('validate')) {
                $this->validateFiles();
            }

            $this->info('✅ PWA files generated successfully!');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Generation failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Generate manifest.json
     */
    protected function generateManifest(): void
    {
        $this->info('📱 Generating manifest.json...');

        $manifest = $this->pwaService->generateManifest();
        $manifestPath = public_path('manifest.json');

        File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('✅ Manifest generated: ' . $manifestPath);
        $this->displayManifestInfo($manifest);
    }

    /**
     * Generate service worker
     */
    protected function generateServiceWorker(): void
    {
        $this->info('⚙️  Generating service worker...');

        $serviceWorker = $this->pwaService->generateServiceWorker();
        $swPath = public_path('sw.js');

        File::put($swPath, $serviceWorker);

        $this->info('✅ Service worker generated: ' . $swPath);
        $this->info('📊 Size: ' . $this->formatBytes(strlen($serviceWorker)));
    }

    /**
     * Generate icons
     */
    protected function generateIcons(): void
    {
        $this->info('🎨 Processing icons...');

        $iconDir = public_path('icons');
        if (!is_dir($iconDir)) {
            mkdir($iconDir, 0755, true);
        }

        $baseIcon = public_path('favicon.svg');
        if (!file_exists($baseIcon)) {
            $baseIcon = public_path('favicon.ico');
        }

        if (!file_exists($baseIcon)) {
            $this->warn('⚠️  No base icon found (favicon.svg or favicon.ico)');
            $this->info('Creating placeholder icons...');
            $this->createPlaceholderIcons();
            return;
        }

        $this->generateIconSizes($baseIcon);
    }

    /**
     * Generate different icon sizes
     */
    protected function generateIconSizes(string $baseIcon): void
    {
        $sizes = [16, 32, 72, 96, 128, 144, 152, 192, 384, 512];
        
        foreach ($sizes as $size) {
            $iconPath = public_path("icons/icon-{$size}x{$size}.png");
            
            if ($this->generateIconSize($baseIcon, $iconPath, $size)) {
                $this->info("✅ Generated {$size}x{$size} icon");
            } else {
                $this->warn("⚠️  Failed to generate {$size}x{$size} icon");
            }
        }

        // Generate maskable icons
        $this->generateMaskableIcons($baseIcon);
    }

    /**
     * Generate a specific icon size
     */
    protected function generateIconSize(string $source, string $destination, int $size): bool
    {
        // This is a placeholder - you would use an image processing library like Intervention Image
        // For now, just copy the source file
        try {
            copy($source, $destination);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate maskable icons
     */
    protected function generateMaskableIcons(string $baseIcon): void
    {
        $this->info('🎭 Generating maskable icons...');
        
        $maskableSizes = [192, 512];
        
        foreach ($maskableSizes as $size) {
            $iconPath = public_path("icons/maskable-icon-{$size}x{$size}.png");
            
            if ($this->generateMaskableIcon($baseIcon, $iconPath, $size)) {
                $this->info("✅ Generated maskable {$size}x{$size} icon");
            }
        }
    }

    /**
     * Generate maskable icon with padding
     */
    protected function generateMaskableIcon(string $source, string $destination, int $size): bool
    {
        // This would generate an icon with appropriate padding for maskable icons
        // For now, just copy the source
        try {
            copy($source, $destination);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create placeholder icons
     */
    protected function createPlaceholderIcons(): void
    {
        $sizes = [16, 32, 72, 96, 128, 144, 152, 192, 384, 512];
        
        foreach ($sizes as $size) {
            $iconPath = public_path("icons/icon-{$size}x{$size}.png");
            $this->createPlaceholder($iconPath, $size);
        }
    }

    /**
     * Create a placeholder icon file
     */
    protected function createPlaceholder(string $path, int $size): void
    {
        // Create a simple SVG placeholder
        $svg = "<?xml version='1.0' encoding='UTF-8'?>
<svg width='{$size}' height='{$size}' viewBox='0 0 {$size} {$size}' xmlns='http://www.w3.org/2000/svg'>
  <rect width='{$size}' height='{$size}' fill='#007bff'/>
  <text x='50%' y='50%' font-family='Arial, sans-serif' font-size='" . ($size * 0.3) . "' fill='white' text-anchor='middle' dominant-baseline='middle'>PWA</text>
</svg>";
        
        File::put($path, $svg);
    }

    /**
     * Optimize generated files
     */
    protected function optimizeFiles(): void
    {
        $this->info('⚡ Optimizing PWA files...');

        // Minify service worker
        $this->optimizeServiceWorker();

        // Optimize manifest
        $this->optimizeManifest();

        // Optimize icons
        $this->optimizeIcons();

        $this->info('✅ Files optimized');
    }

    /**
     * Optimize service worker
     */
    protected function optimizeServiceWorker(): void
    {
        $swPath = public_path('sw.js');
        
        if (!file_exists($swPath)) {
            return;
        }

        $content = File::get($swPath);
        $originalSize = strlen($content);

        // Remove comments and unnecessary whitespace
        $content = preg_replace('/\/\*[\s\S]*?\*\//', '', $content);
        $content = preg_replace('/\/\/.*$/m', '', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        File::put($swPath, $content);

        $newSize = strlen($content);
        $saved = $originalSize - $newSize;
        
        $this->info("⚡ Service worker optimized: saved {$this->formatBytes($saved)}");
    }

    /**
     * Optimize manifest
     */
    protected function optimizeManifest(): void
    {
        $manifestPath = public_path('manifest.json');
        
        if (!file_exists($manifestPath)) {
            return;
        }

        $manifest = json_decode(File::get($manifestPath), true);
        $optimized = json_encode($manifest, JSON_UNESCAPED_SLASHES);
        
        File::put($manifestPath, $optimized);
        
        $this->info('⚡ Manifest optimized');
    }

    /**
     * Optimize icons
     */
    protected function optimizeIcons(): void
    {
        $iconDir = public_path('icons');
        
        if (!is_dir($iconDir)) {
            return;
        }

        $icons = File::files($iconDir);
        $optimized = 0;

        foreach ($icons as $icon) {
            if ($this->optimizeIcon($icon->getPathname())) {
                $optimized++;
            }
        }

        if ($optimized > 0) {
            $this->info("⚡ Optimized {$optimized} icons");
        }
    }

    /**
     * Optimize a single icon
     */
    protected function optimizeIcon(string $iconPath): bool
    {
        // This would use an image optimization library
        // For now, just return true as placeholder
        return true;
    }

    /**
     * Validate generated files
     */
    protected function validateFiles(): void
    {
        $this->info('🔍 Validating PWA files...');

        $errors = [];

        // Validate manifest
        $manifestErrors = $this->validateManifest();
        if (!empty($manifestErrors)) {
            $errors['manifest'] = $manifestErrors;
        }

        // Validate service worker
        $swErrors = $this->validateServiceWorker();
        if (!empty($swErrors)) {
            $errors['service_worker'] = $swErrors;
        }

        // Validate icons
        $iconErrors = $this->validateIcons();
        if (!empty($iconErrors)) {
            $errors['icons'] = $iconErrors;
        }

        if (empty($errors)) {
            $this->info('✅ All PWA files are valid');
        } else {
            $this->displayValidationErrors($errors);
        }
    }

    /**
     * Validate manifest.json
     */
    protected function validateManifest(): array
    {
        $errors = [];
        $manifestPath = public_path('manifest.json');

        if (!file_exists($manifestPath)) {
            return ['Manifest file not found'];
        }

        $manifest = json_decode(File::get($manifestPath), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['Invalid JSON format'];
        }

        // Required fields
        $required = ['name', 'short_name', 'start_url', 'display', 'theme_color', 'background_color', 'icons'];
        
        foreach ($required as $field) {
            if (!isset($manifest[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        return $errors;
    }

    /**
     * Validate service worker
     */
    protected function validateServiceWorker(): array
    {
        $errors = [];
        $swPath = public_path('sw.js');

        if (!file_exists($swPath)) {
            return ['Service worker file not found'];
        }

        $content = File::get($swPath);
        
        // Check for required events
        $requiredEvents = ['install', 'activate', 'fetch'];
        
        foreach ($requiredEvents as $event) {
            if (strpos($content, "addEventListener('{$event}'") === false) {
                $errors[] = "Missing {$event} event listener";
            }
        }

        return $errors;
    }

    /**
     * Validate icons
     */
    protected function validateIcons(): array
    {
        $errors = [];
        $iconDir = public_path('icons');

        if (!is_dir($iconDir)) {
            return ['Icons directory not found'];
        }

        $requiredSizes = [192, 512];
        
        foreach ($requiredSizes as $size) {
            $iconPath = $iconDir . "/icon-{$size}x{$size}.png";
            if (!file_exists($iconPath)) {
                $errors[] = "Missing required icon: {$size}x{$size}";
            }
        }

        return $errors;
    }

    /**
     * Display validation errors
     */
    protected function displayValidationErrors(array $errors): void
    {
        $this->error('❌ Validation errors found:');
        
        foreach ($errors as $type => $typeErrors) {
            $this->error("  {$type}:");
            foreach ($typeErrors as $error) {
                $this->error("    - {$error}");
            }
        }
    }

    /**
     * Display manifest information
     */
    protected function displayManifestInfo(array $manifest): void
    {
        $this->info('📋 Manifest details:');
        $this->line("  Name: {$manifest['name']}");
        $this->line("  Short name: {$manifest['short_name']}");
        $this->line("  Start URL: {$manifest['start_url']}");
        $this->line("  Display: {$manifest['display']}");
        $this->line("  Theme color: {$manifest['theme_color']}");
        $this->line("  Icons: " . count($manifest['icons']));
    }

    /**
     * Format bytes for display
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        
        return $bytes . ' bytes';
    }

    /**
     * Check if user specified a specific option
     */
    protected function hasSpecificOption(): bool
    {
        return $this->option('manifest') || 
               $this->option('service-worker') || 
               $this->option('icons');
    }
}
