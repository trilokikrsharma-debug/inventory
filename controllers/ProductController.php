<?php
/**
 * Product Controller
 *
 * Full CRUD for products with stock management.
 */
class ProductController extends Controller {
    protected $allowedActions = ['index', 'create', 'edit', 'view_product', 'delete', 'archive', 'restore', 'search', 'import', 'download_template'];
    private ?ProductWorkflowService $productWorkflowService = null;
    private ?AccountingLifecycleService $accountingLifecycleService = null;

    private function warehouseFeatureEnabled(): bool {
        return !Session::isSuperAdmin()
            && Tenant::id() !== null
            && Tenant::canUse('multi_warehouse');
    }

    public function index() {
        $this->requirePermission('products.view');
        $productModel = new ProductModel();
        $search = $this->get('search', '');
        $categoryId = $this->get('category_id', '');
        $page = max(1, (int)($this->get('pg', 1)));

        $products = $productModel->getAllWithRelations($search, $categoryId, $page);
        $categories = (new CategoryModel())->allActive();

        $this->view('products.index', [
            'pageTitle'  => 'Products',
            'products'   => $products,
            'categories' => $categories,
            'search'     => $search,
            'categoryId' => $categoryId,
        ]);
    }

    public function create() {
        $this->requirePermission('products.create');

        if ($this->isPost()) {
            $this->validateCSRF();

            // Enterprise validation
            $v = Validator::make($_POST, [
                'name'           => 'required|string|min:2|max:200',
                'sku'            => 'nullable|string|max:50',
                'barcode'        => 'nullable|string|max:50',
                'hsn_code'       => 'nullable|string|min:4|max:20|regex:/^[A-Za-z0-9\\.\\/-]+$/',
                'purchase_price' => 'required|numeric|min:0',
                'selling_price'  => 'required|numeric|min:0',
                'mrp'            => 'nullable|numeric|min:0',
                'tax_rate'       => 'nullable|numeric|min:0|max:100',
                'opening_stock'  => 'nullable|numeric|min:0',
                'low_stock_alert'=> 'nullable|integer|min:0',
            ]);

            if ($v->fails()) {
                $categories = (new CategoryModel())->allActive();
                $brands = (new BrandModel())->allActive();
                $units = (new UnitModel())->allActive();
                $this->setFlash('error', $v->firstError());
                $this->view('products.create', [
                    'pageTitle' => 'Add Product', 'categories' => $categories,
                    'brands' => $brands, 'units' => $units,
                    'warehouses' => $this->warehouseFeatureEnabled() ? (new WarehouseModel())->allActiveOrdered() : [],
                    'hasWarehouseFeature' => $this->warehouseFeatureEnabled(),
                    'settings' => (new SettingsModel())->getSettings(),
                ]);
                return;
            }

            if (Tenant::id() !== null) {
                $currentProducts = (int)Tenant::usageCount('max_products');
                if (!Tenant::canUse('max_products', $currentProducts, 1)) {
                    $limit = (int)(Tenant::usageLimit('max_products') ?? 0);
                    $this->setFlash(
                        'error',
                        $limit > 0
                            ? 'Product limit reached (' . $limit . '). Please upgrade your plan.'
                            : 'Product limit reached for your plan. Please upgrade to add more products.'
                    );
                    $this->redirect('index.php?page=products&action=create');
                    return;
                }
            }

            $productModel = new ProductModel();
            try {
                $data = $this->workflowService()->buildPayload($_POST, Session::isSuperAdmin() || Tenant::canUse('custom_fields'));
            } catch (\RuntimeException $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('index.php?page=products&action=create');
                return;
            }

            // Handle image upload
            if (!empty($_FILES['image']['name'])) {
                $result = Helper::uploadFile($_FILES['image'], 'products', ALLOWED_IMAGE_TYPES);
                if ($result['success']) {
                    $data['image'] = $result['filepath'];
                }
            }

            try {
                $productId = $productModel->create($data);
            } catch (\RuntimeException $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('index.php?page=products&action=create');
                return;
            }

            // Create opening stock history entry (stock is already set via create())
            if ($data['opening_stock'] > 0) {
                $db = Database::getInstance();
                $db->query(
                    "INSERT INTO stock_history (company_id, product_id, type, quantity, stock_before, stock_after, note, created_by) VALUES (?, ?, 'opening', ?, 0, ?, 'Opening stock entry', ?)",
                    [Tenant::id() ?? 1, $productId, $data['opening_stock'], $data['opening_stock'], Session::get('user')['id']]
                );

                if ($this->warehouseFeatureEnabled()) {
                    $warehouseId = $this->selectedWarehouseIdFromPost((new WarehouseModel())->allActiveOrdered());
                    $productModel->allocateOpeningStock($productId, $warehouseId, (float)$data['opening_stock']);
                }
            }

            $this->logActivity('Created product: ' . $data['name'], 'products', $productId);
            Logger::audit('product_created', 'products', $productId, ['name' => $data['name'], 'sku' => $data['sku'], 'price' => $data['selling_price']]);
            $this->setFlash('success', 'Product created successfully.');
            $this->redirect('index.php?page=products');
        }

        $categories = (new CategoryModel())->allActive();
        $brands = (new BrandModel())->allActive();
        $units = (new UnitModel())->allActive();

        $this->view('products.create', [
            'pageTitle'  => 'Add Product',
            'categories' => $categories,
            'brands'     => $brands,
            'units'      => $units,
            'customFieldsPretty' => '',
            'warehouses' => $this->warehouseFeatureEnabled() ? (new WarehouseModel())->allActiveOrdered() : [],
            'hasWarehouseFeature' => $this->warehouseFeatureEnabled(),
            'settings' => (new SettingsModel())->getSettings(),
        ]);
    }

