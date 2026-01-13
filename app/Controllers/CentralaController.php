<?php

namespace App\Controllers;

use App\Services\CentralaService;

class CentralaController extends BaseController
{
    protected CentralaService $centralaService;
    protected $session;

    const ALLOWED_EMAIL = 'vlad.maican@supercom.ro';

    public function __construct()
    {
        $this->centralaService = new CentralaService();
        $this->session = service('session');
    }

    /**
     * Check if current user has access to Centrala
     * 
     * @return bool
     */
    protected function hasAccess(): bool
    {
        $userEmail = $this->session->get('email');
        return $userEmail === self::ALLOWED_EMAIL;
    }

    /**
     * Display Centrala main page
     * GET /centrala
     */
    public function index()
    {
        // Check access
        if (!$this->hasAccess()) {
            return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea de a accesa această pagină.');
        }

        // Get admins with task counts
        $admins = $this->centralaService->getAdminsWithTaskCounts();

        // Get regions with task counts
        $regions = $this->centralaService->getRegionsWithTaskCounts();

        $data = [
            'admins' => $admins,
            'regions' => $regions,
        ];

        return view('centrala/index', $data);
    }

    /**
     * Display tasks for a specific admin
     * GET /centrala/admin/{id}
     */
    public function adminTasks(int $id)
    {
        // Check access
        if (!$this->hasAccess()) {
            return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea de a accesa această pagină.');
        }

        // Verify user is admin
        $userModel = new \App\Models\UserModel();
        $admin = $userModel->find($id);
        
        if (!$admin || $admin['role_level'] < 100) {
            return redirect()->to('/centrala')->with('error', 'Administratorul nu a fost găsit.');
        }

        // Get tasks for admin
        $tasks = $this->centralaService->getTasksForAdmin($id);

        // Prepare status labels
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

        $adminName = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '')) ?: $admin['email'];

        $data = [
            'admin' => $admin,
            'adminName' => $adminName,
            'tasks' => $tasks,
            'statusLabels' => $statusLabels,
            'statusBadgeClasses' => $statusBadgeClasses,
        ];

        return view('centrala/admin-tasks', $data);
    }

    /**
     * Display tasks for a specific region
     * GET /centrala/region/{id}
     */
    public function regionTasks(int $id)
    {
        // Check access
        if (!$this->hasAccess()) {
            return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea de a accesa această pagină.');
        }

        // Verify region exists
        $regionModel = new \App\Models\RegionModel();
        $region = $regionModel->find($id);
        
        if (!$region) {
            return redirect()->to('/centrala')->with('error', 'Regiunea nu a fost găsită.');
        }

        // Get tasks for region
        $tasks = $this->centralaService->getTasksForRegion($id);

        // Prepare status labels
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
            'region' => $region,
            'tasks' => $tasks,
            'statusLabels' => $statusLabels,
            'statusBadgeClasses' => $statusBadgeClasses,
        ];

        return view('centrala/region-tasks', $data);
    }
}

