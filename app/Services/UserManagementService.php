<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\RegionModel;
use App\Models\ContractModel;
use App\Models\DepartmentModel;

/**
 * UserManagementService
 * 
 * Service responsible for user management business logic,
 * including permission checks and data filtering based on user roles.
 */
class UserManagementService
{
    protected UserModel $userModel;
    protected RegionModel $regionModel;
    protected ContractModel $contractModel;
    protected DepartmentModel $departmentModel;

    /**
     * Role level mappings
     */
    protected array $roleLevels = [
        'admin'      => 100,
        'director'   => 80,
        'manager'    => 50,
        'executant'  => 20,
        'auditor'    => 10,
    ];

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->regionModel = new RegionModel();
        $this->contractModel = new ContractModel();
        $this->departmentModel = new DepartmentModel();
    }

    /**
     * Get viewable users based on current user's role
     * 
     * @param int $currentUserId Current user ID
     * @return array Array of users with additional data
     */
    public function getViewableUsers(int $currentUserId): array
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return [];
        }

        $role = $currentUser['role'];
        $roleLevel = $currentUser['role_level'];

        // Admin sees all users
        if ($roleLevel >= $this->roleLevels['admin']) {
            return $this->userModel->getAllUsersWithDetails();
        }

        // Director sees users from his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $regionId = $currentUser['region_id'];
            if (!$regionId) {
                // Director must have region_id - if missing, return empty
                return [];
            }
            return $this->userModel->getUsersForDirector($currentUserId, $regionId);
        }

        // Manager sees users with tasks on his contracts
        if ($roleLevel >= $this->roleLevels['manager']) {
            return $this->userModel->getUsersForContractManager($currentUserId);
        }

        // Executant and Auditor - no access to user management
        return [];
    }

    /**
     * Get users with task counts for contract manager
     * 
     * @param int $managerId Manager user ID
     * @return array Users with task_count
     */
    public function getUsersWithTaskCounts(int $managerId): array
    {
        return $this->userModel->getUsersForContractManagerWithTaskCount($managerId);
    }

    /**
     * Check if current user can create a new user
     * 
     * @param int $currentUserId Current user ID
     * @param int|null $targetRegionId Target region ID (for validation)
     * @return bool
     */
    public function canCreateUser(int $currentUserId, ?int $targetRegionId = null): bool
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];

        // Admin can create anywhere
        if ($roleLevel >= $this->roleLevels['admin']) {
            return true;
        }

        // Director can create only in his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $directorRegionId = $currentUser['region_id'];
            if (!$directorRegionId) {
                // Director must have region_id - cannot create without it
                return false;
            }
            // Must match director's region
            return $targetRegionId === $directorRegionId;
        }

        // Manager cannot create users (only view)
        return false;
    }

    /**
     * Check if current user can edit a target user
     * 
     * @param int $currentUserId Current user ID
     * @param int $targetUserId Target user ID to edit
     * @return bool
     */
    public function canEditUser(int $currentUserId, int $targetUserId): bool
    {
        $currentUser = $this->userModel->find($currentUserId);
        $targetUser = $this->userModel->find($targetUserId);

        if (!$currentUser || !$targetUser) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];

        // Admin can edit anyone
        if ($roleLevel >= $this->roleLevels['admin']) {
            return true;
        }

        // Director can edit only users from his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $directorRegionId = $currentUser['region_id'];
            if (!$directorRegionId) {
                // Director must have region_id - cannot edit without it
                return false;
            }
            // Target user must be in same region
            return $targetUser['region_id'] === $directorRegionId;
        }

        // Manager cannot edit users (only view)
        return false;
    }

    /**
     * Check if current user can delete a user
     * 
     * @param int $currentUserId Current user ID
     * @return bool
     */
    public function canDeleteUser(int $currentUserId): bool
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];

        // Only admin can delete
        return $roleLevel >= $this->roleLevels['admin'];
    }

    /**
     * Check if current user can change roles
     * 
     * @param int $currentUserId Current user ID
     * @return bool
     */
    public function canChangeRole(int $currentUserId): bool
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];

        // Only admin can change roles
        return $roleLevel >= $this->roleLevels['admin'];
    }

    /**
     * Get allowed regions for user creation
     * 
     * @param int $currentUserId Current user ID
     * @return array Array of regions [id => name]
     */
    public function getAllowedRegionsForCreate(int $currentUserId): array
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return [];
        }

        $roleLevel = $currentUser['role_level'];

        // Admin sees all regions
        if ($roleLevel >= $this->roleLevels['admin']) {
            $regions = $this->regionModel->findAll();
            $result = [];
            foreach ($regions as $region) {
                $result[$region['id']] = $region['name'];
            }
            return $result;
        }

        // Director sees only his region
        if ($roleLevel >= $this->roleLevels['director']) {
            $regionId = $currentUser['region_id'];
            if ($regionId) {
                $region = $this->regionModel->find($regionId);
                if ($region) {
                    return [$region['id'] => $region['name']];
                }
            }
        }

        return [];
    }

    /**
     * Get allowed roles for user creation
     * 
     * @param int $currentUserId Current user ID
     * @return array Array of roles
     */
    public function getAllowedRolesForCreate(int $currentUserId): array
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return [];
        }

        $roleLevel = $currentUser['role_level'];

        // Admin can create any role
        if ($roleLevel >= $this->roleLevels['admin']) {
            return ['admin', 'director', 'manager', 'executant', 'auditor'];
        }

        // Director can create any role (but cannot change after creation)
        if ($roleLevel >= $this->roleLevels['director']) {
            return ['director', 'manager', 'executant', 'auditor'];
        }

        // Manager cannot create users
        return [];
    }

    /**
     * Generate random password
     * 
     * @param int $length Password length
     * @return string Generated password
     */
    public function generateRandomPassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    /**
     * Get all departments
     * 
     * @return array Array of departments [id => name]
     */
    public function getAllDepartments(): array
    {
        $departments = $this->departmentModel->findAll();
        $result = [];

        foreach ($departments as $dept) {
            $result[$dept['id']] = $dept['name'];
        }

        return $result;
    }

    /**
     * Get role display name
     * 
     * @param string $role Role code
     * @return string Display name
     */
    public function getRoleDisplayName(string $role): string
    {
        $names = [
            'admin' => 'Admin',
            'director' => 'Director Regional',
            'manager' => 'Manager Contract',
            'executant' => 'Executant',
            'auditor' => 'Auditor',
        ];

        return $names[$role] ?? ucfirst($role);
    }

    /**
     * Get role badge class
     * 
     * @param string $role Role code
     * @return string Badge CSS class
     */
    public function getRoleBadgeClass(string $role): string
    {
        $classes = [
            'admin' => 'bg-subtle-purple',
            'director' => 'bg-subtle-blue',
            'manager' => 'bg-subtle-gray',
            'executant' => 'bg-subtle-orange',
            'auditor' => 'bg-subtle-green',
        ];

        return $classes[$role] ?? 'bg-subtle-gray';
    }

    /**
     * Check if user has active (non-completed) tasks
     * 
     * @param int $userId User ID
     * @return array Returns ['has_active_tasks' => bool, 'count' => int, 'assigned' => int, 'created' => int]
     */
    public function hasActiveTasks(int $userId): array
    {
        $db = \Config\Database::connect();

        // Count assigned tasks that are not completed
        $assignedActive = $db->table('task_assignees')
            ->join('tasks', 'tasks.id = task_assignees.task_id')
            ->where('task_assignees.user_id', $userId)
            ->where('tasks.status !=', 'completed')
            ->countAllResults();

        // Count created tasks that are not completed
        $createdActive = $db->table('tasks')
            ->where('created_by', $userId)
            ->where('status !=', 'completed')
            ->countAllResults();

        $totalActive = $assignedActive + $createdActive;

        return [
            'has_active_tasks' => $totalActive > 0,
            'count' => $totalActive,
            'assigned' => $assignedActive,
            'created' => $createdActive,
        ];
    }

    /**
     * Get role levels mapping (for use in controllers)
     * 
     * @return array
     */
    public function getRoleLevels(): array
    {
        return $this->roleLevels;
    }
}
