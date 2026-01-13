<?php

namespace App\Controllers;

use App\Models\TaskModel;
use App\Models\DepartmentModel;
use App\Services\TaskManagementService;
use App\Services\TaskService;
use App\Services\FileService;

class TaskController extends BaseController
{
    protected TaskModel $taskModel;
    protected DepartmentModel $departmentModel;
    protected TaskManagementService $taskManagementService;
    protected TaskService $taskService;
    protected FileService $fileService;
    protected $validation;
    protected $session;

    public function __construct()
    {
        $this->taskModel = new TaskModel();
        $this->departmentModel = new DepartmentModel();
        $this->taskManagementService = new TaskManagementService();
        $this->taskService = new TaskService();
        $this->fileService = new FileService();
        $this->validation = \Config\Services::validation();
        $this->session = service('session');
    }

    /**
     * List all viewable tasks (table view)
     * GET /tasks
     */
    public function index()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Get viewable tasks based on role
        $tasks = $this->taskManagementService->getViewableTasks($currentUserId);

        // Get permissions
        $canCreate = $this->session->get('role_level') >= 50; // Manager and above
        $canEdit = function ($taskId) use ($currentUserId) {
            return $this->taskManagementService->canEditTask($currentUserId, $taskId);
        };
        $canDelete = function ($taskId) use ($currentUserId) {
            return $this->taskManagementService->canDeleteTask($currentUserId, $taskId);
        };

        // Prepare status and priority labels for view
        $statusLabels = [
            'new' => 'Nou',
            'in_progress' => 'În progres',
            'blocked' => 'Blocat',
            'review' => 'În revizie',
            'completed' => 'Finalizat',
        ];

        $statusBadgeClasses = [
            'new' => 'bg-subtle-gray',
            'in_progress' => 'bg-subtle-blue',
            'blocked' => 'bg-subtle-red',
            'review' => 'bg-subtle-yellow',
            'completed' => 'bg-subtle-green',
        ];

        $priorityLabels = [
            'low' => 'Scăzută',
            'medium' => 'Medie',
            'high' => 'Ridicată',
            'critical' => 'Critică',
        ];

        $priorityBadgeClasses = [
            'low' => 'bg-subtle-green',
            'medium' => 'bg-subtle-yellow',
            'high' => 'bg-subtle-orange',
            'critical' => 'bg-subtle-red',
        ];

        $data = [
            'tasks' => $tasks,
            'canCreate' => $canCreate,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
            'statusLabels' => $statusLabels,
            'statusBadgeClasses' => $statusBadgeClasses,
            'priorityLabels' => $priorityLabels,
            'priorityBadgeClasses' => $priorityBadgeClasses,
        ];

