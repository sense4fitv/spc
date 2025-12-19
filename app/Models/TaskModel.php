<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskModel extends Model
{
    protected $table            = 'tasks';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'subdivision_id',
        'created_by',
        'title',
        'description',
        'status',
        'priority',
        'deadline',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = null;

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Get task's subdivision
     */
    public function getSubdivision(int $taskId)
    {
        $task = $this->find($taskId);
        if (!$task) {
            return null;
        }

        $subdivisionModel = new SubdivisionModel();
        return $subdivisionModel->find($task['subdivision_id']);
    }

    /**
     * Get task creator
     */
    public function getCreator(int $taskId)
    {
        $task = $this->find($taskId);
        if (!$task) {
            return null;
        }

        $userModel = new UserModel();
        return $userModel->find($task['created_by']);
    }

    /**
     * Get task assignees
     */
    public function getAssignees(int $taskId)
    {
        $db = \Config\Database::connect();
        return $db->table('task_assignees')
            ->select('users.*')
            ->join('users', 'users.id = task_assignees.user_id')
            ->where('task_assignees.task_id', $taskId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get task departments
     */
    public function getDepartments(int $taskId)
    {
        $db = \Config\Database::connect();
        return $db->table('task_departments')
            ->join('departments', 'departments.id = task_departments.department_id')
            ->where('task_departments.task_id', $taskId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get task comments
     */
    public function getComments(int $taskId)
    {
        $commentModel = new TaskCommentModel();
        return $commentModel->where('task_id', $taskId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    /**
     * Get task files
     */
    public function getFiles(int $taskId)
    {
        $fileModel = new TaskFileModel();
        return $fileModel->where('task_id', $taskId)->findAll();
    }

    /**
     * Get task activity logs
     */
    public function getActivityLogs(int $taskId)
    {
        $logModel = new TaskActivityLogModel();
        return $logModel->where('task_id', $taskId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Find tasks by status
     */
    public function findByStatus(string $status)
    {
        return $this->where('status', $status)->findAll();
    }

    /**
     * Find tasks by priority
     */
    public function findByPriority(string $priority)
    {
        return $this->where('priority', $priority)->findAll();
    }

    /**
     * Find overdue tasks
     */
    public function findOverdue()
    {
        return $this->where('deadline <', date('Y-m-d H:i:s'))
            ->where('status !=', 'completed')
            ->findAll();
    }

    /**
     * Assign user to task
     */
    public function assignUser(int $taskId, int $userId): bool
    {
        $db = \Config\Database::connect();

        // Check if already assigned
        $exists = $db->table('task_assignees')
            ->where('task_id', $taskId)
            ->where('user_id', $userId)
            ->countAllResults();

        if ($exists > 0) {
            return false; // Already assigned
        }

        return $db->table('task_assignees')->insert([
            'task_id' => $taskId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Remove user from task
     */
    public function unassignUser(int $taskId, int $userId): bool
    {
        $db = \Config\Database::connect();
        return $db->table('task_assignees')
            ->where('task_id', $taskId)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Add department to task
     */
    public function addDepartment(int $taskId, int $departmentId): bool
    {
        $db = \Config\Database::connect();

        // Check if already added
        $exists = $db->table('task_departments')
            ->where('task_id', $taskId)
            ->where('department_id', $departmentId)
            ->countAllResults();

        if ($exists > 0) {
            return false;
        }

        return $db->table('task_departments')->insert([
            'task_id' => $taskId,
            'department_id' => $departmentId,
        ]);
    }

    /**
     * Remove department from task
     */
    public function removeDepartment(int $taskId, int $departmentId): bool
    {
        $db = \Config\Database::connect();
        return $db->table('task_departments')
            ->where('task_id', $taskId)
            ->where('department_id', $departmentId)
            ->delete();
    }

    /**
     * Get all tasks with details (subdivision, contract, region, assignees, creator)
     * 
     * @return array Tasks with joined data
     */
    public function getAllTasksWithDetails(): array
    {
        $db = \Config\Database::connect();

        $tasks = $db->table('tasks t')
            ->select('t.*, 
                sd.id as subdivision_id_full, sd.name as subdivision_name, sd.code as subdivision_code,
                c.id as contract_id, c.name as contract_name, c.contract_number,
                r.id as region_id, r.name as region_name,
                creator.first_name as creator_first_name, creator.last_name as creator_last_name, creator.email as creator_email')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'left')
            ->join('contracts c', 'c.id = sd.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->join('users creator', 'creator.id = t.created_by', 'left')
            ->orderBy('t.created_at', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($tasks as &$task) {
            // Build creator name
            if ($task['creator_first_name'] || $task['creator_last_name']) {
                $task['creator_name'] = trim(($task['creator_first_name'] ?? '') . ' ' . ($task['creator_last_name'] ?? ''));
            } else {
                $task['creator_name'] = $task['creator_email'] ?? 'Necunoscut';
            }

            // Get assignees
            $assignees = $this->getAssignees($task['id']);
            $task['assignees'] = $assignees;
            $task['assignees_names'] = array_map(function ($assignee) {
                $name = trim(($assignee['first_name'] ?? '') . ' ' . ($assignee['last_name'] ?? ''));
                return $name ?: ($assignee['email'] ?? 'Necunoscut');
            }, $assignees);

            // Get departments
            $departments = $this->getDepartments($task['id']);
            $task['departments'] = $departments;
        }

        return $tasks;
    }

    /**
     * Get tasks for a region with details
     * 
     * @param int $regionId Region ID
     * @return array Tasks with details
     */
    public function getTasksForRegionWithDetails(int $regionId): array
    {
        $db = \Config\Database::connect();

        $tasks = $db->table('tasks t')
            ->select('t.*, 
                sd.id as subdivision_id_full, sd.name as subdivision_name, sd.code as subdivision_code,
                c.id as contract_id, c.name as contract_name, c.contract_number,
                r.id as region_id, r.name as region_name,
                creator.first_name as creator_first_name, creator.last_name as creator_last_name, creator.email as creator_email')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'left')
            ->join('contracts c', 'c.id = sd.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->join('users creator', 'creator.id = t.created_by', 'left')
            ->where('r.id', $regionId)
            ->orderBy('t.created_at', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($tasks as &$task) {
            // Build creator name
            if ($task['creator_first_name'] || $task['creator_last_name']) {
                $task['creator_name'] = trim(($task['creator_first_name'] ?? '') . ' ' . ($task['creator_last_name'] ?? ''));
            } else {
                $task['creator_name'] = $task['creator_email'] ?? 'Necunoscut';
            }

            // Get assignees
            $assignees = $this->getAssignees($task['id']);
            $task['assignees'] = $assignees;
            $task['assignees_names'] = array_map(function ($assignee) {
                $name = trim(($assignee['first_name'] ?? '') . ' ' . ($assignee['last_name'] ?? ''));
                return $name ?: ($assignee['email'] ?? 'Necunoscut');
            }, $assignees);

            // Get departments
            $departments = $this->getDepartments($task['id']);
            $task['departments'] = $departments;
        }

        return $tasks;
    }

    /**
     * Get tasks for a manager (contract manager) with details
     * 
     * @param int $managerId Manager user ID
     * @return array Tasks with details
     */
    public function getTasksForManagerWithDetails(int $managerId): array
    {
        $db = \Config\Database::connect();

        $tasks = $db->table('tasks t')
            ->select('t.*, 
                sd.id as subdivision_id_full, sd.name as subdivision_name, sd.code as subdivision_code,
                c.id as contract_id, c.name as contract_name, c.contract_number, c.manager_id,
                r.id as region_id, r.name as region_name,
                creator.first_name as creator_first_name, creator.last_name as creator_last_name, creator.email as creator_email')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'left')
            ->join('contracts c', 'c.id = sd.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->join('users creator', 'creator.id = t.created_by', 'left')
            ->where('c.manager_id', $managerId)
            ->orderBy('t.created_at', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($tasks as &$task) {
            // Build creator name
            if ($task['creator_first_name'] || $task['creator_last_name']) {
                $task['creator_name'] = trim(($task['creator_first_name'] ?? '') . ' ' . ($task['creator_last_name'] ?? ''));
            } else {
                $task['creator_name'] = $task['creator_email'] ?? 'Necunoscut';
            }

            // Get assignees
            $assignees = $this->getAssignees($task['id']);
            $task['assignees'] = $assignees;
            $task['assignees_names'] = array_map(function ($assignee) {
                $name = trim(($assignee['first_name'] ?? '') . ' ' . ($assignee['last_name'] ?? ''));
                return $name ?: ($assignee['email'] ?? 'Necunoscut');
            }, $assignees);

            // Get departments
            $departments = $this->getDepartments($task['id']);
            $task['departments'] = $departments;
        }

        return $tasks;
    }

    /**
     * Get tasks for an assignee (executant) with details
     * 
     * @param int $userId User ID (assignee)
     * @return array Tasks with details
     */
    public function getTasksForAssigneeWithDetails(int $userId): array
    {
        $db = \Config\Database::connect();

        $tasks = $db->table('tasks t')
            ->select('t.*, 
                sd.id as subdivision_id_full, sd.name as subdivision_name, sd.code as subdivision_code,
                c.id as contract_id, c.name as contract_name, c.contract_number,
                r.id as region_id, r.name as region_name,
                creator.first_name as creator_first_name, creator.last_name as creator_last_name, creator.email as creator_email')
            ->join('task_assignees ta', 'ta.task_id = t.id', 'inner')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'left')
            ->join('contracts c', 'c.id = sd.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->join('users creator', 'creator.id = t.created_by', 'left')
            ->where('ta.user_id', $userId)
            ->orderBy('t.created_at', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($tasks as &$task) {
            // Build creator name
            if ($task['creator_first_name'] || $task['creator_last_name']) {
                $task['creator_name'] = trim(($task['creator_first_name'] ?? '') . ' ' . ($task['creator_last_name'] ?? ''));
            } else {
                $task['creator_name'] = $task['creator_email'] ?? 'Necunoscut';
            }

            // Get assignees
            $assignees = $this->getAssignees($task['id']);
            $task['assignees'] = $assignees;
            $task['assignees_names'] = array_map(function ($assignee) {
                $name = trim(($assignee['first_name'] ?? '') . ' ' . ($assignee['last_name'] ?? ''));
                return $name ?: ($assignee['email'] ?? 'Necunoscut');
            }, $assignees);

            // Get departments
            $departments = $this->getDepartments($task['id']);
            $task['departments'] = $departments;
        }

        return $tasks;
    }

    /**
     * Get tasks for a department head (from his department and region) with details
     * 
     * @param int $departmentId Department ID
     * @param int $regionId Region ID
     * @return array Tasks with details
     */
    public function getTasksForDepartmentHeadWithDetails(int $departmentId, int $regionId): array
    {
        $db = \Config\Database::connect();

        $tasks = $db->table('tasks t')
            ->select('t.*, 
                sd.id as subdivision_id_full, sd.name as subdivision_name, sd.code as subdivision_code,
                c.id as contract_id, c.name as contract_name, c.contract_number,
                r.id as region_id, r.name as region_name,
                creator.first_name as creator_first_name, creator.last_name as creator_last_name, creator.email as creator_email')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'left')
            ->join('contracts c', 'c.id = sd.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->join('task_departments td', 'td.task_id = t.id', 'inner')
            ->join('users creator', 'creator.id = t.created_by', 'left')
            ->where('r.id', $regionId)
            ->where('td.department_id', $departmentId)
            ->groupBy('t.id')
            ->orderBy('t.created_at', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($tasks as &$task) {
            // Build creator name
            if ($task['creator_first_name'] || $task['creator_last_name']) {
                $task['creator_name'] = trim(($task['creator_first_name'] ?? '') . ' ' . ($task['creator_last_name'] ?? ''));
            } else {
                $task['creator_name'] = $task['creator_email'] ?? 'Necunoscut';
            }

            // Get assignees
            $assignees = $this->getAssignees($task['id']);
            $task['assignees'] = $assignees;
            $task['assignees_names'] = array_map(function ($assignee) {
                $name = trim(($assignee['first_name'] ?? '') . ' ' . ($assignee['last_name'] ?? ''));
                return $name ?: ($assignee['email'] ?? 'Necunoscut');
            }, $assignees);

            // Get departments
            $departments = $this->getDepartments($task['id']);
            $task['departments'] = $departments;
        }

        return $tasks;
    }

    /**
     * Get tasks assigned to or created by a user (for "My Tasks" view)
     * 
     * @param int $userId User ID
     * @return array Tasks with details
     */
    public function getMyTasksWithDetails(int $userId): array
    {
        $db = \Config\Database::connect();

        // Get tasks created by user
        $tasksByCreator = $db->table('tasks t')
            ->select('t.*, 
                sd.id as subdivision_id_full, sd.name as subdivision_name, sd.code as subdivision_code,
                c.id as contract_id, c.name as contract_name, c.contract_number,
                r.id as region_id, r.name as region_name,
                creator.first_name as creator_first_name, creator.last_name as creator_last_name, creator.email as creator_email')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'left')
            ->join('contracts c', 'c.id = sd.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->join('users creator', 'creator.id = t.created_by', 'left')
            ->where('t.created_by', $userId)
            ->get()
            ->getResultArray();

        $tasksByAssignee = $this->getTasksForAssigneeWithDetails($userId);

        // Merge and remove duplicates by task ID
        $allTasks = [];
        $taskIds = [];

        foreach ($tasksByCreator as $task) {
            if (!in_array($task['id'], $taskIds)) {
                $allTasks[] = $task;
                $taskIds[] = $task['id'];
            }
        }

        foreach ($tasksByAssignee as $task) {
            if (!in_array($task['id'], $taskIds)) {
                $allTasks[] = $task;
                $taskIds[] = $task['id'];
            }
        }

        // Sort by created_at DESC
        usort($allTasks, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        foreach ($allTasks as &$task) {
            // Build creator name
            if ($task['creator_first_name'] || $task['creator_last_name']) {
                $task['creator_name'] = trim(($task['creator_first_name'] ?? '') . ' ' . ($task['creator_last_name'] ?? ''));
            } else {
                $task['creator_name'] = $task['creator_email'] ?? 'Necunoscut';
            }

            // Get assignees
            $assignees = $this->getAssignees($task['id']);
            $task['assignees'] = $assignees;
            $task['assignees_names'] = array_map(function ($assignee) {
                $name = trim(($assignee['first_name'] ?? '') . ' ' . ($assignee['last_name'] ?? ''));
                return $name ?: ($assignee['email'] ?? 'Necunoscut');
            }, $assignees);

            // Get departments
            $departments = $this->getDepartments($task['id']);
            $task['departments'] = $departments;
        }

        return $allTasks;
    }

    /**
     * Get task with full details (for task details view)
     * 
     * @param int $taskId Task ID
     * @return array|null Task with all related data
     */
    public function getTaskWithFullDetails(int $taskId): ?array
    {
        $task = $this->find($taskId);

        if (!$task) {
            return null;
        }

        // Get subdivision
        $subdivision = $this->getSubdivision($taskId);
        $task['subdivision'] = $subdivision;

        // Get contract
        if ($subdivision) {
            $contractModel = new ContractModel();
            $contract = $contractModel->find($subdivision['contract_id']);
            $task['contract'] = $contract;

            // Get contract manager (if exists)
            if ($contract && !empty($contract['manager_id'])) {
                $userModel = new UserModel();
                $contractManager = $userModel->find($contract['manager_id']);
                if ($contractManager) {
                    $task['contract_manager'] = [
                        'id' => $contractManager['id'],
                        'first_name' => $contractManager['first_name'] ?? '',
                        'last_name' => $contractManager['last_name'] ?? '',
                        'email' => $contractManager['email'] ?? '',
                        'phone' => $contractManager['phone'] ?? null,
                        'full_name' => trim(($contractManager['first_name'] ?? '') . ' ' . ($contractManager['last_name'] ?? '')) ?: $contractManager['email'],
                    ];
                }
            }

            // Get region
            if ($contract) {
                $regionModel = new RegionModel();
                $region = $regionModel->find($contract['region_id']);
                $task['region'] = $region;
            }
        }

        // Get creator
        $creator = $this->getCreator($taskId);
        $task['creator'] = $creator;
        if ($creator) {
            $task['creator_name'] = trim(($creator['first_name'] ?? '') . ' ' . ($creator['last_name'] ?? '')) ?: $creator['email'];
        }

        // Get assignees
        $assignees = $this->getAssignees($taskId);
        $task['assignees'] = $assignees;
        $task['assignees_names'] = array_map(function ($assignee) {
            $name = trim(($assignee['first_name'] ?? '') . ' ' . ($assignee['last_name'] ?? ''));
            return $name ?: ($assignee['email'] ?? 'Necunoscut');
        }, $assignees);

        // Get departments
        $departments = $this->getDepartments($taskId);
        $task['departments'] = $departments;

        // Get comments (with authors)
        $commentModel = new TaskCommentModel();
        $comments = $commentModel->findWithAuthors($taskId);
        $task['comments'] = $comments;

        // Get files (with uploader)
        $fileModel = new TaskFileModel();
        $files = $fileModel->findWithUploader($taskId);
        $task['files'] = $files;

        // Get activity logs (with user)
        $logModel = new TaskActivityLogModel();
        $logs = $logModel->findWithUser($taskId);
        $task['activity_logs'] = $logs;

        return $task;
    }
}
