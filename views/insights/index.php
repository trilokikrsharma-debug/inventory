<?php $pageTitle = 'Automated Insights'; ?>

<style>
    .insights-title-icon { color:#f6c23e; }
    .insight-card {
        transition: all 0.3s;
    }
    .insight-card.is-clickable {
        cursor: pointer;
    }
    .insight-card-icon {
        font-size:1.5rem;
        margin-right:0.75rem;
    }
    .insight-priority-badge {
        font-size:0.65rem;
    }
    .insight-message {
        font-size:0.88rem;
        color:#b7b9cc;
    }
    .insight-value {
        font-size:1.1rem;
    }
    .insight-link-hint {
        font-size:0.75rem;
    }
</style>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between">
            <h4 class="mb-0"><i class="fas fa-brain me-2 insights-title-icon"></i>Automated Business Insights</h4>
            <a href="<?= APP_URL ?>/index.php?page=dashboard" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
        <p class="text-muted mt-1 mb-0">Automated data-driven recommendations based on your business activity.</p>
    </div>
</div>

<?php if (empty($insights)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Insights Yet</h5>
                <p class="text-muted">Start recording sales and purchases to unlock automated operational insights.</p>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($insights as $insight): ?>
    <?php
        $allowedColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark', 'light'];
        $insightColor = in_array(($insight['color'] ?? ''), $allowedColors, true) ? $insight['color'] : 'secondary';
    ?>
    <div class="col-lg-6 col-xl-4">
        <div class="card h-100 border-start border-4 border-<?= Helper::escape($insightColor) ?> insight-card <?= !empty($insight['action']) ? 'is-clickable' : '' ?> js-insight-card"
             <?php if (!empty($insight['action'])): ?>data-insight-url="<?= APP_URL ?>/<?= Helper::escape($insight['action']) ?>"<?php endif; ?>>
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <span class="insight-card-icon"><?= Helper::escape($insight['icon'] ?? '') ?></span>
                    <div>
                        <h6 class="mb-0 fw-bold"><?= Helper::escape($insight['title']) ?></h6>
                        <?php if ($insight['priority'] === 'high'): ?>
                        <span class="badge bg-danger insight-priority-badge">HIGH PRIORITY</span>
                        <?php elseif ($insight['priority'] === 'medium'): ?>
                        <span class="badge bg-warning text-dark insight-priority-badge">ATTENTION</span>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="mb-2 insight-message">
                    <?= Helper::escape($insight['message']) ?>
                </p>
                <?php if (!empty($insight['value'])): ?>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-<?= Helper::escape($insightColor) ?> insight-value">
                        <?= Helper::escape($insight['value']) ?>
                    </span>
                    <?php if (!empty($insight['action'])): ?>
                    <span class="text-muted insight-link-hint">
                        <i class="fas fa-arrow-right"></i> View Details
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script nonce="<?= $cspNonce ?? '' ?>">
document.querySelectorAll('.js-insight-card[data-insight-url]').forEach(function (card) {
    card.addEventListener('click', function () {
        window.location = card.dataset.insightUrl || '';
    });
});
</script>
