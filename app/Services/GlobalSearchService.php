<?php

namespace App\Services;

use App\Models\RegionModel;
use App\Models\ContractModel;
use App\Models\SubdivisionModel;
use App\Models\TaskModel;
use App\Models\UserModel;
use App\Models\DepartmentModel;

/**
 * GlobalSearchService
 * 
 * Service responsible for global search across multiple entities
 * (Regions, Contracts, Tasks, Users, Departments, Subdivisions)
 */
class GlobalSearchService
{
    protected RegionModel $regionModel;
    protected ContractModel $contractModel;
    protected SubdivisionModel $subdivisionModel;
    protected TaskModel $taskModel;
    protected UserModel $userModel;
    protected DepartmentModel $departmentModel;

    public function __construct()
    {
        $this->regionModel = new RegionModel();
        $this->contractModel = new ContractModel();
        $this->subdivisionModel = new SubdivisionModel();
        $this->taskModel = new TaskModel();
        $this->userModel = new UserModel();
        $this->departmentModel = new DepartmentModel();
    }

    /**
     * Search across all entities
     * 
     * @param string $query Search query
     * @param int|null $userId Current user ID for permission filtering
     * @param string|null $role Current user role for permission filtering
     * @param int|null $regionId Current user region ID for permission filtering
     * @param int $limit Maximum results per category (default: 5)
     * @return array Categorized search results
     */
    public function search(string $query, ?int $userId = null, ?string $role = null, ?int $regionId = null, int $limit = 5): array
    {
        if (empty(trim($query))) {
            return [];
        }

        $searchTerm = '%' . trim($query) . '%';
        $results = [];

        // Search Regions
        $results['regions'] = $this->searchRegions($searchTerm, $limit);

        // Search Contracts
        $results['contracts'] = $this->searchContracts($searchTerm, $userId, $role, $regionId, $limit);

        // Search Tasks
        $results['tasks'] = $this->searchTasks($searchTerm, $userId, $role, $regionId, $limit);

        // Search Users
        $results['users'] = $this->searchUsers($searchTerm, $userId, $role, $regionId, $limit);

        // Search Departments
        $results['departments'] = $this->searchDepartments($searchTerm, $limit);

        // Search Subdivisions
        $results['subdivisions'] = $this->searchSubdivisions($searchTerm, $userId, $role, $regionId, $limit);

        return $results;
    }

    /**
     * Search regions
     */
    protected function searchRegions(string $searchTerm, int $limit): array
    {
        $db = \Config\Database::connect();
        
        $regions = $db->table('regions')
            ->select('regions.id, regions.name, regions.description')
            ->like('regions.name', $searchTerm)
            ->orLike('regions.description', $searchTerm)
            ->limit($limit)
            ->get()
            ->getResultArray();

        $results = [];
        foreach ($regions as $region) {
            $results[] = [
                'id' => $region['id'],
                'type' => 'region',
                'title' => $region['name'],
                'subtitle' => $region['description'] ?? null,
                'url' => site_url('/dashboard/region/' . $region['id']),
                'icon' => 'bi-geo-alt-fill',
            ];
        }

        return $results;
    }

    /**
     * Search contracts
     */
    protected function searchContracts(string $searchTerm, ?int $userId, ?string $role, ?int $regionId, int $limit): array
    {
        $db = \Config\Database::connect();
        
        $query = $db->table('contracts c')
            ->select('c.id, c.name, c.contract_number, c.region_id, r.name as region_name')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->groupStart()
                ->like('c.name', $searchTerm)
                ->orLike('c.contract_number', $searchTerm)
                ->orLike('c.client_name', $searchTerm)
            ->groupEnd();

        // Apply permission filtering
        if ($role === 'manager' && $userId) {
            $query->where('c.manager_id', $userId);
        } elseif ($role === 'director' && $regionId) {
            $query->where('c.region_id', $regionId);
        } elseif ($role !== 'admin' && $role !== 'auditor') {
            // Other roles don't see contracts in search
            return [];
        }

        $contracts = $query->limit($limit)->get()->getResultArray();

        $results = [];
        foreach ($contracts as $contract) {
            $subtitle = $contract['contract_number'] ?? '';
            if ($contract['region_name']) {
                $subtitle = ($subtitle ? $subtitle . ' • ' : '') . $contract['region_name'];
            }
            
            $results[] = [
                'id' => $contract['id'],
                'type' => 'contract',
                'title' => $contract['name'],
                'subtitle' => $subtitle ?: null,
                'url' => site_url('/dashboard/contract/' . $contract['id']),
                'icon' => 'bi-file-earmark-text',
            ];
        }

        return $results;
    }

