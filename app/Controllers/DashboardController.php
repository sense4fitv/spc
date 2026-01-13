<?php

namespace App\Controllers;

use App\Services\NotificationService;
use App\Services\DashboardService;
use App\Services\DepartmentHeadService;

class DashboardController extends BaseController
{
    protected DashboardService $dashboardService;
    protected NotificationService $notificationService;
    protected DepartmentHeadService $departmentHeadService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
        $this->notificationService = new NotificationService();
        $this->departmentHeadService = new DepartmentHeadService();
    }

    /**
     * Main dashboard view (role-based)
     */
    public function index()
    {

        if ($this->request->getGet('send_global_notification') == 1) {
            $this->testGlobalNotification('La multi ani, Romania! 🇷🇴');
        }

        $session = service('session');
        $role = $session->get('role');
        $userId = $session->get('user_id');
        $roleLevel = $session->get('role_level');
        $regionId = $session->get('region_id');

        log_message('debug', "DashboardController::index - User ID: {$userId}, Role: {$role}, RoleLevel: {$roleLevel}, RegionID: {$regionId}");

        // AuthFilter already handles authentication - if we're here, user is authenticated
        // No need for redundant checks that could cause redirect loops

        // Get KPIs
        $kpis = $this->dashboardService->getKPIs($userId, $role, $regionId);

        // Get regions for drill-down (if applicable)
        $regions = $this->dashboardService->getRegionsForDashboard($userId, $role, $regionId);

        // Get chart data
        $chartData = $this->dashboardService->getTasksPerRegionChart($userId, $role, $regionId, '30days');

        // Get team workload (if applicable)
        $teamWorkload = [];
        if (in_array($role, ['admin', 'director', 'manager', 'auditor'])) {
            $teamWorkload = $this->dashboardService->getTeamWorkload($userId, $role, $regionId);
        }

        // Get critical blockers
        $criticalBlockers = $this->dashboardService->getCriticalBlockers($userId, $role, $regionId, 5);

        // Get upcoming deadlines
        $upcomingDeadlines = $this->dashboardService->getUpcomingDeadlines($userId, $role, $regionId, 5);

        // For executant, get personal tasks
        $personalTasks = [];
        if ($role === 'executant') {
            $taskModel = new \App\Models\TaskModel();
            $personalTasks = $taskModel->getMyTasksWithDetails($userId);
        }

        // For manager, get contracts directly (skip region level)
        $contracts = [];
        if ($role === 'manager' && $regionId) {
            $contracts = $this->dashboardService->getContractsForDashboard($userId, $role, $regionId);
        }

        // Check if user is department head
        $isDepartmentHead = $this->departmentHeadService->isDepartmentHead($userId);
        $departmentHeadAssignments = [];
        $departmentHeadTasks = [];
        $departmentHeadExecutants = [];

        if ($isDepartmentHead) {
            $departmentHeadAssignments = $this->departmentHeadService->getDepartmentsForUser($userId);
            $departmentHeadTasks = $this->departmentHeadService->getViewableTasks($userId);
            $departmentHeadExecutants = $this->departmentHeadService->getViewableExecutants($userId);
        }

        $data = [
            'kpis' => $kpis,
            'regions' => $regions,
            'contracts' => $contracts,
            'chartData' => $chartData,
            'teamWorkload' => $teamWorkload,
            'criticalBlockers' => $criticalBlockers,
            'upcomingDeadlines' => $upcomingDeadlines,
            'personalTasks' => $personalTasks,
            'role' => $role,
            'userId' => $userId,
            'regionId' => $regionId,
            'isDepartmentHead' => $isDepartmentHead,
            'departmentHeadAssignments' => $departmentHeadAssignments,
            'departmentHeadTasks' => $departmentHeadTasks,
            'departmentHeadExecutants' => $departmentHeadExecutants,
        ];

        // If user is department head and has no other high-level role, show department head dashboard
        if ($isDepartmentHead && !in_array($role, ['admin', 'director', 'manager'])) {
            return view('dashboard/department_head', $data);
        }

        switch ($role) {
            case 'admin':
                return view('dashboard/admin', $data);
            case 'director':
                return view('dashboard/director', $data);
            case 'manager':
                return view('dashboard/manager', $data);
            case 'executant':
                return view('dashboard/executant', $data);
            case 'auditor':
                return view('dashboard/auditor', $data);
        }
    }

    /**
     * View region detail (drill-down)
     * GET /dashboard/region/{id}
     */
    public function regionView(int $id)
    {
        $session = service('session');
        $userId = $session->get('user_id');
        $role = $session->get('role');
        $roleLevel = $session->get('role_level');
        $userRegionId = $session->get('region_id');

        // Check permissions
        if ($role === 'manager' && $userRegionId !== $id) {
            return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi această regiune.');
        }

        // Get region data
        $region = $this->dashboardService->getRegionData($id);

        if (!$region) {
            return redirect()->to('/dashboard')->with('error', 'Regiunea nu există.');
        }

        // Get contracts for this region
        // For admin, filter by region_id when viewing a specific region (drill-down)
        if ($role === 'admin') {
            $contractModel = new \App\Models\ContractModel();
            $contracts = $contractModel->getContractsForRegionWithDetails($id);
        } else {
            $contracts = $this->dashboardService->getContractsForDashboard($userId, $role, $id);
        }

        // Get departments with active tasks for this region
        $departments = $this->dashboardService->getDepartmentsWithActiveTasksForRegion($id);

        $data = [
            'region' => $region,
            'contracts' => $contracts,
            'departments' => $departments,
        ];

        return view('dashboard/region', $data);
    }

    /**
     * View contract detail (drill-down)
     * GET /dashboard/contract/{id}
     */
    public function contractView(int $id)
    {
        $session = service('session');
        $userId = $session->get('user_id');
        $role = $session->get('role');
        $userRegionId = $session->get('region_id');

        // Get contract data
        $contract = $this->dashboardService->getContractData($id);

        if (!$contract) {
            return redirect()->to('/dashboard')->with('error', 'Contractul nu există.');
        }

        // Check permissions
        // Admin can view any contract (skip permission check)
        if ($role !== 'admin') {
            if ($role === 'manager') {
                // Manager can view only contracts assigned to him (via manager_id)
                if (!isset($contract['manager_id']) || $contract['manager_id'] != $userId) {
                    return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi acest contract 1.');
                }
            } elseif ($role === 'director' && $userRegionId) {
                // Director can view only contracts from his region
                if (!isset($contract['region_id']) || $contract['region_id'] != $userRegionId) {
                    return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi acest contract 2.');
                }
            } elseif ($role === 'director' && !$userRegionId) {
                // Director must have region_id
                return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi acest contract 3.');
            }
        }

        // Get subdivisions for this contract
        $subdivisionModel = new \App\Models\SubdivisionModel();
        $subdivisions = $subdivisionModel->where('contract_id', $id)->findAll();

        // Add task counts
        foreach ($subdivisions as &$subdivision) {
            $tasks = $subdivisionModel->getTasks($subdivision['id']);
            $subdivision['tasks_count'] = count($tasks);
        }

        $data = [
            'contract' => $contract,
            'subdivisions' => $subdivisions,
        ];

        return view('dashboard/contract', $data);
    }

    /**
     * View subdivision detail (drill-down)
     * GET /dashboard/subdivision/{id}
     */
    public function subdivisionView(int $id)
    {
        $session = service('session');
        $userId = $session->get('user_id');
        $role = $session->get('role');
        $userRegionId = $session->get('region_id');

        // Get subdivision data
        $subdivision = $this->dashboardService->getSubdivisionData($id);

        if (!$subdivision) {
            return redirect()->to('/dashboard')->with('error', 'Subdiviziunea nu există.');
        }

        // Check permissions
        // Admin can view any subdivision (skip permission check)
        if ($role !== 'admin') {
            if ($role === 'manager') {
                // Manager can view only subdivisions from contracts assigned to him (via manager_id)
                if (!isset($subdivision['contract']['manager_id']) || $subdivision['contract']['manager_id'] != $userId) {
                    return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi această subdiviziune.');
                }
            } elseif ($role === 'director' && $userRegionId) {
                // Director can view only subdivisions from contracts in his region
                if (!isset($subdivision['contract']['region_id']) || $subdivision['contract']['region_id'] != $userRegionId) {
                    return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi această subdiviziune.');
                }
            } elseif ($role === 'director' && !$userRegionId) {
                // Director must have region_id
                return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi această subdiviziune.');
            }
        }

        // Get tasks from subdivision data (already included by service)
        $tasks = $subdivision['tasks'] ?? [];

        // Ensure tasks have assignees populated
        $taskModel = new \App\Models\TaskModel();
        foreach ($tasks as &$task) {
            if (!isset($task['assignees'])) {
                $task['assignees'] = $taskModel->getAssignees($task['id']);
            }
        }

        // Prepare status and priority labels
        $statusLabels = [
            'new' => 'Nou',
            'in_progress' => 'În progres',
            'blocked' => 'Blocat',
            'review' => 'În revizie',
            'completed' => 'Finalizat',
        ];

        $priorityLabels = [
            'low' => 'Scăzută',
            'medium' => 'Medie',
            'high' => 'Ridicată',
            'critical' => 'Critică',
        ];

        $statusBadgeClasses = [
            'new' => 'bg-subtle-gray',
            'in_progress' => 'bg-subtle-blue',
            'blocked' => 'bg-subtle-red',
            'review' => 'bg-subtle-yellow',
            'completed' => 'bg-subtle-green',
        ];

        $priorityBadgeClasses = [
            'low' => 'bg-subtle-green',
            'medium' => 'bg-subtle-yellow',
            'high' => 'bg-subtle-orange',
            'critical' => 'bg-subtle-red',
        ];

        $data = [
            'subdivision' => $subdivision,
            'tasks' => $tasks,
            'statusLabels' => $statusLabels,
            'priorityLabels' => $priorityLabels,
            'statusBadgeClasses' => $statusBadgeClasses,
            'priorityBadgeClasses' => $priorityBadgeClasses,
        ];

        return view('dashboard/subdivision', $data);
    }

    /**
     * View department detail (drill-down)
     * GET /dashboard/department/{departmentId}/region/{regionId}
     */
    public function departmentView(int $departmentId, int $regionId)
    {
        $session = service('session');
        $userId = $session->get('user_id');
        $role = $session->get('role');
        $userRegionId = $session->get('region_id');

        // Check permissions
        // Admin can view any department
        if ($role !== 'admin') {
            if ($role === 'director') {
                // Director can view only departments from his region
                if ($userRegionId !== $regionId) {
                    return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi acest departament.');
                }
            } elseif ($role === 'manager') {
                // Manager can view only departments from regions of contracts assigned to him
                $contractModel = new \App\Models\ContractModel();
                $contracts = $contractModel->where('manager_id', $userId)->findAll();
                $allowedRegionIds = array_unique(array_column($contracts, 'region_id'));
                if (!in_array($regionId, $allowedRegionIds)) {
                    return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi acest departament.');
                }
            } elseif ($role === 'department_head') {
                // Department head can view only their own departments in their regions
                $departmentHeadModel = new \App\Models\DepartmentHeadModel();
                $assignments = $departmentHeadModel->getDepartmentsForUser($userId);
                $allowed = false;
                foreach ($assignments as $assignment) {
                    if ($assignment['department_id'] == $departmentId && $assignment['region_id'] == $regionId) {
                        $allowed = true;
                        break;
                    }
                }
                if (!$allowed) {
                    return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi acest departament.');
                }
            } else {
                return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi acest departament.');
            }
        }

        // Get department data with tasks
        $department = $this->dashboardService->getDepartmentDataForRegion($departmentId, $regionId);

        if (!$department) {
            return redirect()->to('/dashboard')->with('error', 'Departamentul nu există sau nu are sarcini în această regiune.');
        }

        // Get tasks
        $tasks = $department['tasks'] ?? [];

        // Ensure tasks have assignees populated
        $taskModel = new \App\Models\TaskModel();
        foreach ($tasks as &$task) {
            if (!isset($task['assignees'])) {
                $task['assignees'] = $taskModel->getAssignees($task['id']);
            }
        }

        // Prepare status and priority labels
        $statusLabels = [
            'new' => 'Nou',
            'in_progress' => 'În progres',
            'blocked' => 'Blocat',
            'review' => 'În revizie',
            'completed' => 'Finalizat',
        ];

        $priorityLabels = [
            'low' => 'Scăzută',
            'medium' => 'Medie',
            'high' => 'Ridicată',
            'critical' => 'Critică',
        ];

        $statusBadgeClasses = [
            'new' => 'bg-subtle-gray',
            'in_progress' => 'bg-subtle-blue',
            'blocked' => 'bg-subtle-red',
            'review' => 'bg-subtle-yellow',
            'completed' => 'bg-subtle-green',
        ];

        $priorityBadgeClasses = [
            'low' => 'bg-subtle-green',
            'medium' => 'bg-subtle-yellow',
            'high' => 'bg-subtle-orange',
            'critical' => 'bg-subtle-red',
        ];

        $data = [
            'department' => $department,
            'tasks' => $tasks,
            'statusLabels' => $statusLabels,
            'priorityLabels' => $priorityLabels,
            'statusBadgeClasses' => $statusBadgeClasses,
            'priorityBadgeClasses' => $priorityBadgeClasses,
        ];

        return view('dashboard/department', $data);
    }

    /**
     * Get chart data (AJAX endpoint for period filtering)
     * GET /dashboard/chart/tasks-region?period=30days
     */
    public function getChartData()
    {
        $session = service('session');
        $userId = $session->get('user_id');
        $role = $session->get('role');
        $regionId = $session->get('region_id');

        if (!$userId || !$role) {
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }

        $period = $this->request->getGet('period') ?? '30days';
        $chartData = $this->dashboardService->getTasksPerRegionChart($userId, $role, $regionId, $period);

        return $this->response->setJSON($chartData);
    }

    /**
     * Send a test notification to the current user
     */
    protected function sendTestNotification(): void
    {
        $userId = session()->get('user_id');

        if (!$userId) {
            return;
        }

        $notificationService = new NotificationService();

        $notificationService->send(
            2,
            'info',
            'Notificare de Test',
            'Aceasta este o notificare de test. Dacă ești online, ai primit-o via Pusher. Dacă nu, ai primit-o pe email!',
            '/dashboard'
        );
    }

    /**
     * Send a test global notification
     */
    /**
     * View active tasks
     * GET /dashboard/active-tasks
     */
    public function activeTasks()
    {
        $session = service('session');
        $userId = $session->get('user_id');
        $role = $session->get('role');
        $regionId = $session->get('region_id');

        // Get active tasks
        $tasks = $this->dashboardService->getActiveTasks($userId, $role, $regionId);

        // Status and priority labels
        $statusLabels = [
            'new' => 'Nou',
            'in_progress' => 'În progres',
            'blocked' => 'Blocat',
            'review' => 'În revizie',
            'completed' => 'Finalizat',
        ];

        $statusBadgeClasses = [
            'new' => 'bg-subtle-gray',
            'in_progress' => 'bg-subtle-blue',
            'blocked' => 'bg-subtle-red',
            'review' => 'bg-subtle-yellow',
            'completed' => 'bg-subtle-green',
        ];

        $data = [
            'tasks' => $tasks,
            'statusLabels' => $statusLabels,
            'statusBadgeClasses' => $statusBadgeClasses,
        ];

        return view('dashboard/active-tasks', $data);
    }

    /**
     * View overdue tasks
     * GET /dashboard/overdue-tasks
     */
    public function overdueTasks()
    {
        $session = service('session');
        $userId = $session->get('user_id');
        $role = $session->get('role');
        $regionId = $session->get('region_id');

        // Get overdue tasks
        $tasks = $this->dashboardService->getOverdueTasks($userId, $role, $regionId);

        // Status and priority labels
        $statusLabels = [
            'new' => 'Nou',
            'in_progress' => 'În progres',
            'blocked' => 'Blocat',
            'review' => 'În revizie',
            'completed' => 'Finalizat',
        ];

        $statusBadgeClasses = [
            'new' => 'bg-subtle-gray',
            'in_progress' => 'bg-subtle-blue',
            'blocked' => 'bg-subtle-red',
            'review' => 'bg-subtle-yellow',
            'completed' => 'bg-subtle-green',
        ];

        $data = [
            'tasks' => $tasks,
            'statusLabels' => $statusLabels,
            'statusBadgeClasses' => $statusBadgeClasses,
        ];

        return view('dashboard/overdue-tasks', $data);
    }

    protected function testGlobalNotification(string $message): void
    {
        $notificationService = new NotificationService();

        $notificationService->sendGlobal(
            'info',
            'Anunț Global',
            $message,
            '/dashboard'
        );
    }
}
