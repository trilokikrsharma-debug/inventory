<?php
/**
 * Category Controller - CRUD operations for categories
 */
class CategoryController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'delete', 'fetch'];

    public function index() {
        $this->requireAuth();
        $categoryModel = new CategoryModel();
        $categories = $categoryModel->allWithCount();

        $this->view('categories.index', [
            'pageTitle'  => 'Categories',
            'categories' => $categories,
        ]);
    }

    public function create() {
        $this->requirePermission('catalog.manage');
        if ($this->isPost()) {
            $this->validateCSRF();
            $categoryModel = new CategoryModel();
            $categoryModel->create([
                'name'        => $this->sanitize($this->post('name')),
                'description' => $this->sanitize($this->post('description')),
                'is_active'   => $this->post('is_active', 1),
            ]);
            $this->setFlash('success', 'Category created successfully.');
            $this->redirect('index.php?page=categories');
        }
    }

    public function edit() {
        $this->requirePermission('catalog.manage');
        $id = (int)$this->get('id');
        $categoryModel = new CategoryModel();

        if ($this->isPost()) {
            $this->validateCSRF();
            $categoryModel->update($id, [
                'name'        => $this->sanitize($this->post('name')),
                'description' => $this->sanitize($this->post('description')),
                'is_active'   => $this->post('is_active', 1),
            ]);
            $this->setFlash('success', 'Category updated successfully.');
            $this->redirect('index.php?page=categories');
        }

        $category = $categoryModel->find($id);
        $this->json($category);
    }

    public function delete() {
        $this->requirePermission('catalog.manage');
        if (!$this->isPost()) { $this->redirect('index.php?page=categories'); }
        $this->validateCSRF();
        $id = (int)$this->post('id');
        (new CategoryModel())->delete($id);
        $this->setFlash('success', 'Category deleted successfully.');
        $this->redirect('index.php?page=categories');
    }

    public function fetch() {
        $this->requireAuth();
        $id = (int)parent::get('id');
        $category = (new CategoryModel())->find($id);
        $this->json($category ?: ['error' => 'Not found']);
    }
}
