// AF-PWA Complete PWA Generator - Script
let currentImage = null;
let currentImageURL = '';
let generatedIcons = {}; // filename => canvas
let protocolHandlers = [];
let shortcuts = [
    { name: '', short_name: '', url: '', description: '' }
];

// Comprehensive icon list (toggle via checkboxes)
const ICON_SETS = {
    favicons: [16, 32, 48],
    pwa: [72, 96, 128, 144, 152, 180, 192, 256, 384, 512],
    apple: [57, 60, 72, 76, 114, 120, 144, 152, 180],
    maskable: [192, 512],
    windows: [70, 150, 310],
};

function buildIconMatrix() {
    const out = [];
    const genFav = byId('generateFavicons')?.checked ?? true;
    const genPWA = byId('generatePWA')?.checked ?? true;
    const genApple = byId('generateApple')?.checked ?? true;
    const genMask = byId('generateMaskable')?.checked ?? true;
    const genWin = byId('generateWindows')?.checked ?? false;
    if (genFav) ICON_SETS.favicons.forEach(s => out.push({ size: s, name: `favicon-${s}x${s}.png`, desc: 'Favicon', type: 'favicon' }));
    if (genPWA) ICON_SETS.pwa.forEach(s => out.push({ size: s, name: `icon-${s}x${s}.png`, desc: 'PWA Icon', type: 'standard' }));
    if (genApple) ICON_SETS.apple.forEach(s => out.push({ size: s, name: `apple-icon-${s}x${s}.png`, desc: 'Apple Touch', type: 'apple' }));
    if (genMask) ICON_SETS.maskable.forEach(s => out.push({ size: s, name: `maskable-icon-${s}x${s}.png`, desc: 'Maskable', type: 'maskable', maskable: true }));
    if (genWin) {
        out.push({ size: 70, name: 'mstile-70x70.png', desc: 'Windows Tile Small', type: 'windows' });
        out.push({ size: 150, name: 'mstile-150x150.png', desc: 'Windows Tile Medium', type: 'windows' });
        out.push({ size: 310, name: 'mstile-310x310.png', desc: 'Windows Tile Large', type: 'windows' });
    }
    return out;
}

document.addEventListener('DOMContentLoaded', () => {
    wireTabs();
    wirePreviewTabs();
    wireUpload();
    wireControls();
    wireShortcuts();
    renderShortcuts();
    syncPreviewTexts();
    updateThemeColor();
    renderOfflinePreview('minimal');
    setCounts();
    toast('Ready. Upload a logo to begin.');
});

function wireTabs() {
    const buttons = qsa('.tab-btn');
    buttons.forEach(btn => btn.addEventListener('click', () => {
        buttons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const id = btn.dataset.tab;
        qsa('.tab-pane').forEach(p => p.classList.remove('active'));
        const pane = byId(id + 'Tab');
        if (pane) pane.classList.add('active');
    }));
}

function wirePreviewTabs() {
    const btns = qsa('.preview-tab');
    btns.forEach(btn => btn.addEventListener('click', () => {
        btns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        qsa('.preview-pane').forEach(p => p.classList.remove('active'));
        const pane = byId(btn.dataset.preview + 'Preview');
        if (pane) pane.classList.add('active');
    }));
    // device picker
    qsa('.device-btn').forEach(b => b.addEventListener('click', () => {
        qsa('.device-btn').forEach(bb => bb.classList.remove('active'));
        b.classList.add('active');
        const dev = b.dataset.device;
        qsa('.device-mockup').forEach(d => d.classList.remove('active'));
        if (dev === 'mobile') byId('mobileDevice').classList.add('active');
        if (dev === 'tablet') byId('tabletDevice').classList.add('active');
        if (dev === 'desktop') byId('desktopDevice').classList.add('active');
    }));
    // offline template picker
    qsa('.template-btn').forEach(b => b.addEventListener('click', () => {
        qsa('.template-btn').forEach(bb => bb.classList.remove('active'));
        b.classList.add('active');
        const tpl = b.dataset.template;
        renderOfflinePreview(tpl);
    }));
    // offline / loading selectors will be wired during render
}

