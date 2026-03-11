<?php $pageTitle = 'Sale: ' . Helper::escape($sale['invoice_number']); ?>
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=sales">Sales</a></li><li class="breadcrumb-item active"><?= Helper::escape($sale['invoice_number']) ?></li></ol></nav>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/index.php?page=sales&action=edit&id=<?= $sale['id'] ?>" class="btn btn-outline-warning btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
        <a href="<?= APP_URL ?>/index.php?page=invoice&id=<?= $sale['id'] ?>&type=sale" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-pdf me-1"></i>Invoice</a>
        <a href="<?= APP_URL ?>/index.php?page=invoice&action=download&type=sale&id=<?= $sale['id'] ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-download me-1"></i>Download PDF</a>
        <button id="btnViewPrint" type="button" class="btn btn-outline-secondary btn-sm"><i class="fas fa-print me-1"></i>Print</button>
    </div>
</div>

<script nonce="<?= $cspNonce ?>">
document.addEventListener('DOMContentLoaded', function() {
    const printBtn = document.getElementById('btnViewPrint');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }
});
</script>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card" id="invoiceCard">
            <div class="card-body p-4">
                <!-- Invoice Header -->
                <div class="row mb-4">
                    <div class="col-6">
                        <h4 class="text-primary mb-1"><?= Helper::escape($company['company_name'] ?? APP_NAME) ?></h4>
                        <small class="text-muted"><?= Helper::escape($company['company_address'] ?? '') ?><br><?= Helper::escape(($company['company_city'] ?? '') . ', ' . ($company['company_state'] ?? '')) ?><br>Ph: <?= Helper::escape($company['company_phone'] ?? '') ?></small>
                    </div>
                    <div class="col-6 text-end">
                        <h5 class="text-muted mb-1"><?= strtoupper(Helper::escape($company['invoice_title'] ?? 'INVOICE')) ?></h5>
                        <div class="fw-bold fs-5"><?= Helper::escape($sale['invoice_number']) ?></div>
                        <small>Date: <?= Helper::formatDate($sale['sale_date']) ?></small>
                    </div>
                </div>
                <div class="row mb-4 py-3" style="border-top:2px solid var(--border-color);border-bottom:2px solid var(--border-color);">
                    <div class="col-6"><strong>Bill To:</strong><br><?= Helper::escape($sale['customer_name']) ?><br><small class="text-muted"><?= Helper::escape($sale['customer_phone'] ?? '') ?><br><?= Helper::escape($sale['customer_address'] ?? '') ?></small></div>
                    <div class="col-6 text-end"><?= Helper::paymentBadge($sale['payment_status']) ?></div>
                </div>
                <!-- Items -->
                <div class="table-responsive"><table class="table">
                    <thead><tr><th>#</th><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Discount</th><?php if(!isset($company['enable_tax']) || $company['enable_tax']): ?><th class="text-end">Tax</th><?php endif; ?><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    <?php if (!empty($sale['items'])): $i=0; foreach ($sale['items'] as $item): $i++; ?>
                    <tr>
                        <td><?= $i ?></td><td><?= Helper::escape($item['product_name']) ?></td>
                        <td class="text-center"><?= Helper::formatQty($item['quantity']) ?></td>
                        <td class="text-end"><?= Helper::formatCurrency($item['unit_price']) ?></td>
                        <td class="text-end"><?= Helper::formatCurrency($item['discount']) ?></td>
                        <?php if(!isset($company['enable_tax']) || $company['enable_tax']): ?>
                        <td class="text-end"><?= $item['tax_rate'] ?>%</td>
                        <?php endif; ?>
                        <td class="text-end fw-bold"><?= Helper::formatCurrency($item['total']) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6>Summary</h6></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span><?= Helper::formatCurrency($sale['subtotal']) ?></span></div>
                <div class="d-flex justify-content-between mb-2"><span>Tax</span><span><?= Helper::formatCurrency($sale['tax_amount']) ?></span></div>
                <?php if ($sale['discount_amount'] > 0): ?><div class="d-flex justify-content-between mb-2"><span>Discount</span><span class="text-danger">-<?= Helper::formatCurrency($sale['discount_amount']) ?></span></div><?php endif; ?>
                <?php if ($sale['shipping_cost'] > 0): ?><div class="d-flex justify-content-between mb-2"><span>Shipping</span><span><?= Helper::formatCurrency($sale['shipping_cost']) ?></span></div><?php endif; ?>
                <?php if ($sale['round_off'] != 0): ?><div class="d-flex justify-content-between mb-2"><span>Round Off</span><span><?= Helper::formatCurrency($sale['round_off']) ?></span></div><?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between mb-2 fs-5 fw-bold"><span>Total</span><span class="text-primary"><?= Helper::formatCurrency($sale['grand_total']) ?></span></div>
                <div class="d-flex justify-content-between mb-2"><span>Paid</span><span class="text-success"><?= Helper::formatCurrency($sale['paid_amount']) ?></span></div>
                <div class="d-flex justify-content-between"><span>Due</span><span class="text-danger fw-bold"><?= Helper::formatCurrency($sale['due_amount']) ?></span></div>
            </div>
        </div>
        <?php if ($sale['due_amount'] > 0): ?>
        <a href="<?= APP_URL ?>/index.php?page=payments&action=create&type=receipt&customer_id=<?= $sale['customer_id'] ?>&sale_id=<?= $sale['id'] ?>" class="btn btn-success w-100 mt-3"><i class="fas fa-money-bill me-2"></i>Record Payment</a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/index.php?page=sale_returns&action=create&sale_id=<?= $sale['id'] ?>" class="btn btn-outline-warning w-100 mt-2">
            <i class="fas fa-undo me-2"></i>Process Return
        </a>
    </div>
</div>
