<?php
/**
 * Brand Controller
 */
class BrandController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'delete', 'fetch'];

    public function index() {
        $this->requireAuth();
        $this->view('brands.index', [
            'pageTitle' => 'Brands',
            'brands' => (new BrandModel())->allWithCount(),
        ]);
    }

    public function create() {
        $this->requirePermission('catalog.manage');
        if ($this->isPost()) {
            $this->validateCSRF();
            (new BrandModel())->create([
                'name' => $this->sanitize($this->post('name')),
                'description' => $this->sanitize($this->post('description')),
                'is_active' => $this->post('is_active', 1),
            ]);
            $this->setFlash('success', 'Brand created successfully.');
            $this->redirect('index.php?page=brands');
        }
    }

    public function edit() {
        $this->requirePermission('catalog.manage');
        $id = (int)$this->get('id');
        if ($this->isPost()) {
            $this->validateCSRF();
            (new BrandModel())->update($id, [
                'name' => $this->sanitize($this->post('name')),
                'description' => $this->sanitize($this->post('description')),
                'is_active' => $this->post('is_active', 1),
            ]);
            $this->setFlash('success', 'Brand updated successfully.');
            $this->redirect('index.php?page=brands');
        }
    }

    public function delete() {
        $this->requirePermission('catalog.manage');
        if (!$this->isPost()) { $this->redirect('index.php?page=brands'); }
        $this->validateCSRF();
        (new BrandModel())->delete((int)$this->post('id'));
        $this->setFlash('success', 'Brand deleted.');
        $this->redirect('index.php?page=brands');
    }

    public function fetch() {
        $this->requireAuth();
        $this->json((new BrandModel())->find((int)parent::get('id')) ?: ['error' => 'Not found']);
    }
}
