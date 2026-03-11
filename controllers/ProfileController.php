<?php
/**
 * Profile Controller
 * 
 * Handles user profile, password change, and theme toggling.
 */
class ProfileController extends Controller {

    protected $allowedActions = ['index', 'password', 'updateTheme', 'update'];

    public function index() {
        $this->requireAuth();
        $userModel = new UserModel();
        $user = $userModel->find(Session::get('user')['id']);
        
        $this->view('profile.index', [
            'pageTitle' => 'My Profile',
            'user' => $user,
        ]);
    }

    public function password() {
        $this->requireAuth();
        
        if ($this->isPost()) {
            $this->validateCSRF();
            $userModel = new UserModel();
            $result = $userModel->changePassword(
                Session::get('user')['id'],
                $this->post('current_password'),
                $this->post('new_password')
            );
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
            $this->redirect('index.php?page=profile&action=password');
        }

        $this->view('profile.password', ['pageTitle' => 'Change Password']);
    }

    public function updateTheme() {
        $this->requireAuth();
        $mode = $this->post('theme_mode');
        if (in_array($mode, ['light', 'dark'])) {
            $userModel = new UserModel();
            $userModel->updateTheme(Session::get('user')['id'], $mode);
            $user = Session::get('user');
            $user['theme_mode'] = $mode;
            Session::set('user', $user);
        }
        if ($this->isAjax()) {
            $this->json(['success' => true]);
        }
    }

    public function update() {
        $this->requireAuth();
        if (!$this->isPost()) {
            $this->redirect('index.php?page=profile');
        }
        $this->validateCSRF();
        
        $userModel = new UserModel();
        $userId = Session::get('user')['id'];
        
        // SECURITY: Explicit allowlist — prevents mass assignment of
        // role, role_id, is_super_admin, is_active, company_id, password
        $data = [
            'full_name' => $this->sanitize($this->post('full_name')),
            'email'     => $this->sanitize($this->post('email')),
            'phone'     => $this->sanitize($this->post('phone')),
        ];

        // Validate email uniqueness if changed
        $currentUser = Session::get('user');
        if ($data['email'] !== ($currentUser['email'] ?? '') && $userModel->emailExists($data['email'], $userId)) {
            $this->setFlash('error', 'Email is already in use by another account.');
            $this->redirect('index.php?page=profile');
            return;
        }

        $userModel->update($userId, $data);
        
        // Update ONLY the allowed fields in session — never overwrite role/SA flag
        $currentUser['full_name'] = $data['full_name'];
        $currentUser['email'] = $data['email'];
        $currentUser['phone'] = $data['phone'];
        Session::set('user', $currentUser);

        $this->setFlash('success', 'Profile updated successfully.');
        $this->redirect('index.php?page=profile');
    }
}
