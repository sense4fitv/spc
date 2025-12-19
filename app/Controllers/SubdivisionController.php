<?php

namespace App\Controllers;

use App\Models\SubdivisionModel;
use App\Models\ContractModel;
use App\Models\UserModel;
use App\Services\SubdivisionManagementService;

class SubdivisionController extends BaseController
{
    protected SubdivisionModel $subdivisionModel;
    protected ContractModel $contractModel;
    protected UserModel $userModel;
    protected SubdivisionManagementService $subdivisionManagementService;
    protected $validation;
    protected $session;

    public function __construct()
    {
        $this->subdivisionModel = new SubdivisionModel();
        $this->contractModel = new ContractModel();
        $this->userModel = new UserModel();
        $this->subdivisionManagementService = new SubdivisionManagementService();
        $this->validation = \Config\Services::validation();
        $this->session = service('session');
    }

    /**
     * List all viewable subdivisions
     * GET /subdivisions
     */
    public function index()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Get viewable subdivisions based on role
        $subdivisions = $this->subdivisionManagementService->getViewableSubdivisions($currentUserId);

        // Get permissions
        $currentUser = $this->userModel->find($currentUserId);
        $canCreate = $currentUser && (
            $currentUser['role_level'] >= 100 || // Admin
            $currentUser['role_level'] >= 80 ||  // Director
            $currentUser['role_level'] >= 50     // Manager
        );
        $canEdit = function ($subdivisionId) use ($currentUserId) {
            return $this->subdivisionManagementService->canEditSubdivision($currentUserId, $subdivisionId);
        };
        $canDelete = function ($subdivisionId) use ($currentUserId) {
            return $this->subdivisionManagementService->canDeleteSubdivision($currentUserId, $subdivisionId);
        };

        $data = [
            'subdivisions' => $subdivisions,
            'canCreate' => $canCreate,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
        ];

