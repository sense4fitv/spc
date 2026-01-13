<?php

namespace App\Controllers;

use App\Models\IssueModel;
use App\Models\DepartmentModel;
use App\Models\RegionModel;
use App\Services\IssueManagementService;
use App\Services\IssueService;
use App\Services\IssueFileService;

class IssueController extends BaseController
{
    protected IssueModel $issueModel;
    protected DepartmentModel $departmentModel;
    protected RegionModel $regionModel;
    protected IssueManagementService $issueManagementService;
    protected IssueService $issueService;
    protected IssueFileService $issueFileService;
    protected $validation;
    protected $session;

    public function __construct()
    {
        $this->issueModel = new IssueModel();
        $this->departmentModel = new DepartmentModel();
        $this->regionModel = new RegionModel();
        $this->issueManagementService = new IssueManagementService();
        $this->issueService = new IssueService();
        $this->issueFileService = new IssueFileService();
        $this->validation = \Config\Services::validation();
        $this->session = service('session');
    }

    /**
     * List all viewable issues (cards view with search)
     * GET /issues
     */
    public function index()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Get viewable issues based on role
        $issues = $this->issueManagementService->getViewableIssues($currentUserId);

        // Apply search filter
        $search = $this->request->getGet('search');
        if (!empty($search)) {
            $searchLower = mb_strtolower($search);
            $issues = array_filter($issues, function ($issue) use ($searchLower) {
                $title = mb_strtolower($issue['title'] ?? '');
                $description = mb_strtolower($issue['description'] ?? '');
                return strpos($title, $searchLower) !== false || strpos($description, $searchLower) !== false;
            });
            $issues = array_values($issues); // Re-index array
        }

        // Apply status filter
        $statusFilter = $this->request->getGet('status');
        if (!empty($statusFilter)) {
            $issues = array_filter($issues, function ($issue) use ($statusFilter) {
                return ($issue['status'] ?? '') === $statusFilter;
            });
            $issues = array_values($issues); // Re-index array
        }

        // Get permissions
        $canCreate = $this->session->get('role_level') >= 80; // Director and above (Admin and Director)
        $canEdit = function ($issueId) use ($currentUserId) {
            return $this->issueManagementService->canEditIssue($currentUserId, $issueId);
        };
        $canArchive = function ($issueId) use ($currentUserId) {
            return $this->issueManagementService->canArchiveIssue($currentUserId, $issueId);
        };

        // Prepare status labels for view
        $statusLabels = [
            'open' => 'Deschisă',
            'answered' => 'Răspuns',
            'closed' => 'Închisă',
            'archived' => 'Arhivată',
        ];

        $statusBadgeClasses = [
            'open' => 'bg-subtle-blue',
            'answered' => 'bg-subtle-green',
            'closed' => 'bg-subtle-gray',
            'archived' => 'bg-subtle-red',
        ];

        // Get search and status filter for view
        $search = $this->request->getGet('search') ?? '';
        $statusFilter = $this->request->getGet('status') ?? '';

        $data = [
            'issues' => $issues,
            'canCreate' => $canCreate,
            'canEdit' => $canEdit,
            'canArchive' => $canArchive,
            'statusLabels' => $statusLabels,
            'statusBadgeClasses' => $statusBadgeClasses,
            'search' => $search,
            'statusFilter' => $statusFilter,
        ];

