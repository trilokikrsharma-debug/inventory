<?php
/**
 * Shared Print Bar Partial
 *
 * Expected variable:
 *   $printLabel (optional) - Button label, defaults to 'Print'
 */
$printLabel = $printLabel ?? 'Print';
$docId = (int)($data['id'] ?? 0);
$docType = (string)($data['type'] ?? ($type ?? 'sale'));
$closeUrl = APP_URL . '/index.php?page=dashboard';
if ($docId > 0) {
    if ($docType === 'sale') {
        $closeUrl = APP_URL . '/index.php?page=sales&action=view_sale&id=' . $docId;
    } elseif ($docType === 'purchase') {
        $closeUrl = APP_URL . '/index.php?page=purchases&action=view_purchase&id=' . $docId;
    } elseif ($docType === 'quotation') {
        $closeUrl = APP_URL . '/index.php?page=quotations&action=detail&id=' . $docId;
    } elseif ($docType === 'return') {
        $closeUrl = APP_URL . '/index.php?page=sale_returns&action=detail&id=' . $docId;
    } elseif ($docType === 'receipt' || $docType === 'payment') {
        $closeUrl = APP_URL . '/index.php?page=payments&action=view_payment&id=' . $docId;
    }
}
?>
<div class="no-print" style="text-align:center; padding:12px; background:#f0f0f0; display:flex; justify-content:center; gap:10px;">
    <button id="btnPrintShared" style="padding:8px 24px; cursor:pointer; border:none; background:#4e73df; color:#fff; border-radius:6px; font-size:13px; font-weight:600;">
        <?= Helper::escape($printLabel) ?>
    </button>
    <?php if ($docId > 0): ?>
    <a href="<?= APP_URL ?>/index.php?page=invoice&action=download&type=<?= urlencode($docType) ?>&id=<?= $docId ?>" style="padding:8px 24px; cursor:pointer; border:none; background:#1cc88a; color:#fff; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center;">
        Download PDF
    </a>
    <?php endif; ?>
    <button id="btnCloseShared" style="padding:8px 24px; cursor:pointer; border:1px solid #ccc; background:#fff; border-radius:6px; font-size:13px;">
        Close
    </button>
</div>
<script<?= isset($cspNonce) ? ' nonce="' . Helper::escape($cspNonce) . '"' : '' ?>>
    (function () {
        const printBtn = document.getElementById('btnPrintShared');
        const closeBtn = document.getElementById('btnCloseShared');
        const closeUrl = <?= json_encode($closeUrl) ?>;

        if (printBtn) {
            printBtn.addEventListener('click', function () {
                window.print();
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                if (window.history.length > 1) {
                    window.history.back();
                    setTimeout(function () {
                        if (document.visibilityState === 'visible') {
                            window.location.href = closeUrl;
                        }
                    }, 300);
                    return;
                }
                window.location.href = closeUrl;
            });
        }
    })();
</script>
