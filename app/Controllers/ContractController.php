<?php

namespace App\Controllers;

use App\Models\ContractModel;
use App\Models\UserModel;
use App\Models\RegionModel;
use App\Services\ContractManagementService;

class ContractController extends BaseController
{
    protected ContractModel $contractModel;
    protected UserModel $userModel;
    protected RegionModel $regionModel;
    protected ContractManagementService $contractManagementService;
    protected $validation;
    protected $session;

    public function __construct()
    {
        $this->contractModel = new ContractModel();
        $this->userModel = new UserModel();
        $this->regionModel = new RegionModel();
        $this->contractManagementService = new ContractManagementService();
        $this->validation = \Config\Services::validation();
        $this->session = service('session');
    }

    /**
     * List all viewable contracts
     * GET /contracts
     */
    public function index()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Get viewable contracts based on role
        $contracts = $this->contractManagementService->getViewableContracts($currentUserId);

        // Get permissions
        $currentUser = $this->userModel->find($currentUserId);
        $canCreate = $currentUser && $this->contractManagementService->canCreateContract($currentUserId);
        $canEdit = function ($contractId) use ($currentUserId) {
            return $this->contractManagementService->canEditContract($currentUserId, $contractId);
        };
        $canDelete = function ($contractId) use ($currentUserId) {
            return $this->contractManagementService->canDeleteContract($currentUserId, $contractId);
        };

        $data = [
            'contracts' => $contracts,
            'canCreate' => $canCreate,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
        ];