        return view('issues/index', $data);
    }

    /**
     * Show issue details (forum/knowledge base layout)
     * GET /issues/view/{id}
     */
    public function view(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check if user can view this issue
        if (!$this->issueManagementService->canViewIssue($currentUserId, $id)) {
            return redirect()->to('/issues')->with('error', 'Nu ai permisiunea să accesezi această problematică.');
        }

        // Get issue with full details
        $issue = $this->issueModel->getIssueWithFullDetails($id);

        if (!$issue) {
            return redirect()->to('/issues')->with('error', 'Problematicea nu există.');
        }

        // Get permissions
        $canEdit = $this->issueManagementService->canEditIssue($currentUserId, $id);
        $canArchive = $this->issueManagementService->canArchiveIssue($currentUserId, $id);
        $canComment = true; // Anyone who can view can comment
        $canUploadFiles = true; // Anyone who can view can upload files

        // Prepare status labels
        $statusLabels = [
            'open' => 'Deschisă',
            'answered' => 'Răspuns',
            'closed' => 'Închisă',
            'archived' => 'Arhivată',
        ];

        $statusBadgeClasses = [
            'open' => 'bg-subtle-blue',
            'answered' => 'bg-subtle-green',
            'closed' => 'bg-subtle-gray',
            'archived' => 'bg-subtle-red',
        ];

        $data = [
            'issue' => $issue,
            'canEdit' => $canEdit,
            'canArchive' => $canArchive,
            'canComment' => $canComment,
            'canUploadFiles' => $canUploadFiles,
            'statusLabels' => $statusLabels,
            'statusBadgeClasses' => $statusBadgeClasses,
            'currentUserId' => $currentUserId,
        ];

        return view('issues/view', $data);
    }

    /**
     * Show create issue form
     * GET /issues/create
     */
    public function create()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check if user can create issues
        $roleLevel = $this->session->get('role_level');
        if ($roleLevel < 80) { // Director and above (Admin and Director)
            return redirect()->to('/issues')->with('error', 'Nu ai permisiunea să creezi problematici.');
        }

        // Get allowed regions
        $regions = $this->issueManagementService->getAllowedRegionsForCreate($currentUserId);

        // Get allowed departments (all for now, since departments are optional)
        $departments = $this->departmentModel->findAll();

        $data = [
            'regions' => $regions,
            'departments' => $departments,
            'validation' => $this->validation,
            'currentUserId' => $currentUserId,
            'roleLevel' => $roleLevel,
        ];

        return view('issues/create', $data);
    }

    /**
     * Store new issue
     * POST /issues/store
     */
    public function store()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check if user can create issues
        $roleLevel = $this->session->get('role_level');
        if ($roleLevel < 80) { // Director and above (Admin and Director)
            return redirect()->to('/issues')->with('error', 'Nu ai permisiunea să creezi problematici.');
        }

        // Validate input
        $rules = [
            'title' => 'required|min_length[3]|max_length[200]',
            'description' => 'permit_empty|max_length[5000]',
            'region_id' => 'permit_empty|integer',
            'department_id' => 'permit_empty|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validation->getErrors());
        }

        // Get region_id (can be null for global issues, but only Admin can create global)
        $regionId = $this->request->getPost('region_id');
        if ($regionId === '' || $regionId === null) {
            $regionId = null;
        } else {
            $regionId = (int)$regionId;
        }

        // Check if user can create issue for this region
        if (!$this->issueManagementService->canCreateIssue($currentUserId, $regionId)) {
            return redirect()->back()->withInput()->with('error', 'Nu ai permisiunea să creezi problematici pentru această regiune.');
        }

        // Get department_id (optional)
        $departmentId = $this->request->getPost('department_id');
        if ($departmentId === '' || $departmentId === null) {
            $departmentId = null;
        } else {
            $departmentId = (int)$departmentId;
        }

        // Prepare issue data
        $issueData = [
            'region_id' => $regionId,
            'department_id' => $departmentId,
            'created_by' => $currentUserId,
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description') ?: null,
            'status' => 'open',
        ];

        // Insert issue
        $issueId = $this->issueModel->insert($issueData);

        if (!$issueId) {
            return redirect()->back()->withInput()->with('error', 'Eroare la crearea problematicii.');
        }

        return redirect()->to('/issues/view/' . $issueId)->with('success', 'Problematicea a fost creată cu succes.');
    }

    /**
     * Show edit issue form
     * GET /issues/edit/{id}
     */
    public function edit(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check if user can edit this issue
        if (!$this->issueManagementService->canEditIssue($currentUserId, $id)) {
            return redirect()->to('/issues')->with('error', 'Nu ai permisiunea să editezi această problematică.');
        }

        // Get issue
        $issue = $this->issueModel->find($id);

        if (!$issue) {
            return redirect()->to('/issues')->with('error', 'Problematicea nu există.');
        }

        // Get allowed regions
        $regions = $this->issueManagementService->getAllowedRegionsForCreate($currentUserId);

        // Get allowed departments (all for now, since departments are optional)
        $departments = $this->departmentModel->findAll();

        // Prepare status labels
        $statusLabels = [
            'open' => 'Deschisă',
            'answered' => 'Răspuns',
            'closed' => 'Închisă',
            'archived' => 'Arhivată',
        ];

        $data = [
            'issue' => $issue,
            'regions' => $regions,
            'departments' => $departments,
            'statusLabels' => $statusLabels,
            'validation' => $this->validation,
            'currentUserId' => $currentUserId,
            'roleLevel' => $this->session->get('role_level'),
        ];

        return view('issues/edit', $data);
    }

    /**
     * Update issue
     * POST /issues/update/{id}
     */
    public function update(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check if user can edit this issue
        if (!$this->issueManagementService->canEditIssue($currentUserId, $id)) {
            return redirect()->to('/issues')->with('error', 'Nu ai permisiunea să editezi această problematică.');
        }

        // Get issue
        $issue = $this->issueModel->find($id);

        if (!$issue) {
            return redirect()->to('/issues')->with('error', 'Problematicea nu există.');
        }

        // Validate input
        $rules = [
            'title' => 'required|min_length[3]|max_length[200]',
            'description' => 'permit_empty|max_length[5000]',
            'region_id' => 'permit_empty|integer',
            'department_id' => 'permit_empty|integer',
            'status' => 'required|in_list[open,answered,closed,archived]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validation->getErrors());
        }

        // Get region_id (can be null for global issues, but only Admin can edit global)
        $regionId = $this->request->getPost('region_id');
        if ($regionId === '' || $regionId === null) {
            $regionId = null;
        } else {
            $regionId = (int)$regionId;
        }

        // Get department_id (optional)
        $departmentId = $this->request->getPost('department_id');
        if ($departmentId === '' || $departmentId === null) {
            $departmentId = null;
        } else {
            $departmentId = (int)$departmentId;
        }

        // Only Admin can archive
        $status = $this->request->getPost('status');
        $roleLevel = $this->session->get('role_level');
        if ($status === 'archived' && $roleLevel < 100) {
            $status = $issue['status']; // Keep current status if not Admin
        }

        // Prepare issue data
        $issueData = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description') ?: null,
            'region_id' => $regionId,
            'department_id' => $departmentId,
            'status' => $status,
        ];

        // Update issue
        $updated = $this->issueModel->update($id, $issueData);

        if (!$updated) {
            return redirect()->back()->withInput()->with('error', 'Eroare la actualizarea problematicii.');
        }

        return redirect()->to('/issues/view/' . $id)->with('success', 'Problematicea a fost actualizată cu succes.');
    }

    /**
     * Archive issue
     * POST /issues/archive/{id}
     */
    public function archive(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check if user can archive this issue
        if (!$this->issueManagementService->canArchiveIssue($currentUserId, $id)) {
            return redirect()->to('/issues')->with('error', 'Nu ai permisiunea să arhivezi această problematică.');
        }

        // Get issue
        $issue = $this->issueModel->find($id);

        if (!$issue) {
            return redirect()->to('/issues')->with('error', 'Problematicea nu există.');
        }

        // Archive issue
        $archived = $this->issueModel->archive($id);

        if (!$archived) {
            return redirect()->back()->with('error', 'Eroare la arhivarea problematicii.');
        }

        return redirect()->to('/issues')->with('success', 'Problematicea a fost arhivată cu succes.');
    }

    /**
     * Add comment to issue (AJAX)
     * POST /issues/{id}/comment
     */
    public function addComment(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nu ești autentificat.']);
        }

        // Check if user can view this issue
        if (!$this->issueManagementService->canViewIssue($currentUserId, $id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nu ai permisiunea să accesezi această problematică.']);
        }

        // Validate input
        $comment = $this->request->getPost('comment');

        if (empty(trim($comment))) {
            return $this->response->setJSON(['success' => false, 'message' => 'Comentariul nu poate fi gol.']);
        }

        // Add comment using IssueService
        $result = $this->issueService->addComment($id, trim($comment), $currentUserId);

        if ($result['success']) {
            return $this->response->setJSON([
                'success' => true,
                'message' => $result['message'],
                'comment_id' => $result['comment_id'],
            ]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => $result['message']]);
        }
    }

    /**
     * Upload file to issue (AJAX)
     * POST /issues/{id}/upload-file
     */
    public function uploadFile(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nu ești autentificat.']);
        }

        // Check if user can view this issue
        if (!$this->issueManagementService->canViewIssue($currentUserId, $id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nu ai permisiunea să accesezi această problematică.']);
        }

        // Get uploaded files
        $files = $this->request->getFiles();

        if (empty($files) || !isset($files['files'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nu a fost încărcat niciun fișier.']);
        }

        // Upload files using IssueFileService
        $result = $this->issueFileService->uploadIssueFiles($id, $files['files'], $currentUserId);

        return $this->response->setJSON($result);
    }

    /**
     * Download file from issue
     * GET /issues/{id}/download-file/{fileId}
     */
    public function downloadFile(int $id, int $fileId)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Get file info and verify access
        $result = $this->issueFileService->downloadIssueFile($id, $fileId, $currentUserId);

        if (!$result) {
            return redirect()->back()->with('error', 'Fișierul nu există sau nu ai permisiunea să-l descarci.');
        }

        // Return file for download
        return $this->response->download($result['filepath'], null)->setFileName($result['filename']);
    }
}

