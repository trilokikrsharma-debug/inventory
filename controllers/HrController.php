<?php
/**
 * HR Controller
 */
class HrController extends Controller {

    protected $allowedActions = ['index'];

    public function index() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.view');

        $this->view('hr.index', [
            'pageTitle' => 'HR Tools',
            'employees' => [
                ['name' => 'John Doe', 'designation' => 'Sales Manager', 'status' => 'Active', 'joined' => '2023-01-10'],
                ['name' => 'Jane Smith', 'designation' => 'Accountant', 'status' => 'Active', 'joined' => '2023-03-15'],
            ]
        ]);
    }
}