        return view('subdivisions/index', $data);
    }

    /**
     * Show create subdivision form
     * GET /subdivisions/create
     */
    public function create()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Get allowed contracts
        $allowedContracts = $this->subdivisionManagementService->getAllowedContractsForCreate($currentUserId);

        if (empty($allowedContracts)) {
            return redirect()->to('/subdivisions')->with('error', 'Nu ai contracte disponibile pentru a crea subdiviziuni.');
        }

        // Get contract ID from query string if provided
        $contractId = $this->request->getGet('contract_id');
        if ($contractId && !isset($allowedContracts[$contractId])) {
            $contractId = null; // Reset if not allowed
        }

        $currentUser = $this->userModel->find($currentUserId);
        $isManager = $currentUser && $currentUser['role'] === 'manager';

        $data = [
            'contracts' => $allowedContracts,
            'selectedContractId' => $contractId ? (int)$contractId : null,
            'isManager' => $isManager,
            'validation' => $this->validation,
        ];

        return view('subdivisions/create', $data);
    }

    /**
     * Store new subdivision
     * POST /subdivisions/store
     */
    public function store()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        $rules = [
            'contract_id' => 'required|is_natural_no_zero',
            'code' => 'required|min_length[2]|max_length[20]',
            'name' => 'required|min_length[2]|max_length[150]',
            'details' => 'permit_empty|max_length[1000]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();
        $contractId = (int)$post['contract_id'];

        // Check permissions
        if (!$this->subdivisionManagementService->canCreateSubdivision($currentUserId, $contractId)) {
            return redirect()->to('/subdivisions')->with('error', 'Nu ai permisiunea să creezi subdiviziuni pentru acest contract.');
        }

        // Check if code is unique for this contract
        $existing = $this->subdivisionModel->where('contract_id', $contractId)
            ->where('code', $post['code'])
            ->first();

        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'Codul subdiviziunii trebuie să fie unic pentru acest contract.');
        }

        $subdivisionData = [
            'contract_id' => $contractId,
            'code' => $post['code'],
            'name' => $post['name'],
            'details' => !empty($post['details']) ? $post['details'] : null,
        ];

        if ($this->subdivisionModel->insert($subdivisionData)) {
            return redirect()->to('/subdivisions')->with('success', 'Subdiviziunea a fost creată cu succes.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Eroare la crearea subdiviziunii.');
        }
    }

    /**
     * Show edit subdivision form
     * GET /subdivisions/edit/{id}
     */
    public function edit(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        if (!$this->subdivisionManagementService->canEditSubdivision($currentUserId, $id)) {
            return redirect()->to('/subdivisions')->with('error', 'Nu ai permisiunea să editezi această subdiviziune.');
        }

        $subdivision = $this->subdivisionModel->find($id);

        if (!$subdivision) {
            return redirect()->to('/subdivisions')->with('error', 'Subdiviziunea nu a fost găsită.');
        }

        // Get allowed contracts (but contract_id is readonly)
        $allowedContracts = $this->subdivisionManagementService->getAllowedContractsForCreate($currentUserId);

        $currentUser = $this->userModel->find($currentUserId);
        $isManager = $currentUser && $currentUser['role'] === 'manager';

        $data = [
            'subdivision' => $subdivision,
            'contracts' => $allowedContracts,
            'isManager' => $isManager,
            'validation' => $this->validation,
        ];

        return view('subdivisions/edit', $data);
    }

    /**
     * Update subdivision
     * POST /subdivisions/update/{id}
     */
    public function update(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        if (!$this->subdivisionManagementService->canEditSubdivision($currentUserId, $id)) {
            return redirect()->to('/subdivisions')->with('error', 'Nu ai permisiunea să editezi această subdiviziune.');
        }

        $subdivision = $this->subdivisionModel->find($id);

        if (!$subdivision) {
            return redirect()->to('/subdivisions')->with('error', 'Subdiviziunea nu a fost găsită.');
        }

        $rules = [
            'code' => 'required|min_length[2]|max_length[20]',
            'name' => 'required|min_length[2]|max_length[150]',
            'details' => 'permit_empty|max_length[1000]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // Check if code is unique for this contract (excluding current subdivision)
        $existing = $this->subdivisionModel->where('contract_id', $subdivision['contract_id'])
            ->where('code', $post['code'])
            ->where('id !=', $id)
            ->first();

        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'Codul subdiviziunii trebuie să fie unic pentru acest contract.');
        }

        $subdivisionData = [
            'code' => $post['code'],
            'name' => $post['name'],
            'details' => !empty($post['details']) ? $post['details'] : null,
        ];

        if ($this->subdivisionModel->update($id, $subdivisionData)) {
            return redirect()->to('/subdivisions')->with('success', 'Subdiviziunea a fost actualizată cu succes.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Eroare la actualizarea subdiviziunii.');
        }
    }

    /**
     * Delete subdivision
     * POST /subdivisions/delete/{id}
     */
    public function delete(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$this->subdivisionManagementService->canDeleteSubdivision($currentUserId, $id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nu ai permisiunea să ștergi această subdiviziune.'])->setStatusCode(403);
        }

        $subdivision = $this->subdivisionModel->find($id);

        if (!$subdivision) {
            return $this->response->setJSON(['success' => false, 'message' => 'Subdiviziunea nu a fost găsită.'])->setStatusCode(404);
        }

        // Check dependencies
        $dependencies = $this->subdivisionManagementService->hasDependencies($id);

        if ($dependencies['has_dependencies']) {
            $message = 'Nu poți șterge această subdiviziune deoarece are ' . $dependencies['tasks_count'] . ' sarcini asociate.';
            $message .= ' Te rugăm să elimini aceste sarcini înainte de a șterge subdiviziunea.';

            return $this->response->setJSON(['success' => false, 'message' => $message])->setStatusCode(400);
        }

        // Delete subdivision
        if ($this->subdivisionModel->delete($id)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Subdiviziunea a fost ștearsă cu succes.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Eroare la ștergerea subdiviziunii.'])->setStatusCode(500);
        }
    }
}
