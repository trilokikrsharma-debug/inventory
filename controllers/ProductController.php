<?php
/**
 * Product Controller
 * 
 * Full CRUD for products with stock management.
 */
class ProductController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'view_product', 'delete', 'search'];

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
                ]);
                return;
            }

            $productModel = new ProductModel();
            $data = [
                'name'           => $this->sanitize($this->post('name')),
                'sku'            => $this->sanitize($this->post('sku')) ?: null,
                'barcode'        => $this->sanitize($this->post('barcode')) ?: null,
                'category_id'    => $this->post('category_id') ?: null,
                'brand_id'       => $this->post('brand_id') ?: null,
                'unit_id'        => $this->post('unit_id') ?: null,
                'purchase_price' => (float)$this->post('purchase_price', 0),
                'selling_price'  => (float)$this->post('selling_price', 0),
                'mrp'            => $this->post('mrp') !== '' ? (float)$this->post('mrp') : null,
                'tax_rate'       => $this->post('tax_rate') !== '' ? (float)$this->post('tax_rate') : null,
                'opening_stock'  => (float)$this->post('opening_stock', 0),
                'current_stock'  => (float)$this->post('opening_stock', 0),
                'low_stock_alert'=> $this->post('low_stock_alert') !== '' ? (int)$this->post('low_stock_alert') : null,
                'description'    => $this->sanitize($this->post('description')),
                'is_active'      => $this->post('is_active', 1),
            ];

            // Handle image upload
            if (!empty($_FILES['image']['name'])) {
                $result = Helper::uploadFile($_FILES['image'], 'products', ALLOWED_IMAGE_TYPES);
                if ($result['success']) {
                    $data['image'] = $result['filepath'];
                }
            }

            $productId = $productModel->create($data);

            // Create opening stock history entry (stock is already set via create())
            if ($data['opening_stock'] > 0) {
                $db = Database::getInstance();
                $db->query(
                    "INSERT INTO stock_history (company_id, product_id, type, quantity, stock_before, stock_after, note, created_by) VALUES (?, ?, 'opening', ?, 0, ?, 'Opening stock entry', ?)",
                    [Tenant::id() ?? 1, $productId, $data['opening_stock'], $data['opening_stock'], Session::get('user')['id']]
                );
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
        ]);
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
            $data = [
                'name'           => $this->sanitize($this->post('name')),
                'sku'            => $this->sanitize($this->post('sku')) ?: null,
                'barcode'        => $this->sanitize($this->post('barcode')) ?: null,
                'category_id'    => $this->post('category_id') ?: null,
                'brand_id'       => $this->post('brand_id') ?: null,
                'unit_id'        => $this->post('unit_id') ?: null,
                'purchase_price' => (float)$this->post('purchase_price', 0),
                'selling_price'  => (float)$this->post('selling_price', 0),
                'mrp'            => $this->post('mrp') !== '' ? (float)$this->post('mrp') : null,
                'tax_rate'       => $this->post('tax_rate') !== '' ? (float)$this->post('tax_rate') : null,
                'low_stock_alert'=> $this->post('low_stock_alert') !== '' ? (int)$this->post('low_stock_alert') : null,
                'description'    => $this->sanitize($this->post('description')),
                'is_active'      => $this->post('is_active', 1),
            ];

            if (!empty($_FILES['image']['name'])) {
                $result = Helper::uploadFile($_FILES['image'], 'products', ALLOWED_IMAGE_TYPES);
                if ($result['success']) {
                    $data['image'] = $result['filepath'];
                }
            }

            $productModel->update($id, $data);
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
        ]);
    }

    public function delete() {
        $this->requirePermission('products.delete');
        if (!$this->isPost()) { $this->redirect('index.php?page=products'); }
        $this->validateCSRF();
        
        $id = (int)$this->post('id');
        $productModel = new ProductModel();
        $product = $productModel->find($id);
        
        if ($product) {
            $db = Database::getInstance();
            $tenantJoin = Tenant::id() !== null ? " AND s.company_id = ?" : "";
            $tenantJoinP = Tenant::id() !== null ? " AND p.company_id = ?" : "";
            $params = [$id];
            if (Tenant::id() !== null) $params[] = Tenant::id();
            $params[] = $id;
            if (Tenant::id() !== null) $params[] = Tenant::id();
            $linked = $db->query(
                "SELECT 
                    (SELECT COUNT(*) FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE si.product_id = ? AND s.deleted_at IS NULL{$tenantJoin}) +
                    (SELECT COUNT(*) FROM purchase_items pi JOIN purchases p ON pi.purchase_id = p.id WHERE pi.product_id = ? AND p.deleted_at IS NULL{$tenantJoinP}) as total",
                $params
            )->fetchColumn();

            if ($linked > 0) {
                $this->setFlash('error', 'Cannot delete product: it is used in active sales or purchases.');
                $this->redirect('index.php?page=products');
                return;
            }

            $productModel->delete($id);
            $this->logActivity('Deleted product: ' . $product['name'], 'products', $id);
            $this->setFlash('success', 'Product deleted successfully.');
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
}
