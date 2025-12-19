<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\TaskModel;
use App\Models\ContractModel;
use App\Models\RegionModel;
use App\Models\SubdivisionModel;
use App\Models\DepartmentModel;
use App\Models\DepartmentHeadModel;

/**
 * DashboardService
 * 
 * Service responsible for dashboard data aggregation, KPIs calculation,
 * and role-based data filtering with caching support.
 */
class DashboardService
{
    protected UserModel $userModel;
    protected TaskModel $taskModel;
    protected ContractModel $contractModel;
    protected RegionModel $regionModel;
    protected SubdivisionModel $subdivisionModel;
    protected DepartmentModel $departmentModel;
    protected DepartmentHeadModel $departmentHeadModel;
    protected DepartmentHeadService $departmentHeadService;
    protected $cache;
    protected int $cacheTTL = 600; // 10 minutes in seconds

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->taskModel = new TaskModel();
        $this->contractModel = new ContractModel();
        $this->regionModel = new RegionModel();
        $this->subdivisionModel = new SubdivisionModel();
        $this->departmentModel = new DepartmentModel();
        $this->departmentHeadModel = new DepartmentHeadModel();
        $this->departmentHeadService = new DepartmentHeadService();
        $this->cache = \Config\Services::cache();
    }

    /**
     * Get KPIs for a user based on their role
     * 
     * @param int $userId User ID
     * @param string $role User role
     * @param int|null $regionId User's region ID (null for super users)
     * @return array KPIs with cached support
     */
    public function getKPIs(int $userId, string $role, ?int $regionId = null): array
    {
        $cacheKey = "dashboard_kpis_{$role}_{$userId}_" . ($regionId ?? 'all');

        // Try to get from cache
        // $cached = $this->cache->get($cacheKey);
        // if ($cached !== null) {
        //     return $cached;
        // }

        $kpis = [];

        switch ($role) {
            case 'admin':
            case 'auditor':
                $kpis = $this->getGlobalKPIs();
                break;

            case 'director':
                // Director must have region_id - use region-specific KPIs
                if ($regionId) {
                    $kpis = $this->getRegionKPIs($regionId);
                } else {
                    // If region_id is missing, return empty KPIs
                    $kpis = [];
                }
                break;

            case 'manager':
                $kpis = $this->getRegionKPIs($regionId);
                break;

            case 'executant':
                $kpis = $this->getPersonalKPIs($userId);
                break;
        }

        // Check if user is department head and merge KPIs
        if ($this->departmentHeadService->isDepartmentHead($userId)) {
            $deptHeadKPIs = $this->getDepartmentKPIs($userId);

            // Merge KPIs (combine counts)
            if (!empty($deptHeadKPIs)) {
                if (empty($kpis)) {
                    $kpis = $deptHeadKPIs;
                } else {
                    // Merge: add department head KPIs to existing ones
                    $kpis['active_tasks'] = ($kpis['active_tasks'] ?? 0) + ($deptHeadKPIs['active_tasks'] ?? 0);
                    $kpis['overdue_tasks'] = ($kpis['overdue_tasks'] ?? 0) + ($deptHeadKPIs['overdue_tasks'] ?? 0);
                    $kpis['tasks_in_review'] = ($kpis['tasks_in_review'] ?? 0) + ($deptHeadKPIs['tasks_in_review'] ?? 0);
                    // Keep department-specific fields
                    if (isset($deptHeadKPIs['executants_count'])) {
                        $kpis['executants_count'] = $deptHeadKPIs['executants_count'];
                    }
                }
            }
        }

        // Cache for 20 minutes
        $this->cache->save($cacheKey, $kpis, $this->cacheTTL);

        return $kpis;
    }

    /**
     * Get global KPIs (Admin, Director, Auditor)
     */
    protected function getGlobalKPIs(): array
    {
        $db = \Config\Database::connect();

        // Active tasks (not completed)
        $activeTasks = $db->table('tasks')
            ->where('status !=', 'completed')
            ->countAllResults(false);

        // Overdue tasks
        $overdueTasks = $db->table('tasks')
            ->where('deadline <', date('Y-m-d H:i:s'))
            ->where('status !=', 'completed')
            ->countAllResults(false);

        // Active contracts
        $activeContracts = $db->table('contracts')
            ->where('status', 'active')
            ->countAllResults(false);

        // Active users
        $activeUsers = $db->table('users')
            ->where('active', 1)
            ->countAllResults(false);

        // Calculate completion rate (last 30 days)
        $startDate = date('Y-m-d H:i:s', strtotime('-30 days'));
        $completedLastMonth = $db->table('tasks')
            ->where('status', 'completed')
            ->where('updated_at >=', $startDate)
            ->countAllResults(false);

        $totalTasksLastMonth = $db->table('tasks')
            ->where('created_at >=', $startDate)
            ->countAllResults(false);

        $completionRate = $totalTasksLastMonth > 0
            ? round(($completedLastMonth / $totalTasksLastMonth) * 100, 1)
            : 0;

        return [
            'active_tasks' => $activeTasks,
            'overdue_tasks' => $overdueTasks,
            'active_contracts' => $activeContracts,
            'active_users' => $activeUsers,
            'completion_rate' => $completionRate,
            'completed_last_month' => $completedLastMonth,
        ];
    }

    /**
     * Get region-specific KPIs (Manager)
     */
    protected function getRegionKPIs(int $regionId): array
    {
        $db = \Config\Database::connect();

        // Active tasks in region (through contracts)
        $activeTasks = $db->table('tasks t')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'inner')
            ->join('contracts c', 'c.id = sd.contract_id', 'inner')
            ->where('c.region_id', $regionId)
            ->where('t.status !=', 'completed')
            ->countAllResults(false);

        // Overdue tasks in region
        $overdueTasks = $db->table('tasks t')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'inner')
            ->join('contracts c', 'c.id = sd.contract_id', 'inner')
            ->where('c.region_id', $regionId)
            ->where('t.deadline <', date('Y-m-d H:i:s'))
            ->where('t.status !=', 'completed')
            ->countAllResults(false);

        // Active contracts in region
        $activeContracts = $db->table('contracts')
            ->where('region_id', $regionId)
            ->where('status', 'active')
            ->countAllResults(false);

        // Tasks in review status (for approval)
        $tasksInReview = $db->table('tasks t')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'inner')
            ->join('contracts c', 'c.id = sd.contract_id', 'inner')
            ->where('c.region_id', $regionId)
            ->where('t.status', 'review')
            ->countAllResults(false);

        return [
            'active_tasks' => $activeTasks,
            'overdue_tasks' => $overdueTasks,
            'active_contracts' => $activeContracts,
            'tasks_in_review' => $tasksInReview,
        ];
    }

    /**
     * Get personal KPIs (Executant)
     */
    protected function getPersonalKPIs(int $userId): array
    {
        // Get all tasks for user (created or assigned)
        $allTasks = $this->taskModel->getMyTasksWithDetails($userId);

        $activeTasks = 0;
        $overdueTasks = 0;
        $completedLastMonth = 0;
        $tasksInReview = 0;

        $startDate = strtotime('-30 days');

        foreach ($allTasks as $task) {
            // Active tasks (not completed)
            if ($task['status'] !== 'completed') {
                $activeTasks++;

                // Overdue tasks
                if (!empty($task['deadline']) && strtotime($task['deadline']) < time()) {
                    $overdueTasks++;
                }

                // Tasks in review
                if ($task['status'] === 'review') {
                    $tasksInReview++;
                }
            }

            // Completed last month
            if ($task['status'] === 'completed' && !empty($task['updated_at'])) {
                if (strtotime($task['updated_at']) >= $startDate) {
                    $completedLastMonth++;
                }
            }
        }

        return [
            'active_tasks' => $activeTasks,
            'overdue_tasks' => $overdueTasks,
            'completed_last_month' => $completedLastMonth,
            'tasks_in_review' => $tasksInReview,
        ];
    }

    /**
     * Get department KPIs for department head
     * 
     * @param int $departmentHeadId Department head user ID
     * @return array KPIs for department
     */
    protected function getDepartmentKPIs(int $departmentHeadId): array
    {
        // Get department head assignments
        $assignments = $this->departmentHeadService->getDepartmentsForUser($departmentHeadId);

        if (empty($assignments)) {
            return [];
        }

        $db = \Config\Database::connect();

        // Get tasks from all departments where user is department head
        $departmentIds = array_unique(array_column($assignments, 'department_id'));
        $regionIds = array_unique(array_column($assignments, 'region_id'));

        // Active tasks in department (from departments' regions)
        $activeTasks = $db->table('tasks t')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'inner')
            ->join('contracts c', 'c.id = sd.contract_id', 'inner')
            ->join('task_departments td', 'td.task_id = t.id', 'inner')
            ->whereIn('td.department_id', $departmentIds)
            ->whereIn('c.region_id', $regionIds)
            ->where('t.status !=', 'completed')
            ->groupBy('t.id')
            ->countAllResults(false);

        // Overdue tasks in department
        $overdueTasks = $db->table('tasks t')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'inner')
            ->join('contracts c', 'c.id = sd.contract_id', 'inner')
            ->join('task_departments td', 'td.task_id = t.id', 'inner')
            ->whereIn('td.department_id', $departmentIds)
            ->whereIn('c.region_id', $regionIds)
            ->where('t.deadline <', date('Y-m-d H:i:s'))
            ->where('t.deadline IS NOT NULL', null, false)
            ->where('t.status !=', 'completed')
            ->groupBy('t.id')
            ->countAllResults(false);

        // Tasks in review status
        $tasksInReview = $db->table('tasks t')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'inner')
            ->join('contracts c', 'c.id = sd.contract_id', 'inner')
            ->join('task_departments td', 'td.task_id = t.id', 'inner')
            ->whereIn('td.department_id', $departmentIds)
            ->whereIn('c.region_id', $regionIds)
            ->where('t.status', 'review')
            ->groupBy('t.id')
            ->countAllResults(false);

        // Get executants count from department head's departments
        $executants = $this->departmentHeadService->getViewableExecutants($departmentHeadId);
        $executantsCount = count($executants);

        return [
            'active_tasks' => $activeTasks,
            'overdue_tasks' => $overdueTasks,
            'tasks_in_review' => $tasksInReview,
            'executants_count' => $executantsCount,
        ];
    }

    /**
     * Get regions for dashboard based on user role
     * 
     * @param int $userId User ID
     * @param string $role User role
     * @param int|null $regionId User's region ID
     * @return array Regions with task counts
     */
    public function getRegionsForDashboard(int $userId, string $role, ?int $regionId = null): array
    {
        $cacheKey = "dashboard_regions_{$role}_{$userId}_" . ($regionId ?? 'all');

        // Try cache
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $regions = [];

        if ($role === 'admin' || $role === 'auditor') {
            // All regions
            $regions = $this->regionModel->getAllRegionsWithDetails();
        } elseif ($role === 'director' && $regionId) {
            // Director sees only his region (region_id is required)
            $region = $this->regionModel->find($regionId);
            if ($region) {
                $regions = [$this->enrichRegionWithCounts($region)];
            } else {
                $regions = [];
            }
        } elseif ($role === 'manager' && $regionId) {
            // Single region
            $region = $this->regionModel->find($regionId);
            if ($region) {
                $regions = [$this->enrichRegionWithCounts($region)];
            }
        }

        // Add task counts for each region
        foreach ($regions as &$region) {
            $region['tasks_count'] = $this->getTaskCountForRegion($region['id']);
            $region['active_tasks_count'] = $this->getActiveTaskCountForRegion($region['id']);
        }

        // Cache for 20 minutes
        $this->cache->save($cacheKey, $regions, $this->cacheTTL);

        return $regions;
    }

    /**
     * Get contracts for dashboard based on user role
     */
    public function getContractsForDashboard(int $userId, string $role, ?int $regionId = null): array
    {
        $contracts = [];

        if ($role === 'admin' || $role === 'auditor') {
            $contracts = $this->contractModel->getAllContractsWithDetails();
        } elseif ($role === 'director' && $regionId) {
            // Director sees only contracts from his region (region_id is required)
            $contracts = $this->contractModel->getContractsForRegionWithDetails($regionId);
        } elseif ($role === 'manager') {
            // Manager sees contracts assigned to him (via manager_id)
            $contracts = $this->contractModel->getContractsForManagerWithDetails($userId);
        }

        // Add active task counts for each contract
        foreach ($contracts as &$contract) {
            $contract['active_tasks_count'] = $this->getActiveTaskCountForContract($contract['id']);
        }

        return $contracts;
    }

    /**
     * Get active task count for a contract
     */
    protected function getActiveTaskCountForContract(int $contractId): int
    {
        $db = \Config\Database::connect();

        return $db->table('tasks t')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'inner')
            ->where('sd.contract_id', $contractId)
            ->where('t.status !=', 'completed')
            ->countAllResults(false);
    }

    /**
     * Get task count for a region
     */
    protected function getTaskCountForRegion(int $regionId): int
    {
        $db = \Config\Database::connect();

        return $db->table('tasks t')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'inner')
            ->join('contracts c', 'c.id = sd.contract_id', 'inner')
            ->where('c.region_id', $regionId)
            ->countAllResults(false);
    }

    /**
     * Get active task count for a region
     */
    protected function getActiveTaskCountForRegion(int $regionId): int
    {
        $db = \Config\Database::connect();

        return $db->table('tasks t')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'inner')
            ->join('contracts c', 'c.id = sd.contract_id', 'inner')
            ->where('c.region_id', $regionId)
            ->where('t.status !=', 'completed')
            ->countAllResults(false);
    }

    /**
     * Enrich region with counts
     */
    protected function enrichRegionWithCounts(array $region): array
    {
        $manager = $this->regionModel->getManager($region['id']);

        $region['users_count'] = count($this->regionModel->getUsers($region['id']));
        $region['contracts_count'] = count($this->regionModel->getContracts($region['id']));

        if ($manager) {
            $region['manager_name'] = trim(($manager['first_name'] ?? '') . ' ' . ($manager['last_name'] ?? ''));
            $region['manager_first_name'] = $manager['first_name'] ?? null;
            $region['manager_last_name'] = $manager['last_name'] ?? null;
            $region['manager_email'] = $manager['email'] ?? null;
        } else {
            $region['manager_name'] = null;
        }

        return $region;
    }

    /**
     * Get task statistics for chart (tasks per region)
     * 
     * @param int $userId User ID
     * @param string $role User role
     * @param int|null $regionId User's region ID
     * @param string $period Period filter (7days, 30days, 3months, 6months, year, all)
     * @return array Chart data
     */
    public function getTasksPerRegionChart(int $userId, string $role, ?int $regionId = null, string $period = '30days'): array
    {
        $cacheKey = "dashboard_chart_tasks_region_{$role}_{$userId}_" . ($regionId ?? 'all') . "_{$period}";

        // Try cache
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $dateFilter = $this->getDateFilterForPeriod($period);

        $db = \Config\Database::connect();

        $query = $db->table('regions r')
            ->select('r.id, r.name, COUNT(DISTINCT t.id) as tasks_count')
            ->join('contracts c', 'c.region_id = r.id', 'left')
            ->join('subdivisions sd', 'sd.contract_id = c.id', 'left')
            ->join('tasks t', 't.subdivision_id = sd.id', 'left')
            ->groupBy('r.id', 'r.name');

        // Apply role-based filtering
        if ($role === 'manager') {
            // Manager sees tasks from contracts assigned to him (via manager_id)
            $query->where('c.manager_id', $userId);
        } elseif ($role === 'director' && $regionId) {
            // Director sees tasks from contracts in his region
            $query->where('r.id', $regionId);
        }

        // Apply date filter if needed
        if ($dateFilter) {
            $query->where('t.created_at >=', $dateFilter);
        }

        $results = $query->get()->getResultArray();

        $labels = [];
        $data = [];

        foreach ($results as $row) {
            $labels[] = $row['name'];
            $data[] = (int)$row['tasks_count'];
        }

        $chartData = [
            'labels' => $labels,
            'data' => $data,
        ];

        // Cache for 20 minutes
        $this->cache->save($cacheKey, $chartData, $this->cacheTTL);

        return $chartData;
    }

    /**
     * Get date filter for period
     */
    protected function getDateFilterForPeriod(string $period): ?string
    {
        switch ($period) {
            case '7days':
                return date('Y-m-d H:i:s', strtotime('-7 days'));
            case '30days':
                return date('Y-m-d H:i:s', strtotime('-30 days'));
            case '3months':
                return date('Y-m-d H:i:s', strtotime('-3 months'));
            case '6months':
                return date('Y-m-d H:i:s', strtotime('-6 months'));
            case 'year':
                return date('Y-m-d H:i:s', strtotime('-1 year'));
            case 'all':
            default:
                return null;
        }
    }

    /**
     * Get team workload statistics
     * 
     * @param int $userId User ID
     * @param string $role User role
     * @param int|null $regionId User's region ID
     * @return array Team workload data
     */
    public function getTeamWorkload(int $userId, string $role, ?int $regionId = null): array
    {
        $cacheKey = "dashboard_workload_{$role}_{$userId}_" . ($regionId ?? 'all');

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $db = \Config\Database::connect();

        // Get users based on role
        if ($role === 'admin' || $role === 'auditor') {
            // All active users
            $users = $db->table('users')
                ->where('active', 1)
                ->where('role_level', '>=', 20) // Executant and above
                ->get()
                ->getResultArray();
        } elseif ($role === 'director' && $regionId) {
            // Director sees users from his region + executants with region_id NULL
            $users = $db->table('users')
                ->groupStart()
                ->where('region_id', $regionId)
                ->orWhere('region_id IS NULL') // Allow executants with region_id NULL
                ->groupEnd()
                ->where('active', 1)
                ->where('role_level', '>=', 20) // Executant and above
                ->get()
                ->getResultArray();
        } elseif ($role === 'manager') {
            // Manager sees executants from regions of contracts assigned to him
            // Get contracts assigned to manager
            $contracts = $db->table('contracts')
                ->where('manager_id', $userId)
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

            // Get users from those regions + executants with region_id NULL
            $users = $db->table('users')
                ->groupStart()
                ->whereIn('region_id', $regionIds)
                ->orWhere('region_id IS NULL') // Allow executants with region_id NULL
                ->groupEnd()
                ->where('active', 1)
                ->where('role_level', '>=', 20) // Executant and above
                ->get()
                ->getResultArray();
        } else {
            return [];
        }

        $workload = [];

        foreach ($users as $user) {
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
            ];
        }

        // Sort by workload percentage descending
        usort($workload, function ($a, $b) {
            return $b['workload_percentage'] - $a['workload_percentage'];
        });

        // Limit to top 10
        $workload = array_slice($workload, 0, 10);

        $this->cache->save($cacheKey, $workload, $this->cacheTTL);

        return $workload;
    }

    /**
     * Get critical blockers and overdue tasks
     * 
     * @param int $userId User ID
     * @param string $role User role
     * @param int|null $regionId User's region ID
     * @param int $limit Limit results
     * @return array Critical tasks
     */
    public function getCriticalBlockers(int $userId, string $role, ?int $regionId = null, int $limit = 10): array
    {
        $cacheKey = "dashboard_blockers_{$role}_{$userId}_" . ($regionId ?? 'all') . "_{$limit}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $db = \Config\Database::connect();

        $query = $db->table('tasks t')
            ->select('t.id, t.title, t.status, t.priority, t.deadline, 
                     c.name as contract_name, c.contract_number,
                     r.name as region_name,
                     sd.name as subdivision_name')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'left')
            ->join('contracts c', 'c.id = sd.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->where('t.status !=', 'completed')
            ->groupStart()
            ->where('t.status', 'blocked')
            ->orGroupStart()
            ->where('t.deadline <', date('Y-m-d H:i:s'))
            ->where('t.deadline IS NOT NULL', null, false)
            ->groupEnd()
            ->groupEnd()
            ->orderBy('t.priority', 'DESC')
            ->orderBy('t.deadline', 'ASC')
            ->limit($limit);

        // Apply role-based filtering
        if ($role === 'manager') {
            // Manager sees tasks from contracts assigned to him (via manager_id)
            $query->where('c.manager_id', $userId);
        } elseif ($role === 'director' && $regionId) {
            // Director sees tasks from contracts in his region
            $query->where('r.id', $regionId);
        } elseif ($role === 'executant') {
            $query->groupStart()
                ->where('t.created_by', $userId)
                ->orWhereIn('t.id', function ($builder) use ($userId) {
                    return $builder->select('task_id')
                        ->from('task_assignees')
                        ->where('user_id', $userId);
                })
                ->groupEnd();
        }

        $tasks = $query->get()->getResultArray();

        foreach ($tasks as &$task) {
            // Calculate days overdue or blocked
            if ($task['status'] === 'blocked') {
                $task['days_info'] = 'Blocat';
            } elseif (!empty($task['deadline']) && strtotime($task['deadline']) < time()) {
                $daysOverdue = floor((time() - strtotime($task['deadline'])) / 86400);
                $task['days_info'] = "Întârziat ({$daysOverdue}z)";
            } else {
                $task['days_info'] = null;
            }
        }

        $this->cache->save($cacheKey, $tasks, $this->cacheTTL);

        return $tasks;
    }

    /**
     * Get upcoming deadlines
     * 
     * @param int $userId User ID
     * @param string $role User role
     * @param int|null $regionId User's region ID
     * @param int $days Number of days ahead
     * @return array Upcoming deadlines
     */
    public function getUpcomingDeadlines(int $userId, string $role, ?int $regionId = null, int $days = 5): array
    {
        $cacheKey = "dashboard_deadlines_{$role}_{$userId}_" . ($regionId ?? 'all') . "_{$days}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $db = \Config\Database::connect();

        $endDate = date('Y-m-d H:i:s', strtotime("+{$days} days"));

        $query = $db->table('tasks t')
            ->select('t.id, t.title, t.status, t.priority, t.deadline,
                     c.name as contract_name, c.contract_number,
                     r.name as region_name')
            ->join('subdivisions sd', 'sd.id = t.subdivision_id', 'left')
            ->join('contracts c', 'c.id = sd.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->where('t.status !=', 'completed')
            ->where('t.deadline >=', date('Y-m-d H:i:s'))
            ->where('t.deadline <=', $endDate)
            ->where('t.deadline IS NOT NULL', null, false)
            ->orderBy('t.deadline', 'ASC')
            ->limit(10);

        // Apply role-based filtering
        if ($role === 'manager') {
            // Manager sees tasks from contracts assigned to him (via manager_id)
            $query->where('c.manager_id', $userId);
        } elseif ($role === 'director' && $regionId) {
            // Director sees tasks from contracts in his region
            $query->where('r.id', $regionId);
        } elseif ($role === 'executant') {
            $query->groupStart()
                ->where('t.created_by', $userId)
                ->orWhereIn('t.id', function ($builder) use ($userId) {
                    return $builder->select('task_id')
                        ->from('task_assignees')
                        ->where('user_id', $userId);
                })
                ->groupEnd();
        }

        $tasks = $query->get()->getResultArray();

        $this->cache->save($cacheKey, $tasks, $this->cacheTTL);

        return $tasks;
    }

    /**
     * Get region data for drill-down view
     */
    public function getRegionData(int $regionId): ?array
    {
        $region = $this->regionModel->find($regionId);
        if (!$region) {
            return null;
        }

        $region = $this->enrichRegionWithCounts($region);
        $region['contracts'] = $this->contractModel->getContractsForRegionWithDetails($regionId);

        return $region;
    }

    /**
     * Get departments with active tasks for a region
     * Returns departments that have at least one active task in the region
     * 
     * @param int $regionId Region ID
     * @return array Departments with task counts and department head info
     */
    public function getDepartmentsWithActiveTasksForRegion(int $regionId): array
    {
        $cacheKey = "dashboard_departments_region_{$regionId}";

        // Try to get from cache
        // $cached = $this->cache->get($cacheKey);
        // if ($cached !== null) {
        //     return $cached;
        // }

        $db = \Config\Database::connect();

        // Get departments that have active tasks in this region
        $departments = $db->table('departments d')
            ->select('d.id, d.name, d.color_code, 
                     COUNT(DISTINCT CASE WHEN t.status != "completed" THEN t.id END) as active_tasks_count,
                     COUNT(DISTINCT CASE WHEN t.deadline < NOW() AND t.status != "completed" THEN t.id END) as overdue_tasks_count')
            ->join('task_departments td', 'td.department_id = d.id', 'inner')
            ->join('tasks t', 't.id = td.task_id', 'inner')
            ->join('subdivisions s', 's.id = t.subdivision_id', 'inner')
            ->join('contracts c', 'c.id = s.contract_id', 'inner')
            ->where('c.region_id', $regionId)
            ->groupBy('d.id', 'd.name', 'd.color_code')
            ->having('active_tasks_count >', 0)
            ->orderBy('d.name', 'ASC')
            ->get()
            ->getResultArray();

        // Add department head information for each department
        foreach ($departments as &$department) {
            $departmentHead = $this->departmentHeadModel->getDepartmentHead($department['id'], $regionId);

            if ($departmentHead) {
                $department['head'] = [
                    'user_id' => $departmentHead['user_id'],
                    'first_name' => $departmentHead['first_name'] ?? '',
                    'last_name' => $departmentHead['last_name'] ?? '',
                    'email' => $departmentHead['email'] ?? '',
                    'full_name' => trim(($departmentHead['first_name'] ?? '') . ' ' . ($departmentHead['last_name'] ?? '')) ?: $departmentHead['email'],
                ];
            } else {
                $department['head'] = null;
            }

            // Convert counts to integers
            $department['active_tasks_count'] = (int)$department['active_tasks_count'];
            $department['overdue_tasks_count'] = (int)$department['overdue_tasks_count'];
        }

        // Cache for 20 minutes
        $this->cache->save($cacheKey, $departments, $this->cacheTTL);

        return $departments;
    }

    /**
     * Get contract data for drill-down view
     */
    public function getContractData(int $contractId): ?array
    {
        $contract = $this->contractModel->find($contractId);
        if (!$contract) {
            return null;
        }

        // Get region info
        $region = $this->regionModel->find($contract['region_id']);
        $contract['region'] = $region;

        // Get subdivisions
        $contract['subdivisions'] = $this->subdivisionModel->getAllSubdivisionsWithDetails();
        $contract['subdivisions'] = array_filter($contract['subdivisions'], function ($sub) use ($contractId) {
            return $sub['contract_id'] == $contractId;
        });
        $contract['subdivisions'] = array_values($contract['subdivisions']);

        // Add task counts
        foreach ($contract['subdivisions'] as &$subdivision) {
            $subdivision['tasks_count'] = count($this->subdivisionModel->getTasks($subdivision['id']));
        }

        return $contract;
    }

    /**
     * Get department data with tasks for drill-down view
     * Returns tasks from a specific department in a specific region
     * 
     * @param int $departmentId Department ID
     * @param int $regionId Region ID
     * @return array|null Department data with tasks or null if not found
     */
    public function getDepartmentDataForRegion(int $departmentId, int $regionId): ?array
    {
        $department = $this->departmentModel->find($departmentId);
        if (!$department) {
            return null;
        }

        // Get region info
        $region = $this->regionModel->find($regionId);
        if (!$region) {
            return null;
        }

        $department['region'] = $region;

        // Get department head
        $departmentHead = $this->departmentHeadModel->getDepartmentHead($departmentId, $regionId);
        if ($departmentHead) {
            $department['head'] = [
                'user_id' => $departmentHead['user_id'],
                'first_name' => $departmentHead['first_name'] ?? '',
                'last_name' => $departmentHead['last_name'] ?? '',
                'email' => $departmentHead['email'] ?? '',
                'full_name' => trim(($departmentHead['first_name'] ?? '') . ' ' . ($departmentHead['last_name'] ?? '')) ?: $departmentHead['email'],
            ];
        } else {
            $department['head'] = null;
        }

        // Get tasks for this department in this region
        $tasks = $this->taskModel->getTasksForDepartmentHeadWithDetails($departmentId, $regionId);

        // Filter to only active tasks (not completed)
        $department['tasks'] = array_filter($tasks, function ($task) {
            return ($task['status'] ?? '') !== 'completed';
        });
        $department['tasks'] = array_values($department['tasks']);

        // Add task counts
        $department['tasks_count'] = count($department['tasks']);
        $department['overdue_tasks_count'] = count(array_filter($department['tasks'], function ($task) {
            $deadline = $task['deadline'] ?? null;
            if (!$deadline) return false;
            return strtotime($deadline) < time() && ($task['status'] ?? '') !== 'completed';
        }));

        return $department;
    }

    /**
     * Get subdivision data with tasks for drill-down view
     */
    public function getSubdivisionData(int $subdivisionId): ?array
    {
        $subdivision = $this->subdivisionModel->find($subdivisionId);
        if (!$subdivision) {
            return null;
        }

        // Get contract and region info
        $contract = $this->contractModel->find($subdivision['contract_id']);
        $subdivision['contract'] = $contract;

        if ($contract) {
            $region = $this->regionModel->find($contract['region_id']);
            $subdivision['region'] = $region;
        }

        // Get tasks
        $tasks = $this->taskModel->getAllTasksWithDetails();
        $subdivision['tasks'] = array_filter($tasks, function ($task) use ($subdivisionId) {
            return isset($task['subdivision_id_full']) && $task['subdivision_id_full'] == $subdivisionId;
        });
        $subdivision['tasks'] = array_values($subdivision['tasks']);

        return $subdivision;
    }

    /**
     * Clear dashboard cache for a user
     */
    public function clearCache(int $userId, string $role, ?int $regionId = null): void
    {
        $suffix = "_{$role}_{$userId}_" . ($regionId ?? 'all');

        $keys = [
            "dashboard_kpis{$suffix}",
            "dashboard_regions{$suffix}",
            "dashboard_workload{$suffix}",
            "dashboard_blockers{$suffix}_10",
            "dashboard_deadlines{$suffix}_5",
            "dashboard_chart_tasks_region{$suffix}_30days",
            "dashboard_chart_tasks_region{$suffix}_7days",
            "dashboard_chart_tasks_region{$suffix}_3months",
            "dashboard_chart_tasks_region{$suffix}_6months",
            "dashboard_chart_tasks_region{$suffix}_year",
            "dashboard_chart_tasks_region{$suffix}_all",
        ];

        foreach ($keys as $key) {
            $this->cache->delete($key);
        }
    }
}
