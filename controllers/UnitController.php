<?php
/**
 * Unit Controller
 */
class UnitController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'delete', 'fetch'];

    public function index() {
        $this->requireAuth();
        $this->view('units.index', [
            'pageTitle' => 'Units',
            'units' => (new UnitModel())->allWithCount(),
        ]);
    }

    public function create() {
        $this->requirePermission('catalog.manage');
        if ($this->isPost()) {
            $this->validateCSRF();
            (new UnitModel())->create([
                'name' => $this->sanitize($this->post('name')),
                'short_name' => $this->sanitize($this->post('short_name')),
                'is_active' => $this->post('is_active', 1),
            ]);
            $this->setFlash('success', 'Unit created successfully.');
            $this->redirect('index.php?page=units');
        }
    }

    public function edit() {
        $this->requirePermission('catalog.manage');
        $id = (int)$this->get('id');
        if ($this->isPost()) {
            $this->validateCSRF();
            (new UnitModel())->update($id, [
                'name' => $this->sanitize($this->post('name')),
                'short_name' => $this->sanitize($this->post('short_name')),
                'is_active' => $this->post('is_active', 1),
            ]);
            $this->setFlash('success', 'Unit updated successfully.');
            $this->redirect('index.php?page=units');
        }
    }

    public function delete() {
        $this->requirePermission('catalog.manage');
        if (!$this->isPost()) { $this->redirect('index.php?page=units'); }
        $this->validateCSRF();
        (new UnitModel())->delete((int)$this->post('id'));
        $this->setFlash('success', 'Unit deleted.');
        $this->redirect('index.php?page=units');
    }

    public function fetch() {
        $this->requireAuth();
        $this->json((new UnitModel())->find((int)parent::get('id')) ?: ['error' => 'Not found']);
    }
}
