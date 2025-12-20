<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RegionModel;
use App\Models\DepartmentModel;
use App\Models\DepartmentHeadModel;
use App\Services\UserManagementService;
use App\Services\EmailService;
use App\Services\DepartmentHeadService;

class UserController extends BaseController
{
    protected UserModel $userModel;
    protected UserManagementService $userManagementService;
    protected EmailService $emailService;
    protected DepartmentHeadModel $departmentHeadModel;
    protected DepartmentHeadService $departmentHeadService;
    protected $validation;
    protected $session;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->userManagementService = new UserManagementService();
        $this->emailService = new EmailService();
        $this->departmentHeadModel = new DepartmentHeadModel();
        $this->departmentHeadService = new DepartmentHeadService();
        $this->validation = \Config\Services::validation();
        $this->session = service('session');
    }

    /**
     * List all viewable users
     * GET /users
     */
    public function index()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Get viewable users based on role
        $users = $this->userManagementService->getViewableUsers($currentUserId);

        // For Manager, get task counts
        $currentUser = $this->userModel->find($currentUserId);
        $isManager = $currentUser && $currentUser['role'] === 'manager';

        if ($isManager) {
            $users = $this->userManagementService->getUsersWithTaskCounts($currentUserId);
        }

        // Get permissions
        $canCreate = $this->userManagementService->canCreateUser($currentUserId);
        $canEdit = function ($targetUserId) use ($currentUserId) {
            return $this->userManagementService->canEditUser($currentUserId, $targetUserId);
        };
        $canDelete = $this->userManagementService->canDeleteUser($currentUserId);

        // Get filter options (regions, departments for filters)
        $regions = (new RegionModel())->findAll();
        $departments = $this->userManagementService->getAllDepartments();

        // Prepare role display names and badge classes for view
        $roleDisplayNames = [
            'admin' => 'Admin',
            'director' => 'Director Regional',
            'manager' => 'Manager Contract',
            'executant' => 'Executant',
            'auditor' => 'Auditor',
        ];

        $roleBadgeClasses = [
            'admin' => 'bg-subtle-purple',
            'director' => 'bg-subtle-blue',
            'manager' => 'bg-subtle-gray',
            'executant' => 'bg-subtle-orange',
            'auditor' => 'bg-subtle-green',
        ];

        $data = [
            'users' => $users,
            'canCreate' => $canCreate,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
            'isManager' => $isManager,
            'regions' => $regions,
            'departments' => $departments,
            'roleDisplayNames' => $roleDisplayNames,
            'roleBadgeClasses' => $roleBadgeClasses,
            'userManagementService' => $this->userManagementService, // Pass service for helper methods
        ];

        return view('users/index', $data);
    }

    /**
     * Show create user form
     * GET /users/create
     */
    public function create()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check permissions
        if (!$this->userManagementService->canCreateUser($currentUserId)) {
            return redirect()->to('/users')->with('error', 'Nu ai permisiunea să creezi utilizatori.');
        }

        // Get allowed regions and roles
        $allowedRegions = $this->userManagementService->getAllowedRegionsForCreate($currentUserId);
        $allowedRoles = $this->userManagementService->getAllowedRolesForCreate($currentUserId);
        $departments = $this->userManagementService->getAllDepartments();

        $currentUser = $this->userModel->find($currentUserId);
        $isAdmin = $currentUser && $currentUser['role'] === 'admin';

        $roleDisplayNames = [
            'admin' => 'Admin',
            'director' => 'Director Regional',
            'manager' => 'Manager Contract',
            'executant' => 'Executant',
            'auditor' => 'Auditor',
        ];

        $data = [
            'regions' => $allowedRegions,
            'roles' => $allowedRoles,
            'departments' => $departments,
            'roleDisplayNames' => $roleDisplayNames,
            'canChangeRole' => $this->userManagementService->canChangeRole($currentUserId),
            'isAdmin' => $isAdmin, // For department head assignment (only Admin)
        ];

        return view('users/create', $data);
    }

    /**
     * Store new user
     * POST /users/store
     */
    public function store()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check permissions
        if (!$this->userManagementService->canCreateUser($currentUserId)) {
            return redirect()->to('/users')->with('error', 'Nu ai permisiunea să creezi utilizatori.');
        }

        // Get role first to determine validation rules
        $role = $this->request->getPost('role');

        // Validation rules
        $rules = [
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name' => 'required|min_length[2]|max_length[100]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'phone' => 'permit_empty|max_length[20]',
            'role' => 'required|in_list[admin,director,manager,executant,auditor]',
            'region_id' => 'permit_empty|integer',
            'departments' => 'permit_empty',
        ];

        // Director must have region_id
        if ($role === 'director') {
            $rules['region_id'] = 'required|integer';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $currentUser = $this->userModel->find($currentUserId);
        $targetRegionId = $this->request->getPost('region_id') ? (int)$this->request->getPost('region_id') : null;

        // Director cannot be created without region_id
        if ($role === 'director' && !$targetRegionId) {
            return redirect()->back()->withInput()->with('error', 'Directorul trebuie să aibă o regiune asignată.');
        }

        // For Director, force his region
        if ($currentUser['role'] === 'director' && $currentUser['region_id']) {
            $targetRegionId = $currentUser['region_id'];
        }

        // Validate region if required
        if (!$this->userManagementService->canCreateUser($currentUserId, $targetRegionId)) {
            return redirect()->back()->withInput()->with('error', 'Nu poți crea utilizatori în această regiune.');
        }

        // Role level mapping
        $roleLevels = [
            'admin' => 100,
            'director' => 80,
            'manager' => 50,
            'executant' => 20,
            'auditor' => 10,
        ];

        $role = $this->request->getPost('role');
        $roleLevel = $roleLevels[$role] ?? 20;

        // Generate temporary password
        $temporaryPassword = $this->userManagementService->generateRandomPassword(12);

        // Prepare user data
        $userData = [
            'username' => $this->request->getPost('email'),
            'email' => $this->request->getPost('email'),
            'password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
            'role' => $role,
            'role_level' => $roleLevel,
            'region_id' => $targetRegionId,
            'first_name' => $this->request->getPost('first_name'),
            'last_name' => $this->request->getPost('last_name'),
            'phone' => $this->request->getPost('phone') ? trim($this->request->getPost('phone')) : null,
            'active' => 0, // Inactive until password is set
            'created_by' => $currentUserId,
        ];

        // Insert user
        $userId = $this->userModel->insert($userData);

        if (!$userId) {
            return redirect()->back()->withInput()->with('error', 'Eroare la crearea utilizatorului.');
        }

        // Handle departments (many-to-many)
        $departments = $this->request->getPost('departments');
        if (!empty($departments) && is_array($departments)) {
            $db = \Config\Database::connect();
            foreach ($departments as $deptId) {
                $db->table('user_departments')->insert([
                    'user_id' => $userId,
                    'department_id' => (int)$deptId,
                ]);
            }
        }

        // Handle department head assignment (only Admin can create department heads)
        $currentUser = $this->userModel->find($currentUserId);
        $isAdmin = $currentUser && $currentUser['role'] === 'admin';

        if ($isAdmin) {
            $departmentHeadDepartmentId = $this->request->getPost('department_head_department_id');
            $departmentHeadRegionId = $this->request->getPost('department_head_region_id');

            // If both department and region are provided, assign as department head
            if ($departmentHeadDepartmentId && $departmentHeadRegionId) {
                $departmentHeadDepartmentId = (int)$departmentHeadDepartmentId;
                $departmentHeadRegionId = (int)$departmentHeadRegionId;

                // Validate that user can be department head (minimum level 50 - Manager or above)
                // Executant (20) cannot be department head
                if ($roleLevel < 50) {
                    return redirect()->back()->withInput()->with('error', 'Un executant nu poate fi șef de departament. Doar Manager, Director sau Admin pot fi șefi de departament.');
                }

                // Check if department already has a head in this region
                $existingHead = $this->departmentHeadModel->getDepartmentHead($departmentHeadDepartmentId, $departmentHeadRegionId);
                if ($existingHead) {
                    // Department already has a head - show error
                    return redirect()->back()->withInput()->with('error', 'Acest departament are deja un șef în această regiune.');
                }

                // Assign as department head
                if (!$this->departmentHeadModel->assignDepartmentHead($userId, $departmentHeadDepartmentId, $departmentHeadRegionId)) {
                    return redirect()->back()->withInput()->with('error', 'Eroare la atribuirea ca șef de departament.');
                }
            }
        }

        // Generate password set token
        $token = $this->userModel->generatePasswordSetToken($userId);

        // Send welcome email
        $this->emailService->sendWelcomeEmail($userId, $temporaryPassword, $token);

        return redirect()->to('/users')->with('success', 'Utilizatorul a fost creat cu succes. Un email cu datele de logare a fost trimis.');
    }

    /**
     * Show edit user form
     * GET /users/edit/{id}
     */
    public function edit(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check permissions
        if (!$this->userManagementService->canEditUser($currentUserId, $id)) {
            return redirect()->to('/users')->with('error', 'Nu ai permisiunea să editezi acest utilizator.');
        }

        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('/users')->with('error', 'Utilizatorul nu a fost găsit.');
        }

        // Get user departments
        $userDepartments = $this->userModel->getDepartments($id);
        $userDepartmentIds = array_column($userDepartments, 'department_id');

        // Get allowed regions and roles
        $allowedRegions = $this->userManagementService->getAllowedRegionsForCreate($currentUserId);
        $allowedRoles = $this->userManagementService->getAllowedRolesForCreate($currentUserId);
        $departments = $this->userManagementService->getAllDepartments();

        $currentUser = $this->userModel->find($currentUserId);
        $isDirector = $currentUser && $currentUser['role'] === 'director';
        $isAdmin = $currentUser && $currentUser['role'] === 'admin';

        // Get department head assignments for this user
        $departmentHeadAssignments = [];
        if ($isAdmin) {
            $departmentHeadAssignments = $this->departmentHeadModel->getDepartmentsForUser($id);
        }

        $roleDisplayNames = [
            'admin' => 'Admin',
            'director' => 'Director Regional',
            'manager' => 'Manager Contract',
            'executant' => 'Executant',
            'auditor' => 'Auditor',
        ];

        $data = [
            'user' => $user,
            'userDepartmentIds' => $userDepartmentIds,
            'regions' => $allowedRegions,
            'roles' => $allowedRoles,
            'departments' => $departments,
            'roleDisplayNames' => $roleDisplayNames,
            'canChangeRole' => $this->userManagementService->canChangeRole($currentUserId),
            'isDirector' => $isDirector, // For readonly fields
            'isAdmin' => $isAdmin, // For department head assignment
            'departmentHeadAssignments' => $departmentHeadAssignments, // Current assignments
        ];

        return view('users/edit', $data);
    }

    /**
     * Update user
     * POST /users/update/{id}
     */
    public function update(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check permissions
        if (!$this->userManagementService->canEditUser($currentUserId, $id)) {
            return redirect()->to('/users')->with('error', 'Nu ai permisiunea să editezi acest utilizator.');
        }

        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('/users')->with('error', 'Utilizatorul nu a fost găsit.');
        }

        // Check if target user is a director
        $targetUserRole = $user['role'];

        // Validation rules
        $rules = [
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name' => 'required|min_length[2]|max_length[100]',
            'email' => "required|valid_email|is_unique[users.email,id,{$id}]",
            'phone' => 'permit_empty|max_length[20]',
            'region_id' => 'permit_empty|integer',
            'departments' => 'permit_empty',
        ];

        // Director must have region_id
        if ($targetUserRole === 'director') {
            $rules['region_id'] = 'required|integer';
        }

        $currentUser = $this->userModel->find($currentUserId);
        $isDirector = $currentUser && $currentUser['role'] === 'director';

        // Director cannot change role or region
        if (!$isDirector && $this->userManagementService->canChangeRole($currentUserId)) {
            $rules['role'] = 'required|in_list[admin,director,manager,executant,auditor]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Get region_id from post (or keep current if director)
        $targetRegionId = $this->request->getPost('region_id') ? (int)$this->request->getPost('region_id') : null;

        // Director cannot be updated without region_id
        if ($targetUserRole === 'director' && !$targetRegionId) {
            return redirect()->back()->withInput()->with('error', 'Directorul trebuie să aibă o regiune asignată.');
        }

        // For Director, force his region (cannot change)
        if ($isDirector && $currentUser['region_id']) {
            $targetRegionId = $currentUser['region_id'];
        }

        // Prepare update data
        $updateData = [
            'first_name' => $this->request->getPost('first_name'),
            'last_name' => $this->request->getPost('last_name'),
            'email' => $this->request->getPost('email'),
            'username' => $this->request->getPost('email'), // Keep username in sync
            'phone' => $this->request->getPost('phone') ? trim($this->request->getPost('phone')) : null,
            'region_id' => $targetRegionId, // Add region_id to update data
        ];

        // Only allow role/region change if not director
        if (!$isDirector && $this->userManagementService->canChangeRole($currentUserId)) {
            $role = $this->request->getPost('role');
            $roleLevels = [
                'admin' => 100,
                'director' => 80,
                'manager' => 50,
                'executant' => 20,
                'auditor' => 10,
            ];

            $updateData['role'] = $role;
            $updateData['role_level'] = $roleLevels[$role] ?? 20;
        }

        // Update user
        if (!$this->userModel->update($id, $updateData)) {
            return redirect()->back()->withInput()->with('error', 'Eroare la actualizarea utilizatorului.');
        }

        // Update departments (many-to-many)
        $db = \Config\Database::connect();

        // Remove existing departments
        $db->table('user_departments')->where('user_id', $id)->delete();

        // Add new departments
        $departments = $this->request->getPost('departments');
        if (!empty($departments) && is_array($departments)) {
            foreach ($departments as $deptId) {
                $db->table('user_departments')->insert([
                    'user_id' => $id,
                    'department_id' => (int)$deptId,
                ]);
            }
        }

        // Handle department head assignment (only Admin can manage department heads)
        $isAdmin = $currentUser && $currentUser['role'] === 'admin';

        if ($isAdmin) {
            // Remove all existing department head assignments for this user
            $this->departmentHeadModel->removeAllForUser($id);

            // Add new department head assignment if provided
            $departmentHeadDepartmentId = $this->request->getPost('department_head_department_id');
            $departmentHeadRegionId = $this->request->getPost('department_head_region_id');

            if ($departmentHeadDepartmentId && $departmentHeadRegionId) {
                $departmentHeadDepartmentId = (int)$departmentHeadDepartmentId;
                $departmentHeadRegionId = (int)$departmentHeadRegionId;

                // Validate that user can be department head (minimum level 50 - Manager or above)
                // Executant (20) cannot be department head
                $targetUserRoleLevel = $user['role_level'] ?? 20;
                if ($targetUserRoleLevel < 50) {
                    return redirect()->back()->withInput()->with('error', 'Un executant nu poate fi șef de departament. Doar Manager, Director sau Admin pot fi șefi de departament.');
                }

                // Check if department already has a head in this region (excluding current user)
                $existingHead = $this->departmentHeadModel->getDepartmentHead($departmentHeadDepartmentId, $departmentHeadRegionId);
                if ($existingHead && $existingHead['user_id'] != $id) {
                    // Department already has a different head - show error
                    return redirect()->back()->withInput()->with('error', 'Acest departament are deja un șef în această regiune.');
                }

                // Assign as department head
                if (!$this->departmentHeadModel->assignDepartmentHead($id, $departmentHeadDepartmentId, $departmentHeadRegionId)) {
                    return redirect()->back()->withInput()->with('error', 'Eroare la atribuirea ca șef de departament.');
                }
            }
        }

        return redirect()->to('/users')->with('success', 'Utilizatorul a fost actualizat cu succes.');
    }

    /**
     * Delete user (soft delete)
     * POST /users/delete/{id}
     */
    public function delete(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check permissions
        if (!$this->userManagementService->canDeleteUser($currentUserId)) {
            return redirect()->to('/users')->with('error', 'Nu ai permisiunea să ștergi utilizatori.');
        }

        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('/users')->with('error', 'Utilizatorul nu a fost găsit.');
        }

        // Prevent self-deletion
        if ($id == $currentUserId) {
            return redirect()->to('/users')->with('error', 'Nu îți poți șterge propriul cont.');
        }

        // Check if user has active tasks
        $taskCheck = $this->userManagementService->hasActiveTasks($id);
        if ($taskCheck['has_active_tasks']) {
            $message = 'Nu poți șterge acest utilizator deoarece are ';
            $parts = [];

            if ($taskCheck['assigned'] > 0) {
                $parts[] = $taskCheck['assigned'] . ' sarcini asignate';
            }
            if ($taskCheck['created'] > 0) {
                $parts[] = $taskCheck['created'] . ' sarcini create';
            }

            $message .= implode(' și ', $parts) . ' care nu sunt finalizate.';
            $message .= ' Te rugăm să finalizezi sau să reasigni aceste sarcini înainte de a șterge utilizatorul.';

            return redirect()->to('/users')->with('error', $message);
        }

        // Soft delete
        if ($this->userModel->softDelete($id)) {
            return redirect()->to('/users')->with('success', 'Utilizatorul a fost dezactivat cu succes.');
        }

        return redirect()->to('/users')->with('error', 'Eroare la dezactivarea utilizatorului.');
    }
}
