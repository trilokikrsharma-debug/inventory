<?php
declare(strict_types=1);

/**
 * Idempotent enterprise demo bootstrapper.
 *
 * Seeds portable baseline data for every active demo tenant:
 * - default HR shift
 * - minimum two warehouses
 * - sample HR employees
 * - sample product stock buckets
 * - sample leave requests
 * - sample warehouse transfer states
 *
 * Usage:
 *   php cli/seed_enterprise_demo.php
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

function demoInfo(string $message): void
{
    echo "[INFO] {$message}" . PHP_EOL;
}

function demoOk(string $message): void
{
    echo "[OK] {$message}" . PHP_EOL;
}

function demoFail(string $message): void
{
    fwrite(STDERR, "[FAIL] {$message}" . PHP_EOL);
}

function demoScalar(PDO $pdo, string $sql, array $params = []): mixed
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function demoFetch(PDO $pdo, string $sql, array $params = []): array|false
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function demoInsert(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$pdo->lastInsertId();
}

try {
    $pdo = Database::getInstance()->getConnection();
    $demoCompanies = $pdo->query(
        "SELECT c.*, p.slug AS saas_plan_slug
         FROM companies c
         LEFT JOIN saas_plans p ON p.id = c.saas_plan_id
         WHERE c.is_demo = 1
           AND c.status = 'active'
         ORDER BY c.id ASC"
    )->fetchAll();

    if (empty($demoCompanies)) {
        throw new RuntimeException('No active demo tenants found.');
    }

    foreach ($demoCompanies as $company) {
        $companyId = (int)$company['id'];
        $companyName = (string)($company['name'] ?? ('Company #' . $companyId));
        demoInfo("Bootstrapping demo tenant: {$companyName} (#{$companyId})");
        Tenant::set($companyId, $company);

        $pdo->beginTransaction();
        try {
            $admin = demoFetch(
                $pdo,
                "SELECT id, full_name
                 FROM users
                 WHERE company_id = ?
                   AND deleted_at IS NULL
                   AND is_active = 1
                 ORDER BY is_super_admin DESC, id ASC
                 LIMIT 1",
                [$companyId]
            );
            if (!$admin) {
                throw new RuntimeException("No active user found for demo tenant {$companyName}");
            }
            $adminId = (int)$admin['id'];

            $shiftId = (int)(demoScalar(
                $pdo,
                "SELECT id
                 FROM hr_shifts
                 WHERE company_id = ?
                   AND is_default = 1
                 ORDER BY id ASC
                 LIMIT 1",
                [$companyId]
            ) ?: 0);
            if ($shiftId <= 0) {
                $shiftId = demoInsert(
                    $pdo,
                    "INSERT INTO hr_shifts
                     (company_id, shift_name, start_time, end_time, weekly_off_day, grace_period_minutes, is_default, notes)
                     VALUES (?, 'General Shift', '09:00:00', '18:00:00', 'Sunday', 15, 1, 'Seeded enterprise demo shift')",
                    [$companyId]
                );
            }
            demoOk("Default HR shift ready for tenant #{$companyId}");

            $warehouses = $pdo->prepare(
                "SELECT id, name, code, is_default
                 FROM warehouses
                 WHERE company_id = ?
                   AND deleted_at IS NULL
                 ORDER BY id ASC"
            );
            $warehouses->execute([$companyId]);
            $warehouseRows = $warehouses->fetchAll();

            if (count($warehouseRows) === 0) {
                demoInsert(
                    $pdo,
                    "INSERT INTO warehouses
                     (company_id, name, code, location, description, is_default, is_active)
                     VALUES (?, 'Main Warehouse', 'MAIN', 'Head Office', 'Seeded primary warehouse', 1, 1)",
                    [$companyId]
                );
                demoInsert(
                    $pdo,
                    "INSERT INTO warehouses
                     (company_id, name, code, location, description, is_default, is_active)
                     VALUES (?, 'Branch Warehouse', 'BRANCH', 'Secondary Branch', 'Seeded secondary warehouse', 0, 1)",
                    [$companyId]
                );
            } elseif (count($warehouseRows) === 1) {
                demoInsert(
                    $pdo,
                    "INSERT INTO warehouses
                     (company_id, name, code, location, description, is_default, is_active)
                     VALUES (?, 'Branch Warehouse', 'BRANCH', 'Secondary Branch', 'Seeded secondary warehouse', 0, 1)",
                    [$companyId]
                );
            }

            $warehouseModel = new WarehouseModel();
            $activeWarehouses = $warehouseModel->allActiveOrdered();
            if (count($activeWarehouses) < 2) {
                throw new RuntimeException("Demo tenant {$companyName} still has fewer than two active warehouses after bootstrap");
            }
            $defaultWarehouseId = (int)($warehouseModel->defaultWarehouseId() ?? 0);
            if ($defaultWarehouseId <= 0) {
                $pdo->prepare(
                    "UPDATE warehouses
                     SET is_default = CASE WHEN id = ? THEN 1 ELSE 0 END,
                         updated_at = NOW()
                     WHERE company_id = ?
                       AND deleted_at IS NULL"
                )->execute([(int)$activeWarehouses[0]['id'], $companyId]);
                $defaultWarehouseId = (int)$activeWarehouses[0]['id'];
            }
            $secondaryWarehouseId = (int)$activeWarehouses[0]['id'] === $defaultWarehouseId
                ? (int)$activeWarehouses[1]['id']
                : (int)$activeWarehouses[0]['id'];
            demoOk("Warehouse baseline ready for tenant #{$companyId}");

            $categoryId = (int)(demoScalar(
                $pdo,
                "SELECT id FROM categories WHERE company_id = ? ORDER BY id ASC LIMIT 1",
                [$companyId]
            ) ?: 0);
            if ($categoryId <= 0) {
                $categoryId = demoInsert($pdo, "INSERT INTO categories (company_id, name) VALUES (?, 'Enterprise Demo Category')", [$companyId]);
            }

            $brandId = (int)(demoScalar(
                $pdo,
                "SELECT id FROM brands WHERE company_id = ? ORDER BY id ASC LIMIT 1",
                [$companyId]
            ) ?: 0);
            if ($brandId <= 0) {
                $brandId = demoInsert($pdo, "INSERT INTO brands (company_id, name) VALUES (?, 'Enterprise Demo Brand')", [$companyId]);
            }

            $unitId = (int)(demoScalar(
                $pdo,
                "SELECT id FROM units WHERE company_id = ? ORDER BY id ASC LIMIT 1",
                [$companyId]
            ) ?: 0);
            if ($unitId <= 0) {
                $unitId = demoInsert($pdo, "INSERT INTO units (company_id, name, short_name) VALUES (?, 'Pieces', 'pcs')", [$companyId]);
            }

            $product = demoFetch(
                $pdo,
                "SELECT id, current_stock
                 FROM products
                 WHERE company_id = ?
                   AND deleted_at IS NULL
                 ORDER BY id ASC
                 LIMIT 1",
                [$companyId]
            );
            if (!$product) {
                $sku = 'DEMO-ENT-' . $companyId;
                $productId = demoInsert(
                    $pdo,
                    "INSERT INTO products
                     (company_id, name, sku, category_id, brand_id, unit_id, purchase_price, selling_price, opening_stock, current_stock, is_active)
                     VALUES (?, 'Enterprise Demo Product', ?, ?, ?, ?, 450.00, 675.00, 30.000, 30.000, 1)",
                    [$companyId, $sku, $categoryId, $brandId, $unitId]
                );
                $product = ['id' => $productId, 'current_stock' => 30];
            }
            $productId = (int)$product['id'];

            $allocations = [
                $defaultWarehouseId => 18.0,
                $secondaryWarehouseId => 12.0,
            ];
            $pdo->prepare(
                "DELETE FROM product_warehouse_stock
                 WHERE company_id = ?
                   AND product_id = ?"
            )->execute([$companyId, $productId]);
            $insertStock = $pdo->prepare(
                "INSERT INTO product_warehouse_stock (company_id, product_id, warehouse_id, quantity)
                 VALUES (?, ?, ?, ?)"
            );
            foreach ($allocations as $warehouseId => $quantity) {
                $insertStock->execute([$companyId, $productId, $warehouseId, $quantity]);
            }
            $pdo->prepare(
                "UPDATE products
                 SET current_stock = ?, opening_stock = GREATEST(opening_stock, ?)
                 WHERE company_id = ?
                   AND id = ?"
            )->execute([array_sum($allocations), array_sum($allocations), $companyId, $productId]);
            demoOk("Product and warehouse stock seeded for tenant #{$companyId}");

            $employeeCount = (int)(demoScalar(
                $pdo,
                "SELECT COUNT(*) FROM hr_employees WHERE company_id = ? AND deleted_at IS NULL",
                [$companyId]
            ) ?: 0);

            if ($employeeCount < 2) {
                $existingCodes = [];
                $stmt = $pdo->prepare("SELECT employee_code FROM hr_employees WHERE company_id = ? AND deleted_at IS NULL");
                $stmt->execute([$companyId]);
                foreach ($stmt->fetchAll() as $row) {
                    $existingCodes[(string)$row['employee_code']] = true;
                }

                $seedEmployees = [
                    ['EMP-DEMO-001', 'Operations Manager', 'Operations', 42000.00],
                    ['EMP-DEMO-002', 'Warehouse Executive', 'Logistics', 28500.00],
                ];

                foreach ($seedEmployees as [$code, $designation, $department, $salary]) {
                    if (isset($existingCodes[$code])) {
                        continue;
                    }
                    demoInsert(
                        $pdo,
                        "INSERT INTO hr_employees
                         (company_id, employee_code, full_name, designation, department, email, phone, shift_id, status, joined_on, salary, reporting_manager_user_id, notes)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', CURDATE(), ?, ?, ?)",
                        [
                            $companyId,
                            $code,
                            $companyName . ' ' . $designation,
                            $designation,
                            $department,
                            strtolower(str_replace(' ', '.', $designation)) . "@demo-{$companyId}.local",
                            '900000' . str_pad((string)$companyId, 4, '0', STR_PAD_LEFT),
                            $shiftId,
                            $salary,
                            $adminId,
                            'Seeded enterprise demo employee',
                        ]
                    );
                }
            }

            $employees = $pdo->prepare(
                "SELECT id
                 FROM hr_employees
                 WHERE company_id = ?
                   AND deleted_at IS NULL
                 ORDER BY id ASC
                 LIMIT 2"
            );
            $employees->execute([$companyId]);
            $employeeRows = $employees->fetchAll();
            if (count($employeeRows) < 2) {
                throw new RuntimeException("Unable to seed minimum HR employees for {$companyName}");
            }
            $employeeA = (int)$employeeRows[0]['id'];
            $employeeB = (int)$employeeRows[1]['id'];
            demoOk("HR employee baseline ready for tenant #{$companyId}");

            $pendingLeaveExists = (int)(demoScalar(
                $pdo,
                "SELECT COUNT(*) FROM hr_leave_requests WHERE company_id = ? AND employee_id = ? AND status = 'pending' LIMIT 1",
                [$companyId, $employeeA]
            ) ?: 0) > 0;
            if (!$pendingLeaveExists) {
                demoInsert(
                    $pdo,
                    "INSERT INTO hr_leave_requests
                     (company_id, employee_id, leave_type, start_date, end_date, days_count, reason, status, requested_by, approver_user_id, manager_status)
                     VALUES (?, ?, 'casual', CURDATE(), CURDATE(), 1, 'Seeded pending demo leave', 'pending', ?, ?, 'pending')",
                    [$companyId, $employeeA, $adminId, $adminId]
                );
            }

            $approvedLeaveExists = (int)(demoScalar(
                $pdo,
                "SELECT COUNT(*) FROM hr_leave_requests WHERE company_id = ? AND employee_id = ? AND status = 'approved' LIMIT 1",
                [$companyId, $employeeB]
            ) ?: 0) > 0;
            if (!$approvedLeaveExists) {
                demoInsert(
                    $pdo,
                    "INSERT INTO hr_leave_requests
                     (company_id, employee_id, leave_type, start_date, end_date, days_count, reason, status, requested_by, approver_user_id, manager_status, manager_approved_by, manager_approved_at, approved_by, approved_at)
                     VALUES (?, ?, 'earned', CURDATE(), CURDATE(), 1, 'Seeded approved demo leave', 'approved', ?, ?, 'approved', ?, NOW(), ?, NOW())",
                    [$companyId, $employeeB, $adminId, $adminId, $adminId, $adminId]
                );
            }
            demoOk("Leave workflow seed ready for tenant #{$companyId}");

            $transferPendingExists = (int)(demoScalar(
                $pdo,
                "SELECT COUNT(*) FROM stock_transfers WHERE company_id = ? AND status = 'pending' LIMIT 1",
                [$companyId]
            ) ?: 0) > 0;
            if (!$transferPendingExists) {
                $transferId = demoInsert(
                    $pdo,
                    "INSERT INTO stock_transfers
                     (company_id, transfer_number, source_warehouse_id, destination_warehouse_id, transfer_date, reference_number, note, status, item_count, total_quantity, created_by)
                     VALUES (?, '', ?, ?, CURDATE(), 'DEMO-PENDING', 'Seeded pending transfer request', 'pending', 1, 2.000, ?)",
                    [$companyId, $defaultWarehouseId, $secondaryWarehouseId, $adminId]
                );
                $transferNumber = 'TRF-' . str_pad((string)$transferId, 6, '0', STR_PAD_LEFT);
                $pdo->prepare("UPDATE stock_transfers SET transfer_number = ? WHERE id = ?")->execute([$transferNumber, $transferId]);
                $pdo->prepare(
                    "INSERT INTO stock_transfer_items
                     (company_id, transfer_id, product_id, quantity, unit_cost, total_cost)
                     VALUES (?, ?, ?, 2.000, 450.00, 900.00)"
                )->execute([$companyId, $transferId, $productId]);
            }

            $transferRejectedExists = (int)(demoScalar(
                $pdo,
                "SELECT COUNT(*) FROM stock_transfers WHERE company_id = ? AND status = 'rejected' LIMIT 1",
                [$companyId]
            ) ?: 0) > 0;
            if (!$transferRejectedExists) {
                $transferId = demoInsert(
                    $pdo,
                    "INSERT INTO stock_transfers
                     (company_id, transfer_number, source_warehouse_id, destination_warehouse_id, transfer_date, reference_number, note, status, item_count, total_quantity, created_by, rejected_by, rejected_at, rejection_reason)
                     VALUES (?, '', ?, ?, CURDATE(), 'DEMO-REJECT', 'Seeded rejected transfer request', 'rejected', 1, 1.000, ?, ?, NOW(), 'Seeded demo rejection')",
                    [$companyId, $secondaryWarehouseId, $defaultWarehouseId, $adminId, $adminId]
                );
                $transferNumber = 'TRF-' . str_pad((string)$transferId, 6, '0', STR_PAD_LEFT);
                $pdo->prepare("UPDATE stock_transfers SET transfer_number = ? WHERE id = ?")->execute([$transferNumber, $transferId]);
                $pdo->prepare(
                    "INSERT INTO stock_transfer_items
                     (company_id, transfer_id, product_id, quantity, unit_cost, total_cost)
                     VALUES (?, ?, ?, 1.000, 450.00, 450.00)"
                )->execute([$companyId, $transferId, $productId]);
            }
            demoOk("Warehouse workflow seed ready for tenant #{$companyId}");

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        } finally {
            Tenant::reset();
        }
    }

    echo "[DONE] Enterprise demo bootstrap completed" . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    demoFail($e->getMessage());
    exit(1);
}
