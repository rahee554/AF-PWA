<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're Offline - {{ $appName ?? 'PWA' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        
        .offline-container {
            max-width: 400px;
            padding: 40px 20px;
        }
        
        .offline-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            opacity: 0.8;
        }
        
        .offline-title {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .offline-message {
            font-size: 16px;
            line-height: 1.5;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        .offline-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .offline-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .offline-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }
        
        .offline-status {
            margin-top: 30px;
            font-size: 14px;
            opacity: 0.7;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        @media (max-width: 480px) {
            .offline-container {
                padding: 20px;
            }
            
            .offline-title {
                font-size: 24px;
            }
            
            .offline-message {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon pulse">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M23,9V7H1V9H23M23,13V11H1V13H23M23,17V15H1V17H23Z" opacity="0.3"/>
                <path d="M3,5V3H21V5H3M3,11V9H21V11H3M3,17V15H21V17H3M3,23V21H21V23H3Z"/>
            </svg>
        </div>
        
        <h1 class="offline-title">You're Offline</h1>
        
        <p class="offline-message">
            {{ $message ?? 'It looks like you\'re not connected to the internet. Please check your connection and try again.' }}
        </p>
        
        <div class="offline-actions">
            <button class="offline-btn" onclick="window.location.reload()">
                Try Again
            </button>
            
            <button class="offline-btn" onclick="history.back()">
                Go Back
            </button>
        </div>
        
        <div class="offline-status">
            <span id="connection-status">Checking connection...</span>
        </div>
    </div>

    <script>
        // Check connection status
        function updateConnectionStatus() {
            const status = document.getElementById('connection-status');
            if (navigator.onLine) {
                status.textContent = 'Connection restored! You can try again.';
                status.style.color = '#4CAF50';
            } else {
                status.textContent = 'Still offline. Please check your internet connection.';
                status.style.color = '#FFF';
            }
        }
        
        // Initial check
        updateConnectionStatus();
        
        // Listen for connection changes
        window.addEventListener('online', function() {
            updateConnectionStatus();
            // Auto-reload after a short delay when back online
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        });
        
        window.addEventListener('offline', updateConnectionStatus);
        
        // Retry button functionality
        function retryConnection() {
            if (navigator.onLine) {
                window.location.reload();
            } else {
                // Try to trigger a connection check
                fetch('/').then(() => {
                    window.location.reload();
                }).catch(() => {
                    updateConnectionStatus();
                });
            }
        }
        
        // Auto-retry every 30 seconds
        setInterval(retryConnection, 30000);
    </script>
</body>
</html>
