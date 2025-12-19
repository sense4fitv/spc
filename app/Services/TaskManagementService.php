<?php

namespace App\Services;

use App\Models\TaskModel;
use App\Models\UserModel;
use App\Models\ContractModel;
use App\Models\SubdivisionModel;
use App\Models\RegionModel;

/**
 * TaskManagementService
 * 
 * Service responsible for task management business logic,
 * including permission checks and data filtering based on user roles.
 */
class TaskManagementService
{
    protected TaskModel $taskModel;
    protected UserModel $userModel;
    protected ContractModel $contractModel;
    protected SubdivisionModel $subdivisionModel;
    protected RegionModel $regionModel;
    protected DepartmentHeadService $departmentHeadService;

    /**
     * Role level mappings
     */
    protected array $roleLevels = [
        'admin'          => 100,
        'director'       => 80,
        'department_head' => 70,
        'manager'        => 50,
        'executant'      => 20,
        'auditor'        => 10,
    ];

    public function __construct()
    {
        $this->taskModel = new TaskModel();
        $this->userModel = new UserModel();
        $this->contractModel = new ContractModel();
        $this->subdivisionModel = new SubdivisionModel();
        $this->regionModel = new RegionModel();
        $this->departmentHeadService = new DepartmentHeadService();
    }

    /**
     * Get viewable tasks based on current user's role
     * 
     * @param int $currentUserId Current user ID
     * @return array Array of tasks with additional data
     */
    public function getViewableTasks(int $currentUserId): array
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return [];
        }

        $roleLevel = $currentUser['role_level'];
        $isDepartmentHead = $this->departmentHeadService->isDepartmentHead($currentUserId);

        // Admin sees all tasks
        if ($roleLevel >= $this->roleLevels['admin']) {
            return $this->taskModel->getAllTasksWithDetails();
        }

        // Check if user is department head - combine with other role permissions (UNION)
        $departmentHeadTasks = [];
        if ($isDepartmentHead) {
            $departmentHeadTasks = $this->departmentHeadService->getViewableTasks($currentUserId);
        }

        // Director sees tasks from contracts in his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $regionId = $currentUser['region_id'];
            if (!$regionId) {
                // Director must have region_id - if missing, return only department head tasks
                return $this->mergeTasks($departmentHeadTasks, []);
            }
            $directorTasks = $this->taskModel->getTasksForRegionWithDetails($regionId);
            return $this->mergeTasks($directorTasks, $departmentHeadTasks);
        }

        // Manager sees only tasks from contracts assigned to him
        if ($roleLevel >= $this->roleLevels['manager']) {
            $managerTasks = $this->taskModel->getTasksForManagerWithDetails($currentUserId);
            return $this->mergeTasks($managerTasks, $departmentHeadTasks);
        }

        // Executant sees only tasks assigned to him
        if ($roleLevel >= $this->roleLevels['executant']) {
            return $this->taskModel->getTasksForAssigneeWithDetails($currentUserId);
        }

        // If user is department head but has no other role level permissions, return department head tasks
        if ($isDepartmentHead) {
            return $departmentHeadTasks;
        }

        // Auditor - no access to tasks
        return [];
    }

    /**
     * Merge two task arrays and remove duplicates
     * 
     * @param array $tasks1 First array of tasks
     * @param array $tasks2 Second array of tasks
     * @return array Merged and deduplicated tasks
     */
    protected function mergeTasks(array $tasks1, array $tasks2): array
    {
        $allTasks = [];
        $taskIds = [];

        foreach ($tasks1 as $task) {
            if (!in_array($task['id'], $taskIds)) {
                $allTasks[] = $task;
                $taskIds[] = $task['id'];
            }
        }

        foreach ($tasks2 as $task) {
            if (!in_array($task['id'], $taskIds)) {
                $allTasks[] = $task;
                $taskIds[] = $task['id'];
            }
        }

        // Sort by created_at DESC
        usort($allTasks, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $allTasks;
    }

    /**
     * Get tasks assigned to or created by a user (for "My Tasks" view)
     * 
     * @param int $currentUserId Current user ID
     * @return array Array of tasks with additional data
     */
    public function getMyTasks(int $currentUserId): array
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return [];
        }

        // Get tasks where user is assignee OR creator
        $tasks = $this->taskModel->getMyTasksWithDetails($currentUserId);

        return $tasks;
    }

    /**
     * Check if current user can create a task for a subdivision
     * 
     * @param int $currentUserId Current user ID
     * @param int $subdivisionId Subdivision ID
     * @return bool
     */
    public function canCreateTask(int $currentUserId, int $subdivisionId): bool
    {
        $currentUser = $this->userModel->find($currentUserId);
        $subdivision = $this->subdivisionModel->find($subdivisionId);

        if (!$currentUser || !$subdivision) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];
        $isDepartmentHead = $this->departmentHeadService->isDepartmentHead($currentUserId);

        // Executant cannot create tasks (unless department head)
        if ($roleLevel < $this->roleLevels['manager'] && !$isDepartmentHead) {
            return false;
        }

        // Get the contract for this subdivision
        $contract = $this->contractModel->find($subdivision['contract_id']);
        if (!$contract) {
            return false;
        }

        // Admin can create anywhere
        if ($roleLevel >= $this->roleLevels['admin']) {
            return true;
        }

        // Check if department head can create for this subdivision (must be in his region)
        if ($isDepartmentHead) {
            $assignments = $this->departmentHeadService->getDepartmentsForUser($currentUserId);
            foreach ($assignments as $assignment) {
                if ($contract['region_id'] == $assignment['region_id']) {
                    return true; // Can create in any subdivision from his region
                }
            }
        }

        // Director can create only in contracts from his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $directorRegionId = $currentUser['region_id'];
            if (!$directorRegionId) {
                // Director must have region_id - cannot create without it
                return false;
            }
            return $contract['region_id'] == $directorRegionId;
        }

        // Manager can create only in contracts assigned to him
        if ($roleLevel >= $this->roleLevels['manager']) {
            return $contract['manager_id'] == $currentUserId;
        }

        return false;
    }

    /**
     * Check if current user can edit a task
     * 
     * @param int $currentUserId Current user ID
     * @param int $taskId Task ID to edit
     * @return bool
     */
    public function canEditTask(int $currentUserId, int $taskId): bool
    {
        $currentUser = $this->userModel->find($currentUserId);
        $task = $this->taskModel->find($taskId);

        if (!$currentUser || !$task) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];
        $isDepartmentHead = $this->departmentHeadService->isDepartmentHead($currentUserId);

        // Executant cannot edit tasks (only view)
        if ($roleLevel < $this->roleLevels['manager'] && !$isDepartmentHead) {
            return false;
        }

        // Check if department head can edit (check first for performance)
        if ($isDepartmentHead && $this->departmentHeadService->canEditTask($currentUserId, $taskId)) {
            return true;
        }

        // Get the subdivision and contract for this task
        $subdivision = $this->subdivisionModel->find($task['subdivision_id']);
        if (!$subdivision) {
            return false;
        }

        $contract = $this->contractModel->find($subdivision['contract_id']);
        if (!$contract) {
            return false;
        }

        // Admin can edit any task
        if ($roleLevel >= $this->roleLevels['admin']) {
            return true;
        }

        // Director can edit only tasks from contracts in his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $directorRegionId = $currentUser['region_id'];
            if (!$directorRegionId) {
                // Director must have region_id - cannot edit without it
                return false;
            }
            return $contract['region_id'] == $directorRegionId;
        }

        // Manager can edit only tasks from contracts assigned to him
        if ($roleLevel >= $this->roleLevels['manager']) {
            return $contract['manager_id'] == $currentUserId;
        }

        return false;
    }

    /**
     * Check if current user can delete a task
     * 
     * @param int $currentUserId Current user ID
     * @param int $taskId Task ID to delete
     * @return bool
     */
    public function canDeleteTask(int $currentUserId, int $taskId): bool
    {
        // Same logic as edit
        return $this->canEditTask($currentUserId, $taskId);
    }

    /**
     * Check if current user can view a task
     * 
     * @param int $currentUserId Current user ID
     * @param int $taskId Task ID to view
     * @return bool
     */
    public function canViewTask(int $currentUserId, int $taskId): bool
    {
        $currentUser = $this->userModel->find($currentUserId);
        $task = $this->taskModel->find($taskId);

        if (!$currentUser || !$task) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];
        $isDepartmentHead = $this->departmentHeadService->isDepartmentHead($currentUserId);

        // Admin can view any task
        if ($roleLevel >= $this->roleLevels['admin']) {
            return true;
        }

        // Check if department head can view (check first for performance)
        if ($isDepartmentHead && $this->departmentHeadService->canViewTask($currentUserId, $taskId)) {
            return true;
        }

        // Executant can view only tasks assigned to him
        if ($roleLevel >= $this->roleLevels['executant'] && $roleLevel < $this->roleLevels['manager']) {
            // Check if user is assignee
            // getAssignees() returns users.* data, so use 'id' field
            $assignees = $this->taskModel->getAssignees($taskId);
            foreach ($assignees as $assignee) {
                if (isset($assignee['id']) && $assignee['id'] == $currentUserId) {
                    return true;
                }
            }
            // Or if user is creator
            return $task['created_by'] == $currentUserId;
        }

        // For managers and directors, check based on contract permissions
        $subdivision = $this->subdivisionModel->find($task['subdivision_id']);
        if (!$subdivision) {
            return false;
        }

        $contract = $this->contractModel->find($subdivision['contract_id']);
        if (!$contract) {
            return false;
        }

        // Director can view tasks from contracts in his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $directorRegionId = $currentUser['region_id'];
            if (!$directorRegionId) {
                // Director must have region_id - cannot view without it
                return false;
            }
            return $contract['region_id'] == $directorRegionId;
        }

        // Manager can view tasks from contracts assigned to him
        if ($roleLevel >= $this->roleLevels['manager']) {
            return $contract['manager_id'] == $currentUserId;
        }

        return false;
    }

    /**
     * Get allowed subdivisions for task creation
     * 
     * @param int $currentUserId Current user ID
     * @return array Array of subdivisions [id => name (with contract info)]
     */
    public function getAllowedSubdivisionsForCreate(int $currentUserId): array
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return [];
        }

        $roleLevel = $currentUser['role_level'];
        $isDepartmentHead = $this->departmentHeadService->isDepartmentHead($currentUserId);

        // Executant cannot create tasks (unless department head)
        if ($roleLevel < $this->roleLevels['manager'] && !$isDepartmentHead) {
            return [];
        }

        // Admin sees all subdivisions
        if ($roleLevel >= $this->roleLevels['admin']) {
            $subdivisions = $this->subdivisionModel->findAll();
            $result = [];
            foreach ($subdivisions as $subdivision) {
                $contract = $this->contractModel->find($subdivision['contract_id']);
                $contractName = $contract ? $contract['name'] : 'Necunoscut';
                $result[$subdivision['id']] = $subdivision['name'] . ' (' . $contractName . ')';
            }
            return $result;
        }

        // Department head sees subdivisions from contracts in his regions
        $departmentHeadSubdivisions = [];
        if ($isDepartmentHead) {
            $assignments = $this->departmentHeadService->getDepartmentsForUser($currentUserId);
            $regionIds = array_unique(array_column($assignments, 'region_id'));

            foreach ($regionIds as $regionId) {
                $subdivisions = $this->subdivisionModel->getSubdivisionsForRegionWithDetails($regionId);
                foreach ($subdivisions as $subdivision) {
                    $departmentHeadSubdivisions[$subdivision['id']] = $subdivision['name'] . ' (' . $subdivision['contract_name'] . ')';
                }
            }
        }

        // Director sees subdivisions from contracts in his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $regionId = $currentUser['region_id'];
            if (!$regionId) {
                // Director must have region_id - return only department head subdivisions if applicable
                return $departmentHeadSubdivisions;
            }
            $subdivisions = $this->subdivisionModel->getSubdivisionsForRegionWithDetails($regionId);
            $result = [];
            foreach ($subdivisions as $subdivision) {
                $result[$subdivision['id']] = $subdivision['name'] . ' (' . $subdivision['contract_name'] . ')';
            }
            // Merge with department head subdivisions
            return array_merge($result, $departmentHeadSubdivisions);
        }

        // Manager sees subdivisions from contracts assigned to him
        if ($roleLevel >= $this->roleLevels['manager']) {
            $subdivisions = $this->subdivisionModel->getSubdivisionsForManagerWithDetails($currentUserId);
            $result = [];
            foreach ($subdivisions as $subdivision) {
                $result[$subdivision['id']] = $subdivision['name'] . ' (' . $subdivision['contract_name'] . ')';
            }
            // Merge with department head subdivisions
            return array_merge($result, $departmentHeadSubdivisions);
        }

        // If user is department head but has no other role level permissions, return department head subdivisions
        if ($isDepartmentHead) {
            return $departmentHeadSubdivisions;
        }

        return [];
    }

    /**
     * Get all users that can be assigned to tasks
     * Based on current user's permissions, returns appropriate list
     * 
     * @param int $currentUserId Current user ID
     * @return array Array of users [id => full_name]
     */
    public function getAllowedUsersForAssignment(int $currentUserId): array
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return [];
        }

        $roleLevel = $currentUser['role_level'];
        $isDepartmentHead = $this->departmentHeadService->isDepartmentHead($currentUserId);

        // Admin can assign to anyone
        if ($roleLevel >= $this->roleLevels['admin']) {
            $users = $this->userModel->where('active', 1)->findAll();
            $result = [];
            foreach ($users as $user) {
                $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                $result[$user['id']] = $fullName ?: $user['email'];
            }
            return $result;
        }

        // Get department head's executants
        $departmentHeadUsers = [];
        if ($isDepartmentHead) {
            $executants = $this->departmentHeadService->getViewableExecutants($currentUserId);
            foreach ($executants as $user) {
                $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                $departmentHeadUsers[$user['id']] = $fullName ?: $user['email'];
            }
        }

        // Director can assign to users in his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $regionId = $currentUser['region_id'];
            if (!$regionId) {
                // Director must have region_id - return only department head users if applicable
                return $departmentHeadUsers;
            }
            $users = $this->userModel->getUsersForDirector($currentUserId, $regionId);
            $result = [];
            foreach ($users as $user) {
                $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                $result[$user['id']] = $fullName ?: $user['email'];
            }
            // Merge with department head users
            return array_merge($result, $departmentHeadUsers);
        }

        // Manager can assign to users he manages
        if ($roleLevel >= $this->roleLevels['manager']) {
            $users = $this->userModel->getUsersForContractManager($currentUserId);
            $result = [];
            foreach ($users as $user) {
                $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                $result[$user['id']] = $fullName ?: $user['email'];
            }
            // Merge with department head users
            return array_merge($result, $departmentHeadUsers);
        }

        // If user is department head but has no other role level permissions, return department head users
        if ($isDepartmentHead) {
            return $departmentHeadUsers;
        }

        return [];
    }
}
