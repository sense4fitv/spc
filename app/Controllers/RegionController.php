<?php

namespace App\Controllers;

use App\Models\RegionModel;
use App\Models\UserModel;

class RegionController extends BaseController
{
    protected RegionModel $regionModel;
    protected UserModel $userModel;
    protected $validation;
    protected $session;

    public function __construct()
    {
        $this->regionModel = new RegionModel();
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
     * List all regions
     * GET /regions
     */
    public function index()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi această pagină.');
        }

        $regions = $this->regionModel->getAllRegionsWithDetails();

        $data = [
            'regions' => $regions,
        ];

        return view('regions/index', $data);
    }

    /**
     * Show create region form
     * GET /regions/create
     */
    public function create()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/regions')->with('error', 'Nu ai permisiunea să creezi regiuni.');
        }

        // Get all directors for manager selection
        $directors = $this->userModel->where('role', 'director')
            ->where('active', 1)
            ->orderBy('last_name', 'ASC')
            ->orderBy('first_name', 'ASC')
            ->findAll();

        $data = [
            'directors' => $directors,
            'validation' => $this->validation,
        ];

        return view('regions/create', $data);
    }

    /**
     * Store new region
     * POST /regions/store
     */
    public function store()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/regions')->with('error', 'Nu ai permisiunea să creezi regiuni.');
        }

        $rules = [
            'name' => 'required|min_length[2]|max_length[150]|is_unique[regions.name]',
            'description' => 'permit_empty|max_length[1000]',
            'manager_id' => 'permit_empty|is_natural_no_zero',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // Validate manager_id if provided
        if (!empty($post['manager_id'])) {
            $manager = $this->userModel->find($post['manager_id']);
            if (!$manager || $manager['role'] !== 'director') {
                return redirect()->back()->withInput()->with('error', 'Managerul selectat nu este un director valid.');
            }
        }

        $regionData = [
            'name' => $post['name'],
            'description' => !empty($post['description']) ? $post['description'] : null,
            'manager_id' => !empty($post['manager_id']) ? $post['manager_id'] : null,
        ];

        if ($this->regionModel->insert($regionData)) {
            return redirect()->to('/regions')->with('success', 'Regiunea a fost creată cu succes.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Eroare la crearea regiunii.');
        }
    }

    /**
     * Show edit region form
     * GET /regions/edit/{id}
     */
    public function edit(int $id)
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/regions')->with('error', 'Nu ai permisiunea să editezi regiuni.');
        }

        $region = $this->regionModel->find($id);

        if (!$region) {
            return redirect()->to('/regions')->with('error', 'Regiunea nu a fost găsită.');
        }

        // Get all directors for manager selection
        $directors = $this->userModel->where('role', 'director')
            ->where('active', 1)
            ->orderBy('last_name', 'ASC')
            ->orderBy('first_name', 'ASC')
            ->findAll();

        $data = [
            'region' => $region,
            'directors' => $directors,
            'validation' => $this->validation,
        ];

        return view('regions/edit', $data);
    }

    /**
     * Update region
     * POST /regions/update/{id}
     */
    public function update(int $id)
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/regions')->with('error', 'Nu ai permisiunea să editezi regiuni.');
        }

        $region = $this->regionModel->find($id);

        if (!$region) {
            return redirect()->to('/regions')->with('error', 'Regiunea nu a fost găsită.');
        }

        $rules = [
            'name' => "required|min_length[2]|max_length[150]|is_unique[regions.name,id,{$id}]",
            'description' => 'permit_empty|max_length[1000]',
            'manager_id' => 'permit_empty|is_natural_no_zero',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // Validate manager_id if provided
        if (!empty($post['manager_id'])) {
            $manager = $this->userModel->find($post['manager_id']);
            if (!$manager || $manager['role'] !== 'director') {
                return redirect()->back()->withInput()->with('error', 'Managerul selectat nu este un director valid.');
            }
        }

        $regionData = [
            'name' => $post['name'],
            'description' => !empty($post['description']) ? $post['description'] : null,
            'manager_id' => !empty($post['manager_id']) ? $post['manager_id'] : null,
        ];

        if ($this->regionModel->update($id, $regionData)) {
            return redirect()->to('/regions')->with('success', 'Regiunea a fost actualizată cu succes.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Eroare la actualizarea regiunii.');
        }
    }

    /**
     * Delete region
     * POST /regions/delete/{id}
     */
    public function delete(int $id)
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nu ai permisiunea să ștergi regiuni.'])->setStatusCode(403);
        }

        $region = $this->regionModel->find($id);

        if (!$region) {
            return $this->response->setJSON(['success' => false, 'message' => 'Regiunea nu a fost găsită.'])->setStatusCode(404);
        }

        // Check dependencies
        $dependencies = $this->regionModel->hasDependencies($id);
        
        if ($dependencies['has_dependencies']) {
            $message = 'Nu poți șterge această regiune deoarece are ';
            $parts = [];
            
            if ($dependencies['users_count'] > 0) {
                $parts[] = $dependencies['users_count'] . ' utilizatori';
            }
            if ($dependencies['contracts_count'] > 0) {
                $parts[] = $dependencies['contracts_count'] . ' contracte';
            }
            
            $message .= implode(' și ', $parts) . ' asociate.';
            $message .= ' Te rugăm să elimini sau să muți aceste dependențe înainte de a șterge regiunea.';
            
            return $this->response->setJSON(['success' => false, 'message' => $message])->setStatusCode(400);
        }

        // Delete region
        if ($this->regionModel->delete($id)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Regiunea a fost ștearsă cu succes.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Eroare la ștergerea regiunii.'])->setStatusCode(500);
        }
    }
}

