<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartmentModel extends Model
{
    protected $table            = 'departments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'color_code',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = null;
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
     * Get all users in a department
     */
    public function getUsers(int $departmentId)
    {
        $db = \Config\Database::connect();
        return $db->table('user_departments')
            ->join('users', 'users.id = user_departments.user_id')
            ->where('user_departments.department_id', $departmentId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get all tasks for a department
     */
    public function getTasks(int $departmentId)
    {
        $db = \Config\Database::connect();
        return $db->table('task_departments')
            ->join('tasks', 'tasks.id = task_departments.task_id')
            ->where('task_departments.department_id', $departmentId)
            ->get()
            ->getResultArray();
    }

    /**
     * Check if department has dependencies (users or tasks)
     * Returns array with counts for checking before deletion
     * 
     * @param int $departmentId Department ID
     * @return array ['has_dependencies' => bool, 'users_count' => int, 'tasks_count' => int]
     */
    public function hasDependencies(int $departmentId): array
    {
        $users = $this->getUsers($departmentId);
        $tasks = $this->getTasks($departmentId);

        $usersCount = count($users);
        $tasksCount = count($tasks);

        return [
            'has_dependencies' => $usersCount > 0 || $tasksCount > 0,
            'users_count' => $usersCount,
            'tasks_count' => $tasksCount,
        ];
    }

    /**
     * Get all departments with details (users count, tasks count)
     * 
     * @return array Departments with additional information
     */
    public function getAllDepartmentsWithDetails(): array
    {
        $departments = $this->findAll();

        // Get counts for each department
        foreach ($departments as &$department) {
            $users = $this->getUsers($department['id']);
            $tasks = $this->getTasks($department['id']);
            
            $department['users_count'] = count($users);
            $department['tasks_count'] = count($tasks);
        }

        return $departments;
    }
}

