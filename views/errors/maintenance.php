<?php
$baseUrl = defined('APP_URL') ? APP_URL : '/';
$homeUrl = $baseUrl;
$requestId = defined('REQUEST_ID') ? REQUEST_ID : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Scheduled Maintenance - InvenBill Pro</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #07111f;
            --panel: rgba(15, 23, 42, 0.94);
            --panel-border: rgba(96, 165, 250, 0.18);
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
                radial-gradient(circle at top, rgba(96, 165, 250, 0.16), transparent 32%),
                radial-gradient(circle at bottom right, rgba(34, 197, 94, 0.1), transparent 24%),
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
            text-align: center;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(96, 165, 250, 0.12);
            border: 1px solid rgba(96, 165, 250, 0.2);
            color: #bfdbfe;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 28px;
        }
        .icon {
            width: 88px;
            height: 88px;
            margin: 0 auto 18px;
            border-radius: 24px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(96, 165, 250, 0.18), rgba(34, 197, 94, 0.14));
            border: 1px solid rgba(148, 163, 184, 0.16);
            font-size: 2.3rem;
        }
        h1 {
            font-size: clamp(1.8rem, 4vw, 2.4rem);
            line-height: 1.1;
            margin-bottom: 14px;
        }
        p {
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
            max-width: 60ch;
            margin: 0 auto;
        }
        .status {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            margin-top: 22px;
            padding: 12px 16px;
            border-radius: 999px;
            border: 1px solid rgba(34, 197, 94, 0.2);
            background: rgba(34, 197, 94, 0.08);
            color: #bbf7d0;
            font-size: 0.92rem;
            font-weight: 600;
        }
        .actions {
            display: flex;
            justify-content: center;
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
        .meta {
            margin-top: 18px;
            font-size: 0.85rem;
            color: #64748b;
        }
        @media (max-width: 720px) {
            .actions { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="badge">Scheduled maintenance in progress</div>
        <div class="icon">Maintenance</div>
        <h1>We will be back shortly.</h1>
        <p>
            InvenBill is temporarily offline while we apply updates and verify the deployment.
            No data is being served from this page and no sensitive details are exposed here.
        </p>

        <div class="status">Expected downtime: about 15 minutes</div>

        <div class="actions">
            <a class="btn btn-primary" href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>">Return home</a>
        </div>

        <?php if ($requestId !== ''): ?>
            <div class="meta">Reference ID: <?= htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </main>
</body>
</html>
