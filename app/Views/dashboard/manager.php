<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="fade-in">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark">Dashboard Manager</h3>
            <p class="text-muted m-0 mt-1">Vizualizare operațională la data de <?= date('d M Y') ?>.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('/tasks/create') ?>" class="btn btn-spor-primary"><i class="bi bi-plus-lg me-2"></i>Task Nou</a>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4 mb-5">
        <div class="col">
            <a href="<?= site_url('dashboard/active-tasks') ?>" class="text-decoration-none">
                <div class="spor-card p-4 h-100" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" 
                     onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';" 
                     onmouseout="this.style.transform=''; this.style.boxShadow='';">
                    <div class="text-muted fw-medium text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Task-uri Active</div>
                    <div class="d-flex align-items-baseline gap-2 mt-2">
                        <h3 class="fw-bold m-0"><?= $kpis['active_tasks'] ?? 0 ?></h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="<?= site_url('dashboard/overdue-tasks') ?>" class="text-decoration-none">
                <div class="spor-card p-4 h-100" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" 
                     onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';" 
                     onmouseout="this.style.transform=''; this.style.boxShadow='';">
                    <div class="text-muted fw-medium text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Întârziate</div>
                    <div class="d-flex align-items-baseline gap-2 mt-2">
                        <h3 class="fw-bold m-0 text-danger"><?= $kpis['overdue_tasks'] ?? 0 ?></h3>
                        <?php if (($kpis['overdue_tasks'] ?? 0) > 0): ?>
                        <span class="text-danger small fw-medium bg-danger-subtle px-1 rounded">Critical</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <div class="spor-card p-4 h-100">
                <div class="text-muted fw-medium text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Contracte</div>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h3 class="fw-bold m-0"><?= $kpis['active_contracts'] ?? 0 ?></h3>
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

    <!-- REGION CARD (if region exists) -->
    <?php if (!empty($regions) && count($regions) > 0): 
        $myRegion = $regions[0]; // Manager sees only one region
    ?>
    <div class="mb-5">
        <h6 class="fw-bold text-dark mb-3 ps-1">Regiunea Mea</h6>
        <div class="row">
            <div class="col-lg-8">
                <a href="<?= site_url('/dashboard/region/' . $myRegion['id']) ?>" class="text-decoration-none">
                    <div class="spor-card interactive p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h5 class="fw-bold mb-1 text-dark"><?= esc($myRegion['name']) ?></h5>
                                <?php if (!empty($myRegion['manager_name'])): ?>
                                <div class="text-muted small">Resp: <?= esc($myRegion['manager_name']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-building text-secondary fs-4"></i>
                            </div>
                        </div>
                        <div class="d-flex gap-4 border-top pt-3 border-light">
                            <div>
                                <div class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 600;">Contracte</div>
                                <div class="fw-bold text-dark"><?= $myRegion['contracts_count'] ?? 0 ?> Active</div>
                            </div>
                            <div>
                                <div class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 600;">Tasks</div>
                                <div class="fw-bold text-dark"><?= $myRegion['active_tasks_count'] ?? 0 ?> Open</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- CHART & TEAM WORKLOAD -->
    <div class="row g-4 mb-5">
        <!-- Chart Section -->
        <div class="col-lg-8">
            <div class="spor-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0 text-dark">Task-uri Deschise per Regiune</h5>
                    <select id="chartPeriodFilter" class="form-select form-select-sm" style="width: auto;">
                        <option value="7days">Ultimele 7 zile</option>
                        <option value="30days" selected>Ultimele 30 zile</option>
                        <option value="3months">Ultimele 3 luni</option>
                        <option value="6months">Ultimele 6 luni</option>
                        <option value="year">Tot anul</option>
                        <option value="all">Anterior</option>
                    </select>
                </div>
                <div style="height: 300px;">
                    <canvas id="regionTasksChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Team Workload -->
        <div class="col-lg-4">
            <div class="spor-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold m-0 text-dark">Încărcare Echipă</h6>
                </div>
                <div class="d-flex flex-column gap-4">
                    <?php if (empty($teamWorkload)): ?>
                    <p class="text-muted small mb-0">Nu există date disponibile.</p>
                    <?php else: ?>
                        <?php foreach ($teamWorkload as $member): 
                            $name = trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));
                            $name = $name ?: ($member['email'] ?? 'Necunoscut');
                            $percentage = $member['workload_percentage'] ?? 0;
                            $activeTasks = $member['active_tasks'] ?? 0;
                            $role = ucfirst($member['role'] ?? '');
                            
                            $progressClass = 'bg-monochrome-low';
                            if ($percentage >= 80) {
                                $progressClass = 'bg-monochrome-high';
                            } elseif ($percentage >= 50) {
                                $progressClass = 'bg-monochrome-medium';
                            }
                        ?>
                        <div>
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="fw-bold text-dark"><?= esc($name) ?></span>
                                <span class="text-monochrome-high fw-bold"><?= $percentage ?>%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar <?= $progressClass ?>" role="progressbar" style="width: <?= $percentage ?>%"></div>
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;"><?= $activeTasks ?> task-uri active<?= $role ? ' • ' . esc($role) : '' ?></small>
                        </div>
                        <?php endforeach; ?>
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
                        <p class="text-muted small mb-0 text-center">Nu există blocaje critice sau task-uri întârziate.</p>
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

    <!-- CONTRACTS GRID -->
    <h6 class="fw-bold text-dark mb-3 ps-1">Contracte</h6>
    <div class="row row-cols-1 row-cols-lg-3 g-4">
        <?php if (empty($contracts)): ?>
        <div class="col-12">
            <div class="spor-card p-4 text-center">
                <p class="text-muted mb-0">Nu există contracte disponibile.</p>
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($contracts as $contract): ?>
            <div class="col">
                <a href="<?= site_url('/dashboard/contract/' . $contract['id']) ?>" class="text-decoration-none">
                    <div class="spor-card interactive p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-bold mb-1 text-dark"><?= esc($contract['name']) ?></h5>
                                <?php if (!empty($contract['contract_number'])): ?>
                                <div class="text-muted small"><?= esc($contract['contract_number']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-file-earmark-text text-secondary"></i>
                            </div>
                        </div>
                        <div class="d-flex gap-4 border-top pt-3 border-light">
                            <div>
                                <div class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 600;">Tasks</div>
                                <div class="fw-bold text-dark"><?= $contract['active_tasks_count'] ?? 0 ?> Active</div>
                            </div>
                            <?php if (!empty($contract['progress_percentage'])): ?>
                            <div>
                                <div class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 600;">Progres</div>
                                <div class="fw-bold text-dark"><?= $contract['progress_percentage'] ?>%</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('regionTasksChart');
    if (!ctx) return;

    let chartInstance = null;
    const chartData = <?= json_encode($chartData) ?>;
    const periodFilter = document.getElementById('chartPeriodFilter');

    function initChart(data) {
        if (chartInstance) {
            chartInstance.destroy();
        }

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'Task-uri Deschise',
                    data: data.data || [],
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderColor: 'rgba(13, 110, 253, 0.8)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    function loadChartData(period) {
        fetch('<?= site_url('/dashboard/chart/tasks-region') ?>?period=' + period)
            .then(response => response.json())
            .then(data => {
                initChart(data);
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
            });
    }

    initChart(chartData);

    if (periodFilter) {
        periodFilter.addEventListener('change', function() {
            loadChartData(this.value);
        });
    }
});
</script>

<?= $this->endSection() ?>

