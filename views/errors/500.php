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
    <title>500 - Server Error</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #080b14;
            --panel: rgba(15, 23, 42, 0.94);
            --panel-border: rgba(239, 68, 68, 0.18);
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #f97316;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top, rgba(239, 68, 68, 0.16), transparent 32%),
                radial-gradient(circle at bottom left, rgba(249, 115, 22, 0.12), transparent 24%),
                var(--bg);
            color: var(--text);
        }
        .card {
            width: min(100%, 740px);
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 28px;
            box-shadow: 0 24px 80px rgba(2, 6, 23, 0.62);
            padding: clamp(28px, 5vw, 56px);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fecaca;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 28px;
        }
        .code {
            font-size: clamp(4.5rem, 14vw, 7rem);
            line-height: 0.9;
            font-weight: 900;
            letter-spacing: -0.06em;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fee2e2 0%, #fb7185 42%, #f97316 100%);
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
            max-width: 60ch;
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
            color: #120b07;
            background: linear-gradient(135deg, #fdba74, #f97316);
            box-shadow: 0 12px 30px rgba(249, 115, 22, 0.25);
        }
        .btn-secondary {
            color: var(--text);
            background: rgba(15, 23, 42, 0.65);
            border: 1px solid rgba(148, 163, 184, 0.18);
        }
        .meta {
            margin-top: 22px;
            font-size: 0.85rem;
            color: #64748b;
            line-height: 1.6;
        }
        .note {
            margin-top: 28px;
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(15, 23, 42, 0.62);
            border: 1px solid rgba(148, 163, 184, 0.12);
            color: var(--muted);
        }
        .note strong {
            display: block;
            color: #fff;
            margin-bottom: 6px;
        }
        @media (max-width: 720px) {
            .actions { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="badge">500 - Internal server error</div>
        <div class="code">500</div>
        <h1>Something went wrong on the server.</h1>
        <p>
            The application hit an unexpected error. The issue has been logged for review,
            and the page has been kept intentionally generic so sensitive details do not leak.
        </p>

        <div class="actions">
            <a class="btn btn-primary" href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>">Go to home</a>
            <a class="btn btn-secondary" href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">Try sign in again</a>
        </div>

        <div class="note">
            <strong>What to do next</strong>
            Retry the action once, or share the reference ID below with support if the error persists.
        </div>

        <?php if ($requestId !== ''): ?>
            <div class="meta">Reference ID: <?= htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </main>
</body>
</html>