function wireUpload() {
    const fileInput = byId('fileInput');
    const zone = byId('uploadZone');
    fileInput.addEventListener('change', handleFileSelect);
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => { e.preventDefault(); zone.classList.remove('dragover'); const f = e.dataTransfer.files?.[0]; if (f) handleFileFile(f); });
    // click delegate
    zone.addEventListener('click', () => fileInput.click());
}

function wireControls() {
    ['iconBackgroundType','iconBackgroundColor','iconPadding','iconRadius','iconShadow','iconQuality','generateFavicons','generatePWA','generateApple','generateMaskable','generateWindows']
        .forEach(id => byId(id)?.addEventListener('input', () => {
            if (currentImage) generateIconsToRight();
        }));
    ['appName','shortName','manifestBackgroundColor','displayMode','orientation','startUrl','scope','appDescription','appCategory']
        .forEach(id => byId(id)?.addEventListener('input', syncPreviewTexts));
    byId('themeColor')?.addEventListener('input', () => { syncPreviewTexts(); updateThemeColor(); renderOfflinePreview(byId('offlineTemplate').value); });
    // offline
    ['offlineTemplate','offlineMessage','offlineDescription','offlineIndicator','offlineRetry']
        .forEach(id => byId(id)?.addEventListener('input', () => renderOfflinePreview(byId('offlineTemplate').value)));
    // loading previews
    qsa('.loading-btn').forEach(b => b.addEventListener('click', () => {
        qsa('.loading-btn').forEach(bb => bb.classList.remove('active'));
        b.classList.add('active');
        qsa('.loading-demo').forEach(d => d.classList.remove('active'));
        const id = b.dataset.loading;
        if (id === 'splash') byId('splashDemo').classList.add('active');
        if (id === 'skeleton') byId('skeletonDemo').classList.add('active');
        if (id === 'progress') byId('progressDemo').classList.add('active');
    }));
}

function wireShortcuts() {
    // addShortcut/removeShortcut buttons call functions below
}

function handleFileSelect(event) { const file = event.target.files[0]; if (file) handleFileFile(file); }
function handleFileFile(file) {
    if (!file.type.startsWith('image/')) { toast('Please select a valid image file', 'error'); return; }
    const reader = new FileReader();
    reader.onload = e => {
        currentImageURL = String(e.target.result);
        const img = new Image();
        img.onload = () => {
            currentImage = img;
            byId('uploadPreview').classList.remove('hidden');
            byId('currentImage').src = currentImageURL;
            byId('imageSize').textContent = `${img.naturalWidth}×${img.naturalHeight}`;
            generateIconsToRight();
            toggleBottomPanel(true);
        };
        img.src = currentImageURL;
    };
    reader.readAsDataURL(file);
}

// obsolete handlers replaced by wireUpload

// replaced by handleFileFile and uploadPreview

function updateControlsDisplay() {
    const p = byId('iconPadding').value; byId('paddingValue').textContent = `${p}%`;
    const r = byId('iconRadius').value; byId('radiusValue').textContent = `${r}%`;
}
function syncPreviewTexts() {
    const appName = byId('appName').value || 'My PWA App';
    const shortName = byId('shortName').value || 'PWA';
    byId('mobileAppName')?.replaceChildren(document.createTextNode(shortName));
    byId('tabletAppName')?.replaceChildren(document.createTextNode(appName));
    byId('splashAppName').textContent = appName;
    // desktop navbar
    qs('#desktopDevice #desktopAppName')?.replaceChildren(document.createTextNode(appName));
}

function updateThemeColor() {
    const theme = byId('themeColor').value || '#2563eb';
    // apply to splash preview icon border
    byId('splashDemo').style.setProperty('--brand', theme);
}

