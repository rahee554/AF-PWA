<?php

namespace ArtflowStudio\AfPwa\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateIconsCommand extends Command
{
    protected $signature = 'af-pwa:generate-icons {--force : Force regeneration of existing icons}';
    protected $description = 'Generate default PWA icons';

    public function handle(): int
    {
        $this->info('🎨 Generating PWA icons...');
        $this->newLine();

        $iconsPath = public_path('vendor/artflow-studio/pwa/icons');
        
        // Create icons directory if it doesn't exist
        if (!File::exists($iconsPath)) {
            File::makeDirectory($iconsPath, 0755, true);
            $this->info('✅ Created icons directory');
        }

        $sizes = [
            ['size' => 192, 'name' => 'icon-192x192.png'],
            ['size' => 512, 'name' => 'icon-512x512.png'],
            ['size' => 192, 'name' => 'maskable-icon-192x192.png'],
            ['size' => 512, 'name' => 'maskable-icon-512x512.png'],
        ];

        foreach ($sizes as $iconConfig) {
            $iconPath = $iconsPath . '/' . $iconConfig['name'];
            
            if (File::exists($iconPath) && !$this->option('force')) {
                $this->line("⏭️  Skipped: {$iconConfig['name']} (already exists)");
                continue;
            }

            $this->generateIcon($iconPath, $iconConfig['size']);
            $this->info("✅ Generated: {$iconConfig['name']}");
        }

        $this->newLine();
        $this->info('🎉 Icon generation complete!');
        $this->newLine();
        $this->comment('💡 Tip: Replace these placeholder icons with your custom icons for better branding');

        return 0;
    }

    private function generateIcon(string $path, int $size): void
    {
        // Create a simple colored square as placeholder icon
        $image = imagecreatetruecolor($size, $size);
        
        // Use app theme color or default blue
        $themeColor = config('af-pwa.theme_color', '#4F46E5');
        $rgb = $this->hexToRgb($themeColor);
        
        $backgroundColor = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        imagefilledrectangle($image, 0, 0, $size, $size, $backgroundColor);
        
        // Add app name initial in white
        $textColor = imagecolorallocate($image, 255, 255, 255);
        $appName = config('app.name', 'APP');
        $initial = strtoupper(substr($appName, 0, 1));
        
        // Calculate font size based on icon size
        $fontSize = $size / 3;
        
        // Use built-in GD font if TTF not available
        $fontPath = public_path('fonts/Roboto-Bold.ttf');
        if (file_exists($fontPath)) {
            // Calculate text position to center it
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $initial);
            $x = ($size - ($bbox[2] - $bbox[0])) / 2;
            $y = ($size - ($bbox[1] - $bbox[7])) / 2 + $fontSize;
            
            imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $initial);
        } else {
            // Fallback: Use built-in font and draw in center
            $font = 5; // Largest built-in font
            $textWidth = imagefontwidth($font) * strlen($initial);
            $textHeight = imagefontheight($font);
            $x = ($size - $textWidth) / 2;
            $y = ($size - $textHeight) / 2;
            
            imagestring($image, $font, $x, $y, $initial, $textColor);
        }
        
        // Save the image
        imagepng($image, $path);
        imagedestroy($image);
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
    }
}