    public function import() {
        $this->requireFeature('bulk_import');
        $this->requirePermission('products.create');

        $analysis = null;
        $dryRun = true;

        if ($this->isPost()) {
            $this->validateCSRF();
            $dryRun = $this->post('dry_run') === '1';
            $service = new ProductImportService();

            try {
                $analysis = $service->analyzeUploadedFile($_FILES['import_file'] ?? [], $service->buildContext());

                if (!$dryRun) {
                    $validRows = (array)($analysis['valid_rows'] ?? []);
                    $invalidCount = (int)($analysis['summary']['invalid_rows'] ?? 0);

                    if ($invalidCount > 0) {
                        $this->setFlash('error', 'Fix invalid rows before importing products.');
                    } elseif (empty($validRows)) {
                        $this->setFlash('error', 'No valid rows were found to import.');
                    } else {
                        $currentProducts = Tenant::id() !== null ? (int)Tenant::usageCount('max_products') : 0;
                        if (Tenant::id() !== null && !Tenant::canUse('max_products', $currentProducts, count($validRows))) {
                            $limit = (int)(Tenant::usageLimit('max_products') ?? 0);
                            $this->setFlash(
                                'error',
                                $limit > 0
                                    ? 'Import would exceed your product limit (' . $limit . '). Please upgrade your plan.'
                                    : 'Import would exceed your current product limit. Please upgrade your plan.'
                            );
                        } else {
                            $imported = $this->workflowService()->persistImportedProducts(
                                $validRows,
                                (int)(Session::get('user')['id'] ?? 0),
                                $this->warehouseFeatureEnabled()
                            );
                            $this->setFlash('success', 'Imported ' . $imported . ' product(s) successfully.');
                            $analysis['imported_count'] = $imported;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->setFlash('error', $e->getMessage());
            }
        }

        $this->view('products.import', [
            'pageTitle' => 'Bulk Import Products',
            'analysis' => $analysis,
            'dryRun' => $dryRun,
        ]);
    }

    public function download_template() {
        $this->requireFeature('bulk_import');
        $this->requirePermission('products.create');

        $service = new ProductImportService();
        $filename = 'product_import_template_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $service->templateCsv();
        exit;
    }

    public function edit() {
        $this->requirePermission('products.edit');
        $id = (int)$this->get('id');
        $productModel = new ProductModel();
        $product = $productModel->getWithDetails($id);

        // Shared resource: non-admin users cannot edit products (pricing, stock alerts)
        $this->authorizeRecordAccess($product, 'index.php?page=products', false);

        if ($this->isPost()) {
            $this->validateCSRF();
            $v = Validator::make($_POST, [
                'name'           => 'required|string|min:2|max:200',
                'sku'            => 'nullable|string|max:50',
                'barcode'        => 'nullable|string|max:50',
                'hsn_code'       => 'nullable|string|min:4|max:20|regex:/^[A-Za-z0-9\\.\\/-]+$/',
                'purchase_price' => 'required|numeric|min:0',
                'selling_price'  => 'required|numeric|min:0',
                'mrp'            => 'nullable|numeric|min:0',
                'tax_rate'       => 'nullable|numeric|min:0|max:100',
                'low_stock_alert'=> 'nullable|integer|min:0',
            ]);
            if ($v->fails()) {
                $this->setFlash('error', $v->firstError());
                $this->redirect('index.php?page=products&action=edit&id=' . $id);
                return;
            }

            try {
                $data = $this->workflowService()->buildPayload($_POST, Session::isSuperAdmin() || Tenant::canUse('custom_fields'));
                unset($data['opening_stock']);
            } catch (\RuntimeException $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('index.php?page=products&action=edit&id=' . $id);
                return;
            }

            if (!empty($_FILES['image']['name'])) {
                $result = Helper::uploadFile($_FILES['image'], 'products', ALLOWED_IMAGE_TYPES);
                if ($result['success']) {
                    $data['image'] = $result['filepath'];
                }
            }

            try {
                $productModel->update($id, $data);

                if ($this->warehouseFeatureEnabled()) {
                    $warehouses = (new WarehouseModel())->allActiveOrdered();
                    $allocations = WarehouseStockService::normalizeAllocations(
                        (array)$this->post('warehouse_stock', []),
                        array_map(static fn(array $warehouse): int => (int)$warehouse['id'], $warehouses)
                    );
                    $productModel->syncWarehouseAllocations(
                        $id,
                        $allocations,
                        (int)(Session::get('user')['id'] ?? 0),
                        'Warehouse stock rebalanced from product edit'
                    );
                }
            } catch (\RuntimeException $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('index.php?page=products&action=edit&id=' . $id);
                return;
            }

            $this->logActivity('Updated product: ' . $data['name'], 'products', $id);
            $this->setFlash('success', 'Product updated successfully.');
            $this->redirect('index.php?page=products');
        }

        $categories = (new CategoryModel())->allActive();
        $brands = (new BrandModel())->allActive();
        $units = (new UnitModel())->allActive();

        $this->view('products.edit', [
            'pageTitle'  => 'Edit Product',
            'product'    => $product,
            'categories' => $categories,
            'brands'     => $brands,
            'units'      => $units,
            'customFieldsPretty' => $this->customFieldsPretty($product['custom_fields'] ?? null),
            'customFieldsDecoded' => CustomFieldService::decode($product['custom_fields'] ?? null),
            'warehouseBreakdown' => $this->warehouseFeatureEnabled() ? $productModel->getWarehouseBreakdown($id) : [],
            'hasWarehouseFeature' => $this->warehouseFeatureEnabled(),
            'settings' => (new SettingsModel())->getSettings(),
        ]);
    }

    public function view_product() {
        $this->requirePermission('products.view');
        $id = (int)$this->get('id');
        $productModel = new ProductModel();
        $product = $productModel->getWithDetails($id);

        // Shared resource: all authenticated users can view product details
        $this->authorizeRecordAccess($product, 'index.php?page=products', true);

        $stockHistory = $productModel->getStockHistory($id);

        $this->view('products.view', [
            'pageTitle'    => 'Product Details',
            'product'      => $product,
            'stockHistory' => $stockHistory,
            'customFields' => CustomFieldService::decode($product['custom_fields'] ?? null),
            'warehouseBreakdown' => $this->warehouseFeatureEnabled() ? $productModel->getWarehouseBreakdown($id) : [],
            'hasWarehouseFeature' => $this->warehouseFeatureEnabled(),
            'settings' => (new SettingsModel())->getSettings(),
        ]);
    }

    public function delete() {
        $this->requirePermission('products.delete');
        if (!$this->isPost()) { $this->redirect('index.php?page=products'); }
        $this->validateCSRF();

        $id = (int)$this->post('id');
        try {
            $result = $this->lifecycleService()->retireOrDeleteProduct($id);
        } catch (\InvalidArgumentException $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('index.php?page=products');
            return;
        }

        if ($result['action'] === 'archived') {
            $this->logActivity('Archived product: ' . $result['record']['name'], 'products', $id, json_encode($result['usage']));
            $this->setFlash('success', 'Product archived because it is referenced in transactions. Historical records remain intact.');
        } else {
            $this->logActivity('Deleted product: ' . $result['record']['name'], 'products', $id);
            $this->setFlash('success', 'Unused product deleted successfully.');
        }

        $this->redirect('index.php?page=products');
    }

    public function archive() {
        $this->requirePermission('products.delete');
        if (!$this->isPost()) { $this->redirect('index.php?page=products'); }
        $this->validateCSRF();

        $id = (int)$this->post('id');
        try {
            $result = $this->lifecycleService()->setProductArchived($id, true);
            $this->logActivity('Archived product: ' . $result['record']['name'], 'products', $id);
            $this->setFlash('success', 'Product archived successfully.');
        } catch (\InvalidArgumentException $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('index.php?page=products');
    }

    public function restore() {
        $this->requirePermission('products.delete');
        if (!$this->isPost()) { $this->redirect('index.php?page=products'); }
        $this->validateCSRF();

        $id = (int)$this->post('id');
        try {
            $result = $this->lifecycleService()->setProductArchived($id, false);
            $this->logActivity('Restored product: ' . $result['record']['name'], 'products', $id);
            $this->setFlash('success', 'Product restored successfully.');
        } catch (\InvalidArgumentException $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('index.php?page=products');
    }

    /**
     * AJAX: Search products for autocomplete
     */
    public function search() {
        $this->requireAuth();
        $term = $this->get('term', '');
        $productModel = new ProductModel();
        $results = $productModel->search($term);
        // Sanitize before placing into DOM via JSON
        foreach ($results as &$r) {
            $rawName = Helper::decodeHtmlEntities($r['name'] ?? '');
            $r['name_raw'] = $rawName;
            $r['name'] = Helper::escape($rawName);
            if (isset($r['sku'])) {
                $rawSku = Helper::decodeHtmlEntities($r['sku']);
                $r['sku_raw'] = $rawSku;
                $r['sku'] = Helper::escape($rawSku);
            }
        }
        $this->json($results);
    }

    private function customFieldsPretty($raw): string {
        return CustomFieldService::pretty($raw);
    }

    /**
     * @param array<int, array<string, mixed>> $warehouses
     */
    private function selectedWarehouseIdFromPost(array $warehouses): ?int {
        if (empty($warehouses)) {
            return null;
        }

        $selectedId = (int)$this->post('opening_warehouse_id', 0);
        foreach ($warehouses as $warehouse) {
            if ((int)$warehouse['id'] === $selectedId) {
                return $selectedId;
            }
        }

        foreach ($warehouses as $warehouse) {
            if (!empty($warehouse['is_default'])) {
                return (int)$warehouse['id'];
            }
        }

        return (int)$warehouses[0]['id'];
    }

    private function workflowService(): ProductWorkflowService {
        if ($this->productWorkflowService === null) {
            $this->productWorkflowService = new ProductWorkflowService();
        }

        return $this->productWorkflowService;
    }

    private function lifecycleService(): AccountingLifecycleService {
        if ($this->accountingLifecycleService === null) {
            $this->accountingLifecycleService = new AccountingLifecycleService();
        }

        return $this->accountingLifecycleService;
    }
}
