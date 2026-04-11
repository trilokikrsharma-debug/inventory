<?php
/**
 * Signup Controller - Public Self-Registration Flow
 *
 * Handles new company + owner user creation in a single atomic transaction.
 * No authentication required.
 */
class SignupController extends Controller {
    protected $allowedActions = ['index'];
    private ?SignupService $signupService = null;

    public function index() {
        $isDemoSignupExit = Session::isLoggedIn()
            && Tenant::isDemo()
            && $this->get('from_demo', '') === '1';

        if ($isDemoSignupExit) {
            RememberMeService::revokeCurrentToken();
            Session::clearPermissionCache();
            Session::destroy();
            $this->redirect('signup');
            return;
        }

        if (Session::isLoggedIn()) {
            if (Session::isTwoFactorPending()) {
                $this->redirect('twoFactor/verify');
                return;
            }

            $this->redirect('dashboard');
            return;
        }

        $error = '';
        $errors = [];
        $success = '';
        $formData = [
            'email' => trim(strtolower((string)$this->get('email', ''))),
        ];

        if ($this->isPost()) {
            $this->validateCSRF();

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            if (!RateLimiter::attempt('signup_ip:' . $ip, 10, 3600)) {
                $error = 'Too many signup attempts from your network. Please try again in some time.';
                $this->renderPartial('auth.signup', [
                    'error' => $error,
                    'errors' => ['general' => $error],
                    'success' => $success,
                    'formData' => $formData,
                ]);
                return;
            }

            if (!RateLimiter::attempt('signup_global', 200, 3600)) {
                $error = 'Signup service is temporarily busy. Please retry after some time.';
                $this->renderPartial('auth.signup', [
                    'error' => $error,
                    'errors' => ['general' => $error],
                    'success' => $success,
                    'formData' => $formData,
                ]);
                return;
            }

            $companyName = trim($this->sanitize($this->post('company_name', '')));
            $ownerName = trim($this->sanitize($this->post('full_name', '')));
            $email = trim(strtolower($this->post('email', '')));
            $phone = trim($this->sanitize($this->post('phone', '')));
            $username = trim(strtolower($this->sanitize($this->post('username', ''))));
            $password = $this->post('password', '');
            $confirmPass = $this->post('confirm_password', '');
            $referralCode = strtoupper(trim((string)$this->post('referral_code', '')));

            $formData = compact('companyName', 'ownerName', 'email', 'phone', 'username', 'referralCode');
            $minPasswordLength = defined('PASSWORD_MIN_LENGTH')
                ? max(6, (int)PASSWORD_MIN_LENGTH)
                : 6;

            if ($companyName === '' || strlen($companyName) < 2) {
                $errors['company_name'] = 'Company name is required (minimum 2 characters).';
            } elseif (strlen($companyName) > 120) {
                $errors['company_name'] = 'Company name must be 120 characters or fewer.';
            }

            if ($ownerName === '' || strlen($ownerName) < 2) {
                $errors['full_name'] = 'Full name is required (minimum 2 characters).';
            } elseif (strlen($ownerName) > 120) {
                $errors['full_name'] = 'Full name must be 120 characters or fewer.';
            }

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'A valid email address is required.';
            } elseif (strlen($email) > 190) {
                $errors['email'] = 'Email address is too long.';
            }

            if ($phone !== '' && !preg_match('/^\+?[0-9\s\-()]{7,20}$/', $phone)) {
                $errors['phone'] = 'Phone number format looks invalid.';
            }

            if ($username === '' || strlen($username) < 3 || strlen($username) > 40 || !preg_match('/^[a-z0-9_]+$/', $username)) {
                $errors['username'] = 'Username must be 3-40 characters (lowercase letters, numbers, underscore only).';
            }

            if ($password === '' || strlen($password) < $minPasswordLength) {
                $errors['password'] = "Password must be at least {$minPasswordLength} characters.";
            } elseif (defined('PASSWORD_COMPLEXITY') && PASSWORD_COMPLEXITY) {
                if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                    $errors['password'] = 'Password must contain at least 1 uppercase letter and 1 number.';
                }
            }

            if ($confirmPass === '') {
                $errors['confirm_password'] = 'Please confirm your password.';
            } elseif ($password !== $confirmPass) {
                $errors['confirm_password'] = 'Passwords do not match.';
            }

            if ($referralCode !== '' && !preg_match('/^[A-Z0-9_-]{4,40}$/', $referralCode)) {
                $errors['referral_code'] = 'Referral code format is invalid.';
            }

            if (empty($errors)) {
                $userModel = new UserModel();
                if ($userModel->emailExists($email)) {
                    $errors['email'] = 'This email is already registered. Please log in or use a different email.';
                }
            }

            if (empty($errors)) {
                try {
                    $provisioned = $this->signupService()->registerTenant([
                        'company_name' => $companyName,
                        'full_name' => $ownerName,
                        'email' => $email,
                        'phone' => $phone,
                        'username' => $username,
                        'password' => $password,
                        'referral_code' => $referralCode,
                    ]);
                    $companyId = (int)$provisioned['company_id'];
                    $user = $provisioned['user'];
                    $company = $provisioned['company'];

                    session_regenerate_id(true);
                    CSRF::rotateToken();
                    Session::initFingerprint();
                    Session::clearPermissionCache();
                    unset($user['password'], $user['twofa_secret'], $user['twofa_recovery_codes']);
                    $user['is_super_admin'] = false;

                    Session::set('user', $user);
                    Tenant::set($companyId, $company);
                    Session::setFlash('success', 'Welcome to ' . APP_NAME . '! Your account has been created.');

                    header('Location: ' . APP_URL . '/dashboard');
                    exit;
                } catch (Exception $e) {
                    $message = trim((string)$e->getMessage());

                    if ($message !== '' && stripos($message, 'referral') !== false) {
                        $errors['referral_code'] = $message;
                    } elseif ($message !== '' && stripos($message, 'pricing plans are not configured') !== false) {
                        $errors['general'] = 'Signup is temporarily unavailable. Please contact support while billing setup is being finished.';
                    } elseif ($message !== '' && stripos($message, 'duplicate') !== false && stripos($message, 'email') !== false) {
                        $errors['email'] = 'This email is already registered. Please log in or use a different email.';
                    } elseif ($message !== '' && stripos($message, 'duplicate') !== false && stripos($message, 'username') !== false) {
                        $errors['username'] = 'This username is not available. Please choose another one.';
                    } else {
                        $errors['general'] = 'Registration failed. Please try again or contact support.';
                    }

                    error_log('[SIGNUP] Error: ' . $message);
                }
            }

            if ($errors !== []) {
                $error = (string) reset($errors);
            }
        }

        $this->renderPartial('auth.signup', [
            'error' => $error,
            'errors' => $errors,
            'success' => $success,
            'formData' => $formData,
        ]);
    }

    private function signupService(): SignupService {
        if ($this->signupService === null) {
            $this->signupService = new SignupService();
        }

        return $this->signupService;
    }
}
