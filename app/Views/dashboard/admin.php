<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>


<!-- VIEW 1: DASHBOARD OVERVIEW -->
<div id="view-dashboard" class="fade-in">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark">Privire de ansamblu</h3>
            <p class="text-muted m-0 mt-1">Status operațional la data de <?= date('d M Y') ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('/tasks/create') ?>" class="btn btn-spor-primary"><i class="bi bi-plus-lg me-2"></i>Sarcină Nouă</a>
        </div>
    </div>

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
                <div class="text-muted fw-medium text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Contracte</div>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h3 class="fw-bold m-0"><?= $kpis['active_contracts'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="spor-card p-4 h-100">
                <div class="text-muted fw-medium text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Echipă</div>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h3 class="fw-bold m-0"><?= $kpis['active_users'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- REGIONS GRID -->
    <h6 class="fw-bold text-dark mb-3 ps-1">Sucursale Operaționale</h6>
    <div class="row row-cols-1 row-cols-lg-3 g-4">
        <?php if (empty($regions)): ?>
            <div class="col-12">
                <div class="spor-card p-4 text-center">
                    <p class="text-muted mb-0">Nu există succursale disponibile.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($regions as $region): ?>
                <div class="col">
                    <a href="<?= site_url('/dashboard/region/' . $region['id']) ?>" class="text-decoration-none">
                        <div class="spor-card interactive p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <h5 class="fw-bold mb-1 text-dark"><?= esc($region['name']) ?></h5>
                                    <?php if (!empty($region['manager_name'])): ?>
                                        <div class="text-muted small">Resp: <?= esc($region['manager_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-building text-secondary"></i>
                                </div>
                            </div>
                            <div class="d-flex gap-4 border-top pt-3 border-light">
                                <div>
                                    <div class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 600;">Contracte</div>
                                    <div class="fw-bold text-dark"><?= $region['contracts_count'] ?? 0 ?> Active</div>
                                </div>
                                <div>
                                    <div class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 600;">Sarcini</div>
                                    <div class="fw-bold text-dark"><?= $region['active_tasks_count'] ?? 0 ?> Active</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- NEW: SPLIT CHART & TEAM LOAD -->
    <div class="row g-4 mb-5 mt-5">
        <!-- Chart Section (Left) -->
        <div class="col-lg-8">
            <div class="spor-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0 text-dark">Sarcini Deschise per Sucursală</h5>
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

        <!-- Widget: Team Workload (Right) - MONOCHROME UPDATE -->
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

                            // Determine progress bar class based on percentage
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
                                <small class="text-muted" style="font-size: 0.75rem;"><?= $activeTasks ?> sarcini active<?= $role ? ' • ' . esc($role) : '' ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- NEW: CRITICAL BLOCKERS & UPCOMING DEADLINES -->
    <div class="row g-4 mb-5">
        <!-- Widget: Critical Blockers -->
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

                            // Determine badge
                            $badgeClass = 'bg-danger-subtle text-danger';
                            $badgeText = $task['days_info'] ?? '';
                            if ($task['status'] === 'blocked') {
                                $badgeClass = 'bg-danger-subtle text-danger';
                                $badgeText = 'Blocat';
                            } elseif (strpos($badgeText, 'Întârziat') !== false) {
                                $badgeClass = 'bg-danger-subtle text-danger';
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

        <!-- Widget: Upcoming Deadlines -->
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

                            // Map Romanian day names
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


</div>


<!-- VIEW 8: USER PROFILE PAGE (NEW) -->
<div id="view-user-profile" class="d-none fade-in">
    <div class="mb-5 border-bottom border-light pb-4">
        <h3 class="fw-bold text-dark m-0">Profilul Meu</h3>
        <p class="text-muted m-0 mt-1">Gestionează setările contului tău.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs nav-tabs-custom" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-selected="true">General</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab" aria-selected="false">Notificări</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab" aria-selected="false">Jurnal Activitate</button>
                </li>
            </ul>

            <!-- Tabs Content -->
            <div class="tab-content" id="profileTabsContent">

                <!-- TAB 1: General Settings -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <div class="spor-card p-5">
                        <!-- Avatar Section -->
                        <div class="d-flex align-items-center gap-4 mb-5">
                            <div class="bg-light border rounded-circle d-flex align-items-center justify-content-center text-secondary fw-bold fs-3" style="width: 80px; height: 80px;">PA</div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Poză de profil</h6>
                                <p class="text-muted small mb-2">JPG, GIF sau PNG. Max 1MB.</p>
                                <button class="btn btn-sm btn-spor-secondary">Schimbă</button>
                            </div>
                        </div>

                        <h6 class="fw-bold text-dark mb-4">Date Personale</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Nume</label>
                                <input type="text" class="form-control" value="Patronat Admin">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Email</label>
                                <input type="email" class="form-control" value="supercom@hq.ro" readonly>
                            </div>
                        </div>

                        <hr class="border-light my-4">

                        <h6 class="fw-bold text-dark mb-4">Securitate</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Parolă Nouă</label>
                                <input type="password" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Confirmă Parola</label>
                                <input type="password" class="form-control">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button class="btn btn-spor-primary px-4" onclick="showToast('Profil actualizat!', 'success')">Salvează Modificări</button>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Notifications -->
                <div class="tab-pane fade" id="notifications" role="tabpanel">
                    <div class="spor-card p-5">
                        <h6 class="fw-bold text-dark mb-4">Preferințe Email</h6>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <div class="fw-medium text-dark">Sarcini noi atribuite</div>
                                <div class="small text-muted">Primește email când ești desemnat responsabil.</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" checked>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <div class="fw-medium text-dark">Avertismente Deadline</div>
                                <div class="small text-muted">Notificări cu 24h înainte de termen.</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" checked>
                            </div>
                        </div>

                        <hr class="border-light my-4">

                        <h6 class="fw-bold text-dark mb-4">Notificări Sistem (Push)</h6>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <div class="fw-medium text-dark">Comentarii și mențiuni</div>
                                <div class="small text-muted">Notificări in-app când cineva comentează.</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" checked>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: Activity Log (Audit) -->
                <div class="tab-pane fade" id="activity" role="tabpanel">
                    <div class="spor-card p-5">
                        <h6 class="fw-bold text-dark mb-5">Istoric Recent</h6>

                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="fw-bold text-dark small">Utilizator Nou Creat</div>
                                <div class="text-secondary small mt-1">Ai creat contul pentru <span class="fw-medium text-dark">Elena Dobre</span> (Executant).</div>
                                <div class="text-muted small mt-2" style="font-size: 0.75rem;">Astăzi, 14:30</div>
                            </div>

                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="fw-bold text-dark small">Export Raport</div>
                                <div class="text-secondary small mt-1">Ai descărcat raportul lunar pentru <span class="fw-medium text-dark">Regiunea Sud</span>.</div>
                                <div class="text-muted small mt-2" style="font-size: 0.75rem;">Ieri, 09:15</div>
                            </div>

                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="fw-bold text-dark small">Autentificare</div>
                                <div class="text-secondary small mt-1">Autentificare reușită de pe IP 192.168.1.1.</div>
                                <div class="text-muted small mt-2" style="font-size: 0.75rem;">Ieri, 09:00</div>
                            </div>

                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="fw-bold text-dark small">Actualizare Contract</div>
                                <div class="text-secondary small mt-1">Ai schimbat statusul contractului #CN-2024-001 în "In Execuție".</div>
                                <div class="text-muted small mt-2" style="font-size: 0.75rem;">25 Nov, 16:45</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
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

        // Initialize chart
        function initChart(data) {
            if (chartInstance) {
                chartInstance.destroy();
            }

            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        label: 'Sarcini Deschise',
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

        // Load chart data for selected period
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

        // Initialize with default data
        initChart(chartData);

        // Handle period filter change
        if (periodFilter) {
            periodFilter.addEventListener('change', function() {
                const selectedPeriod = this.value;
                loadChartData(selectedPeriod);
            });
        }
    });
</script>

<?= $this->endSection() ?>