    /**
     * Search tasks
     */
    protected function searchTasks(string $searchTerm, ?int $userId, ?string $role, ?int $regionId, int $limit): array
    {
        $db = \Config\Database::connect();
        
        $query = $db->table('tasks t')
            ->select('t.id, t.title, t.description, t.status, 
                     c.name as contract_name, r.name as region_name,
                     creator.first_name as creator_first_name, creator.last_name as creator_last_name')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'left')
            ->join('contracts c', 'c.id = sd.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->join('users creator', 'creator.id = t.created_by', 'left')
            ->groupStart()
                ->like('t.title', $searchTerm)
                ->orLike('t.description', $searchTerm)
            ->groupEnd();

        // Apply permission filtering
        if ($role === 'executant' && $userId) {
            $query->join('task_assignees ta', 'ta.task_id = t.id', 'inner')
                  ->where('ta.user_id', $userId);
        } elseif ($role === 'manager' && $userId) {
            $query->where('c.manager_id', $userId);
        } elseif ($role === 'director' && $regionId) {
            $query->where('r.id', $regionId);
        } elseif ($role !== 'admin' && $role !== 'auditor') {
            return [];
        }

        $tasks = $query->limit($limit)->get()->getResultArray();

        $results = [];
        foreach ($tasks as $task) {
            $subtitle = $task['contract_name'] ?? '';
            if ($task['region_name']) {
                $subtitle = ($subtitle ? $subtitle . ' • ' : '') . $task['region_name'];
            }

            $results[] = [
                'id' => $task['id'],
                'type' => 'task',
                'title' => $task['title'],
                'subtitle' => $subtitle ?: null,
                'url' => site_url('/tasks/view/' . $task['id']),
                'icon' => 'bi-check2-square',
                'status' => $task['status'] ?? 'new',
            ];
        }

        return $results;
    }

    /**
     * Search users
     */
    protected function searchUsers(string $searchTerm, ?int $userId, ?string $role, ?int $regionId, int $limit): array
    {
        $db = \Config\Database::connect();
        
        $query = $db->table('users u')
            ->select('u.id, u.first_name, u.last_name, u.email, u.role, r.name as region_name')
            ->join('regions r', 'r.id = u.region_id', 'left')
            ->where('u.active', 1)
            ->groupStart()
                ->like('u.first_name', $searchTerm)
                ->orLike('u.last_name', $searchTerm)
                ->orLike('u.email', $searchTerm)
            ->groupEnd();

        // Apply permission filtering
        if ($role === 'director' && $regionId) {
            $query->groupStart()
                  ->where('u.region_id', $regionId)
                  ->orWhere('u.region_id IS NULL')
                  ->groupEnd();
        } elseif ($role === 'manager' && $userId) {
            // Managers see users from regions of their contracts
            $contracts = $db->table('contracts')
                ->select('region_id')
                ->where('manager_id', $userId)
                ->get()
                ->getResultArray();
            
            $allowedRegionIds = array_unique(array_column($contracts, 'region_id'));
            if (!empty($allowedRegionIds)) {
                $query->groupStart()
                      ->whereIn('u.region_id', $allowedRegionIds)
                      ->orWhere('u.region_id IS NULL')
                      ->groupEnd();
            } else {
                return [];
            }
        } elseif ($role !== 'admin' && $role !== 'auditor') {
            return [];
        }

        $users = $query->limit($limit)->get()->getResultArray();

        $results = [];
        foreach ($users as $user) {
            $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $title = $fullName ?: $user['email'];
            $subtitle = $user['email'] !== $title ? $user['email'] : null;
            if ($user['region_name']) {
                $subtitle = ($subtitle ? $subtitle . ' • ' : '') . $user['region_name'];
            }

            $results[] = [
                'id' => $user['id'],
                'type' => 'user',
                'title' => $title,
                'subtitle' => $subtitle,
                'url' => site_url('/users'),
                'icon' => 'bi-person',
            ];
        }

        return $results;
    }

    /**
     * Search departments
     */
    protected function searchDepartments(string $searchTerm, int $limit): array
    {
        $db = \Config\Database::connect();
        
        $departments = $db->table('departments')
            ->select('id, name, color_code')
            ->like('name', $searchTerm)
            ->limit($limit)
            ->get()
            ->getResultArray();

        $results = [];
        foreach ($departments as $department) {
            $results[] = [
                'id' => $department['id'],
                'type' => 'department',
                'title' => $department['name'],
                'subtitle' => null,
                'url' => site_url('/departments'),
                'icon' => 'bi-building',
                'color' => $department['color_code'] ?? null,
            ];
        }

        return $results;
    }

    /**
     * Search subdivisions
     */
    protected function searchSubdivisions(string $searchTerm, ?int $userId, ?string $role, ?int $regionId, int $limit): array
    {
        $db = \Config\Database::connect();
        
        $query = $db->table('subdivisions sd')
            ->select('sd.id, sd.name, sd.code, c.name as contract_name, c.region_id, r.name as region_name')
            ->join('contracts c', 'c.id = sd.contract_id', 'inner')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->groupStart()
                ->like('sd.name', $searchTerm)
                ->orLike('sd.code', $searchTerm)
            ->groupEnd();

        // Apply permission filtering
        if ($role === 'manager' && $userId) {
            $query->where('c.manager_id', $userId);
        } elseif ($role === 'director' && $regionId) {
            $query->where('c.region_id', $regionId);
        } elseif ($role !== 'admin' && $role !== 'auditor') {
            return [];
        }

        $subdivisions = $query->limit($limit)->get()->getResultArray();

        $results = [];
        foreach ($subdivisions as $subdivision) {
            $title = $subdivision['name'];
            if ($subdivision['code']) {
                $title = $subdivision['code'] . ' - ' . $title;
            }
            
            $subtitle = $subdivision['contract_name'] ?? '';
            if ($subdivision['region_name']) {
                $subtitle = ($subtitle ? $subtitle . ' • ' : '') . $subdivision['region_name'];
            }

            $results[] = [
                'id' => $subdivision['id'],
                'type' => 'subdivision',
                'title' => $title,
                'subtitle' => $subtitle ?: null,
                'url' => site_url('/dashboard/subdivision/' . $subdivision['id']),
                'icon' => 'bi-diagram-3',
            ];
        }

        return $results;
    }
}

