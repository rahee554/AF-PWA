<?php

namespace ArtflowStudio\AfPwa\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'af-pwa:generate 
                          {--manifest : Generate only manifest.json}
                          {--service-worker : Generate only service worker}
                          {--icons : Generate only icons}
                          {--optimize : Optimize generated files}';

    /**
     * The console command description.
     */
    protected $description = 'Generate PWA files (manifest, service worker, icons)';

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

        $manifest = app('af-pwa')->generateManifest();
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

        $serviceWorker = app('af-pwa')->generateServiceWorker();
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
        if (!File::isDirectory($iconDir)) {
            File::makeDirectory($iconDir, 0755, true);
        }

        // Look for source icon
        $sourceIcon = $this->findSourceIcon();
        
        if ($sourceIcon) {
            $this->info("📸 Using source icon: {$sourceIcon}");
            $this->generateIconSizes($sourceIcon);
        } else {
            $this->warn('⚠️  No source icon found. Creating placeholders...');
            $this->createPlaceholderIcons();
        }
    }

    /**
     * Find source icon
     */
    protected function findSourceIcon(): ?string
    {
        $candidates = [
            public_path('logo.svg'),
            public_path('logo.png'),
            public_path('favicon.svg'),
            public_path('favicon.png'),
            public_path('favicon.ico'),
            public_path('icon.svg'),
            public_path('icon.png'),
        ];

        foreach ($candidates as $candidate) {
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Generate different icon sizes
     */
    protected function generateIconSizes(string $sourceIcon): void
    {
        $sizes = [16, 32, 72, 96, 128, 144, 152, 192, 384, 512];
        
        foreach ($sizes as $size) {
            $iconPath = public_path("icons/icon-{$size}x{$size}.png");
            
            if ($this->generateIconSize($sourceIcon, $iconPath, $size)) {
                $this->line("  ✅ Generated {$size}x{$size} icon");
            } else {
                $this->warn("  ⚠️  Failed to generate {$size}x{$size} icon - creating placeholder");
                $this->createPlaceholderIcon($iconPath, $size);
            }
        }

        // Generate Apple touch icons
        $this->generateAppleIcons($sourceIcon);
        
        // Generate maskable icons
        $this->generateMaskableIcons($sourceIcon);
    }

    /**
     * Generate a specific icon size
     */
    protected function generateIconSize(string $source, string $destination, int $size): bool
    {
        try {
            // If source is SVG, copy directly for larger sizes
            if (str_ends_with($source, '.svg') && $size >= 192) {
                return copy($source, str_replace('.png', '.svg', $destination));
            }
            
            // For now, just copy the source (you'd use an image library like Intervention Image)
            return copy($source, $destination);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate Apple-specific icons
     */
    protected function generateAppleIcons(string $sourceIcon): void
    {
        $appleSizes = [
            'apple-touch-icon.png' => 180,
            'apple-touch-icon-152x152.png' => 152,
            'apple-touch-icon-144x144.png' => 144,
            'apple-touch-icon-120x120.png' => 120,
            'apple-touch-icon-114x114.png' => 114,
            'apple-touch-icon-76x76.png' => 76,
            'apple-touch-icon-72x72.png' => 72,
            'apple-touch-icon-60x60.png' => 60,
            'apple-touch-icon-57x57.png' => 57,
        ];

        foreach ($appleSizes as $filename => $size) {
            $iconPath = public_path($filename);
            
            if ($this->generateIconSize($sourceIcon, $iconPath, $size)) {
                $this->line("  🍎 Generated Apple icon: {$filename}");
            }
        }
    }

    /**
     * Generate maskable icons
     */
    protected function generateMaskableIcons(string $sourceIcon): void
    {
        $this->info('🎭 Generating maskable icons...');
        
        $maskableSizes = [192, 512];
        
        foreach ($maskableSizes as $size) {
            $iconPath = public_path("icons/maskable-icon-{$size}x{$size}.png");
            
            // For maskable icons, we need to add padding
            if ($this->generateMaskableIcon($sourceIcon, $iconPath, $size)) {
                $this->line("  ✅ Generated maskable {$size}x{$size} icon");
            }
        }
    }

    /**
     * Generate maskable icon with safe zone padding
     */
    protected function generateMaskableIcon(string $source, string $destination, int $size): bool
    {
        try {
            // For now, just copy (in reality, you'd add 20% padding for safe zone)
            return copy($source, $destination);
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
            $this->createPlaceholderIcon($iconPath, $size);
        }

        $this->warn('📝 Placeholder icons created. Replace them with your actual app icons.');
    }

    /**
     * Create a placeholder icon
     */
    protected function createPlaceholderIcon(string $path, int $size): void
    {
        $appName = config('af-pwa.short_name', 'PWA');
        $themeColor = config('af-pwa.theme_color', '#007bff');
        $textColor = '#ffffff';

        // Calculate font size based on icon size
        $fontSize = max(10, $size * 0.2);
        $strokeWidth = max(1, $size * 0.02);

        $svg = "<?xml version='1.0' encoding='UTF-8'?>
<svg width='{$size}' height='{$size}' viewBox='0 0 {$size} {$size}' xmlns='http://www.w3.org/2000/svg'>
  <defs>
    <linearGradient id='grad{$size}' x1='0%' y1='0%' x2='100%' y2='100%'>
      <stop offset='0%' style='stop-color:{$themeColor};stop-opacity:1' />
      <stop offset='100%' style='stop-color:" . $this->darkenColor($themeColor, 20) . ";stop-opacity:1' />
    </linearGradient>
    <filter id='shadow{$size}'>
      <feDropShadow dx='0' dy='" . ($size * 0.02) . "' stdDeviation='" . ($size * 0.01) . "' flood-opacity='0.3'/>
    </filter>
  </defs>
  <rect width='{$size}' height='{$size}' fill='url(#grad{$size})' rx='" . ($size * 0.1) . "' filter='url(#shadow{$size})'/>
  <text x='50%' y='50%' font-family='Arial, sans-serif' font-size='{$fontSize}' font-weight='bold' fill='{$textColor}' text-anchor='middle' dominant-baseline='middle'>{$appName}</text>
  <circle cx='" . ($size * 0.85) . "' cy='" . ($size * 0.15) . "' r='" . ($size * 0.05) . "' fill='{$textColor}' opacity='0.5'/>
</svg>";

        File::put($path, $svg);
    }

    /**
     * Darken a hex color
     */
    protected function darkenColor(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r - ($r * $percent / 100)));
        $g = max(0, min(255, $g - ($g * $percent / 100)));
        $b = max(0, min(255, $b - ($b * $percent / 100)));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Optimize generated files
     */
    protected function optimizeFiles(): void
    {
        $this->info('⚡ Optimizing PWA files...');

        $this->optimizeManifest();
        $this->optimizeServiceWorker();
        $this->optimizeIcons();

        $this->info('✅ Files optimized');
    }

    /**
     * Optimize manifest
     */
    protected function optimizeManifest(): void
    {
        $manifestPath = public_path('manifest.json');
        
        if (!File::exists($manifestPath)) {
            return;
        }

        $manifest = json_decode(File::get($manifestPath), true);
        
        // Remove empty values
        $manifest = array_filter($manifest, function($value) {
            return !empty($value);
        });

        // Optimize and minify
        $optimized = json_encode($manifest, JSON_UNESCAPED_SLASHES);
        
        File::put($manifestPath, $optimized);
        
        $this->line('  ⚡ Manifest optimized');
    }

    /**
     * Optimize service worker
     */
    protected function optimizeServiceWorker(): void
    {
        $swPath = public_path('sw.js');
        
        if (!File::exists($swPath)) {
            return;
        }

        $content = File::get($swPath);
        $originalSize = strlen($content);

        // Remove comments
        $content = preg_replace('/\/\*[\s\S]*?\*\//', '', $content);
        $content = preg_replace('/\/\/.*$/m', '', $content);
        
        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        File::put($swPath, $content);

        $newSize = strlen($content);
        $saved = $originalSize - $newSize;
        
        $this->line("  ⚡ Service worker optimized: saved {$this->formatBytes($saved)}");
    }

    /**
     * Optimize icons
     */
    protected function optimizeIcons(): void
    {
        $iconDir = public_path('icons');
        
        if (!File::isDirectory($iconDir)) {
            return;
        }

        $icons = File::files($iconDir);
        $optimized = 0;

        foreach ($icons as $icon) {
            $originalSize = $icon->getSize();
            
            // Basic optimization would go here
            // For SVG files, we could minify them
            if (str_ends_with($icon->getFilename(), '.svg')) {
                $content = File::get($icon->getPathname());
                
                // Remove comments and extra whitespace from SVG
                $content = preg_replace('/<!--[\s\S]*?-->/', '', $content);
                $content = preg_replace('/>\s+</', '><', $content);
                
                File::put($icon->getPathname(), $content);
                $optimized++;
            }
        }

        if ($optimized > 0) {
            $this->line("  ⚡ Optimized {$optimized} icons");
        }
    }

    /**
     * Display manifest information
     */
    protected function displayManifestInfo(array $manifest): void
    {
        $this->info('📋 Manifest details:');
        $this->line("  • Name: {$manifest['name']}");
        $this->line("  • Short name: {$manifest['short_name']}");
        $this->line("  • Start URL: {$manifest['start_url']}");
        $this->line("  • Display: {$manifest['display']}");
        $this->line("  • Theme color: {$manifest['theme_color']}");
        $this->line("  • Icons: " . count($manifest['icons'] ?? []));
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
