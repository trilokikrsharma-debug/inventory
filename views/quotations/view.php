<?php
$company      = (new SettingsModel())->getSettings();
$pageTitle    = 'Quotation: ' . Helper::escape($quote['quotation_number']);
$statusColors = ['draft' => 'secondary', 'sent' => 'info', 'converted' => 'success', 'cancelled' => 'danger'];
$isTaxEnabled = !isset($company['enable_tax']) || !empty($company['enable_tax']);
$isGstEnabled = !isset($company['enable_gst']) || !empty($company['enable_gst']);
?>
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=quotations">Quotations</a></li>
        <li class="breadcrumb-item active"><?= Helper::escape($quote['quotation_number']) ?></li>
    </ol></nav>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/index.php?page=invoice&type=quotation&id=<?= $quote['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-invoice me-1"></i>Print Quotation</a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-print me-1"></i>Print</button>
        <?php if ($quote['status'] !== 'converted' && $quote['status'] !== 'cancelled'): ?>
        <!-- Update Status -->
        <div class="dropdown">
            <button class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-edit me-1"></i>Update Status
            </button>
            <ul class="dropdown-menu">
                <?php foreach (['draft'=>'Draft','sent'=>'Mark as Sent','cancelled'=>'Cancel'] as $s => $label): ?>
                <?php if ($s !== $quote['status']): ?>
                <li>
                    <form method="POST" action="<?= APP_URL ?>/index.php?page=quotations&action=updateStatus">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="id" value="<?= $quote['id'] ?>">
                        <input type="hidden" name="status" value="<?= $s ?>">
                        <button type="submit" class="dropdown-item"><?= $label ?></button>
                    </form>
                </li>
                <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <!-- Convert to Sale -->
        <?php if (Session::hasPermission('quotations.convert')): ?>
        <form method="POST" action="<?= APP_URL ?>/index.php?page=quotations&action=convert" data-confirm="Convert this quotation to a Sale? Stock will be deducted.">
            <?= CSRF::field() ?>
            <input type="hidden" name="id" value="<?= $quote['id'] ?>">
            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Convert to Sale</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
        <!-- Delete Quotation -->
        <?php if (Session::hasPermission('quotations.delete')): ?>
        <form method="POST" action="<?= APP_URL ?>/index.php?page=quotations&action=delete" data-confirm="Delete this quotation permanently?">
            <?= CSRF::field() ?>
            <input type="hidden" name="id" value="<?= $quote['id'] ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash me-1"></i>Delete</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <!-- Header -->
                <div class="row mb-4">
                    <div class="col-6">
                        <h5 class="text-primary">QUOTATION</h5>
                        <h4 class="fw-bold"><?= Helper::escape($quote['quotation_number']) ?></h4>
                        <span class="badge bg-<?= $statusColors[$quote['status']] ?? 'secondary' ?> fs-6">
                            <?= ucfirst($quote['status']) ?>
                        </span>
                    </div>
                    <div class="col-6 text-end">
                        <div><strong>Date:</strong> <?= Helper::formatDate($quote['quotation_date']) ?></div>
                        <?php if ($quote['valid_until']): ?>
                        <div><strong>Valid Until:</strong> <?= Helper::formatDate($quote['valid_until']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Customer -->
                <div class="row mb-4">
                    <div class="col-6">
                        <div class="bg-light rounded p-3">
                            <div class="text-muted small text-uppercase fw-bold mb-1">Customer</div>
                            <div class="fw-bold"><?= Helper::escape($quote['customer_name'] ?? '') ?></div>
                            <?php if ($quote['customer_phone']): ?><div class="small"><?= Helper::escape($quote['customer_phone']) ?></div><?php endif; ?>
                            <?php if ($quote['customer_email']): ?><div class="small"><?= Helper::escape($quote['customer_email']) ?></div><?php endif; ?>
                            <?php if ($quote['customer_address']): ?><div class="small text-muted"><?= Helper::escape($quote['customer_address']) ?></div><?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <table class="table">
                    <thead class="table-light">
                        <tr><th>#</th><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Disc</th><?php if($isTaxEnabled && $isGstEnabled): ?><th class="text-end">Tax</th><?php endif; ?><th class="text-end">Total</th></tr>
                    </thead>
                    <tbody>
                    <?php $i=0; foreach ($quote['items'] as $item): $i++; ?>
                    <tr>
                        <td><?= $i ?></td>
                        <td><?= Helper::escape($item['product_name']) ?><br><small class="text-muted"><?= Helper::escape($item['sku'] ?? '') ?></small></td>
                        <td class="text-center"><?= Helper::formatQty($item['quantity']) ?></td>
                        <td class="text-end"><?= Helper::formatCurrency($item['unit_price']) ?></td>
                        <td class="text-end"><?= Helper::formatCurrency($item['discount']) ?></td>
                        <?php if($isTaxEnabled && $isGstEnabled): ?>
                        <td class="text-end"><?= Helper::formatCurrency($item['tax_amount']) ?></td>
                        <?php endif; ?>
                        <td class="text-end fw-bold"><?= Helper::formatCurrency($item['total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="row justify-content-end">
                    <div class="col-md-5">
                        <table class="table table-sm">
                            <tr><td>Subtotal</td><td class="text-end"><?= Helper::formatCurrency($quote['subtotal']) ?></td></tr>
                            <?php if ($isTaxEnabled && $isGstEnabled): ?><tr><td>Tax</td><td class="text-end"><?= Helper::formatCurrency($quote['tax_amount']) ?></td></tr><?php endif; ?>
                            <?php if ($quote['discount_amount'] > 0): ?><tr><td>Discount</td><td class="text-end text-danger">-<?= Helper::formatCurrency($quote['discount_amount']) ?></td></tr><?php endif; ?>
                            <?php if ($quote['shipping_cost'] > 0): ?><tr><td>Shipping</td><td class="text-end"><?= Helper::formatCurrency($quote['shipping_cost']) ?></td></tr><?php endif; ?>
                            <tr class="table-active fw-bold"><td>Grand Total</td><td class="text-end text-primary fs-5"><?= Helper::formatCurrency($quote['grand_total']) ?></td></tr>
                        </table>
                    </div>
                </div>

                <?php if ($quote['note'] || $quote['terms']): ?>
                <div class="row mt-3">
                    <?php if ($quote['note']): ?><div class="col-6"><div class="small text-muted fw-bold">Note</div><div class="small"><?= Helper::escape($quote['note']) ?></div></div><?php endif; ?>
                    <?php if ($quote['terms']): ?><div class="col-6"><div class="small text-muted fw-bold">Terms & Conditions</div><div class="small"><?= Helper::escape($quote['terms']) ?></div></div><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6>Quick Info</h6></div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5">Status</dt>
                    <dd class="col-7"><span class="badge bg-<?= $statusColors[$quote['status']] ?? 'secondary' ?>"><?= ucfirst($quote['status']) ?></span></dd>
                    <dt class="col-5">Created By</dt>
                    <dd class="col-7"><?= Helper::escape($quote['created_by_name'] ?? '') ?></dd>
                    <dt class="col-5">Total Value</dt>
                    <dd class="col-7 fw-bold text-primary"><?= Helper::formatCurrency($quote['grand_total']) ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>