async function generateIconsToRight() {
    if (!currentImage) return;
    updateControlsDisplay();
    const list = buildIconMatrix();
    const grid = byId('iconsGrid');
    grid.innerHTML = '';
    generatedIcons = {};
    showOverlay('Generating icons...', 'We are rendering your icon set');
    const quality = parseFloat(byId('iconQuality').value || '1.0');
    for (let i = 0; i < list.length; i++) {
        const cfg = list[i];
        const canvas = await createIcon({
            size: cfg.size,
            maskable: !!cfg.maskable,
        });
        generatedIcons[cfg.name] = canvas;
        addIconCard(grid, canvas, cfg, quality);
        byId('loadingProgress').style.width = `${Math.round(((i + 1) / list.length) * 100)}%`;
        byId('loadingPercentage').textContent = `${Math.round(((i + 1) / list.length) * 100)}%`;
        await delay(10);
    }
    hideOverlay();
    setCounts();
    // Update device icon placeholders (use 180 or 192, else 128)
    const prefer = generatedIcons['icon-192x192.png'] || generatedIcons['apple-icon-180x180.png'] || generatedIcons['icon-128x128.png'];
    if (prefer) {
        const url = prefer.toDataURL('image/png', quality);
        // mobile
        const mob = qs('#mobileDevice .app-icon .icon-placeholder');
        if (mob) mob.replaceChildren(canvasToImg(prefer));
        // tablet small icon
        const tab = qs('#tabletDevice .app-icon-small');
        if (tab) tab.replaceChildren(canvasToImg(prefer, 22));
        // desktop navbar icon
        const d = qs('#desktopDevice .navbar-icon');
        if (d) d.replaceChildren(canvasToImg(prefer, 22));
        // splash
        const s = byId('splashAppIcon');
        if (s) s.replaceChildren(canvasToImg(prefer, 48));
        // progress
        const p = byId('progressAppIcon');
        if (p) p.replaceChildren(canvasToImg(prefer, 28));
    }
}

async function createIcon(config) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const size = config.size;
    
    canvas.width = size;
    canvas.height = size;

    // Get settings
    const backgroundType = byId('iconBackgroundType').value;
    const backgroundColor = byId('iconBackgroundColor').value;
    const padding = parseInt(byId('iconPadding').value || '10');
    const borderRadius = parseInt(byId('iconRadius').value || '0');
    const addShadow = byId('iconShadow').checked;

    // Clear canvas
    ctx.clearRect(0, 0, size, size);

    // Save context for clipping
    ctx.save();

    // Create clipping path for rounded corners
    if (borderRadius > 0) {
        const radius = (size * borderRadius) / 100;
        ctx.beginPath();
        if (ctx.roundRect) {
            ctx.roundRect(0, 0, size, size, radius);
        } else {
            // Fallback for browsers without roundRect
            createRoundedRect(ctx, 0, 0, size, size, radius);
        }
        ctx.clip();
    }

    // Draw background
    if (backgroundType !== 'transparent') {
        if (backgroundType === 'solid') {
            ctx.fillStyle = backgroundColor;
            ctx.fillRect(0, 0, size, size);
        } else if (backgroundType === 'gradient') {
            const gradient = ctx.createLinearGradient(0, 0, size, size);
            gradient.addColorStop(0, backgroundColor);
            gradient.addColorStop(1, adjustColor(backgroundColor, -20));
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, size, size);
        }
    }

    // Calculate image dimensions with padding
    const paddingPx = (size * padding) / 100;
    const imageSize = size - (paddingPx * 2);
    const imageX = paddingPx;
    const imageY = paddingPx;

    // Add shadow if enabled
    if (addShadow && backgroundType !== 'transparent') {
        ctx.shadowColor = 'rgba(0, 0, 0, 0.3)';
        ctx.shadowBlur = size * 0.02;
        ctx.shadowOffsetY = size * 0.01;
    }

    // Draw the image
    ctx.drawImage(currentImage, imageX, imageY, imageSize, imageSize);

    // Reset shadow
    ctx.shadowColor = 'transparent';
    ctx.shadowBlur = 0;
    ctx.shadowOffsetY = 0;

    // For maskable icons, add safe zone indicator
    if (config.maskable) {
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.2)';
        ctx.lineWidth = Math.max(1, size * 0.002);
        ctx.setLineDash([size * 0.02, size * 0.02]);
        const safeZone = size * 0.8;
        const safeOffset = (size - safeZone) / 2;
        ctx.strokeRect(safeOffset, safeOffset, safeZone, safeZone);
        ctx.setLineDash([]);
    }

    // Restore context
    ctx.restore();

    return canvas;
}