        return view('contracts/index', $data);
    }

    /**
     * Show create contract form
     * GET /contracts/create
     */
    public function create()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        if (!$this->contractManagementService->canCreateContract($currentUserId)) {
            return redirect()->to('/contracts')->with('error', 'Nu ai permisiunea să creezi contracte.');
        }

        // Get allowed regions and managers
        $allowedRegions = $this->contractManagementService->getAllowedRegionsForCreate($currentUserId);
        $managers = $this->contractManagementService->getAllManagers();

        $currentUser = $this->userModel->find($currentUserId);
        $isDirector = $currentUser && $currentUser['role'] === 'director' && $currentUser['region_id'];

        $data = [
            'regions' => $allowedRegions,
            'managers' => $managers,
            'isDirector' => $isDirector,
            'directorRegionId' => $isDirector ? $currentUser['region_id'] : null,
            'validation' => $this->validation,
        ];

        return view('contracts/create', $data);
    }

    /**
     * Store new contract
     * POST /contracts/store
     */
    public function store()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        if (!$this->contractManagementService->canCreateContract($currentUserId)) {
            return redirect()->to('/contracts')->with('error', 'Nu ai permisiunea să creezi contracte.');
        }

        $rules = [
            'name' => 'required|min_length[2]|max_length[200]',
            'contract_number' => 'permit_empty|max_length[50]',
            'client_name' => 'permit_empty|max_length[150]',
            'region_id' => 'required|is_natural_no_zero',
            'manager_id' => 'permit_empty|is_natural_no_zero',
            'start_date' => 'permit_empty|valid_date',
            'end_date' => 'permit_empty|valid_date',
            'progress_percentage' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
            'status' => 'required|in_list[planning,active,on_hold,completed]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // Enforce region_id for directors
        $currentUser = $this->userModel->find($currentUserId);
        if ($currentUser && $currentUser['role'] === 'director' && $currentUser['region_id']) {
            if ($post['region_id'] != $currentUser['region_id']) {
                return redirect()->back()->withInput()->with('error', 'Nu poți crea contracte în afara regiunii tale.');
            }
        }

        // Validate manager_id if provided
        if (!empty($post['manager_id'])) {
            $manager = $this->userModel->find($post['manager_id']);
            if (!$manager || $manager['role'] !== 'manager') {
                return redirect()->back()->withInput()->with('error', 'Managerul selectat nu este un manager de contract valid.');
            }
        }

        $contractData = [
            'name' => $post['name'],
            'contract_number' => !empty($post['contract_number']) ? $post['contract_number'] : null,
            'client_name' => !empty($post['client_name']) ? $post['client_name'] : null,
            'region_id' => $post['region_id'],
            'manager_id' => !empty($post['manager_id']) ? $post['manager_id'] : null,
            'start_date' => !empty($post['start_date']) ? $post['start_date'] : null,
            'end_date' => !empty($post['end_date']) ? $post['end_date'] : null,
            'progress_percentage' => !empty($post['progress_percentage']) ? (int)$post['progress_percentage'] : 0,
            'status' => $post['status'],
        ];

        if ($this->contractModel->insert($contractData)) {
            return redirect()->to('/contracts')->with('success', 'Contractul a fost creat cu succes.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Eroare la crearea contractului.');
        }
    }

    /**
     * Show edit contract form
     * GET /contracts/edit/{id}
     */
    public function edit(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        if (!$this->contractManagementService->canEditContract($currentUserId, $id)) {
            return redirect()->to('/contracts')->with('error', 'Nu ai permisiunea să editezi acest contract.');
        }

        $contract = $this->contractModel->find($id);

        if (!$contract) {
            return redirect()->to('/contracts')->with('error', 'Contractul nu a fost găsit.');
        }

        // Get allowed regions and managers
        $allowedRegions = $this->contractManagementService->getAllowedRegionsForCreate($currentUserId);
        $managers = $this->contractManagementService->getAllManagers();

        $currentUser = $this->userModel->find($currentUserId);
        $isDirector = $currentUser && $currentUser['role'] === 'director' && $currentUser['region_id'];

        $data = [
            'contract' => $contract,
            'regions' => $allowedRegions,
            'managers' => $managers,
            'isDirector' => $isDirector,
            'validation' => $this->validation,
        ];

        return view('contracts/edit', $data);
    }

    /**
     * Update contract
     * POST /contracts/update/{id}
     */
    public function update(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        if (!$this->contractManagementService->canEditContract($currentUserId, $id)) {
            return redirect()->to('/contracts')->with('error', 'Nu ai permisiunea să editezi acest contract.');
        }

        $contract = $this->contractModel->find($id);

        if (!$contract) {
            return redirect()->to('/contracts')->with('error', 'Contractul nu a fost găsit.');
        }

        $rules = [
            'name' => 'required|min_length[2]|max_length[200]',
            'contract_number' => 'permit_empty|max_length[50]',
            'client_name' => 'permit_empty|max_length[150]',
            'region_id' => 'required|is_natural_no_zero',
            'manager_id' => 'permit_empty|is_natural_no_zero',
            'start_date' => 'permit_empty|valid_date',
            'end_date' => 'permit_empty|valid_date',
            'progress_percentage' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
            'status' => 'required|in_list[planning,active,on_hold,completed]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // Enforce region_id for directors (readonly)
        $currentUser = $this->userModel->find($currentUserId);
        if ($currentUser && $currentUser['role'] === 'director' && $currentUser['region_id']) {
            if ($post['region_id'] != $contract['region_id']) {
                return redirect()->back()->withInput()->with('error', 'Nu poți schimba regiunea contractului.');
            }
        }

        // Validate manager_id if provided
        if (!empty($post['manager_id'])) {
            $manager = $this->userModel->find($post['manager_id']);
            if (!$manager || $manager['role'] !== 'manager') {
                return redirect()->back()->withInput()->with('error', 'Managerul selectat nu este un manager de contract valid.');
            }
        }

        $contractData = [
            'name' => $post['name'],
            'contract_number' => !empty($post['contract_number']) ? $post['contract_number'] : null,
            'client_name' => !empty($post['client_name']) ? $post['client_name'] : null,
            'region_id' => $post['region_id'],
            'manager_id' => !empty($post['manager_id']) ? $post['manager_id'] : null,
            'start_date' => !empty($post['start_date']) ? $post['start_date'] : null,
            'end_date' => !empty($post['end_date']) ? $post['end_date'] : null,
            'progress_percentage' => !empty($post['progress_percentage']) ? (int)$post['progress_percentage'] : 0,
            'status' => $post['status'],
        ];

        if ($this->contractModel->update($id, $contractData)) {
            return redirect()->to('/contracts')->with('success', 'Contractul a fost actualizat cu succes.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Eroare la actualizarea contractului.');
        }
    }

    /**
     * Delete contract
     * POST /contracts/delete/{id}
     */
    public function delete(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$this->contractManagementService->canDeleteContract($currentUserId, $id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nu ai permisiunea să ștergi acest contract.'])->setStatusCode(403);
        }

        $contract = $this->contractModel->find($id);

        if (!$contract) {
            return $this->response->setJSON(['success' => false, 'message' => 'Contractul nu a fost găsit.'])->setStatusCode(404);
        }

        // Check dependencies
        $dependencies = $this->contractManagementService->hasDependencies($id);
        
        if ($dependencies['has_dependencies']) {
            $message = 'Nu poți șterge acest contract deoarece are ' . $dependencies['subdivisions_count'] . ' subdiviziuni asociate.';
            $message .= ' Te rugăm să elimini aceste subdiviziuni înainte de a șterge contractul.';
            
            return $this->response->setJSON(['success' => false, 'message' => $message])->setStatusCode(400);
        }

        // Delete contract
        if ($this->contractModel->delete($id)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Contractul a fost șters cu succes.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Eroare la ștergerea contractului.'])->setStatusCode(500);
        }
    }
}

