<?php
$baseUrl = defined('APP_URL') ? APP_URL : '/';
$homeUrl = $baseUrl;
$loginUrl = $baseUrl . '/login';
$requestId = defined('REQUEST_ID') ? REQUEST_ID : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>404 - Page Not Found</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #07111f;
            --panel: rgba(15, 23, 42, 0.9);
            --panel-border: rgba(148, 163, 184, 0.16);
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #60a5fa;
            --accent-2: #22c55e;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top, rgba(96, 165, 250, 0.18), transparent 32%),
                radial-gradient(circle at bottom right, rgba(34, 197, 94, 0.12), transparent 22%),
                var(--bg);
            color: var(--text);
        }
        .card {
            width: min(100%, 720px);
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 28px;
            box-shadow: 0 24px 80px rgba(2, 6, 23, 0.55);
            padding: clamp(28px, 5vw, 56px);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(96, 165, 250, 0.12);
            border: 1px solid rgba(96, 165, 250, 0.24);
            color: #bfdbfe;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            margin-bottom: 28px;
        }
        .code {
            font-size: clamp(4.5rem, 14vw, 7rem);
            line-height: 0.9;
            font-weight: 900;
            letter-spacing: -0.06em;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #dbeafe 0%, #60a5fa 45%, #22c55e 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        h1 {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            line-height: 1.1;
            margin-bottom: 14px;
        }
        p {
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
            max-width: 56ch;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            color: #08111f;
            background: linear-gradient(135deg, #93c5fd, #60a5fa);
            box-shadow: 0 12px 30px rgba(96, 165, 250, 0.28);
        }
        .btn-secondary {
            color: var(--text);
            background: rgba(15, 23, 42, 0.65);
            border: 1px solid rgba(148, 163, 184, 0.18);
        }
        .meta {
            margin-top: 20px;
            font-size: 0.85rem;
            color: #64748b;
            word-break: break-word;
        }
        .grid {
            margin-top: 32px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }
        .tile {
            border-radius: 18px;
            padding: 16px;
            background: rgba(15, 23, 42, 0.65);
            border: 1px solid rgba(148, 163, 184, 0.12);
        }
        .tile strong {
            display: block;
            margin-bottom: 6px;
            color: #fff;
        }
        .tile span {
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.5;
        }
        @media (max-width: 720px) {
            .grid { grid-template-columns: 1fr; }
            .actions { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="badge">404 - Requested route not found</div>
        <div class="code">404</div>
        <h1>We could not find that page.</h1>
        <p>
            The URL may be misspelled, the page may have moved, or the route may no longer exist.
            Use the buttons below to get back to the product safely.
        </p>

        <div class="actions">
            <a class="btn btn-primary" href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>">Go to home</a>
            <a class="btn btn-secondary" href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">Sign in</a>
        </div>

        <div class="grid">
            <div class="tile">
                <strong>Landing page</strong>
                <span>Product overview, pricing, and signup entry point.</span>
            </div>
            <div class="tile">
                <strong>App login</strong>
                <span>Use your tenant account to access the dashboard.</span>
            </div>
            <div class="tile">
                <strong>Need help?</strong>
                <span>Share the reference ID with support for faster tracing.</span>
            </div>
        </div>

        <?php if ($requestId !== ''): ?>
            <div class="meta">Reference ID: <?= htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </main>
</body>
</html>
