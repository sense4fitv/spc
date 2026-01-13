<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\RegionModel;
use App\Models\TaskModel;

/**
 * CentralaService
 * 
 * Service responsible for Centrala page data.
 */
class CentralaService
{
    protected UserModel $userModel;
    protected RegionModel $regionModel;
    protected TaskModel $taskModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->regionModel = new RegionModel();
        $this->taskModel = new TaskModel();
    }

    /**
     * Get all admins with their task counts (assigned tasks)
     * 
     * @return array Array of admins with task_count and overdue_count
     */
    public function getAdminsWithTaskCounts(): array
    {
        $db = \Config\Database::connect();

        // Get all admins (role_level >= 100)
        $admins = $this->userModel->where('role_level', 100)
            ->where('active', 1)
            ->findAll();

        // Get task counts for each admin (from task_assignees)
        $adminsWithCounts = [];
        foreach ($admins as $admin) {
            // Total task count
            $taskCount = $db->table('task_assignees')
                ->where('user_id', $admin['id'])
                ->countAllResults(false);

            // Overdue task count (deadline < now AND status != 'completed')
            $overdueCount = $db->table('task_assignees ta')
                ->join('tasks t', 't.id = ta.task_id', 'inner')
                ->where('ta.user_id', $admin['id'])
                ->where('t.deadline <', date('Y-m-d H:i:s'))
                ->where('t.deadline IS NOT NULL', null, false)
                ->where('t.status !=', 'completed')
                ->countAllResults(false);

            $adminsWithCounts[] = [
                'id' => $admin['id'],
                'first_name' => $admin['first_name'],
                'last_name' => $admin['last_name'],
                'email' => $admin['email'],
                'task_count' => $taskCount,
                'overdue_count' => $overdueCount,
            ];
        }

        // Sort by task count descending, then by name
        usort($adminsWithCounts, function ($a, $b) {
            if ($b['task_count'] != $a['task_count']) {
                return $b['task_count'] - $a['task_count'];
            }
            $nameA = trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? ''));
            $nameB = trim(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? ''));
            return strcmp($nameA, $nameB);
        });

        return $adminsWithCounts;
    }

    /**
     * Get all regions with their task counts
     * 
     * @return array Array of regions with task_count and overdue_count
     */
    public function getRegionsWithTaskCounts(): array
    {
        $db = \Config\Database::connect();
        $regions = $this->regionModel->findAll();

        $regionsWithCounts = [];
        foreach ($regions as $region) {
            // Get tasks for this region using TaskModel method
            $tasks = $this->taskModel->getTasksForRegionWithDetails($region['id']);
            $taskCount = count($tasks);

            // Count overdue tasks (deadline < now AND status != 'completed')
            $overdueCount = $db->table('tasks t')
                ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'inner')
                ->join('contracts c', 'c.id = sd.contract_id', 'inner')
                ->join('regions r', 'r.id = c.region_id', 'inner')
                ->where('r.id', $region['id'])
                ->where('t.deadline <', date('Y-m-d H:i:s'))
                ->where('t.deadline IS NOT NULL', null, false)
                ->where('t.status !=', 'completed')
                ->countAllResults(false);

            $regionsWithCounts[] = [
                'id' => $region['id'],
                'name' => $region['name'],
                'task_count' => $taskCount,
                'overdue_count' => $overdueCount,
            ];
        }

        // Sort by task count descending, then by name
        usort($regionsWithCounts, function ($a, $b) {
            if ($b['task_count'] != $a['task_count']) {
                return $b['task_count'] - $a['task_count'];
            }
            return strcmp($a['name'], $b['name']);
        });

        return $regionsWithCounts;
    }

    /**
     * Get tasks assigned to a specific admin
     * 
     * @param int $adminId Admin user ID
     * @return array Tasks with details
     */
    public function getTasksForAdmin(int $adminId): array
    {
        return $this->taskModel->getTasksForAssigneeWithDetails($adminId);
    }

    /**
     * Get tasks for a specific region
     * 
     * @param int $regionId Region ID
     * @return array Tasks with details
     */
    public function getTasksForRegion(int $regionId): array
    {
        return $this->taskModel->getTasksForRegionWithDetails($regionId);
    }
}

