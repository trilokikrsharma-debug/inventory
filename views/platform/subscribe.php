<?php
$currentPlanId = (int)($latestSubscription['plan_id'] ?? 0);
$currentPlanStatus = (string)($latestSubscription['status'] ?? 'none');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1"><i class="fas fa-crown me-2 text-primary"></i>Plans & Billing</h2>
        <p class="text-muted mb-0">Choose your plan, apply promo code, and complete payment securely.</p>
    </div>
    <div class="text-muted small mt-2 mt-md-0">
        Your referral code: <code><?= e($referralCode ?? '-') ?></code>
    </div>
</div>

<?php if ($currentPlanId > 0): ?>
<div class="alert alert-info border-0 shadow-sm">
    <strong>Current subscription:</strong>
    Plan #<?= (int)$currentPlanId ?>,
    Status <span class="badge bg-secondary text-uppercase"><?= e($currentPlanStatus) ?></span>
    <?php if (!empty($latestSubscription['current_end'])): ?>
        , valid until <?= e(date('Y-m-d', strtotime($latestSubscription['current_end']))) ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Promo Code</label>
                <input type="text" id="promo_code" class="form-control text-uppercase" placeholder="e.g. NEWUSER50">
            </div>
            <div class="col-md-3">
                <label class="form-label">Selected Plan ID</label>
                <input type="text" id="selected_plan_label" class="form-control" value="Not selected" readonly>
            </div>
            <div class="col-md-4 d-grid">
                <button type="button" class="btn btn-outline-success" id="btn_apply_promo" disabled>
                    <i class="fas fa-check-circle me-1"></i>Apply Promo
                </button>
            </div>
        </div>
        <div class="mt-3 small" id="promo_message"></div>
        <div class="mt-2" id="promo_summary" style="display:none;">
            <div>Original: <strong id="summary_original">Rs 0.00</strong></div>
            <div>Discount: <strong class="text-success" id="summary_discount">Rs 0.00</strong></div>
            <div>Final Payable: <strong class="text-primary" id="summary_final">Rs 0.00</strong></div>
        </div>
    </div>
</div>

<div class="row g-4" id="plan_grid">
    <?php if (!empty($plans)): ?>
        <?php foreach ($plans as $plan): ?>
            <?php
                $basePrice = SaaSBillingHelper::effectivePlanPrice($plan);
                $regularPrice = (float)$plan['price'];
                $isCurrent = $currentPlanId === (int)$plan['id'] && in_array($currentPlanStatus, ['active', 'pending', 'trial'], true);
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm <?= !empty($plan['is_featured']) ? 'border border-warning' : '' ?>">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="mb-0"><?= e($plan['name']) ?></h5>
                            <?php if (!empty($plan['is_featured'])): ?>
                                <span class="badge bg-warning text-dark">Popular</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($plan['description'])): ?>
                            <p class="text-muted small mb-3"><?= e($plan['description']) ?></p>
                        <?php endif; ?>

                        <div class="mb-3">
                            <?php if (!empty($plan['offer_price']) && (float)$plan['offer_price'] < (float)$plan['price']): ?>
                                <div class="text-muted small"><s>Rs <?= number_format($regularPrice, 2) ?></s></div>
                            <?php endif; ?>
                            <div class="display-6 fw-bold text-primary">Rs <?= number_format($basePrice, 2) ?></div>
                            <div class="text-muted text-uppercase small"><?= e($plan['billing_type']) ?> / <?= (int)$plan['duration_days'] ?> days</div>
                        </div>

                        <ul class="small text-muted ps-3 mb-4">
                            <li>Plan ID: <?= (int)$plan['id'] ?></li>
                            <li>Status: <?= e($plan['status']) ?></li>
                            <li>Razorpay Plan: <?= !empty($plan['razorpay_plan_id']) ? e($plan['razorpay_plan_id']) : 'Not linked' ?></li>
                        </ul>

                        <div class="mt-auto d-grid gap-2">
                            <button
                                type="button"
                                class="btn <?= $isCurrent ? 'btn-outline-secondary' : 'btn-primary' ?> btn-plan-select"
                                data-plan-id="<?= (int)$plan['id'] ?>"
                                data-base="<?= number_format($basePrice, 2, '.', '') ?>"
                                data-name="<?= e($plan['name']) ?>"
                                <?= $isCurrent ? 'disabled' : '' ?>
                            >
                                <?= $isCurrent ? 'Current Plan' : 'Select Plan' ?>
                            </button>
                            <?php if (!$isCurrent): ?>
                            <button
                                type="button"
                                class="btn btn-success btn-start-checkout"
                                data-plan-id="<?= (int)$plan['id'] ?>"
                                data-name="<?= e($plan['name']) ?>"
                            >
                                <i class="fas fa-credit-card me-1"></i>Subscribe
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-warning">No active plans available.</div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($razorpayKey)): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<?php endif; ?>

