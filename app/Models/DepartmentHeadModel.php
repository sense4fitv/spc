<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartmentHeadModel extends Model
{
    protected $table            = 'department_heads';
    protected $primaryKey       = ['user_id', 'department_id', 'region_id'];
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'department_id',
        'region_id',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = null;
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
     * Check if user is a department head
     * 
     * @param int $userId User ID
     * @return bool
     */
    public function isDepartmentHead(int $userId): bool
    {
        return $this->where('user_id', $userId)->countAllResults() > 0;
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
        return $this->where('user_id', $userId)
            ->where('department_id', $departmentId)
            ->where('region_id', $regionId)
            ->countAllResults() > 0;
    }

    /**
     * Get all department head assignments for a user
     * 
     * @param int $userId User ID
     * @return array Array of department head assignments with department and region details
     */
    public function getDepartmentsForUser(int $userId): array
    {
        $db = \Config\Database::connect();
        return $db->table('department_heads')
            ->select('department_heads.*, departments.name as department_name, departments.color_code, regions.name as region_name')
            ->join('departments', 'departments.id = department_heads.department_id')
            ->join('regions', 'regions.id = department_heads.region_id')
            ->where('department_heads.user_id', $userId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get department head for a specific department and region
     * 
     * @param int $departmentId Department ID
     * @param int $regionId Region ID
     * @return array|null Department head user data or null if not found
     */
    public function getDepartmentHead(int $departmentId, int $regionId)
    {
        $db = \Config\Database::connect();
        $result = $db->table('department_heads')
            ->select('department_heads.*, users.first_name, users.last_name, users.email, departments.name as department_name, regions.name as region_name')
            ->join('users', 'users.id = department_heads.user_id')
            ->join('departments', 'departments.id = department_heads.department_id')
            ->join('regions', 'regions.id = department_heads.region_id')
            ->where('department_heads.department_id', $departmentId)
            ->where('department_heads.region_id', $regionId)
            ->get()
            ->getRowArray();

        return $result ?: null;
    }

    /**
     * Get all department heads for a region
     * 
     * @param int $regionId Region ID
     * @return array Array of department heads
     */
    public function getDepartmentHeadsForRegion(int $regionId): array
    {
        $db = \Config\Database::connect();
        return $db->table('department_heads')
            ->select('department_heads.*, users.first_name, users.last_name, users.email, departments.name as department_name')
            ->join('users', 'users.id = department_heads.user_id')
            ->join('departments', 'departments.id = department_heads.department_id')
            ->where('department_heads.region_id', $regionId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get all department heads for a department (across all regions)
     * 
     * @param int $departmentId Department ID
     * @return array Array of department heads
     */
    public function getDepartmentHeadsForDepartment(int $departmentId): array
    {
        $db = \Config\Database::connect();
        return $db->table('department_heads')
            ->select('department_heads.*, users.first_name, users.last_name, users.email, regions.name as region_name')
            ->join('users', 'users.id = department_heads.user_id')
            ->join('regions', 'regions.id = department_heads.region_id')
            ->where('department_heads.department_id', $departmentId)
            ->get()
            ->getResultArray();
    }

    /**
     * Assign user as department head
     * 
     * @param int $userId User ID
     * @param int $departmentId Department ID
     * @param int $regionId Region ID
     * @return bool Success
     */
    public function assignDepartmentHead(int $userId, int $departmentId, int $regionId): bool
    {
        // Check if already assigned
        $exists = $this->isDepartmentHeadFor($userId, $departmentId, $regionId);
        if ($exists) {
            return false; // Already assigned
        }

        // Check if department already has a head in this region (UNIQUE constraint will also prevent this)
        $existingHead = $this->getDepartmentHead($departmentId, $regionId);
        if ($existingHead) {
            return false; // Department already has a head in this region
        }

        return $this->insert([
            'user_id' => $userId,
            'department_id' => $departmentId,
            'region_id' => $regionId,
        ]);
    }

    /**
     * Remove department head assignment
     * 
     * @param int $userId User ID
     * @param int $departmentId Department ID
     * @param int $regionId Region ID
     * @return bool Success
     */
    public function removeDepartmentHead(int $userId, int $departmentId, int $regionId): bool
    {
        return $this->where('user_id', $userId)
            ->where('department_id', $departmentId)
            ->where('region_id', $regionId)
            ->delete() !== false;
    }

    /**
     * Remove all department head assignments for a user
     * 
     * @param int $userId User ID
     * @return bool Success
     */
    public function removeAllForUser(int $userId): bool
    {
        return $this->where('user_id', $userId)->delete() !== false;
    }

    /**
     * Get all department heads with full details
     * 
     * @return array Array of department heads with user, department, and region details
     */
    public function getAllWithDetails(): array
    {
        $db = \Config\Database::connect();
        return $db->table('department_heads')
            ->select('department_heads.*, 
                      users.first_name, users.last_name, users.email, users.role, 
                      departments.name as department_name, departments.color_code,
                      regions.name as region_name')
            ->join('users', 'users.id = department_heads.user_id')
            ->join('departments', 'departments.id = department_heads.department_id')
            ->join('regions', 'regions.id = department_heads.region_id')
            ->get()
            ->getResultArray();
    }
}

