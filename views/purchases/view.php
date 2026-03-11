<?php $pageTitle = 'Purchase: ' . Helper::escape($purchase['invoice_number']); ?>
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=purchases">Purchases</a></li><li class="breadcrumb-item active"><?= Helper::escape($purchase['invoice_number']) ?></li></ol></nav>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/index.php?page=purchases&action=edit&id=<?= $purchase['id'] ?>" class="btn btn-outline-warning btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
        <a href="<?= APP_URL ?>/index.php?page=invoice&type=purchase&id=<?= $purchase['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-invoice me-1"></i>Purchase Bill</a>
        <a href="<?= APP_URL ?>/index.php?page=invoice&action=download&type=purchase&id=<?= $purchase['id'] ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-download me-1"></i>Download PDF</a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-print me-1"></i>Print</button>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6><i class="fas fa-file-invoice me-2"></i><?= Helper::escape($purchase['invoice_number']) ?></h6><?= Helper::paymentBadge($purchase['payment_status']) ?></div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6"><strong>Supplier:</strong> <?= Helper::escape($purchase['supplier_name']) ?><br><small class="text-muted"><?= Helper::escape($purchase['supplier_phone'] ?? '') ?></small></div>
                    <div class="col-md-6 text-md-end"><strong>Date:</strong> <?= Helper::formatDate($purchase['purchase_date']) ?><br><small class="text-muted">Ref: <?= Helper::escape($purchase['reference_number'] ?? '-') ?></small></div>
                </div>
                <div class="table-responsive"><table class="table table-sm">
                    <thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Price</th><th>Discount</th><th>Tax</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    <?php if (!empty($purchase['items'])): $i=0; foreach ($purchase['items'] as $item): $i++; ?>
                    <tr>
                        <td><?= $i ?></td>
                        <td><?= Helper::escape($item['product_name']) ?></td>
                        <td><?= Helper::formatQty($item['quantity']) ?></td>
                        <td><?= Helper::formatCurrency($item['unit_price']) ?></td>
                        <td><?= Helper::formatCurrency($item['discount']) ?></td>
                        <td><?= $item['tax_rate'] ?>% (<?= Helper::formatCurrency($item['tax_amount']) ?>)</td>
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
                <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span><?= Helper::formatCurrency($purchase['subtotal']) ?></span></div>
                <div class="d-flex justify-content-between mb-2"><span>Tax</span><span><?= Helper::formatCurrency($purchase['tax_amount']) ?></span></div>
                <?php if ($purchase['discount_amount'] > 0): ?><div class="d-flex justify-content-between mb-2"><span>Discount</span><span class="text-danger">-<?= Helper::formatCurrency($purchase['discount_amount']) ?></span></div><?php endif; ?>
                <?php if ($purchase['shipping_cost'] > 0): ?><div class="d-flex justify-content-between mb-2"><span>Shipping</span><span><?= Helper::formatCurrency($purchase['shipping_cost']) ?></span></div><?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between mb-2 fs-5 fw-bold"><span>Grand Total</span><span class="text-primary"><?= Helper::formatCurrency($purchase['grand_total']) ?></span></div>
                <div class="d-flex justify-content-between mb-2"><span>Paid</span><span class="text-success"><?= Helper::formatCurrency($purchase['paid_amount']) ?></span></div>
                <div class="d-flex justify-content-between"><span>Due</span><span class="text-danger fw-bold"><?= Helper::formatCurrency($purchase['due_amount']) ?></span></div>
            </div>
        </div>
        <?php if ($purchase['note']): ?>
        <div class="card mt-3"><div class="card-body"><strong>Note:</strong><br><?= nl2br(Helper::escape($purchase['note'])) ?></div></div>
        <?php endif; ?>
        <?php if ($purchase['due_amount'] > 0): ?>
        <a href="<?= APP_URL ?>/index.php?page=payments&action=create&type=payment&supplier_id=<?= $purchase['supplier_id'] ?>&purchase_id=<?= $purchase['id'] ?>&amount=<?= $purchase['due_amount'] ?>" class="btn btn-primary w-100 mt-3">
            <i class="fas fa-money-bill me-2"></i>Make Payment
        </a>
        <?php endif; ?>
    </div>
</div>