// Fallback function for rounded rectangles
function createRoundedRect(ctx, x, y, width, height, radius) {
    ctx.moveTo(x + radius, y);
    ctx.lineTo(x + width - radius, y);
    ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
    ctx.lineTo(x + width, y + height - radius);
    ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
    ctx.lineTo(x + radius, y + height);
    ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
    ctx.lineTo(x, y + radius);
    ctx.quadraticCurveTo(x, y, x + radius, y);
}

function addIconCard(container, canvas, config, quality) {
    const card = el('div', 'icon-card');
    const preview = el('div', 'icon-preview');
    const clone = canvas.cloneNode(true);
    clone.getContext('2d').drawImage(canvas, 0, 0);
    preview.appendChild(clone);
    const size = el('div', 'icon-size', `${config.size}×${config.size}px`);
    const info = el('div', 'icon-info', config.desc || 'Icon');
    if (config.maskable) card.appendChild(el('div', 'icon-type', 'Maskable'));
    const btn = el('button', 'download-btn', 'Download');
    btn.addEventListener('click', () => downloadSingleIcon(canvas, config.name, quality));
    card.append(preview, size, info, btn);
    container.appendChild(card);
}

// handled by generateIconsToRight using canvasToImg

function downloadSingleIcon(canvas, filename, quality = 1.0) {
    try {
        const link = document.createElement('a');
        link.download = filename;
    link.href = canvas.toDataURL('image/png', quality);
        link.click();
    toast(`Downloaded ${filename}`, 'success');
    } catch (error) {
    toast(`Failed to download ${filename}`, 'error');
    }
}