<?php
$inlineScript = "
const csrfToken = " . json_encode($csrfToken ?? '') . ";
const appUrl = " . json_encode(APP_URL) . ";
const hasRazorpay = " . (!empty($razorpayKey) ? 'true' : 'false') . ";

let selectedPlanId = null;
let appliedPromoByPlan = {};

function setPromoMessage(text, type = 'muted') {
    const box = document.getElementById('promo_message');
    box.className = 'mt-3 small text-' + type;
    box.textContent = text || '';
}

function updateSummary(data) {
    const wrap = document.getElementById('promo_summary');
    if (!data) {
        wrap.style.display = 'none';
        return;
    }
    wrap.style.display = 'block';
    document.getElementById('summary_original').textContent = 'Rs ' + Number(data.base_amount || 0).toFixed(2);
    document.getElementById('summary_discount').textContent = 'Rs ' + Number(data.discount_amount || 0).toFixed(2);
    document.getElementById('summary_final').textContent = 'Rs ' + Number(data.final_amount || 0).toFixed(2);
}

function setSelectedPlan(planId) {
    selectedPlanId = planId;
    document.getElementById('selected_plan_label').value = planId ? String(planId) : 'Not selected';
    document.getElementById('btn_apply_promo').disabled = !planId;

    const promo = appliedPromoByPlan[planId] || null;
    if (promo) {
        setPromoMessage('Promo ' + promo.promo_code + ' already applied for selected plan.', 'success');
        updateSummary(promo);
    } else {
        setPromoMessage('');
        updateSummary(null);
    }
}

async function postForm(url, payload) {
    const body = new URLSearchParams(payload);
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: body.toString()
    });
    return response.json();
}

document.querySelectorAll('.btn-plan-select').forEach(btn => {
    btn.addEventListener('click', () => {
        setSelectedPlan(parseInt(btn.dataset.planId, 10));
    });
});

document.getElementById('btn_apply_promo').addEventListener('click', async () => {
    if (!selectedPlanId) {
        setPromoMessage('Select a plan first.', 'danger');
        return;
    }

    const code = (document.getElementById('promo_code').value || '').trim().toUpperCase();
    if (!code) {
        setPromoMessage('Enter promo code.', 'danger');
        return;
    }

    setPromoMessage('Validating promo...', 'info');

    try {
        const result = await postForm(appUrl + '/index.php?page=saas_billing&action=validate_promo', {
            " . json_encode(CSRF_TOKEN_NAME) . ": csrfToken,
            plan_id: selectedPlanId,
            promo_code: code
        });

        if (!result.success) {
            delete appliedPromoByPlan[selectedPlanId];
            setPromoMessage(result.message || 'Invalid promo code.', 'danger');
            updateSummary(null);
            return;
        }

        appliedPromoByPlan[selectedPlanId] = result;
        setPromoMessage(result.message || 'Promo applied.', 'success');
        updateSummary(result);
    } catch (err) {
        setPromoMessage('Failed to validate promo. Please retry.', 'danger');
    }
});

document.querySelectorAll('.btn-start-checkout').forEach(btn => {
    btn.addEventListener('click', async () => {
        const planId = parseInt(btn.dataset.planId, 10);
        setSelectedPlan(planId);

        if (!hasRazorpay) {
            setPromoMessage('Razorpay key is not configured.', 'danger');
            return;
        }

        const promoCode = ((appliedPromoByPlan[planId] || {}).promo_code) || (document.getElementById('promo_code').value || '').trim().toUpperCase();
        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class=\\\"fas fa-spinner fa-spin me-1\\\"></i>Preparing...';

        try {
            const payload = await postForm(appUrl + '/index.php?page=saas_billing&action=create_checkout', {
                " . json_encode(CSRF_TOKEN_NAME) . ": csrfToken,
                plan_id: planId,
                promo_code: promoCode
            });

            if (!payload.success) {
                setPromoMessage(payload.message || 'Checkout creation failed.', 'danger');
                return;
            }

            const options = payload.checkout || {};
            options.handler = async function (resp) {
                const verify = await postForm(appUrl + '/index.php?page=saas_billing&action=verify_payment', {
                    " . json_encode(CSRF_TOKEN_NAME) . ": csrfToken,
                    local_subscription_id: payload.local_subscription_id,
                    razorpay_payment_id: resp.razorpay_payment_id || '',
                    razorpay_order_id: resp.razorpay_order_id || '',
                    razorpay_subscription_id: resp.razorpay_subscription_id || '',
                    razorpay_signature: resp.razorpay_signature || ''
                });

                if (verify.success) {
                    setPromoMessage(verify.message || 'Payment verified successfully.', 'success');
                    window.location.reload();
                } else {
                    setPromoMessage(verify.message || 'Payment verification failed.', 'danger');
                }
            };
            options.modal = {
                ondismiss: function() {
                    setPromoMessage('Checkout dismissed.', 'warning');
                }
            };

            const rzp = new Razorpay(options);
            rzp.open();
        } catch (err) {
            setPromoMessage('Unexpected error while initiating checkout.', 'danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
});
";
?>
