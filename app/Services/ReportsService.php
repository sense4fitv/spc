<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\TaskModel;
use App\Models\ContractModel;
use App\Models\RegionModel;
use App\Models\SubdivisionModel;

/**
 * ReportsService
 * 
 * Service responsible for generating report data for Directors.
 * Handles data aggregation, filtering, and formatting for various report types.
 */
class ReportsService
{
    protected UserModel $userModel;
    protected TaskModel $taskModel;
    protected ContractModel $contractModel;
    protected RegionModel $regionModel;
    protected SubdivisionModel $subdivisionModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->taskModel = new TaskModel();
        $this->contractModel = new ContractModel();
        $this->regionModel = new RegionModel();
        $this->subdivisionModel = new SubdivisionModel();
    }

    /**
     * Get Operational Regional Report data
     * 
     * @param array $filters ['region_id' => int|null, 'date_from' => string, 'date_to' => string]
     * @return array Report data with regions, KPIs, workload
     */
    public function getOperationalRegionalReport(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $filters['date_to'] ?? date('Y-m-d');
        $regionId = $filters['region_id'] ?? null;

        $db = \Config\Database::connect();

        // Get all regions (or specific region)
        $regionsQuery = $db->table('regions r');
        if ($regionId) {
            $regionsQuery->where('r.id', $regionId);
        }
        $regions = $regionsQuery->get()->getResultArray();

        $reportData = [
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'regions' => [],
            'summary' => [
                'total_regions' => count($regions),
                'total_tasks' => 0,
                'total_active_tasks' => 0,
                'total_overdue_tasks' => 0,
                'total_users' => 0,
            ],
        ];

        foreach ($regions as $region) {
            // Get contracts for this region
            $contracts = $this->contractModel->getContractsForRegionWithDetails($region['id']);

            // Get tasks for this region within date range
            $tasks = $db->table('tasks t')
                ->select('t.*, 
                    sd.id as subdivision_id_full, sd.name as subdivision_name,
                    c.id as contract_id, c.name as contract_name,
                    r.id as region_id, r.name as region_name')
                ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'left')
                ->join('contracts c', 'c.id = sd.contract_id', 'left')
                ->join('regions r', 'r.id = c.region_id', 'left')
                ->where('r.id', $region['id'])
                ->where('t.created_at >=', $dateFrom . ' 00:00:00')
                ->where('t.created_at <=', $dateTo . ' 23:59:59')
                ->get()
                ->getResultArray();

            // Calculate KPIs for this region
            $activeTasks = array_filter($tasks, fn($t) => !in_array($t['status'], ['completed']));
            $overdueTasks = array_filter($tasks, function ($t) {
                if ($t['status'] === 'completed') return false;
                if (empty($t['deadline'])) return false;
                return strtotime($t['deadline']) < time();
            });

            // Get users for this region
            $regionUsers = $db->table('users')
                ->where('region_id', $region['id'])
                ->where('active', 1)
                ->get()
                ->getResultArray();

            $users = count($regionUsers);

            // Calculate workload per user for this region
            $workload = [];
            foreach ($regionUsers as $user) {
                // Count active tasks for user
                $activeTasksCount = $db->table('tasks t')
                    ->groupStart()
                    ->where('t.created_by', $user['id'])
                    ->orWhereIn('t.id', function ($builder) use ($user) {
                        return $builder->select('task_id')
                            ->from('task_assignees')
                            ->where('user_id', $user['id']);
                    })
                    ->groupEnd()
                    ->where('t.status !=', 'completed')
                    ->countAllResults(false);

                // Calculate workload percentage (assuming 15 tasks = 100%)
                $maxTasks = 15;
                $percentage = min(100, round(($activeTasksCount / $maxTasks) * 100));

                $workload[] = [
                    'user_id' => $user['id'],
                    'first_name' => $user['first_name'] ?? '',
                    'last_name' => $user['last_name'] ?? '',
                    'email' => $user['email'] ?? '',
                    'role' => $user['role'] ?? '',
                    'active_tasks' => $activeTasksCount,
                    'workload_percentage' => $percentage,
                    'region_id' => $region['id'],
                ];
            }

            $regionData = [
                'id' => $region['id'],
                'name' => $region['name'],
                'description' => $region['description'] ?? null,
                'contracts_count' => count($contracts),
                'tasks_count' => count($tasks),
                'active_tasks_count' => count($activeTasks),
                'overdue_tasks_count' => count($overdueTasks),
                'completed_tasks_count' => count($tasks) - count($activeTasks),
                'users_count' => $users,
                'workload' => $workload,
            ];

            $reportData['regions'][] = $regionData;
            $reportData['summary']['total_tasks'] += count($tasks);
            $reportData['summary']['total_active_tasks'] += count($activeTasks);
            $reportData['summary']['total_overdue_tasks'] += count($overdueTasks);
            $reportData['summary']['total_users'] += $users;
        }

        return $reportData;
    }

    /**
     * Get Contracts Performance Report data
     * 
     * @param array $filters ['region_id' => int|null, 'date_from' => string, 'date_to' => string]
     * @return array Report data with contracts, progress, deadlines
     */
    public function getContractsPerformanceReport(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $filters['date_to'] ?? date('Y-m-d');
        $regionId = $filters['region_id'] ?? null;

        $db = \Config\Database::connect();

        // Get contracts
        $contractsQuery = $db->table('contracts c')
            ->select('c.*, r.id as region_id, r.name as region_name,
                     u.first_name as manager_first_name, u.last_name as manager_last_name')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->join('users u', 'u.id = c.manager_id', 'left');

        if ($regionId) {
            $contractsQuery->where('c.region_id', $regionId);
        }

        $contracts = $contractsQuery->get()->getResultArray();

        $reportData = [
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'contracts' => [],
            'summary' => [
                'total_contracts' => count($contracts),
                'active_contracts' => 0,
                'total_tasks' => 0,
                'overdue_tasks' => 0,
            ],
        ];

        foreach ($contracts as $contract) {
            // Get subdivisions
            $subdivisions = $db->table('subdivisions')
                ->where('contract_id', $contract['id'])
                ->get()
                ->getResultArray();

            // Get tasks for this contract
            $tasks = $db->table('tasks t')
                ->select('t.*')
                ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'left')
                ->where('sd.contract_id', $contract['id'])
                ->where('t.created_at >=', $dateFrom . ' 00:00:00')
                ->where('t.created_at <=', $dateTo . ' 23:59:59')
                ->get()
                ->getResultArray();

            $activeTasks = array_filter($tasks, fn($t) => !in_array($t['status'], ['completed']));
            $overdueTasks = array_filter($tasks, function ($t) {
                if ($t['status'] === 'completed') return false;
                if (empty($t['deadline'])) return false;
                return strtotime($t['deadline']) < time();
            });

            // Calculate progress percentage (simple: completed vs total)
            $totalTasks = count($tasks);
            $completedTasks = count($tasks) - count($activeTasks);
            $progressPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

            // Get upcoming deadlines (next 7 days)
            $upcomingDeadlines = array_filter($tasks, function ($t) {
                if ($t['status'] === 'completed') return false;
                if (empty($t['deadline'])) return false;
                $daysUntilDeadline = (strtotime($t['deadline']) - time()) / 86400;
                return $daysUntilDeadline >= 0 && $daysUntilDeadline <= 7;
            });

            $contractData = [
                'id' => $contract['id'],
                'name' => $contract['name'],
                'contract_number' => $contract['contract_number'] ?? null,
                'client_name' => $contract['client_name'] ?? null,
                'region_name' => $contract['region_name'] ?? null,
                'status' => $contract['status'] ?? 'planning',
                'start_date' => $contract['start_date'] ?? null,
                'end_date' => $contract['end_date'] ?? null,
                'manager_name' => trim(($contract['manager_first_name'] ?? '') . ' ' . ($contract['manager_last_name'] ?? '')),
                'progress_percentage' => $progressPercentage,
                'subdivisions_count' => count($subdivisions),
                'tasks_count' => $totalTasks,
                'active_tasks_count' => count($activeTasks),
                'overdue_tasks_count' => count($overdueTasks),
                'completed_tasks_count' => $completedTasks,
                'upcoming_deadlines_count' => count($upcomingDeadlines),
            ];

            $reportData['contracts'][] = $contractData;
            if (in_array($contract['status'] ?? 'planning', ['active', 'planning'])) {
                $reportData['summary']['active_contracts']++;
            }
            $reportData['summary']['total_tasks'] += $totalTasks;
            $reportData['summary']['overdue_tasks'] += count($overdueTasks);
        }

        return $reportData;
    }

    /**
     * Get Resources Report data
     * 
     * @param array $filters ['region_id' => int|null, 'date_from' => string, 'date_to' => string]
     * @return array Report data with users, workload, performance
     */
    public function getResourcesReport(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $filters['date_to'] ?? date('Y-m-d');
        $regionId = $filters['region_id'] ?? null;

        $db = \Config\Database::connect();

        // Get users
        $usersQuery = $db->table('users u')
            ->select('u.*, r.name as region_name')
            ->join('regions r', 'r.id = u.region_id', 'left')
            ->where('u.active', 1);

        if ($regionId) {
            $usersQuery->where('u.region_id', $regionId);
        }

        $users = $usersQuery->get()->getResultArray();

        $reportData = [
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'users' => [],
            'summary' => [
                'total_users' => count($users),
                'total_tasks_created' => 0,
                'total_tasks_completed' => 0,
            ],
        ];

        foreach ($users as $user) {
            // Get tasks created by this user
            $tasksCreated = $db->table('tasks')
                ->where('created_by', $user['id'])
                ->where('created_at >=', $dateFrom . ' 00:00:00')
                ->where('created_at <=', $dateTo . ' 23:59:59')
                ->countAllResults();

            // Get tasks completed by this user
            $tasksCompleted = $db->table('tasks')
                ->where('created_by', $user['id'])
                ->where('status', 'completed')
                ->where('updated_at >=', $dateFrom . ' 00:00:00')
                ->where('updated_at <=', $dateTo . ' 23:59:59')
                ->countAllResults();

            // Get active tasks assigned (including created by user)
            $activeTasks = $db->table('tasks t')
                ->groupStart()
                ->where('t.created_by', $user['id'])
                ->orWhereIn('t.id', function ($builder) use ($user) {
                    return $builder->select('task_id')
                        ->from('task_assignees')
                        ->where('user_id', $user['id']);
                })
                ->groupEnd()
                ->where('t.status !=', 'completed')
                ->countAllResults(false);

            // Calculate workload percentage (assuming 15 tasks = 100%)
            $maxTasks = 15;
            $workloadPercentage = min(100, round(($activeTasks / $maxTasks) * 100));

            $userData = [
                'id' => $user['id'],
                'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
                'email' => $user['email'],
                'role' => $user['role'],
                'region_name' => $user['region_name'] ?? null,
                'tasks_created' => $tasksCreated,
                'tasks_completed' => $tasksCompleted,
                'active_tasks' => $activeTasks,
                'workload_percentage' => $workloadPercentage,
            ];

            $reportData['users'][] = $userData;
            $reportData['summary']['total_tasks_created'] += $tasksCreated;
            $reportData['summary']['total_tasks_completed'] += $tasksCompleted;
        }

        // Sort by tasks completed (top performers)
        usort($reportData['users'], fn($a, $b) => $b['tasks_completed'] <=> $a['tasks_completed']);

        return $reportData;
    }

    /**
     * Get Critical Tasks Report data
     * 
     * @param array $filters ['region_id' => int|null, 'date_from' => string, 'date_to' => string]
     * @return array Report data with critical and overdue tasks
     */
    public function getCriticalTasksReport(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $filters['date_to'] ?? date('Y-m-d');
        $regionId = $filters['region_id'] ?? null;

        $db = \Config\Database::connect();

        // Get blocked tasks
        $blockedTasks = $db->table('tasks t')
            ->select('t.*, 
                sd.id as subdivision_id_full, sd.name as subdivision_name,
                c.id as contract_id, c.name as contract_name, c.contract_number,
                r.id as region_id, r.name as region_name')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'left')
            ->join('contracts c', 'c.id = sd.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->where('t.status', 'blocked')
            ->where('t.updated_at >=', $dateFrom . ' 00:00:00')
            ->where('t.updated_at <=', $dateTo . ' 23:59:59');

        if ($regionId) {
            $blockedTasks->where('r.id', $regionId);
        }

        $blockedTasks = $blockedTasks->get()->getResultArray();

        // Get overdue tasks (all overdue, not just > 7 days - consistent with dashboard)
        $overdueTasks = $db->table('tasks t')
            ->select('t.*, 
                sd.id as subdivision_id_full, sd.name as subdivision_name,
                c.id as contract_id, c.name as contract_name, c.contract_number,
                r.id as region_id, r.name as region_name')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'left')
            ->join('contracts c', 'c.id = sd.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->where('t.status !=', 'completed')
            ->where('t.deadline IS NOT NULL', null, false)
            ->where('t.deadline <', date('Y-m-d H:i:s'));

        if ($regionId) {
            $overdueTasks->where('r.id', $regionId);
        }

        $overdueTasks = $overdueTasks->get()->getResultArray();

        // Get critical priority tasks
        $criticalTasks = $db->table('tasks t')
            ->select('t.*, 
                sd.id as subdivision_id_full, sd.name as subdivision_name,
                c.id as contract_id, c.name as contract_name, c.contract_number,
                r.id as region_id, r.name as region_name')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'left')
            ->join('contracts c', 'c.id = sd.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->where('t.priority', 'critical')
            ->where('t.status !=', 'completed')
            ->where('t.created_at >=', $dateFrom . ' 00:00:00')
            ->where('t.created_at <=', $dateTo . ' 23:59:59');

        if ($regionId) {
            $criticalTasks->where('r.id', $regionId);
        }

        $criticalTasks = $criticalTasks->orderBy('t.deadline', 'ASC')
            ->limit(20)
            ->get()
            ->getResultArray();

        // Add days overdue/blocked info
        foreach ($overdueTasks as &$task) {
            if (!empty($task['deadline'])) {
                $daysOverdue = floor((time() - strtotime($task['deadline'])) / 86400);
                $task['days_overdue'] = $daysOverdue;
            }
        }

        foreach ($blockedTasks as &$task) {
            if (!empty($task['updated_at'])) {
                $daysBlocked = floor((time() - strtotime($task['updated_at'])) / 86400);
                $task['days_blocked'] = $daysBlocked;
            }
        }

        return [
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'blocked_tasks' => $blockedTasks,
            'overdue_tasks' => $overdueTasks,
            'critical_tasks' => $criticalTasks,
            'summary' => [
                'total_blocked' => count($blockedTasks),
                'total_overdue' => count($overdueTasks),
                'total_critical' => count($criticalTasks),
            ],
        ];
    }
}
