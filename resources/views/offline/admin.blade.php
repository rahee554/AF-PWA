<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Offline - {{ $appName ?? 'PWA' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        
        .offline-container {
            max-width: 500px;
            padding: 40px 20px;
        }
        
        .offline-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 30px;
        }
        
        .admin-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .offline-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            opacity: 0.8;
        }
        
        .offline-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .offline-subtitle {
            font-size: 18px;
            opacity: 0.8;
            margin-bottom: 20px;
        }
        
        .offline-message {
            font-size: 16px;
            line-height: 1.5;
            opacity: 0.9;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }
        
        .offline-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 30px;
        }
        
        .offline-btn {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .offline-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }
        
        .offline-btn.primary {
            background: #007bff;
            border-color: #007bff;
        }
        
        .offline-btn.primary:hover {
            background: #0056b3;
        }
        
        .cached-data {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .cached-data h3 {
            font-size: 16px;
            margin-bottom: 12px;
            color: #28a745;
        }
        
        .cached-data p {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .offline-status {
            font-size: 14px;
            opacity: 0.7;
            padding: 16px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        @media (max-width: 600px) {
            .offline-actions {
                grid-template-columns: 1fr;
            }
            
            .offline-container {
                padding: 20px;
            }
            
            .offline-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-header">
            <div class="admin-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12,15C12.81,15 13.5,14.7 14.11,14.11C14.7,13.5 15,12.81 15,12C15,11.19 14.7,10.5 14.11,9.89C13.5,9.3 12.81,9 12,9C11.19,9 10.5,9.3 9.89,9.89C9.3,10.5 9,11.19 9,12C9,12.81 9.3,13.5 9.89,14.11C10.5,14.7 11.19,15 12,15M12,2C14.75,2 17.1,3 19.05,4.95C21,6.9 22,9.25 22,12V13.45C22,14.45 21.65,15.3 21,16C20.3,16.67 19.5,17 18.5,17C17.3,17 16.31,16.5 15.56,15.5C14.56,16.5 13.38,17 12,17C10.63,17 9.45,16.5 8.46,15.54C7.5,14.55 7,13.38 7,12C7,10.63 7.5,9.45 8.46,8.46C9.45,7.5 10.63,7 12,7C13.38,7 14.55,7.5 15.54,8.46C16.5,9.45 17,10.63 17,12V13.45C17,13.86 17.16,14.22 17.46,14.53C17.76,14.84 18.11,15 18.5,15C18.92,15 19.27,14.84 19.57,14.53C19.87,14.22 20,13.86 20,13.45V12C20,9.81 19.23,7.93 17.65,6.35C16.07,4.77 14.19,4 12,4C9.81,4 7.93,4.77 6.35,6.35C4.77,7.93 4,9.81 4,12C4,14.19 4.77,16.07 6.35,17.65C7.93,19.23 9.81,20 12,20H16V22H12C9.25,22 6.9,21 4.95,19.05C3,17.1 2,14.75 2,12C2,9.25 3,6.9 4.95,4.95C6.9,3 9.25,2 12,2Z" />
                </svg>
            </div>
        </div>
        
        <div class="offline-icon pulse">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z" opacity="0.3"/>
                <path d="M12,10A2,2 0 0,0 10,12A2,2 0 0,0 12,14A2,2 0 0,0 14,12A2,2 0 0,0 12,10Z"/>
            </svg>
        </div>
        
        <h1 class="offline-title">Admin Panel Offline</h1>
        <p class="offline-subtitle">Administrative Interface</p>
        
        <div class="offline-message">
            <strong>Connection Lost:</strong> The admin panel requires an internet connection to function properly. Some cached data may still be available below.
        </div>
        
        <div class="cached-data">
            <h3>📊 Cached Information Available</h3>
            <p>Recent dashboard data and user information may be accessible offline. Changes will sync when connection is restored.</p>
        </div>
        
        <div class="offline-actions">
            <button class="offline-btn primary" onclick="window.location.reload()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.65,6.35C16.2,4.9 14.21,4 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20C15.73,20 18.84,17.45 19.73,14H17.65C16.83,16.33 14.61,18 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6C13.66,6 15.14,6.69 16.22,7.78L13,11H20V4L17.65,6.35Z" />
                </svg>
                Retry Connection
            </button>
            
            <button class="offline-btn" onclick="history.back()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20,11V13H8L13.5,18.5L12.08,19.92L4.16,12L12.08,4.08L13.5,5.5L8,11H20Z" />
                </svg>
                Go Back
            </button>
            
            <a href="/admin" class="offline-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z" />
                </svg>
                Admin Home
            </a>
            
            <button class="offline-btn" onclick="clearCache()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z" />
                </svg>
                Clear Cache
            </button>
        </div>
        
        <div class="offline-status">
            <span id="connection-status">Monitoring connection...</span>
        </div>
    </div>

    <script>
        function updateConnectionStatus() {
            const status = document.getElementById('connection-status');
            if (navigator.onLine) {
                status.innerHTML = '✅ Connection restored! Redirecting to admin panel...';
                status.style.color = '#28a745';
                setTimeout(() => window.location.href = '/admin', 2000);
            } else {
                status.innerHTML = '🔴 Admin panel offline. Waiting for connection...';
                status.style.color = '#dc3545';
            }
        }
        
        function clearCache() {
            if ('caches' in window) {
                caches.keys().then(function(cacheNames) {
                    cacheNames.forEach(function(cacheName) {
                        caches.delete(cacheName);
                    });
                });
            }
            localStorage.clear();
            sessionStorage.clear();
            alert('Cache cleared. Please try refreshing the page.');
        }
        
        // Initial check
        updateConnectionStatus();
        
        // Listen for connection changes
        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);
        
        // Auto-retry every 15 seconds for admin
        setInterval(() => {
            if (navigator.onLine) {
                fetch('/admin').then(() => {
                    window.location.href = '/admin';
                }).catch(() => {
                    updateConnectionStatus();
                });
            }
        }, 15000);
    </script>
</body>
</html>
