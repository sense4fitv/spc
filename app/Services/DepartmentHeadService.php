<?php

namespace App\Services;

use App\Models\DepartmentHeadModel;
use App\Models\UserModel;
use App\Models\TaskModel;
use App\Models\DepartmentModel;
use App\Models\RegionModel;

/**
 * DepartmentHeadService
 * 
 * Service responsible for department head business logic,
 * including permission checks and data filtering for department heads.
 */
class DepartmentHeadService
{
    protected DepartmentHeadModel $departmentHeadModel;
    protected UserModel $userModel;
    protected TaskModel $taskModel;
    protected DepartmentModel $departmentModel;
    protected RegionModel $regionModel;

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
        $this->departmentHeadModel = new DepartmentHeadModel();
        $this->userModel = new UserModel();
        $this->taskModel = new TaskModel();
        $this->departmentModel = new DepartmentModel();
        $this->regionModel = new RegionModel();
    }

    /**
     * Check if user is a department head
     * 
     * @param int $userId User ID
     * @return bool
     */
    public function isDepartmentHead(int $userId): bool
    {
        return $this->departmentHeadModel->isDepartmentHead($userId);
    }

    /**
     * Get department head assignments for a user
     * 
     * @param int $userId User ID
     * @return array Array of department head assignments
     */
    public function getDepartmentsForUser(int $userId): array
    {
        return $this->departmentHeadModel->getDepartmentsForUser($userId);
    }

    /**
     * Check if user is department head for specific department and region
     * 
     * @param int $userId User ID
     * @param int $departmentId Department ID
     * @param int $regionId Region ID
     * @return bool
     */
    public function isDepartmentHeadFor(int $userId, int $departmentId, int $regionId): bool
    {
        return $this->departmentHeadModel->isDepartmentHeadFor($userId, $departmentId, $regionId);
    }

    /**
     * Check if department head can view a task
     * Task must be:
     * - From the department head's region
     * - With the department head's department (via task_departments)
     * 
     * @param int $departmentHeadId Department head user ID
     * @param int $taskId Task ID
     * @return bool
     */
    public function canViewTask(int $departmentHeadId, int $taskId): bool
    {
        // Get department head assignments
        $assignments = $this->getDepartmentsForUser($departmentHeadId);
        
        if (empty($assignments)) {
            return false;
        }

        // Get task details
        $task = $this->taskModel->getTaskWithFullDetails($taskId);
        if (!$task) {
            return false;
        }

        // Check if task is from one of the department head's regions
        $taskRegionId = $task['region']['id'] ?? null;
        if (!$taskRegionId) {
            return false;
        }

        // Get task departments
        $taskDepartments = $this->taskModel->getDepartments($taskId);
        $taskDepartmentIds = array_column($taskDepartments, 'id');

        // Check if task is in one of the department head's departments and regions
        foreach ($assignments as $assignment) {
            if ($assignment['region_id'] == $taskRegionId && 
                in_array($assignment['department_id'], $taskDepartmentIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get viewable tasks for a department head
     * Returns tasks from all departments/regions where user is department head
     * 
     * @param int $departmentHeadId Department head user ID
     * @return array Array of tasks with details
     */
    public function getViewableTasks(int $departmentHeadId): array
    {
        // Get department head assignments
        $assignments = $this->getDepartmentsForUser($departmentHeadId);
        
        if (empty($assignments)) {
            return [];
        }

        // Get tasks for each department/region combination
        $allTasks = [];
        $taskIds = [];

        foreach ($assignments as $assignment) {
            $tasks = $this->taskModel->getTasksForDepartmentHeadWithDetails(
                $assignment['department_id'],
                $assignment['region_id']
            );

            foreach ($tasks as $task) {
                if (!in_array($task['id'], $taskIds)) {
                    $allTasks[] = $task;
                    $taskIds[] = $task['id'];
                }
            }
        }

        // Sort by created_at DESC
        usort($allTasks, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $allTasks;
    }

    /**
     * Check if department head can create task for a department in a region
     * 
     * @param int $departmentHeadId Department head user ID
     * @param int $departmentId Department ID
     * @param int $regionId Region ID
     * @return bool
     */
    public function canCreateTaskForDepartment(int $departmentHeadId, int $departmentId, int $regionId): bool
    {
        return $this->isDepartmentHeadFor($departmentHeadId, $departmentId, $regionId);
    }

    /**
     * Get viewable executants for a department head
     * Returns executants from the department head's department and region
     * 
     * @param int $departmentHeadId Department head user ID
     * @return array Array of executants with details
     */
    public function getViewableExecutants(int $departmentHeadId): array
    {
        // Get department head assignments
        $assignments = $this->getDepartmentsForUser($departmentHeadId);
        
        if (empty($assignments)) {
            return [];
        }

        // Get executants for each department/region combination
        $allExecutants = [];
        $executantIds = [];

        foreach ($assignments as $assignment) {
            $executants = $this->userModel->getUsersForDepartmentHead(
                $departmentHeadId,
                $assignment['department_id'],
                $assignment['region_id']
            );

            foreach ($executants as $executant) {
                // Only include executants (role_level >= 20)
                if (($executant['role_level'] ?? 0) >= $this->roleLevels['executant']) {
                    if (!in_array($executant['id'], $executantIds)) {
                        $allExecutants[] = $executant;
                        $executantIds[] = $executant['id'];
                    }
                }
            }
        }

        // Sort by last_name, first_name
        usort($allExecutants, function ($a, $b) {
            $aName = ($a['last_name'] ?? '') . ' ' . ($a['first_name'] ?? '');
            $bName = ($b['last_name'] ?? '') . ' ' . ($b['first_name'] ?? '');
            return strcasecmp($aName, $bName);
        });

        return $allExecutants;
    }

    /**
     * Check if department head can edit a task
     * Same logic as canViewTask for now
     * 
     * @param int $departmentHeadId Department head user ID
     * @param int $taskId Task ID
     * @return bool
     */
    public function canEditTask(int $departmentHeadId, int $taskId): bool
    {
        return $this->canViewTask($departmentHeadId, $taskId);
    }

    /**
     * Get department head's primary assignment (first one, or null)
     * Used for determining default region/department
     * 
     * @param int $departmentHeadId Department head user ID
     * @return array|null First assignment or null
     */
    public function getPrimaryAssignment(int $departmentHeadId): ?array
    {
        $assignments = $this->getDepartmentsForUser($departmentHeadId);
        return !empty($assignments) ? $assignments[0] : null;
    }
}

