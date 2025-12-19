<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'username',
        'email',
        'password_hash',
        'role',
        'role_level',
        'region_id',
        'first_name',
        'last_name',
        'active',
        'avatar_url',
        'created_by',
        'password_set_token',
        'token_expires_at',
        'phone',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

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
     * Find user by email
     */
    public function findByEmail(string $email)
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Find active users only
     */
    public function findActive()
    {
        return $this->where('active', 1)->findAll();
    }

    /**
     * Find users by role level
     */
    public function findByRoleLevel(int $minLevel)
    {
        return $this->where('role_level >=', $minLevel)->findAll();
    }

    /**
     * Find super users (region_id is NULL)
     */
    public function findSuperUsers()
    {
        return $this->where('region_id IS NULL')->findAll();
    }

    /**
     * Get user's region
     */
    public function getRegion(int $userId)
    {
        $user = $this->find($userId);
        if (!$user || !$user['region_id']) {
            return null;
        }

        $regionModel = new RegionModel();
        return $regionModel->find($user['region_id']);
    }

    /**
     * Get user's departments
     */
    public function getDepartments(int $userId)
    {
        $db = \Config\Database::connect();
        return $db->table('user_departments')
            ->join('departments', 'departments.id = user_departments.department_id')
            ->where('user_departments.user_id', $userId)
            ->get()
            ->getResultArray();
    }

    /**
     * Check if user has access to all departments (super user)
     */
    public function hasAccessToAllDepartments(int $userId): bool
    {
        $db = \Config\Database::connect();
        $count = $db->table('user_departments')
            ->where('user_id', $userId)
            ->countAllResults();

        return $count === 0; // No departments = super user
    }

    /**
     * Get user's created tasks
     */
    public function getCreatedTasks(int $userId)
    {
        $taskModel = new TaskModel();
        return $taskModel->where('created_by', $userId)->findAll();
    }

    /**
     * Get user's assigned tasks
     */
    public function getAssignedTasks(int $userId)
    {
        $db = \Config\Database::connect();
        return $db->table('task_assignees')
            ->join('tasks', 'tasks.id = task_assignees.task_id')
            ->where('task_assignees.user_id', $userId)
            ->get()
            ->getResultArray();
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        return password_verify($plainPassword, $hashedPassword);
    }

    /**
     * Generate password set token (16 characters)
     */
    public function generatePasswordSetToken(int $userId): string
    {
        // Generate 16 character random token
        $token = bin2hex(random_bytes(8)); // 16 characters hex
        
        // Set token expiry to 24 hours from now
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $this->update($userId, [
            'password_set_token' => $token,
            'token_expires_at' => $expiresAt,
        ]);
        
        return $token;
    }

    /**
     * Find user by password set token
     */
    public function findByPasswordSetToken(string $token)
    {
        return $this->where('password_set_token', $token)
            ->where('token_expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    /**
     * Clear password set token (after successful password set)
     */
    public function clearPasswordSetToken(int $userId): bool
    {
        return $this->update($userId, [
            'password_set_token' => null,
            'token_expires_at' => null,
        ]);
    }

    /**
     * Set new password and activate account
     */
    public function setPassword(int $userId, string $newPassword): bool
    {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        return $this->update($userId, [
            'password_hash' => $passwordHash,
            'active' => 1,
            'password_set_token' => null,
            'token_expires_at' => null,
        ]);
    }

    /**
     * Get all users with details (regions, departments)
     * For Admin role
     * 
     * @return array Users with joined data
     */
    public function getAllUsersWithDetails(): array
    {
        $db = \Config\Database::connect();
        
        $users = $db->table('users u')
            ->select('u.*, r.name as region_name')
            ->join('regions r', 'r.id = u.region_id', 'left')
            ->orderBy('u.last_name', 'ASC')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResultArray();

        // Add departments for each user
        foreach ($users as &$user) {
            $user['departments'] = $this->getDepartments($user['id']);
            $user['departments_list'] = implode(', ', array_column($user['departments'], 'name'));
        }

        return $users;
    }

    /**
     * Get users for a director (from his region)
     * 
     * @param int $directorId Director user ID
     * @param int $regionId Region ID
     * @return array Users with details
     */
    public function getUsersForDirector(int $directorId, int $regionId): array
    {
        $db = \Config\Database::connect();
        
        $users = $db->table('users u')
            ->select('u.*, r.name as region_name')
            ->join('regions r', 'r.id = u.region_id', 'left')
            ->groupStart()
            ->where('u.region_id', $regionId)
            ->orWhere('u.region_id IS NULL') // Allow executants with region_id NULL
            ->groupEnd()
            ->orderBy('u.last_name', 'ASC')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResultArray();

        // Add departments for each user
        foreach ($users as &$user) {
            $user['departments'] = $this->getDepartments($user['id']);
            $user['departments_list'] = implode(', ', array_column($user['departments'], 'name'));
        }

        return $users;
    }

    /**
     * Get users for a contract manager (all executants from regions of contracts assigned to him)
     * 
     * @param int $managerId Manager user ID
     * @return array Users with details
     */
    public function getUsersForContractManager(int $managerId): array
    {
        $db = \Config\Database::connect();
        
        // Get contracts assigned to this manager
        $contracts = $db->table('contracts')
            ->select('region_id')
            ->where('manager_id', $managerId)
            ->get()
            ->getResultArray();
        
        if (empty($contracts)) {
            return [];
        }
        
        // Get unique region IDs from contracts
        $regionIds = array_unique(array_column($contracts, 'region_id'));
        $regionIds = array_filter($regionIds); // Remove nulls
        
        if (empty($regionIds)) {
            return [];
        }
        
        // Get all users from those regions (executants and above) + executants with region_id NULL
        $users = $db->table('users u')
            ->select('u.*, r.name as region_name')
            ->join('regions r', 'r.id = u.region_id', 'left')
            ->groupStart()
            ->whereIn('u.region_id', $regionIds)
            ->orWhere('u.region_id IS NULL') // Allow executants with region_id NULL
            ->groupEnd()
            ->where('u.active', 1)
            ->where('u.role_level >=', 20) // Executant and above
            ->orderBy('u.last_name', 'ASC')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResultArray();

        // Add departments for each user
        foreach ($users as &$user) {
            $user['departments'] = $this->getDepartments($user['id']);
            $user['departments_list'] = implode(', ', array_column($user['departments'], 'name'));
        }

        return $users;
    }

    /**
     * Get users for contract manager with task counts
     * 
     * @param int $managerId Manager user ID
     * @return array Users with task_count
     */
    public function getUsersForContractManagerWithTaskCount(int $managerId): array
    {
        $db = \Config\Database::connect();
        
        $users = $db->table('users u')
            ->select('u.*, r.name as region_name, COUNT(DISTINCT ta.task_id) as task_count')
            ->join('task_assignees ta', 'ta.user_id = u.id')
            ->join('tasks t', 't.id = ta.task_id')
            ->join('subdivisions s', 's.id = t.subdivision_id')
            ->join('contracts c', 'c.id = s.contract_id')
            ->join('regions r', 'r.id = u.region_id', 'left')
            ->where('c.manager_id', $managerId)
            ->where('u.active', 1)
            ->groupBy('u.id')
            ->orderBy('u.last_name', 'ASC')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResultArray();

        // Add departments for each user
        foreach ($users as &$user) {
            $user['departments'] = $this->getDepartments($user['id']);
            $user['departments_list'] = implode(', ', array_column($user['departments'], 'name'));
        }

        return $users;
    }

    /**
     * Soft delete user (set active = 0)
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function softDelete(int $userId): bool
    {
        return $this->update($userId, ['active' => 0]);
    }

    /**
     * Restore user (set active = 1)
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function restore(int $userId): bool
    {
        return $this->update($userId, ['active' => 1]);
    }

    /**
     * Check if user is a department head
     * 
     * @param int $userId User ID
     * @return bool
     */
    public function isDepartmentHead(int $userId): bool
    {
        $departmentHeadModel = new DepartmentHeadModel();
        return $departmentHeadModel->isDepartmentHead($userId);
    }

    /**
     * Get department head assignments for a user
     * 
     * @param int $userId User ID
     * @return array Array of department head assignments
     */
    public function getDepartmentHeadAssignments(int $userId): array
    {
        $departmentHeadModel = new DepartmentHeadModel();
        return $departmentHeadModel->getDepartmentsForUser($userId);
    }

    /**
     * Get users for a department head (executants from his department and region)
     * 
     * @param int $departmentHeadId Department head user ID
     * @param int $departmentId Department ID
     * @param int $regionId Region ID
     * @return array Users with details
     */
    public function getUsersForDepartmentHead(int $departmentHeadId, int $departmentId, int $regionId): array
    {
        $db = \Config\Database::connect();
        
        // Get users from the region who are in the department
        $users = $db->table('users u')
            ->select('u.*, r.name as region_name')
            ->join('regions r', 'r.id = u.region_id', 'left')
            ->join('user_departments ud', 'ud.user_id = u.id')
            ->where('ud.department_id', $departmentId)
            ->groupStart()
            ->where('u.region_id', $regionId)
            ->orWhere('u.region_id IS NULL') // Allow executants with region_id NULL
            ->groupEnd()
            ->where('u.active', 1)
            ->groupBy('u.id')
            ->orderBy('u.last_name', 'ASC')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResultArray();

        // Add departments for each user
        foreach ($users as &$user) {
            $user['departments'] = $this->getDepartments($user['id']);
            $user['departments_list'] = implode(', ', array_column($user['departments'], 'name'));
        }

        return $users;
    }
}
