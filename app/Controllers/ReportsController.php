<?php

namespace App\Controllers;

use App\Services\ReportsService;
use App\Models\RegionModel;

class ReportsController extends BaseController
{
    protected ReportsService $reportsService;
    protected RegionModel $regionModel;
    protected $session;

    public function __construct()
    {
        $this->reportsService = new ReportsService();
        $this->regionModel = new RegionModel();
        $this->session = service('session');
    }

    /**
     * Reports index page
     * GET /reports
     */
    public function index()
    {
        // Check if user is Director or Admin (level 80+)
        $roleLevel = $this->session->get('role_level');
        if (!$roleLevel || $roleLevel < 80) {
            return redirect()->to('/dashboard')->with('error', 'Nu ai permisiunea să accesezi acestă pagină.');
        }

        // Get regions for filter
        $regions = $this->regionModel->findAll();

        // Get default filters (last 30 days)
        $defaultFilters = [
            'date_from' => date('Y-m-d', strtotime('-30 days')),
            'date_to' => date('Y-m-d'),
            'region_id' => null,
        ];

        // Get preview stats for each report (lightweight)
        $previewStats = [
            'operational' => $this->getOperationalPreviewStats($defaultFilters),
            'contracts' => $this->getContractsPreviewStats($defaultFilters),
            'resources' => $this->getResourcesPreviewStats($defaultFilters),
            'critical' => $this->getCriticalPreviewStats($defaultFilters),
        ];

        $data = [
            'regions' => $regions,
            'default_filters' => $defaultFilters,
            'preview_stats' => $previewStats,
        ];

        return view('reports/index', $data);
    }

