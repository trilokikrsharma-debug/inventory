<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #0f0f1a; color: #e2e8f0;
        }
        .error-container {
            text-align: center; padding: 2.5rem;
            max-width: 480px; width: 90%;
        }
        .error-icon {
            width: 80px; height: 80px; margin: 0 auto 1.5rem;
            border-radius: 50%; background: rgba(99, 102, 241, 0.12);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: #6366f1;
        }
        .error-code {
            font-size: 4rem; font-weight: 800; letter-spacing: -2px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; line-height: 1;
        }
        .error-title {
            font-size: 1.25rem; font-weight: 600; margin: 0.75rem 0 0.5rem;
            color: #f1f5f9;
        }
        .error-message {
            font-size: 0.9rem; color: #94a3b8; line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn-home {
            display: inline-block; padding: 0.625rem 1.75rem;
            background: linear-gradient(135deg, #4e73df, #6366f1);
            color: #fff; text-decoration: none; border-radius: 0.5rem;
            font-weight: 500; font-size: 0.9rem; transition: opacity 0.2s;
        }
        .btn-home:hover { opacity: 0.85; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔍</div>
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-message">
            The page you're looking for doesn't exist or has been moved.<br>
            Please check the URL or return to the dashboard.
        </p>
        <a href="<?= defined('APP_URL') ? APP_URL : '/' ?>" class="btn-home">Go to Dashboard</a>
    </div>
</body>
</html>