        return view('tasks/index', $data);
    }

    /**
     * Show my tasks (cards view)
     * GET /tasks/my-tasks
     */
    public function myTasks()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Get tasks assigned to or created by user
        $tasks = $this->taskManagementService->getMyTasks($currentUserId);

        // Group tasks by status
        $tasksByStatus = [
            'new' => [],
            'in_progress' => [],
            'blocked' => [],
            'review' => [],
            'completed' => [],
        ];

        foreach ($tasks as $task) {
            $status = $task['status'] ?? 'new';
            if (isset($tasksByStatus[$status])) {
                $tasksByStatus[$status][] = $task;
            }
        }

        // Prepare status and priority labels for view
        $statusLabels = [
            'new' => 'Nou',
            'in_progress' => 'În progres',
            'blocked' => 'Blocat',
            'review' => 'În revizie',
            'completed' => 'Finalizat',
        ];

        $statusBadgeClasses = [
            'new' => 'bg-subtle-gray',
            'in_progress' => 'bg-subtle-blue',
            'blocked' => 'bg-subtle-red',
            'review' => 'bg-subtle-yellow',
            'completed' => 'bg-subtle-green',
        ];

        $priorityLabels = [
            'low' => 'Scăzută',
            'medium' => 'Medie',
            'high' => 'Ridicată',
            'critical' => 'Critică',
        ];

        $priorityBadgeClasses = [
            'low' => 'bg-subtle-green',
            'medium' => 'bg-subtle-yellow',
            'high' => 'bg-subtle-orange',
            'critical' => 'bg-subtle-red',
        ];

        $data = [
            'tasksByStatus' => $tasksByStatus,
            'statusLabels' => $statusLabels,
            'statusBadgeClasses' => $statusBadgeClasses,
            'priorityLabels' => $priorityLabels,
            'priorityBadgeClasses' => $priorityBadgeClasses,
        ];

        return view('tasks/my-tasks', $data);
    }

    /**
     * Show task details
     * GET /tasks/view/{id}
     */
    public function view(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check if user can view this task
        if (!$this->taskManagementService->canViewTask($currentUserId, $id)) {
            return redirect()->to('/tasks')->with('error', 'Nu ai permisiunea să accesezi acest task.');
        }

        // Get task with full details
        $task = $this->taskModel->getTaskWithFullDetails($id);

        if (!$task) {
            return redirect()->to('/tasks')->with('error', 'Task-ul nu există.');
        }

        // Get permissions
        $canEdit = $this->taskManagementService->canEditTask($currentUserId, $id);
        $canDelete = $this->taskManagementService->canDeleteTask($currentUserId, $id);
        $canComment = true; // Anyone who can view can comment
        $canUploadFiles = true; // Anyone who can view can upload files

        // Check if user is assignee (executants can change status even if they can't edit)
        $isAssignee = false;
        $assignees = $this->taskModel->getAssignees($id);
        foreach ($assignees as $assignee) {
            if (isset($assignee['id']) && $assignee['id'] == $currentUserId) {
                $isAssignee = true;
                break;
            }
        }

        // Can change status if can edit OR is assignee
        $canChangeStatus = $canEdit || $isAssignee;

        // Get available statuses for dropdown (based on workflow)
        $availableStatuses = $this->getAvailableStatuses($task['status']);

        // Prepare status and priority labels
        $statusLabels = [
            'new' => 'Nou',
            'in_progress' => 'În progres',
            'blocked' => 'Blocat',
            'review' => 'În revizie',
            'completed' => 'Finalizat',
        ];

        $priorityLabels = [
            'low' => 'Scăzută',
            'medium' => 'Medie',
            'high' => 'Ridicată',
            'critical' => 'Critică',
        ];

        $data = [
            'task' => $task,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
            'canComment' => $canComment,
            'canUploadFiles' => $canUploadFiles,
            'canChangeStatus' => $canChangeStatus,
            'isAssignee' => $isAssignee,
            'availableStatuses' => $availableStatuses,
            'statusLabels' => $statusLabels,
            'priorityLabels' => $priorityLabels,
            'currentUserId' => $currentUserId,
        ];

        return view('tasks/view', $data);
    }

    /**
     * Show create task form
     * GET /tasks/create
     */
    public function create()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check if user can create tasks
        if ($this->session->get('role_level') < 50) { // Manager and above
            return redirect()->to('/tasks')->with('error', 'Nu ai permisiunea să creezi sarcini.');
        }

        // Get allowed subdivisions
        $subdivisions = $this->taskManagementService->getAllowedSubdivisionsForCreate($currentUserId);

        // Get allowed users for assignment
        $users = $this->taskManagementService->getAllowedUsersForAssignment($currentUserId);

        // Get all departments
        $departments = $this->departmentModel->findAll();

        // Prepare priority and status labels
        $priorityLabels = [
            'low' => 'Scăzută',
            'medium' => 'Medie',
            'high' => 'Ridicată',
            'critical' => 'Critică',
        ];

        $data = [
            'subdivisions' => $subdivisions,
            'users' => $users,
            'departments' => $departments,
            'priorityLabels' => $priorityLabels,
            'validation' => $this->validation,
        ];

        return view('tasks/create', $data);
    }

    /**
     * Store new task
     * POST /tasks/store
     */
    public function store()
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check if user can create tasks
        if ($this->session->get('role_level') < 50) {
            return redirect()->to('/tasks')->with('error', 'Nu ai permisiunea să creezi sarcini.');
        }

        // Validate input
        $rules = [
            'subdivision_id' => 'required|integer',
            'title' => 'required|min_length[3]|max_length[200]',
            'description' => 'permit_empty|max_length[5000]',
            'status' => 'permit_empty|in_list[new,in_progress,blocked,review,completed]',
            'priority' => 'required|in_list[low,medium,high,critical]',
            'deadline' => 'permit_empty|valid_date',
            'assignees' => 'permit_empty',
            'departments' => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validation->getErrors());
        }

        // Check if user can create task for this subdivision
        $subdivisionId = $this->request->getPost('subdivision_id');
        if (!$this->taskManagementService->canCreateTask($currentUserId, $subdivisionId)) {
            return redirect()->back()->withInput()->with('error', 'Nu ai permisiunea să creezi sarcini pentru această subdiviziune.');
        }

        // Prepare task data
        $taskData = [
            'subdivision_id' => $subdivisionId,
            'created_by' => $currentUserId,
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description') ?: null,
            'status' => $this->request->getPost('status') ?: 'new',
            'priority' => $this->request->getPost('priority'),
            'deadline' => $this->request->getPost('deadline') ?: null,
        ];

        // Get assignees
        $assignees = $this->request->getPost('assignees');
        $assigneeIds = [];
        if (is_array($assignees)) {
            $assigneeIds = array_filter(array_map('intval', $assignees));
        }

        // Get departments
        $departments = $this->request->getPost('departments');
        $departmentIds = [];
        if (is_array($departments)) {
            $departmentIds = array_filter(array_map('intval', $departments));
        }

        // Create task using TaskService
        $result = $this->taskService->createTask($taskData, $assigneeIds, $departmentIds);

        if ($result['success']) {
            return redirect()->to('/tasks/view/' . $result['task_id'])->with('success', $result['message']);
        } else {
            return redirect()->back()->withInput()->with('error', $result['message']);
        }
    }

    /**
     * Helper method to get available statuses for a task based on workflow
     */
    protected function getAvailableStatuses(string $currentStatus): array
    {
        $allStatuses = [
            'new' => 'Nou',
            'in_progress' => 'În progres',
            'blocked' => 'Blocat',
            'review' => 'În revizie',
            'completed' => 'Finalizat',
        ];

        $available = [];

        // Blocked can be set from any status (except completed)
        if ($currentStatus !== 'completed') {
            $available['blocked'] = $allStatuses['blocked'];
        }

        // Check workflow transitions
        if ($currentStatus === 'new') {
            $available['in_progress'] = $allStatuses['in_progress'];
        } elseif ($currentStatus === 'in_progress') {
            $available['review'] = $allStatuses['review'];
        } elseif ($currentStatus === 'review') {
            $available['completed'] = $allStatuses['completed'];
            $available['in_progress'] = $allStatuses['in_progress'];
        } elseif ($currentStatus === 'blocked') {
            $available['in_progress'] = $allStatuses['in_progress'];
        }

        return $available;
    }

    /**
     * Show edit task form
     * GET /tasks/edit/{id}
     */
    public function edit(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check if user can edit this task
        if (!$this->taskManagementService->canEditTask($currentUserId, $id)) {
            return redirect()->to('/tasks')->with('error', 'Nu ai permisiunea să editezi acesata sarcină.');
        }

        // Get task
        $task = $this->taskModel->find($id);

        if (!$task) {
            return redirect()->to('/tasks')->with('error', 'Sarcina nu există.');
        }

        // Get allowed subdivisions
        $subdivisions = $this->taskManagementService->getAllowedSubdivisionsForCreate($currentUserId);

        // Get allowed users for assignment
        $users = $this->taskManagementService->getAllowedUsersForAssignment($currentUserId);

        // Get all departments
        $departments = $this->departmentModel->findAll();

        // Get current assignees
        $assignees = $this->taskModel->getAssignees($id);
        $currentAssignees = array_map(function ($assignee) {
            return $assignee['user_id'] ?? $assignee['id'];
        }, $assignees);

        // Get current departments
        $taskDepartments = $this->taskModel->getDepartments($id);
        $currentDepartments = array_map(function ($dept) {
            return $dept['department_id'] ?? $dept['id'];
        }, $taskDepartments);

        // Prepare priority labels
        $priorityLabels = [
            'low' => 'Scăzută',
            'medium' => 'Medie',
            'high' => 'Ridicată',
            'critical' => 'Critică',
        ];

        $data = [
            'task' => $task,
            'subdivisions' => $subdivisions,
            'users' => $users,
            'departments' => $departments,
            'currentAssignees' => $currentAssignees,
            'currentDepartments' => $currentDepartments,
            'priorityLabels' => $priorityLabels,
            'validation' => $this->validation,
        ];

        return view('tasks/edit', $data);
    }

    /**
     * Update task
     * POST /tasks/update/{id}
     */
    public function update(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Check if user can edit this task
        if (!$this->taskManagementService->canEditTask($currentUserId, $id)) {
            return redirect()->to('/tasks')->with('error', 'Nu ai permisiunea să editezi acesata sarcină.');
        }

        // Get task
        $task = $this->taskModel->find($id);

        if (!$task) {
            return redirect()->to('/tasks')->with('error', 'Sarcina nu există.');
        }

        // Validate input
        $rules = [
            'subdivision_id' => 'required|integer',
            'title' => 'required|min_length[3]|max_length[200]',
            'description' => 'permit_empty|max_length[5000]',
            'priority' => 'required|in_list[low,medium,high,critical]',
            'deadline' => 'permit_empty|valid_date',
            'assignees' => 'permit_empty',
            'departments' => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validation->getErrors());
        }

        // Check if user can create task for this subdivision
        $subdivisionId = $this->request->getPost('subdivision_id');
        if (!$this->taskManagementService->canCreateTask($currentUserId, $subdivisionId)) {
            return redirect()->back()->withInput()->with('error', 'Nu ai permisiunea să editezi sarcini pentru această subdiviziune.');
        }

        // Prepare task data
        $taskData = [
            'subdivision_id' => $subdivisionId,
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description') ?: null,
            'priority' => $this->request->getPost('priority'),
            'deadline' => $this->request->getPost('deadline') ?: null,
        ];

        // Update task
        $updated = $this->taskModel->update($id, $taskData);

        if (!$updated) {
            return redirect()->back()->withInput()->with('error', 'Eroare la actualizarea sarcinii.');
        }

        // Update assignees
        $assignees = $this->request->getPost('assignees');
        $assigneeIds = [];
        if (is_array($assignees)) {
            $assigneeIds = array_filter(array_map('intval', $assignees));
        }

        // Get current assignees
        $currentAssignees = $this->taskModel->getAssignees($id);
        $currentAssigneeIds = array_map(function ($assignee) {
            return $assignee['user_id'] ?? $assignee['id'];
        }, $currentAssignees);

        // Remove assignees that are no longer assigned
        foreach ($currentAssigneeIds as $currentAssigneeId) {
            if (!in_array($currentAssigneeId, $assigneeIds)) {
                $this->taskModel->unassignUser($id, $currentAssigneeId);
            }
        }

        // Add new assignees
        foreach ($assigneeIds as $assigneeId) {
            if (!in_array($assigneeId, $currentAssigneeIds)) {
                $this->taskModel->assignUser($id, $assigneeId);
            }
        }

        // Update departments
        $departments = $this->request->getPost('departments');
        $departmentIds = [];
        if (is_array($departments)) {
            $departmentIds = array_filter(array_map('intval', $departments));
        }

        // Get current departments
        $currentDepartments = $this->taskModel->getDepartments($id);
        $currentDepartmentIds = array_map(function ($dept) {
            return $dept['department_id'] ?? $dept['id'];
        }, $currentDepartments);

        // Remove departments that are no longer assigned
        foreach ($currentDepartmentIds as $currentDepartmentId) {
            if (!in_array($currentDepartmentId, $departmentIds)) {
                $this->taskModel->removeDepartment($id, $currentDepartmentId);
            }
        }

        // Add new departments
        foreach ($departmentIds as $departmentId) {
            if (!in_array($departmentId, $currentDepartmentIds)) {
                $this->taskModel->addDepartment($id, $departmentId);
            }
        }

        return redirect()->to('/tasks/view/' . $id)->with('success', 'Sarcina a fost actualizată cu succes.');
    }

    /**
     * Delete task
     * POST /tasks/delete/{id}
     */
    public function delete(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login')->with('error', 'Nu ești autentificat.');
        }

        // Check if user can delete this task
        if (!$this->taskManagementService->canDeleteTask($currentUserId, $id)) {
            return redirect()->to('/tasks')->with('error', 'Nu ai permisiunea să ștergi acesata sarcină.');
        }

        // Get task
        $task = $this->taskModel->find($id);

        if (!$task) {
            return redirect()->to('/tasks')->with('error', 'Sarcina nu există.');
        }

        // Delete task (will cascade to assignees, departments, comments, files, logs)
        $deleted = $this->taskModel->delete($id);

        if ($deleted) {
            return redirect()->to('/tasks')->with('success', 'Sarcina a fost ștersă cu succes.');
        } else {
            return redirect()->to('/tasks')->with('error', 'Eroare la ștergerea sarcinii.');
        }
    }

    /**
     * Add comment to task (AJAX)
     * POST /tasks/{id}/comment
     */
    public function addComment(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login')->with('error', 'Nu ești autentificat.');
        }

        // Check if user can view this task
        if (!$this->taskManagementService->canViewTask($currentUserId, $id)) {
            return redirect()->to('/tasks')->with('error', 'Nu ai permisiunea să accesezi acesata sarcină.');
        }

        // Validate input
        $comment = $this->request->getPost('comment');

        if (empty(trim($comment))) {
            return redirect()->to('/tasks')->with('error', 'Comentariul nu poate fi gol.');
        }

        // Add comment using TaskService
        $result = $this->taskService->addComment($id, trim($comment), $currentUserId);

        if ($result['success']) {
            return redirect()->to('/tasks/view/' . $id)->with('success', $result['message']);
        } else {
            return redirect()->to('/tasks/view/' . $id)->with('error', $result['message']);
        }
    }

    /**
     * Upload file to task (AJAX)
     * POST /tasks/{id}/upload-file
     */
    public function uploadFile(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login')->with('error', 'Nu ești autentificat.');
        }

        // Check if user can view this task
        if (!$this->taskManagementService->canViewTask($currentUserId, $id)) {
            return redirect()->to('/tasks')->with('error', 'Nu ai permisiunea să accesezi acesata sarcină.');
        }

        // Get uploaded files
        $files = $this->request->getFiles();

        if (empty($files) || !isset($files['files'])) {
            return redirect()->to('/tasks')->with('error', 'Nu a fost încărcat niciun fișier.');
        }

        // Upload files using FileService
        $result = $this->fileService->uploadTaskFiles($id, $files['files'], $currentUserId);

        // Send notifications if files were uploaded successfully
        if ($result['success'] && !empty($result['files'])) {
            $task = $this->taskModel->find($id);
            // Notification will be sent via TaskService when file is uploaded
            // For now, we'll just log activity
            $uploaderName = $this->session->get('first_name') . ' ' . $this->session->get('last_name');
            $taskTitle = $task['title'] ?? 'Sarcină';
            $taskLink = '/tasks/view/' . $id;

            // Get assignees
            $assignees = $this->taskModel->getAssignees($id);
            $assigneeIds = [];
            foreach ($assignees as $assignee) {
                $assigneeId = $assignee['user_id'] ?? $assignee['id'];
                if ($assigneeId != $currentUserId) {
                    $assigneeIds[] = $assigneeId;
                }
            }

            // Send notifications to assignees
            $notificationService = new \App\Services\NotificationService();
            foreach ($assigneeIds as $assigneeId) {
                $notificationService->send(
                    $assigneeId,
                    'info',
                    'Fișier nou încărcat',
                    $uploaderName . ' a încărcat un fișier la sarcina "' . $taskTitle . '"',
                    $taskLink
                );
            }
        }

        return redirect()->to('/tasks/view/' . $id)->with('success', $result['message']);
    }

    /**
     * Download file from task
     * GET /tasks/files/download/{fileId}
     */
    public function downloadFile(int $fileId)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login');
        }

        // Get file info and verify access
        $result = $this->fileService->downloadFile($fileId, $currentUserId);

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        // Return file for download
        return $this->response->download($result['filepath'], null)->setFileName($result['filename']);
    }

    /**
     * Update task status (AJAX)
     * POST /tasks/{id}/update-status
     */
    public function updateStatus(int $id)
    {
        $currentUserId = $this->session->get('user_id');

        if (!$currentUserId) {
            return redirect()->to('/auth/login')->with('error', 'Nu ești autentificat.');
        }

        // Get task
        $task = $this->taskModel->find($id);

        if (!$task) {
            return redirect()->to('/tasks')->with('error', 'Sarcina nu există.');
        }

        // Check permissions: can edit or is assignee
        $canEdit = $this->taskManagementService->canEditTask($currentUserId, $id);
        $isAssignee = false;

        if (!$canEdit) {
            // Check if user is assignee
            // getAssignees() returns users.* data, so use 'id' field
            $assignees = $this->taskModel->getAssignees($id);
            foreach ($assignees as $assignee) {
                if (isset($assignee['id']) && $assignee['id'] == $currentUserId) {
                    $isAssignee = true;
                    break;
                }
            }
        }

        if (!$canEdit && !$isAssignee) {
            return redirect()->to('/tasks')->with('error', 'Nu ai permisiunea să modifici statusul acesatei sarcini.');
        }

        // Get new status
        $newStatus = $this->request->getPost('status');

        if (empty($newStatus)) {
            return redirect()->to('/tasks')->with('error', 'Status-ul nu a fost specificat.');
        }

        // Update status using TaskService (includes workflow validation)
        $result = $this->taskService->updateTaskStatus($id, $newStatus, $currentUserId);

        return redirect()->to('/tasks/view/' . $id)->with('success', $result['message']);
    }
}
