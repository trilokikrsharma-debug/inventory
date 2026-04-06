<?php
$item = is_array($item ?? null) ? $item : [];
$company = is_array($company ?? null) ? $company : [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payslip <?= Helper::escape($item['employee_code'] ?? '') ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 24px; }
        .sheet { max-width: 820px; margin: 0 auto; background: #fff; border-radius: 18px; box-shadow: 0 18px 48px rgba(15,23,42,.08); overflow: hidden; }
        .hero { padding: 28px 32px; background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%); color: #e2e8f0; }
        .hero h1 { margin: 0 0 6px; font-size: 28px; }
        .hero p { margin: 0; color: #cbd5e1; }
        .content { padding: 28px 32px; }
        .grid { display: table; width: 100%; border-collapse: separate; border-spacing: 16px 16px; }
        .grid .cell { display: table-cell; width: 50%; vertical-align: top; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; }
        .label { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: #64748b; margin-bottom: 8px; }
        .value { font-size: 18px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { font-size: 12px; text-transform: uppercase; letter-spacing: .08em; color: #64748b; }
        .text-end { text-align: right; }
        .totals { margin-top: 18px; width: 100%; }
        .totals td { border: 0; padding: 6px 0; }
        .net { font-size: 22px; font-weight: 800; color: #1d4ed8; }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="hero">
            <h1>Payslip</h1>
            <p><?= Helper::escape($company['company_name'] ?? APP_NAME) ?> • Payroll Month <?= Helper::escape(date('F Y', strtotime(($item['payroll_month'] ?? date('Y-m')) . '-01'))) ?></p>
        </div>
        <div class="content">
            <div class="grid">
                <div class="cell">
                    <div class="label">Employee</div>
                    <div class="value"><?= Helper::escape($item['full_name'] ?? '-') ?></div>
                    <div><?= Helper::escape($item['employee_code'] ?? '-') ?> • <?= Helper::escape($item['designation'] ?? '-') ?></div>
                    <div><?= Helper::escape($item['department'] ?? '-') ?></div>
                </div>
                <div class="cell">
                    <div class="label">Payroll Period</div>
                    <div class="value"><?= Helper::escape(Helper::formatDate($item['period_start'] ?? date('Y-m-01'), 'd M Y')) ?> to <?= Helper::escape(Helper::formatDate($item['period_end'] ?? date('Y-m-t'), 'd M Y')) ?></div>
                    <div>Payment Status: <?= Helper::escape(ucfirst($item['payment_status'] ?? 'pending')) ?></div>
                    <div>Paid At: <?= !empty($item['paid_at']) ? Helper::escape(Helper::formatDate($item['paid_at'], 'd M Y')) : '-' ?></div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-end">Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Monthly Salary</td>
                        <td class="text-end"><?= Helper::formatCurrency($item['gross_salary'] ?? 0) ?></td>
                    </tr>
                    <?php if ((float)($item['allowance_amount'] ?? 0) > 0): ?>
                    <tr>
                        <td>Allowance</td>
                        <td class="text-end"><?= Helper::formatCurrency($item['allowance_amount'] ?? 0) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ((float)($item['bonus_amount'] ?? 0) > 0): ?>
                    <tr>
                        <td>Bonus</td>
                        <td class="text-end"><?= Helper::formatCurrency($item['bonus_amount'] ?? 0) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td>Attendance Units</td>
                        <td class="text-end"><?= Helper::formatQty($item['attendance_units'] ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td>Approved Leave Days</td>
                        <td class="text-end"><?= (int)($item['approved_leave_days'] ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td>Deductions</td>
                        <td class="text-end"><?= Helper::formatCurrency($item['deduction_amount'] ?? 0) ?></td>
                    </tr>
                    <?php if ((float)($item['statutory_deduction_amount'] ?? 0) > 0): ?>
                    <tr>
                        <td>PF</td>
                        <td class="text-end"><?= Helper::formatCurrency($item['pf_amount'] ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td>ESI</td>
                        <td class="text-end"><?= Helper::formatCurrency($item['esi_amount'] ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td>TDS</td>
                        <td class="text-end"><?= Helper::formatCurrency($item['tds_amount'] ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td>Statutory Deductions</td>
                        <td class="text-end"><?= Helper::formatCurrency($item['statutory_deduction_amount'] ?? 0) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ((float)($item['other_deduction_amount'] ?? 0) > 0): ?>
                    <tr>
                        <td>Additional Deductions</td>
                        <td class="text-end"><?= Helper::formatCurrency($item['other_deduction_amount'] ?? 0) ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($item['adjustment_notes'])): ?>
            <table class="totals">
                <tr>
                    <td>Adjustment Notes</td>
                    <td class="text-end"><?= Helper::escape($item['adjustment_notes']) ?></td>
                </tr>
            </table>
            <?php endif; ?>

            <table class="totals">
                <tr>
                    <td>Net Salary</td>
                    <td class="text-end net"><?= Helper::formatCurrency($item['net_salary'] ?? 0) ?></td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
