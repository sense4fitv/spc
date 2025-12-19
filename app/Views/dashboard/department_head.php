<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="fade-in">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark">Dashboard Șef de Departament</h3>
            <p class="text-muted m-0 mt-1">Vizualizare operațională la data de <?= date('d M Y') ?>.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('/tasks/create') ?>" class="btn btn-spor-primary"><i class="bi bi-plus-lg me-2"></i>Task Nou</a>
        </div>
    </div>

    <!-- DEPARTMENT INFO -->
    <?php if (!empty($departmentHeadAssignments)): ?>
        <div class="mb-5">
            <h6 class="fw-bold text-dark mb-3 ps-1">Departamentul Meu</h6>
            <div class="row">
                <?php foreach ($departmentHeadAssignments as $assignment): ?>
                    <div class="col-lg-6 mb-3">
                        <div class="spor-card p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1 text-dark"><?= esc($assignment['department_name'] ?? 'Necunoscut') ?></h5>
                                    <div class="text-muted small">Regiune: <?= esc($assignment['region_name'] ?? 'Necunoscut') ?></div>
                                </div>
                                <?php if (!empty($assignment['color_code'])): ?>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: <?= esc($assignment['color_code']) ?>20;">
                                        <i class="bi bi-building text-secondary fs-4" style="color: <?= esc($assignment['color_code']) ?>;"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                        <i class="bi bi-building text-secondary fs-4"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-4 border-top pt-3 border-light">
                                <div>
                                    <div class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 600;">Executanți</div>
                                    <div class="fw-bold text-dark"><?= count($departmentHeadExecutants) ?></div>
                                </div>
                                <div>
                                    <div class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 600;">Tasks</div>
                                    <div class="fw-bold text-dark"><?= count(array_filter($departmentHeadTasks ?? [], function ($t) {
                                                                        return $t['status'] !== 'completed';
                                                                    })) ?> Active</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- KPI CARDS -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4 mb-5">
        <div class="col">
            <div class="spor-card p-4 h-100">
                <div class="text-muted fw-medium text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Sarcini Active</div>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h3 class="fw-bold m-0"><?= $kpis['active_tasks'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="spor-card p-4 h-100">
                <div class="text-muted fw-medium text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Întârziate</div>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h3 class="fw-bold m-0 text-danger"><?= $kpis['overdue_tasks'] ?? 0 ?></h3>
                    <?php if (($kpis['overdue_tasks'] ?? 0) > 0): ?>
                        <span class="text-danger small fw-medium bg-danger-subtle px-1 rounded">Critical</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="spor-card p-4 h-100">
                <div class="text-muted fw-medium text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Executanți</div>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h3 class="fw-bold m-0"><?= $kpis['executants_count'] ?? count($departmentHeadExecutants ?? []) ?></h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="spor-card p-4 h-100">
                <div class="text-muted fw-medium text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">În Revizie</div>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h3 class="fw-bold m-0"><?= $kpis['tasks_in_review'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- CHART & EXECUTANTS -->
    <div class="row g-4 mb-5">
        <!-- Chart Section -->
        <div class="col-lg-8">
            <div class="spor-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0 text-dark">Sarcini per Status</h5>
                </div>
                <div style="height: 300px;">
                    <canvas id="tasksStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Executants List -->
        <div class="col-lg-4">
            <div class="spor-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold m-0 text-dark">Executanți Departament</h6>
                </div>
                <div class="d-flex flex-column gap-3">
                    <?php if (empty($departmentHeadExecutants)): ?>
                        <p class="text-muted small mb-0">Nu există executanți în departament.</p>
                    <?php else: ?>
                        <?php foreach (array_slice($departmentHeadExecutants, 0, 8) as $executant):
                            $name = trim(($executant['first_name'] ?? '') . ' ' . ($executant['last_name'] ?? ''));
                            $name = $name ?: ($executant['email'] ?? 'Necunoscut');
                            $email = $executant['email'] ?? '';
                            $phone = $executant['phone'] ?? null;
                        ?>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
                                    <i class="bi bi-person text-secondary" style="font-size: 0.9rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark small"><?= esc($name) ?></div>
                                    <?php if ($email): ?>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= esc($email) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count($departmentHeadExecutants) > 8): ?>
                            <div class="text-center mt-2">
                                <small class="text-muted">+<?= count($departmentHeadExecutants) - 8 ?> alți executanți</small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- CRITICAL BLOCKERS & UPCOMING DEADLINES -->
    <div class="row g-4 mb-5">
        <!-- Critical Blockers -->
        <div class="col-lg-6">
            <div class="spor-card p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <div class="bg-danger-subtle text-danger rounded p-1 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 0.8rem;"></i>
                    </div>
                    <h6 class="fw-bold m-0 text-dark">Blocaje Critice & Întârzieri</h6>
                </div>
                <div class="list-group list-group-custom">
                    <?php if (empty($criticalBlockers)): ?>
                        <div class="list-group-item">
                            <p class="text-muted small mb-0 text-center">Nu există blocaje critice sau sarcini întârziate.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($criticalBlockers as $task):
                            $contractInfo = !empty($task['contract_name']) ? esc($task['contract_name']) : '';
                            $regionInfo = !empty($task['region_name']) ? esc($task['region_name']) : '';
                            $context = trim($contractInfo . ($contractInfo && $regionInfo ? ' • ' : '') . $regionInfo);
                            if (!$context) {
                                $context = !empty($task['subdivision_name']) ? esc($task['subdivision_name']) : '';
                            }

                            $badgeClass = 'bg-danger-subtle text-danger';
                            $badgeText = $task['days_info'] ?? '';
                            if ($task['status'] === 'blocked') {
                                $badgeText = 'Blocat';
                            }
                        ?>
                            <a href="<?= site_url('/tasks/view/' . $task['id']) ?>" class="text-decoration-none">
                                <div class="list-group-item d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div>
                                            <div class="fw-bold text-dark small"><?= esc($task['title']) ?></div>
                                            <?php if ($context): ?>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?= $context ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($badgeText): ?>
                                        <span class="badge <?= $badgeClass ?> border border-danger-subtle"><?= esc($badgeText) ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Deadlines -->
        <div class="col-lg-6">
            <div class="spor-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-event text-secondary"></i>
                        <h6 class="fw-bold m-0 text-dark">Termene Limită (5 Zile)</h6>
                    </div>
                    <span class="badge bg-light text-dark border"><?= date('F Y') ?></span>
                </div>
                <div class="list-group list-group-custom">
                    <?php if (empty($upcomingDeadlines)): ?>
                        <div class="list-group-item">
                            <p class="text-muted small mb-0 text-center">Nu există termene limită în următoarele 5 zile.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcomingDeadlines as $task):
                            $deadline = $task['deadline'] ?? null;
                            if (!$deadline) continue;

                            $deadlineDate = new \DateTime($deadline);
                            $today = new \DateTime();
                            $diff = $today->diff($deadlineDate);
                            $daysDiff = (int)$diff->format('%r%a');

                            $dayNumber = $deadlineDate->format('d');
                            $dayName = strtoupper($deadlineDate->format('D'));
                            $dayMap = ['MON' => 'LUN', 'TUE' => 'MAR', 'WED' => 'MIE', 'THU' => 'JOI', 'FRI' => 'VIN', 'SAT' => 'SÂM', 'SUN' => 'DUM'];
                            $dayName = $dayMap[$dayName] ?? $dayName;

                            $isToday = $daysDiff == 0;
                            $contractInfo = !empty($task['contract_name']) ? esc($task['contract_name']) : '';
                            $time = $deadlineDate->format('H:i');
                        ?>
                            <a href="<?= site_url('/tasks/view/' . $task['id']) ?>" class="text-decoration-none">
                                <div class="list-group-item d-flex gap-3 align-items-start">
                                    <div class="bg-light border rounded text-center py-1 px-2 flex-shrink-0" style="min-width: 50px;">
                                        <div class="fw-bold text-dark lh-1"><?= $dayNumber ?></div>
                                        <small class="text-uppercase text-secondary" style="font-size: 0.65rem;"><?= $isToday ? 'AZI' : $dayName ?></small>
                                    </div>
                                    <div class="w-100">
                                        <div class="fw-bold text-dark small"><?= esc($task['title']) ?></div>
                                        <div class="text-muted small mb-1"><?= $contractInfo ?: 'Task' ?> • Ora <?= $time ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- TASKS GRID -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark m-0">Sarcini Departament</h5>
        <a href="<?= site_url('/tasks') ?>" class="btn btn-sm btn-spor-secondary">
            <i class="bi bi-list-ul me-2"></i>Vezi toate
        </a>
    </div>

    <?php
    // Prepare tasks by status
    $tasksByStatus = [];
    $statusLabels = [
        'new' => 'Nou',
        'in_progress' => 'În Progres',
        'blocked' => 'Blocat',
        'review' => 'În Revizie',
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

    if (!empty($departmentHeadTasks)) {
        foreach ($departmentHeadTasks as $task) {
            $status = $task['status'] ?? 'new';
            if (!isset($tasksByStatus[$status])) {
                $tasksByStatus[$status] = [];
            }
            $tasksByStatus[$status][] = $task;
        }
    }
    ?>

    <div class="row g-4">
        <?php if (empty($departmentHeadTasks)): ?>
            <div class="col-12">
                <div class="spor-card p-5 text-center">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3 mb-0">Nu există sarcini în departament momentan.</p>
                </div>
            </div>
        <?php else: ?>
            <?php
            // Show only active statuses (not completed)
            $activeStatuses = ['new', 'in_progress', 'blocked', 'review'];
            foreach ($activeStatuses as $status):
                if (empty($tasksByStatus[$status])) continue;
                $tasks = array_slice($tasksByStatus[$status], 0, 6); // Limit to 6 per status
            ?>
                <div class="col-12">
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark">
                            <?= $statusLabels[$status] ?? ucfirst($status) ?>
                            <span class="badge bg-secondary ms-2"><?= count($tasksByStatus[$status]) ?></span>
                        </h6>
                    </div>
                    <div class="row g-3">
                        <?php foreach ($tasks as $task):
                            $priority = $task['priority'] ?? 'medium';
                            $priorityBadgeClass = $priorityBadgeClasses[$priority] ?? 'bg-subtle-yellow';
                            $priorityLabel = $priorityLabels[$priority] ?? ucfirst($priority);
                        ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="spor-card p-4 h-100" style="cursor: pointer;" onclick="window.location.href='<?= site_url('tasks/view/' . $task['id']) ?>'">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1"><?= esc($task['title']) ?></h6>
                                            <small class="text-muted">#<?= $task['id'] ?></small>
                                        </div>
                                        <span class="spor-badge <?= $priorityBadgeClass ?>"><?= $priorityLabel ?></span>
                                    </div>

                                    <?php if (!empty($task['description'])): ?>
                                        <p class="text-secondary small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            <?= esc(substr($task['description'], 0, 100)) ?><?= strlen($task['description']) > 100 ? '...' : '' ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($task['contract_name'])): ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Contract:</small>
                                            <span class="small fw-medium text-dark"><?= esc($task['contract_name']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($task['deadline'])):
                                        $deadlineDate = strtotime($task['deadline']);
                                        $isOverdue = $deadlineDate < time() && $task['status'] !== 'completed';
                                    ?>
                                        <div class="mb-2">
                                            <small class="text-muted d-flex align-items-center gap-1 <?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                                <i class="bi bi-calendar4"></i>
                                                Deadline: <?= date('d.m.Y', $deadlineDate) ?>
                                                <?php if ($isOverdue): ?>
                                                    <span class="badge bg-danger-subtle text-danger">Întârziat</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex align-items-center justify-content-between border-top pt-3 border-light">
                                        <span class="spor-badge <?= $statusBadgeClasses[$status] ?? 'bg-subtle-gray' ?>">
                                            <?= $statusLabels[$status] ?? ucfirst($status) ?>
                                        </span>
                                        <i class="bi bi-arrow-right text-muted"></i>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count($tasksByStatus[$status]) > 6): ?>
                            <div class="col-md-6 col-lg-4">
                                <a href="<?= site_url('/tasks?status=' . $status) ?>" class="text-decoration-none">
                                    <div class="spor-card p-4 h-100 d-flex align-items-center justify-content-center border-2 border-dashed" style="min-height: 200px;">
                                        <div class="text-center">
                                            <i class="bi bi-arrow-right-circle text-secondary" style="font-size: 2rem;"></i>
                                            <p class="text-muted small mt-2 mb-0">Vezi toate (<?= count($tasksByStatus[$status]) ?>)</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('tasksStatusChart');
        if (!ctx) return;

        // Prepare chart data from tasks
        const tasks = <?= json_encode($departmentHeadTasks ?? []) ?>;

        const statusCounts = {
            'new': 0,
            'in_progress': 0,
            'blocked': 0,
            'review': 0,
            'completed': 0
        };

        tasks.forEach(task => {
            const status = task.status || 'new';
            if (statusCounts.hasOwnProperty(status)) {
                statusCounts[status]++;
            }
        });

        const statusLabels = {
            'new': 'Nou',
            'in_progress': 'În Progres',
            'blocked': 'Blocat',
            'review': 'În Revizie',
            'completed': 'Finalizat'
        };

        const chartData = {
            labels: Object.keys(statusCounts).map(status => statusLabels[status] || status),
            data: Object.values(statusCounts)
        };

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.data,
                    backgroundColor: [
                        'rgba(108, 117, 125, 0.1)',
                        'rgba(13, 110, 253, 0.1)',
                        'rgba(220, 53, 69, 0.1)',
                        'rgba(255, 193, 7, 0.1)',
                        'rgba(25, 135, 84, 0.1)'
                    ],
                    borderColor: [
                        'rgba(108, 117, 125, 0.8)',
                        'rgba(13, 110, 253, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(25, 135, 84, 0.8)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
</script>

<?= $this->endSection() ?>