async function downloadIcons() {
    if (Object.keys(generatedIcons).length === 0) {
        toast('No icons generated yet. Please upload an image first.', 'error');
        return;
    }
    showOverlay('Packaging icons...', 'Preparing your ZIP');
    
    try {
        const zip = new JSZip();
        const iconsFolder = zip.folder('pwa-icons');
        
        // Add all icons
        for (const [filename, canvas] of Object.entries(generatedIcons)) {
            const dataURL = canvas.toDataURL('image/png', 1.0);
            const base64Data = dataURL.split(',')[1];
            iconsFolder.file(filename, base64Data, { base64: true });
        }
        // Generate and download zip
        const blob = await zip.generateAsync({ type: 'blob' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'af-pwa-icons-package.zip';
        link.click();
        toast('Icons ZIP downloaded', 'success');
    } catch (error) {
        toast('Failed to create download package', 'error');
        console.error(error);
    }
    hideOverlay();
}

function downloadManifest() {
    try {
    const manifest = generateManifestJSON();
        const blob = new Blob([JSON.stringify(manifest, null, 2)], { type: 'application/json' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'manifest.json';
        link.click();
    toast('Manifest downloaded', 'success');
    } catch (error) {
    toast('Failed to download manifest', 'error');
    }
}

function downloadLaravelConfig() {
    try {
    const config = generateLaravelConfig();
    const blob = new Blob([config], { type: 'text/plain' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'af-pwa-icons-config.php';
    link.click();
    toast('Laravel config downloaded', 'success');
    } catch (error) {
    toast('Failed to generate configuration', 'error');
    }
}

function downloadComplete() {
    try {
    showOverlay('Building package...', 'Manifest, icons, offline and config');
    buildCompleteZip();
    } catch (error) {
    toast('Failed to build package', 'error');
    }
}

function generateManifestJSON() {
    const appName = byId('appName').value || 'My PWA App';
    const shortName = byId('shortName').value || 'PWA';
    const themeColor = byId('themeColor').value || '#2563eb';
    const backgroundColor = byId('manifestBackgroundColor').value || '#0b1220';
    const start_url = byId('startUrl').value || '/';
    const scope = byId('scope').value || '/';
    const display = byId('displayMode').value || 'standalone';
    const orientation = byId('orientation').value || 'any';
    const description = byId('appDescription').value || 'A Progressive Web App powered by AF-PWA';
    const lang = byId('appLanguage').value || 'en';
    const dir = byId('textDirection').value || 'ltr';
    const category = byId('appCategory').value || '';
    const icons = Object.entries(generatedIcons).map(([name, canvas]) => {
        const size = name.match(/(\d+)x\1/)?.[1];
        return size ? { src: `/icons/${name}`, sizes: `${size}x${size}`, type: 'image/png', purpose: name.includes('maskable') ? 'maskable' : 'any' } : null;
    }).filter(Boolean);
    const manifest = { name: appName, short_name: shortName, description, start_url, scope, display, background_color: backgroundColor, theme_color: themeColor, orientation, icons, lang, dir };
    if (category) manifest.categories = [category];
    if (protocolHandlers.length) manifest.protocol_handlers = protocolHandlers.map(p => ({ protocol: p.scheme, url: p.url }));
    if (shortcuts && shortcuts.length) {
        manifest.shortcuts = shortcuts.filter(s => s.name && s.url).map(s => ({ name: s.name, short_name: s.short_name || s.name, url: s.url, description: s.description || '' }));
    }
    return manifest;
}

function generateLaravelConfig() {
    const appName = byId('appName').value || 'My PWA App';
    const shortName = byId('shortName').value || 'PWA';
    const themeColor = byId('themeColor').value || '#2563eb';
    const backgroundColor = byId('manifestBackgroundColor').value || '#0b1220';
    const iconsPhp = Object.keys(generatedIcons).map(name => {
        const size = name.match(/(\d+)x\1/)?.[1] || '0';
        const maskable = name.includes('maskable');
        return `        [\n            'src' => '/icons/${name}',\n            'sizes' => '${size}x${size}',\n            'type' => 'image/png'${maskable ? ",\n            'purpose' => 'maskable'" : ''}\n        ]`;
    }).join(',\n');

    return `<?php
// AF-PWA Icon Configuration
// Generated by AF-PWA Icon Generator
// Package: artflow-studio/pwa

return [
        'name' => '${appName}',
        'short_name' => '${shortName}',
        'description' => 'A Progressive Web Application built with AF-PWA',
        'start_url' => '/',
        'display' => 'standalone',
        'background_color' => '${backgroundColor}',
        'theme_color' => '${themeColor}',
        'orientation' => 'any',
        'scope' => '/',

        'icons' => [
${iconsPhp}
        ],
];`;
}

function generateReadme() {
    const appName = document.getElementById('appName').value || 'My PWA App';
    const entries = Object.keys(generatedIcons);
    const list = entries.map(name => {
        const m = name.match(/(\d+)x\1/);
        const size = m ? `${m[1]}×${m[1]}px` : '';
        const isMask = name.includes('maskable');
        return `- **${name}** ${size ? `(${size})` : ''}${isMask ? ' [Maskable]' : ''}`;
    }).join('\n');
    
    return `# ${appName} - PWA Icons Package

This package contains all the necessary assets for your Progressive Web Application generated with AF-PWA Icon Generator.

## Package Information
- **Laravel Package**: artflow-studio/pwa
- **Generated**: ${new Date().toLocaleDateString()}
- **Total Icons**: ${entries.length}

## Installation

### For AF-PWA Laravel Package

1. Copy all icon files in \`public/icons\` to your Laravel public/icons directory
2. Copy \`public/manifest.json\` to your public directory
3. Merge the provided configuration from \`config/af-pwa-icons.php\` into \`config/af-pwa.php\`
4. Run: \`php artisan af-pwa:refresh\`

### Manual Installation

1. Add \`<link rel="manifest" href="/manifest.json">\` to your HTML
2. Copy icon files to your \`public/icons\` directory

## Icons Included

${list || '- (Generate icons to see the list)'}

---

Generated by AF-PWA Icon Generator`;
}

function generatePreviewHTMLContent() {
    const appName = document.getElementById('appName').value || 'My PWA App';
    const entries = Object.keys(generatedIcons);
    const items = entries.map(name => {
        const m = name.match(/(\d+)x\1/);
        const size = m ? m[1] : '';
        const mask = name.includes('maskable');
        return { name, size, mask };
    });
    return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${appName} - PWA Icon Preview</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 20px; 
            background: #f5f5f5; 
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-radius: 12px;
        }
        .icon-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); 
            gap: 20px; 
            margin: 30px 0; 
        }
        .icon-card { 
            background: white; 
            padding: 20px; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
            text-align: center;
            transition: transform 0.3s ease;
        }
        .icon-card:hover {
            transform: translateY(-4px);
        }
        .icon-preview { 
            width: 100px; 
            height: 100px; 
            margin: 0 auto 15px; 
            border: 1px solid #dee2e6; 
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }
        .icon-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        h1 { margin: 0; font-size: 2.5rem; }
        h2 { color: #495057; margin: 40px 0 20px; font-size: 1.8rem; }
        .subtitle { opacity: 0.9; margin-top: 10px; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .maskable-badge {
            background: #007bff;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 8px;
        }
        .installation {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin: 30px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>${appName}</h1>
        <div class="subtitle">PWA Icon Package - Generated with AF-PWA</div>
        <div class="stats">
            <div class="stat">
        <div class="stat-number">${items.length}</div>
                <div>Total Icons</div>
            </div>
            <div class="stat">
        <div class="stat-number">${items.filter(i => i.mask).length}</div>
                <div>Maskable Icons</div>
            </div>
            <div class="stat">
        <div class="stat-number">${items.filter(i => i.name.startsWith('apple-icon')).length}</div>
        <div>Apple Touch Icons</div>
            </div>
        </div>
    </div>
    
    <h2>📱 All Generated Icons</h2>
    <div class="icon-grid">
    ${items.map(icon => `
        <div class="icon-card">
            <div class="icon-preview">
        <img src="icons/${icon.name}" alt="${icon.name}" onerror="this.style.display='none'">
            </div>
        <strong>${icon.size ? `${icon.size}×${icon.size}px` : ''}</strong>
        ${icon.mask ? '<span class="maskable-badge">MASKABLE</span>' : ''}
            <br>
        <small style="color: #6c757d;">${icon.name}</small>
        </div>
        `).join('')}
    </div>
    
    <div class="installation">
        <h2>🚀 Installation Instructions</h2>
        
        <h3>For AF-PWA Laravel Package</h3>
        <ol>
            <li>Copy all icon files to your <code>public/icons/</code> directory</li>
            <li>Update your <code>config/af-pwa.php</code> with the provided configuration</li>
            <li>Run: <code>php artisan af-pwa:refresh</code></li>
            <li>Test: <code>php artisan af-pwa:test</code></li>
        </ol>
        
        <h3>Manual Installation</h3>
        <ol>
            <li>Copy <code>manifest.json</code> to your public directory</li>
            <li>Add to your HTML: <code>&lt;link rel="manifest" href="/manifest.json"&gt;</code></li>
            <li>Copy icon files to your <code>public/icons/</code> directory</li>
        </ol>
        
    <h3>Install AF-PWA Package</h3>
    <code>composer require artflow-studio/pwa</code>
    </div>
    
    <div style="text-align: center; margin: 40px 0; color: #6c757d;">
    <p>Generated by <strong>AF-PWA Icon Generator</strong></p>
    <p>Package: <code>artflow-studio/pwa</code></p>
    </div>
</body>
</html>`;
}

// New overlay-based progress
function showOverlay(title, msg) { byId('loadingOverlay').classList.remove('hidden'); byId('loadingTitle').textContent = title; byId('loadingMessage').textContent = msg; byId('loadingProgress').style.width = '0%'; byId('loadingPercentage').textContent = '0%'; }
function hideOverlay() { byId('loadingOverlay').classList.add('hidden'); }

function toast(msg, type = 'info') {
    const box = byId('notificationsContainer');
    const t = el('div', 'toast ' + (type === 'success' ? 'success' : type === 'error' ? 'error' : ''));
    t.textContent = msg;
    box.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

function adjustColor(color, amount) {
    const num = parseInt(color.replace("#", ""), 16);
    const amt = Math.round(2.55 * amount);
    const R = Math.max(0, Math.min(255, (num >> 16) + amt));
    const G = Math.max(0, Math.min(255, (num >> 8 & 0x00FF) + amt));
    const B = Math.max(0, Math.min(255, (num & 0x0000FF) + amt));
    return "#" + (0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1);
}

// Offline preview renderer
function renderOfflinePreview(template) {
    const wrap = byId('offlinePagePreview');
    const title = byId('offlineMessage').value || "You're currently offline";
    const desc = byId('offlineDescription').value || 'Please check your internet connection and try again.';
    const showStatus = byId('offlineIndicator').checked;
    const showRetry = byId('offlineRetry').checked;
    const brand = byId('themeColor').value || '#2563eb';

    const status = showStatus ? `<div style="display:flex;align-items:center;gap:6px;color:#93c5fd;background:#111c33;border:1px solid #1e3a8a;padding:6px 10px;border-radius:999px;width:max-content;margin-bottom:12px"><span style="width:8px;height:8px;border-radius:50%;background:#ef4444;display:inline-block"></span> Offline</div>` : '';
    const retry = showRetry ? `<button style="background:${brand};border:0;color:white;padding:8px 12px;border-radius:8px;cursor:pointer">Retry</button>` : '';

    const branded = `
        <div style="display:grid;gap:10px;justify-items:center;text-align:center;">
            ${status}
            <div style="width:56px;height:56px;border-radius:14px;background:#0f172a;border:1px solid #1f2937;display:grid;place-items:center;color:#64748b">⚡</div>
            <h3 style="margin:4px 0 0">${title}</h3>
            <p style="color:#94a3b8;margin:0 0 8px">${desc}</p>
            ${retry}
        </div>`;

    const minimal = `<div><h3>${title}</h3><p style="color:#94a3b8">${desc}</p>${retry}</div>`;
    const interactive = `<div>${branded}<div style="margin-top:12px;color:#94a3b8;font-size:12px">Tips: Try toggling Airplane mode off, or reconnect to Wi‑Fi.</div></div>`;
    const html = template === 'branded' ? branded : template === 'interactive' ? interactive : minimal;
    wrap.innerHTML = html;
}

// Protocol handlers
function addProtocolHandler() {
    const scheme = byId('protocolScheme').value.trim();
    const url = byId('protocolUrl').value.trim();
    if (!scheme || !url) return toast('Enter scheme and URL', 'error');
    protocolHandlers.push({ scheme, url });
    byId('protocolScheme').value = '';
    byId('protocolUrl').value = '';
    renderProtocolList();
}
function renderProtocolList() {
    const list = byId('protocolList');
    list.innerHTML = '';
    protocolHandlers.forEach((p, idx) => {
        const row = el('div', null, `${p.scheme} → ${p.url}`);
        row.style.fontSize = '12px'; row.style.color = '#94a3b8';
        list.appendChild(row);
    });
}

// Shortcuts
function addShortcut() {
    if (shortcuts.length >= 4) return toast('Maximum 4 shortcuts', 'error');
    shortcuts.push({ name: '', short_name: '', url: '', description: '' });
    renderShortcuts();
}
function removeShortcut(index) {
    shortcuts.splice(index, 1);
    renderShortcuts();
}
function renderShortcuts() {
    const container = qs('.shortcuts-container');
    container.innerHTML = '';
    shortcuts.forEach((s, i) => {
        const item = document.createElement('div');
        item.className = 'shortcut-item';
        item.innerHTML = `
            <div class="shortcut-header">
                <h5>Shortcut ${i + 1}</h5>
                <button class="remove-shortcut" onclick="removeShortcut(${i})"><i class="fas fa-trash"></i></button>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Name</label><input type="text" value="${s.name}" oninput="shortcuts[${i}].name=this.value"></div>
                <div class="form-group"><label>Short Name</label><input type="text" value="${s.short_name}" oninput="shortcuts[${i}].short_name=this.value"></div>
            </div>
            <div class="form-group"><label>URL</label><input type="url" value="${s.url}" oninput="shortcuts[${i}].url=this.value"></div>
            <div class="form-group"><label>Description</label><input type="text" value="${s.description}" oninput="shortcuts[${i}].description=this.value"></div>
        `;
        container.appendChild(item);
    });
}

// Complete ZIP
async function buildCompleteZip() {
    const zip = new JSZip();
    const iconsFolder = zip.folder('public/icons');
    for (const [name, canvas] of Object.entries(generatedIcons)) {
        const dataURL = canvas.toDataURL('image/png', 1.0);
        iconsFolder.file(name, dataURL.split(',')[1], { base64: true });
    }
    const manifest = JSON.stringify(generateManifestJSON(), null, 2);
    zip.file('public/manifest.json', manifest);
    // offline page template
    zip.file('resources/views/vendor/af-pwa/offline/custom.blade.php', offlineBladeTemplate());
    // Laravel config
    zip.file('config/af-pwa-icons.php', generateLaravelConfig());
    // README
    zip.file('README-PWA.txt', 'Generated by AF-PWA Icon Generator');
    const blob = await zip.generateAsync({ type: 'blob' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'af-pwa-complete.zip';
    link.click();
    hideOverlay();
    toast('Complete package downloaded', 'success');
}

function offlineBladeTemplate() {
    const msg = byId('offlineMessage').value || "You're offline";
    const desc = byId('offlineDescription').value || 'Please check your internet connection and try again.';
    return `@extends('layouts.app')
@section('content')
<div style="display:grid;place-items:center;min-height:70vh">
    <div style="text-align:center">
        <h2>${msg}</h2>
        <p>${desc}</p>
    </div>
</div>
@endsection`;
}

function generatePWA() {
    if (!currentImage) return toast('Upload a logo first', 'error');
    downloadComplete();
}

// UI helpers
function toggleBottomPanel(forceOpen) {
    const panel = byId('bottomPanel');
    if (forceOpen === true) panel.classList.remove('hidden');
    else panel.classList.toggle('hidden');
}
function setCounts() {
    byId('iconCount').textContent = Object.keys(generatedIcons).length.toString();
    const settings = qsa('.form-group input, .form-group select, .form-group textarea').length;
    byId('settingsCount').textContent = settings.toString();
    const label = qs('#iconsPreview .icon-count');
    if (label) label.textContent = `${Object.keys(generatedIcons).length} icons generated`;
}

// DOM utils
function byId(id) { return document.getElementById(id); }
function qs(sel) { return document.querySelector(sel); }
function qsa(sel) { return Array.from(document.querySelectorAll(sel)); }
function el(tag, cls, text) { const n = document.createElement(tag); if (cls) n.className = cls; if (text) n.textContent = text; return n; }
function delay(ms) { return new Promise(r => setTimeout(r, ms)); }
function canvasToImg(canvas, desired = 0) { const img = new Image(); img.src = canvas.toDataURL('image/png', 1.0); if (desired) { img.style.width = desired + 'px'; img.style.height = desired + 'px'; } return img; }
