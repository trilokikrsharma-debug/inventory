<?php
/**
 * Business insight generation service.
 *
 * Encapsulates rule-based dashboard insight generation so controllers only
 * handle access control and response formatting.
 */
class BusinessInsightService {
    public function generateForCurrentTenant(): array {
        $cid = Tenant::id();
        if (!$cid) {
            return [];
        }

        return $this->generateForCompany($cid);
    }

    public function generateForCompany(int $companyId): array {
        if ($companyId <= 0) {
            return [];
        }

        $insights = [];
        $db = Database::getInstance();

        try {
            $thisMonth = $db->query(
                "SELECT COALESCE(SUM(grand_total), 0) as total FROM sales WHERE company_id = ? AND deleted_at IS NULL AND sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
                [$companyId]
            )->fetchColumn();

            $lastMonth = $db->query(
                "SELECT COALESCE(SUM(grand_total), 0) as total FROM sales WHERE company_id = ? AND deleted_at IS NULL AND sale_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') AND sale_date < DATE_FORMAT(CURDATE(), '%Y-%m-01')",
                [$companyId]
            )->fetchColumn();

            if ($lastMonth > 0) {
                $change = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
                $icon = $change >= 0 ? '📈' : '📉';
                $color = $change >= 0 ? 'success' : 'danger';
                $direction = $change >= 0 ? 'up' : 'down';
                $insights[] = [
                    'type' => 'revenue_trend',
                    'icon' => $icon,
                    'color' => $color,
                    'title' => 'Revenue Trend',
                    'message' => "Your revenue is {$direction} by " . abs($change) . "% compared to last month.",
                    'value' => Helper::formatCurrency($thisMonth),
                    'priority' => $change < -10 ? 'high' : 'medium',
                ];
            }
        } catch (\Exception $e) {}

        try {
            $lowStockCount = $db->query(
                "SELECT COUNT(*) FROM products WHERE company_id = ? AND deleted_at IS NULL AND is_active = 1 AND current_stock <= COALESCE(low_stock_alert, 10)",
                [$companyId]
            )->fetchColumn();

            if ($lowStockCount > 0) {
                $insights[] = [
                    'type' => 'low_stock',
                    'icon' => '⚠️',
                    'color' => 'warning',
                    'title' => 'Low Stock Alert',
                    'message' => "{$lowStockCount} product(s) are running low on stock. Reorder soon to avoid stockouts.",
                    'value' => $lowStockCount . ' items',
                    'priority' => $lowStockCount > 5 ? 'high' : 'medium',
                    'action' => 'index.php?page=reports&action=stock',
                ];
            }
        } catch (\Exception $e) {}

        try {
            $customerDues = (float)$db->query(
                "SELECT COALESCE(SUM(current_balance), 0) FROM customers WHERE company_id = ? AND current_balance > 0 AND deleted_at IS NULL",
                [$companyId]
            )->fetchColumn();

            $overdueCount = $db->query(
                "SELECT COUNT(*) FROM sales WHERE company_id = ? AND deleted_at IS NULL AND payment_status IN ('unpaid', 'partial') AND sale_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
                [$companyId]
            )->fetchColumn();

            if ($customerDues > 1000 || $overdueCount > 0) {
                $message = 'You have ' . Helper::formatCurrency($customerDues) . ' in outstanding customer dues.';
                if ($overdueCount > 0) {
                    $message .= " {$overdueCount} invoice(s) are overdue by 30+ days.";
                }
                $insights[] = [
                    'type' => 'outstanding_dues',
                    'icon' => '💰',
                    'color' => 'danger',
                    'title' => 'Outstanding Receivables',
                    'message' => $message,
                    'value' => Helper::formatCurrency($customerDues),
                    'priority' => $overdueCount > 3 ? 'high' : 'medium',
                    'action' => 'index.php?page=customers',
                ];
            }
        } catch (\Exception $e) {}

        try {
            $topProduct = $db->query(
                "SELECT p.name, SUM(si.quantity) as qty FROM sale_items si
                 JOIN sales s ON si.sale_id = s.id JOIN products p ON si.product_id = p.id
                 WHERE s.company_id = ? AND s.deleted_at IS NULL AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY si.product_id ORDER BY qty DESC LIMIT 1",
                [$companyId]
            )->fetch();

            if ($topProduct) {
                $insights[] = [
                    'type' => 'top_product',
                    'icon' => '🌟',
                    'color' => 'info',
                    'title' => 'Best Seller (30 Days)',
                    'message' => '"' . $topProduct['name'] . '" is your top seller with ' . (int)$topProduct['qty'] . ' units sold this month.',
                    'value' => $topProduct['name'],
                    'priority' => 'low',
                ];
            }
        } catch (\Exception $e) {}

        try {
            $profit = $db->query(
                "SELECT COALESCE(SUM(si.total), 0) as revenue, COALESCE(SUM(si.quantity * p.purchase_price), 0) as cost
                 FROM sale_items si
                 JOIN sales s ON si.sale_id = s.id
                 JOIN products p ON si.product_id = p.id
                 WHERE s.company_id = ? AND s.deleted_at IS NULL AND s.sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
                [$companyId]
            )->fetch();

            if ($profit && $profit['revenue'] > 0) {
                $margin = round((($profit['revenue'] - $profit['cost']) / $profit['revenue']) * 100, 1);
                $insights[] = [
                    'type' => 'profit_margin',
                    'icon' => $margin >= 20 ? '✅' : '🔻',
                    'color' => $margin >= 20 ? 'success' : 'warning',
                    'title' => 'Gross Profit Margin',
                    'message' => "Your gross margin this month is {$margin}%. " . ($margin < 15 ? 'Consider reviewing your pricing strategy.' : 'Looking healthy!'),
                    'value' => $margin . '%',
                    'priority' => $margin < 10 ? 'high' : 'low',
                ];
            }
        } catch (\Exception $e) {}

        try {
            $slowMoving = $db->query(
                "SELECT COUNT(*) FROM products p WHERE p.company_id = ? AND p.deleted_at IS NULL AND p.is_active = 1 AND p.current_stock > 10
                 AND p.id NOT IN (SELECT DISTINCT si.product_id FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE s.company_id = ? AND s.deleted_at IS NULL AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY))",
                [$companyId, $companyId]
            )->fetchColumn();

            if ($slowMoving > 0) {
                $insights[] = [
                    'type' => 'slow_moving',
                    'icon' => '🐢',
                    'color' => 'secondary',
                    'title' => 'Slow-Moving Inventory',
                    'message' => "{$slowMoving} product(s) with stock haven't sold in 60 days. Consider running promotions or discounts.",
                    'value' => $slowMoving . ' items',
                    'priority' => 'low',
                ];
            }
        } catch (\Exception $e) {}

        usort($insights, static function($a, $b) {
            $order = ['high' => 0, 'medium' => 1, 'low' => 2];
            return ($order[$a['priority']] ?? 2) - ($order[$b['priority']] ?? 2);
        });

        return $insights;
    }
}