    /**
     * Preview report data (AJAX)
     * GET /reports/preview/{type}
     */
    public function preview(string $type)
    {
        // Check permissions
        $roleLevel = $this->session->get('role_level');
        if (!$roleLevel || $roleLevel < 80) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Nu ai permisiunea să accesezi acest raport.',
            ])->setStatusCode(403);
        }

        // Get filters from request
        $filters = [
            'date_from' => $this->request->getGet('date_from') ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $this->request->getGet('date_to') ?? date('Y-m-d'),
            'region_id' => $this->request->getGet('region_id') ? (int)$this->request->getGet('region_id') : null,
        ];

        try {
            $reportData = match ($type) {
                'operational' => $this->reportsService->getOperationalRegionalReport($filters),
                'contracts' => $this->reportsService->getContractsPerformanceReport($filters),
                'resources' => $this->reportsService->getResourcesReport($filters),
                'critical' => $this->reportsService->getCriticalTasksReport($filters),
                default => throw new \InvalidArgumentException('Tip de raport invalid.'),
            };

            return $this->response->setJSON([
                'success' => true,
                'data' => $reportData,
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ])->setStatusCode(400);
        }
    }

    /**
     * Export report to Excel (CSV format)
     * GET /reports/export/{type}/excel
     */
    public function exportExcel(string $type)
    {
        // Check permissions
        $roleLevel = $this->session->get('role_level');
        if (!$roleLevel || $roleLevel < 80) {
            return redirect()->to('/reports')->with('error', 'Nu ai permisiunea să exporti rapoarte.');
        }

        // Get filters
        $filters = [
            'date_from' => $this->request->getGet('date_from') ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $this->request->getGet('date_to') ?? date('Y-m-d'),
            'region_id' => $this->request->getGet('region_id') ? (int)$this->request->getGet('region_id') : null,
        ];

        try {
            $reportData = match ($type) {
                'operational' => $this->reportsService->getOperationalRegionalReport($filters),
                'contracts' => $this->reportsService->getContractsPerformanceReport($filters),
                'resources' => $this->reportsService->getResourcesReport($filters),
                'critical' => $this->reportsService->getCriticalTasksReport($filters),
                default => throw new \InvalidArgumentException('Tip de raport invalid.'),
            };

            $csvContent = $this->generateExcelContent($type, $reportData);
            $filename = $this->getFilename($type, $filters, 'xlsx');

            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setBody($csvContent);
        } catch (\Exception $e) {
            return redirect()->to('/reports')->with('error', 'Eroare la export: ' . $e->getMessage());
        }
    }

    /**
     * Export report to PDF
     * GET /reports/export/{type}/pdf
     */
    public function exportPdf(string $type)
    {
        // Check permissions
        $roleLevel = $this->session->get('role_level');
        if (!$roleLevel || $roleLevel < 80) {
            return redirect()->to('/reports')->with('error', 'Nu ai permisiunea să exporti rapoarte.');
        }

        // Get filters
        $filters = [
            'date_from' => $this->request->getGet('date_from') ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $this->request->getGet('date_to') ?? date('Y-m-d'),
            'region_id' => $this->request->getGet('region_id') ? (int)$this->request->getGet('region_id') : null,
        ];

        try {
            $reportData = match ($type) {
                'operational' => $this->reportsService->getOperationalRegionalReport($filters),
                'contracts' => $this->reportsService->getContractsPerformanceReport($filters),
                'resources' => $this->reportsService->getResourcesReport($filters),
                'critical' => $this->reportsService->getCriticalTasksReport($filters),
                default => throw new \InvalidArgumentException('Tip de raport invalid.'),
            };

            // Generate HTML view for PDF
            $html = view('reports/pdf/' . $type, [
                'reportData' => $reportData,
                'filters' => $filters,
            ]);

            $filename = $this->getFilename($type, $filters, 'pdf');

            // Return HTML that can be printed to PDF (or use browser print-to-PDF)
            return $this->response
                ->setHeader('Content-Type', 'text/html; charset=UTF-8')
                ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->setBody($html);
        } catch (\Exception $e) {
            return redirect()->to('/reports')->with('error', 'Eroare la export: ' . $e->getMessage());
        }
    }

    /**
     * Get preview stats for operational report
     */
    protected function getOperationalPreviewStats(array $filters): array
    {
        $data = $this->reportsService->getOperationalRegionalReport($filters);
        return [
            'regions' => $data['summary']['total_regions'] ?? 0,
            'tasks' => $data['summary']['total_active_tasks'] ?? 0,
            'users' => $data['summary']['total_users'] ?? 0,
        ];
    }

    /**
     * Get preview stats for contracts report
     */
    protected function getContractsPreviewStats(array $filters): array
    {
        $data = $this->reportsService->getContractsPerformanceReport($filters);
        return [
            'contracts' => $data['summary']['total_contracts'] ?? 0,
            'active' => $data['summary']['active_contracts'] ?? 0,
            'tasks' => $data['summary']['total_tasks'] ?? 0,
        ];
    }

    /**
     * Get preview stats for resources report
     */
    protected function getResourcesPreviewStats(array $filters): array
    {
        $data = $this->reportsService->getResourcesReport($filters);
        return [
            'users' => $data['summary']['total_users'] ?? 0,
            'created' => $data['summary']['total_tasks_created'] ?? 0,
            'completed' => $data['summary']['total_tasks_completed'] ?? 0,
        ];
    }

    /**
     * Get preview stats for critical report
     */
    protected function getCriticalPreviewStats(array $filters): array
    {
        $data = $this->reportsService->getCriticalTasksReport($filters);
        return [
            'blocked' => $data['summary']['total_blocked'] ?? 0,
            'overdue' => $data['summary']['total_overdue'] ?? 0,
            'critical' => $data['summary']['total_critical'] ?? 0,
        ];
    }

    /**
     * Generate CSV/Excel content
     */
    protected function generateExcelContent(string $type, array $reportData): string
    {
        // Simple CSV generation (can be enhanced with PhpSpreadsheet)
        $output = fopen('php://temp', 'r+');

        switch ($type) {
            case 'operational':
                fputcsv($output, ['Regiune', 'Contracte', 'Sarcini Active', 'Sarcini Întârziate', 'Utilizatori']);
                foreach ($reportData['regions'] as $region) {
                    fputcsv($output, [
                        $region['name'],
                        $region['contracts_count'],
                        $region['active_tasks_count'],
                        $region['overdue_tasks_count'],
                        $region['users_count'],
                    ]);
                }
                break;

            case 'contracts':
                fputcsv($output, ['Contract', 'Număr', 'Regiune', 'Status', 'Progres %', 'Sarcini Active', 'Sarcini Întârziate']);
                foreach ($reportData['contracts'] as $contract) {
                    fputcsv($output, [
                        $contract['name'],
                        $contract['contract_number'] ?? '',
                        $contract['region_name'] ?? '',
                        $contract['status'],
                        $contract['progress_percentage'],
                        $contract['tasks_count'],
                        $contract['overdue_tasks_count'],
                    ]);
                }
                break;

            case 'resources':
                fputcsv($output, ['Nume', 'Email', 'Rol', 'Regiune', 'Sarcini Create', 'Sarcini Finalizate', 'Workload %']);
                foreach ($reportData['users'] as $user) {
                    fputcsv($output, [
                        $user['name'],
                        $user['email'],
                        $user['role'],
                        $user['region_name'] ?? '',
                        $user['tasks_created'],
                        $user['tasks_completed'],
                        $user['workload_percentage'],
                    ]);
                }
                break;

            case 'critical':
                fputcsv($output, ['ID', 'Titlu', 'Contract', 'Regiune', 'Status', 'Prioritate', 'Deadline', 'Zile Întârziat']);
                // Blocked tasks
                foreach ($reportData['blocked_tasks'] as $task) {
                    fputcsv($output, [
                        $task['id'],
                        $task['title'],
                        $task['contract_name'] ?? '',
                        $task['region_name'] ?? '',
                        'Blocked',
                        $task['priority'] ?? '',
                        $task['deadline'] ?? '',
                        $task['days_blocked'] ?? '',
                    ]);
                }
                // Overdue tasks
                foreach ($reportData['overdue_tasks'] as $task) {
                    fputcsv($output, [
                        $task['id'],
                        $task['title'],
                        $task['contract_name'] ?? '',
                        $task['region_name'] ?? '',
                        'Overdue',
                        $task['priority'] ?? '',
                        $task['deadline'] ?? '',
                        $task['days_overdue'] ?? '',
                    ]);
                }
                break;
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Generate filename for export
     */
    protected function getFilename(string $type, array $filters, string $extension): string
    {
        $typeNames = [
            'operational' => 'Raport_Operational_Regional',
            'contracts' => 'Raport_Contracte_Performanta',
            'resources' => 'Raport_Resurse',
            'critical' => 'Raport_Taskuri_Critice',
        ];

        $name = $typeNames[$type] ?? 'Raport';
        $dateFrom = date('Y-m-d', strtotime($filters['date_from']));
        $dateTo = date('Y-m-d', strtotime($filters['date_to']));

        return "{$name}_{$dateFrom}_{$dateTo}.{$extension}";
    }
}
