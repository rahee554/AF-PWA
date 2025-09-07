<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Area Offline - {{ $appName ?? 'PWA' }}</title>
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
        
        .member-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
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
            border-left: 4px solid #17a2b8;
        }
        
        .member-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 30px;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }
        
        .feature-card svg {
            width: 32px;
            height: 32px;
            margin-bottom: 8px;
            opacity: 0.8;
        }
        
        .feature-card h4 {
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .feature-card p {
            font-size: 12px;
            opacity: 0.7;
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
            background: #17a2b8;
            border-color: #17a2b8;
        }
        
        .offline-btn.primary:hover {
            background: #138496;
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
            
            .member-features {
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
            <div class="member-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" />
                </svg>
            </div>
        </div>
        
        <div class="offline-icon pulse">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z" opacity="0.3"/>
                <path d="M12,10A2,2 0 0,0 10,12A2,2 0 0,0 12,14A2,2 0 0,0 14,12A2,2 0 0,0 12,10Z"/>
            </svg>
        </div>
        
        <h1 class="offline-title">Member Area Offline</h1>
        <p class="offline-subtitle">Member Portal</p>
        
        <div class="offline-message">
            <strong>Connection Required:</strong> The member area needs an internet connection to access your latest information and updates.
        </div>
        
        <div class="member-features">
            <div class="feature-card">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z" />
                </svg>
                <h4>Profile</h4>
                <p>View & update your member profile</p>
            </div>
            
            <div class="feature-card">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M16,6L18.29,8.29L13.41,13.17L9.41,9.17L2,16.59L3.41,18L9.41,12L13.41,16L19.71,9.71L22,12V6H16Z" />
                </svg>
                <h4>Events</h4>
                <p>Access member events & activities</p>
            </div>
            
            <div class="feature-card">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20,2H4A2,2 0 0,0 2,4V22L6,18H20A2,2 0 0,0 22,16V4A2,2 0 0,0 20,2Z" />
                </svg>
                <h4>Messages</h4>
                <p>Read announcements & messages</p>
            </div>
            
            <div class="feature-card">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9,11H15L13,9L11,11M7,5V3C7,2.45 7.45,2 8,2H16C16.55,2 17,2.45 17,3V5H21V7H3V5H7M4,19V8H20V19C20,20.1 19.1,21 18,21H6C4.9,21 4,20.1 4,19Z" />
                </svg>
                <h4>Resources</h4>
                <p>Download member resources</p>
            </div>
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
            
            <a href="/member" class="offline-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z" />
                </svg>
                Member Home
            </a>
            
            <a href="/login" class="offline-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10,17V14H3V10H10V7L15,12L10,17M10,2H19A2,2 0 0,1 21,4V20A2,2 0 0,1 19,22H10A2,2 0 0,1 8,20V18H10V20H19V4H10V6H8V4A2,2 0 0,1 10,2Z" />
                </svg>
                Login
            </a>
        </div>
        
        <div class="offline-status">
            <span id="connection-status">Checking member area connection...</span>
        </div>
    </div>

    <script>
        function updateConnectionStatus() {
            const status = document.getElementById('connection-status');
            if (navigator.onLine) {
                status.innerHTML = '✅ Connection restored! Redirecting to member area...';
                status.style.color = '#28a745';
                setTimeout(() => window.location.href = '/member', 2000);
            } else {
                status.innerHTML = '🔴 Member area offline. Please check your connection.';
                status.style.color = '#dc3545';
            }
        }
        
        // Initial check
        updateConnectionStatus();
        
        // Listen for connection changes
        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);
        
        // Auto-retry every 20 seconds for members
        setInterval(() => {
            if (navigator.onLine) {
                fetch('/member').then(() => {
                    window.location.href = '/member';
                }).catch(() => {
                    updateConnectionStatus();
                });
            }
        }, 20000);
    </script>
</body>
</html>
