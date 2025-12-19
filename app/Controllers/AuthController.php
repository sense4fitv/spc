<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\UserLoginModel;
use App\Filters\RateLimitFilter;
use CodeIgniter\Controller;

class AuthController extends Controller
{
    protected $helpers = ['form', 'url'];

    protected $userModel;
    protected $userLoginModel;
    protected $session;
    protected $validation;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->userLoginModel = new UserLoginModel();
        $this->session = service('session');
        $this->validation = service('validation');
    }

    /**
     * Display login page
     */
    public function login()
    {
        $isLoggedIn = $this->session->get('is_logged_in');
        $role = $this->session->get('role');
        $roleLevel = $this->session->get('role_level');

        log_message('debug', "AuthController::login - is_logged_in: " . var_export($isLoggedIn, true));
        log_message('debug', "AuthController::login - role: " . var_export($role, true));
        log_message('debug', "AuthController::login - role_level: " . var_export($roleLevel, true));

        // If already logged in, redirect to dashboard
        if ($isLoggedIn) {
            log_message('debug', "AuthController::login - Already logged in, redirecting to dashboard");
            return redirect()->to('/dashboard');
        }

        log_message('debug', "AuthController::login - Showing login form");
        return view('auth/login');
    }

    /**
     * Process login
     */
    public function doLogin()
    {
        if ($this->request->getMethod() !== 'POST') {
            return redirect()->to('/');
        }

        // Validation rules
        $rules = [
            'email' => [
                'rules' => 'required|valid_email',
                'errors' => [
                    'required' => 'Email-ul este obligatoriu.',
                    'valid_email' => 'Email-ul nu este valid.',
                ],
            ],
            'password' => [
                'rules' => 'required|min_length[1]',
                'errors' => [
                    'required' => 'Parola este obligatorie.',
                ],
            ],
        ];

        if (!$this->validate($rules)) {
            $this->session->setFlashdata('error', 'Email sau parolă incorectă.');
            return redirect()->back()->withInput();
        }

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        // Find user by email
        $user = $this->userModel->findByEmail($email);

        // Case 3: User doesn't exist OR password is wrong
        // Generic error message for security (don't reveal if email exists)
        if (!$user || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
            $this->session->setFlashdata('error', 'Email sau parolă incorectă.');
            return redirect()->back()->withInput();
        }

        // Case 2: User exists, password OK, but account is not active
        if ($user['active'] == 0) {
            // Generate password set token
            $token = $this->userModel->generatePasswordSetToken($user['id']);

            // Redirect to set password page with token
            return redirect()->to('/auth/set-password?token=' . $token);
        }

        // Case 1: Login successful - create session
        $this->createSession($user);

        // Log login
        $this->logLogin($user['id']);

        // Clear rate limit for this IP (successful login)
        RateLimitFilter::clearRateLimit($this->request->getIPAddress(), '/auth/login');

        // Redirect to dashboard
        return redirect()->to('/dashboard');
    }

    /**
     * Create session with user data
     */
    protected function createSession(array $user): void
    {
        $sessionData = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'username' => $user['username'],
            'role' => $user['role'],
            'role_level' => (int)$user['role_level'], // Ensure integer type
            'region_id' => $user['region_id'] !== null ? (int)$user['region_id'] : null, // Ensure integer type or null
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'is_logged_in' => true,
            'is_super_user' => $user['region_id'] === null,
        ];

        // Check if user has access to all departments
        $hasAllDepartments = $this->userModel->hasAccessToAllDepartments($user['id']);
        $sessionData['has_all_departments'] = $hasAllDepartments;

        $this->session->set($sessionData);
    }

    /**
     * Log user login
     */
    protected function logLogin(int $userId): void
    {
        $ipAddress = $this->request->getIPAddress();
        $userAgent = $this->request->getUserAgent()->getAgentString();

        $this->userLoginModel->logLogin($userId, $ipAddress, $userAgent);
    }

    /**
     * Display set password page
     */
    public function showSetPassword()
    {
        $token = $this->request->getGet('token');

        if (!$token) {
            $this->session->setFlashdata('error', 'Token-ul lipsă. Te rugăm să folosești link-ul primit.');
            return redirect()->to('/auth/login');
        }

        // Validate token
        $user = $this->userModel->findByPasswordSetToken($token);

        if (!$user) {
            $this->session->setFlashdata('error', 'Token invalid sau expirat. Te rugăm să contactezi administratorul.');
            return redirect()->to('/auth/login');
        }

        $data = [
            'token' => $token,
            'email' => $user['email'],
        ];

        return view('auth/set_password', $data);
    }

    /**
     * Process set password
     */
    public function setPassword()
    {
        if ($this->request->getMethod() !== 'POST') {
            return redirect()->to('/');
        }

        $token = $this->request->getPost('token');
        $password = $this->request->getPost('password');
        $passwordConfirm = $this->request->getPost('password_confirm');

        // Validate token
        if (!$token) {
            $this->session->setFlashdata('error', 'Token-ul lipsă.');
            return redirect()->to('/auth/login');
        }

        $user = $this->userModel->findByPasswordSetToken($token);

        if (!$user) {
            $this->session->setFlashdata('error', 'Token invalid sau expirat. Te rugăm să contactezi administratorul.');
            return redirect()->to('/auth/login');
        }

        // Validation rules
        $rules = [
            'password' => [
                'rules' => 'required|min_length[8]|matches[password_confirm]',
                'errors' => [
                    'required' => 'Parola este obligatorie.',
                    'min_length' => 'Parola trebuie să aibă minimum 8 caractere.',
                    'matches' => 'Parolele nu se potrivesc.',
                ],
            ],
            'password_confirm' => [
                'rules' => 'required|matches[password]',
                'errors' => [
                    'required' => 'Confirmarea parolei este obligatorie.',
                    'matches' => 'Parolele nu se potrivesc.',
                ],
            ],
        ];

        if (!$this->validate($rules)) {
            $errors = $this->validator->getErrors();
            $errorMessage = !empty($errors) ? implode('<br>', array_values($errors)) : 'Eroare de validare.';
            $this->session->setFlashdata('error', $errorMessage);
            return redirect()->back()->withInput();
        }

        // Set password and activate account
        if ($this->userModel->setPassword($user['id'], $password)) {
            // Clear token
            $this->userModel->clearPasswordSetToken($user['id']);

            // Create session (user can login now)
            $updatedUser = $this->userModel->find($user['id']);
            $this->createSession($updatedUser);

            // Log login
            $this->logLogin($user['id']);

            $this->session->setFlashdata('success', 'Parola a fost setată cu succes! Vei fi redirecționat către dashboard.');

            // Redirect to dashboard after a moment
            return redirect()->to('/dashboard');
        }

        $this->session->setFlashdata('error', 'A apărut o eroare la setarea parolei. Te rugăm să încerci din nou.');
        return redirect()->back();
    }

    /**
     * Logout
     */
    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('/auth/login');
    }
}
