<?php

namespace App\Controllers;

use App\Models\DepartmentModel;
use App\Models\UserModel;

class DepartmentController extends BaseController
{
    protected DepartmentModel $departmentModel;
    protected UserModel $userModel;
    protected $validation;
    protected $session;

    public function __construct()
    {
        $this->departmentModel = new DepartmentModel();
        $this->userModel = new UserModel();
        $this->validation = \Config\Services::validation();
        $this->session = service('session');
    }

    /**
     * Check if current user is admin
     * 
     * @return bool
     */
    protected function isAdmin(): bool
    {
        $currentUserId = $this->session->get('user_id');
        if (!$currentUserId) {
            return false;
        }

        $user = $this->userModel->find($currentUserId);
        return $user && $user['role'] === 'admin' && $user['role_level'] >= 100;
    }

    /**
     * List all departments
     * GET /departments
     */
    public function index()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi această pagină.');
        }

        $departments = $this->departmentModel->getAllDepartmentsWithDetails();

        $data = [
            'departments' => $departments,
        ];

        return view('departments/index', $data);
    }

    /**
     * Show create department form
     * GET /departments/create
     */
    public function create()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/departments')->with('error', 'Nu ai permisiunea să creezi departamente.');
        }

        $data = [
            'validation' => $this->validation,
        ];

        return view('departments/create', $data);
    }

    /**
     * Store new department
     * POST /departments/store
     */
    public function store()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/departments')->with('error', 'Nu ai permisiunea să creezi departamente.');
        }

        $rules = [
            'name' => 'required|min_length[2]|max_length[50]|is_unique[departments.name]',
            'color_code' => 'permit_empty|regex_match[/^#[0-9A-Fa-f]{6}$/]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // Use color_code from picker, or from text input if picker is not set
        $colorCode = !empty($post['color_code']) ? $post['color_code'] : (!empty($post['color_code_text']) ? $post['color_code_text'] : '#808080');

        // Ensure uppercase and valid format
        $colorCode = strtoupper(trim($colorCode));
        if (!preg_match('/^#[0-9A-F]{6}$/', $colorCode)) {
            $colorCode = '#808080';
        }

        $departmentData = [
            'name' => $post['name'],
            'color_code' => $colorCode,
        ];

        if ($this->departmentModel->insert($departmentData)) {
            return redirect()->to('/departments')->with('success', 'Departamentul a fost creat cu succes.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Eroare la crearea departamentului.');
        }
    }

    /**
     * Show edit department form
     * GET /departments/edit/{id}
     */
    public function edit(int $id)
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/departments')->with('error', 'Nu ai permisiunea să editezi departamente.');
        }

        $department = $this->departmentModel->find($id);

        if (!$department) {
            return redirect()->to('/departments')->with('error', 'Departamentul nu a fost găsit.');
        }

        $data = [
            'department' => $department,
            'validation' => $this->validation,
        ];

        return view('departments/edit', $data);
    }

    /**
     * Update department
     * POST /departments/update/{id}
     */
    public function update(int $id)
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/departments')->with('error', 'Nu ai permisiunea să editezi departamente.');
        }

        $department = $this->departmentModel->find($id);

        if (!$department) {
            return redirect()->to('/departments')->with('error', 'Departamentul nu a fost găsit.');
        }

        $rules = [
            'name' => "required|min_length[2]|max_length[50]|is_unique[departments.name,id,{$id}]",
            'color_code' => 'permit_empty|regex_match[/^#[0-9A-Fa-f]{6}$/]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // Use color_code from picker, or from text input if picker is not set
        $colorCode = !empty($post['color_code']) ? $post['color_code'] : (!empty($post['color_code_text']) ? $post['color_code_text'] : '#808080');

        // Ensure uppercase and valid format
        $colorCode = strtoupper(trim($colorCode));
        if (!preg_match('/^#[0-9A-F]{6}$/', $colorCode)) {
            $colorCode = '#808080';
        }

        $departmentData = [
            'name' => $post['name'],
            'color_code' => $colorCode,
        ];

        if ($this->departmentModel->update($id, $departmentData)) {
            return redirect()->to('/departments')->with('success', 'Departamentul a fost actualizat cu succes.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Eroare la actualizarea departamentului.');
        }
    }

    /**
     * Delete department
     * POST /departments/delete/{id}
     */
    public function delete(int $id)
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nu ai permisiunea să ștergi departamente.'])->setStatusCode(403);
        }

        $department = $this->departmentModel->find($id);

        if (!$department) {
            return $this->response->setJSON(['success' => false, 'message' => 'Departamentul nu a fost găsit.'])->setStatusCode(404);
        }

        // Check dependencies
        $dependencies = $this->departmentModel->hasDependencies($id);

        if ($dependencies['has_dependencies']) {
            $message = 'Nu poți șterge acest departament deoarece are ';
            $parts = [];

            if ($dependencies['users_count'] > 0) {
                $parts[] = $dependencies['users_count'] . ' utilizatori';
            }
            if ($dependencies['tasks_count'] > 0) {
                $parts[] = $dependencies['tasks_count'] . ' sarcini';
            }

            $message .= implode(' și ', $parts) . ' asociate.';
            $message .= ' Te rugăm să elimini aceste dependențe înainte de a șterge departamentul.';

            return $this->response->setJSON(['success' => false, 'message' => $message])->setStatusCode(400);
        }

        // Delete department
        if ($this->departmentModel->delete($id)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Departamentul a fost șters cu succes.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Eroare la ștergerea departamentului.'])->setStatusCode(500);
        }
    }
}
