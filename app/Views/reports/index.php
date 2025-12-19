<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="fade-in">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark">Rapoarte</h3>
            <p class="text-muted m-0 mt-1">Gestiune și analiză rapoarte operaționale.</p>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Quick Period Filters -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <button class="btn btn-sm btn-outline-secondary active" data-period="7days" onclick="setQuickFilter('7days')">
            Ultima săptămână
        </button>
        <button class="btn btn-sm btn-outline-secondary" data-period="30days" onclick="setQuickFilter('30days')">
            Ultimele 30 zile
        </button>
        <button class="btn btn-sm btn-outline-secondary" data-period="3months" onclick="setQuickFilter('3months')">
            Ultimele 3 luni
        </button>
        <button class="btn btn-sm btn-outline-secondary" data-period="custom" onclick="toggleCustomDateRange()">
            <i class="bi bi-calendar3 me-1"></i>Perioadă custom
        </button>
    </div>

    <!-- Advanced Filters (Collapsible) -->
    <div class="spor-card p-3 mb-5" id="advancedFilters">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Regiune</label>
                <select class="form-select form-select-sm" id="filterRegion" onchange="updatePreviewStats()">
                    <option value="">Toate regiunile</option>
                    <?php foreach ($regions as $region): ?>
                        <option value="<?= $region['id'] ?>"><?= esc($region['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">De la</label>
                <input type="date" class="form-control form-control-sm" id="filterDateFrom" value="<?= $default_filters['date_from'] ?>" onchange="updatePreviewStats()">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Până la</label>
                <input type="date" class="form-control form-control-sm" id="filterDateTo" value="<?= $default_filters['date_to'] ?>" onchange="updatePreviewStats()">
            </div>
        </div>
    </div>

    <!-- Reports Cards -->
    <div class="row g-4">
        <!-- Report 1: Operational Regional -->
        <div class="col-12">
            <div class="spor-card p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-subtle text-primary rounded p-2">
                            <i class="bi bi-file-earmark-bar-graph fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold m-0 text-dark">Raport Operațional Regional</h5>
                            <small class="text-muted">Analiză pe regiuni, KPIs, performanță echipă</small>
                        </div>
                    </div>
                    <span class="badge bg-light text-secondary border" id="operationalLastUpdate">
                        <i class="bi bi-clock me-1"></i>Actualizat acum
                    </span>
                </div>
                
                <div class="mb-3">
                    <p class="text-secondary small mb-2"><strong>Include:</strong></p>
                    <ul class="text-secondary small mb-0" style="list-style: none; padding-left: 0;">
                        <li><i class="bi bi-check-circle text-success me-2"></i>Task-uri per regiune</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Workload distribution</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Comparație regiuni (grafice)</li>
                    </ul>
                </div>
                
                <div class="border-top pt-3 mb-3" id="operationalPreview">
                    <div class="row g-3">
                        <div class="col-4">
                            <small class="text-muted d-block">Regiuni</small>
                            <strong id="operationalRegions"><?= $preview_stats['operational']['regions'] ?? 0 ?></strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Task-uri Active</small>
                            <strong id="operationalTasks"><?= $preview_stats['operational']['tasks'] ?? 0 ?></strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Utilizatori</small>
                            <strong id="operationalUsers"><?= $preview_stats['operational']['users'] ?? 0 ?></strong>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2 border-top pt-3">
                    <button class="btn btn-sm btn-spor-secondary" onclick="previewReport('operational')">
                        <i class="bi bi-eye me-2"></i>Preview
                    </button>
                    <button class="btn btn-sm btn-spor-secondary" onclick="exportReport('operational', 'excel')">
                        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
                    </button>
                    <button class="btn btn-sm btn-spor-primary" onclick="exportReport('operational', 'pdf')">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- Report 2: Contracts Performance -->
        <div class="col-12">
            <div class="spor-card p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success-subtle text-success rounded p-2">
                            <i class="bi bi-file-earmark-text fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold m-0 text-dark">Raport Contracte & Performanță</h5>
                            <small class="text-muted">Progres contracte, task-uri, deadline-uri</small>
                        </div>
                    </div>
                    <span class="badge bg-light text-secondary border" id="contractsLastUpdate">
                        <i class="bi bi-clock me-1"></i>Actualizat acum
                    </span>
                </div>
                
                <div class="mb-3">
                    <p class="text-secondary small mb-2"><strong>Include:</strong></p>
                    <ul class="text-secondary small mb-0" style="list-style: none; padding-left: 0;">
                        <li><i class="bi bi-check-circle text-success me-2"></i>Contracte active cu progres detaliated</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Status breakdown per contract</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Deadline-uri următoare</li>
                    </ul>
                </div>
                
                <div class="border-top pt-3 mb-3" id="contractsPreview">
                    <div class="row g-3">
                        <div class="col-4">
                            <small class="text-muted d-block">Contracte</small>
                            <strong id="contractsTotal"><?= $preview_stats['contracts']['contracts'] ?? 0 ?></strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Active</small>
                            <strong id="contractsActive"><?= $preview_stats['contracts']['active'] ?? 0 ?></strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Task-uri</small>
                            <strong id="contractsTasks"><?= $preview_stats['contracts']['tasks'] ?? 0 ?></strong>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2 border-top pt-3">
                    <button class="btn btn-sm btn-spor-secondary" onclick="previewReport('contracts')">
                        <i class="bi bi-eye me-2"></i>Preview
                    </button>
                    <button class="btn btn-sm btn-spor-secondary" onclick="exportReport('contracts', 'excel')">
                        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
                    </button>
                    <button class="btn btn-sm btn-spor-primary" onclick="exportReport('contracts', 'pdf')">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- Report 3: Resources -->
        <div class="col-12">
            <div class="spor-card p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-info-subtle text-info rounded p-2">
                            <i class="bi bi-people fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold m-0 text-dark">Raport Resurse</h5>
                            <small class="text-muted">Utilizatori activi, workload, top performers</small>
                        </div>
                    </div>
                    <span class="badge bg-light text-secondary border" id="resourcesLastUpdate">
                        <i class="bi bi-clock me-1"></i>Actualizat acum
                    </span>
                </div>
                
                <div class="mb-3">
                    <p class="text-secondary small mb-2"><strong>Include:</strong></p>
                    <ul class="text-secondary small mb-0" style="list-style: none; padding-left: 0;">
                        <li><i class="bi bi-check-circle text-success me-2"></i>Utilizatori activi per regiune</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Workload distribution</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Top performers</li>
                    </ul>
                </div>
                
                <div class="border-top pt-3 mb-3" id="resourcesPreview">
                    <div class="row g-3">
                        <div class="col-4">
                            <small class="text-muted d-block">Utilizatori</small>
                            <strong id="resourcesUsers"><?= $preview_stats['resources']['users'] ?? 0 ?></strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Task-uri Create</small>
                            <strong id="resourcesCreated"><?= $preview_stats['resources']['created'] ?? 0 ?></strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Task-uri Finalizate</small>
                            <strong id="resourcesCompleted"><?= $preview_stats['resources']['completed'] ?? 0 ?></strong>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2 border-top pt-3">
                    <button class="btn btn-sm btn-spor-secondary" onclick="previewReport('resources')">
                        <i class="bi bi-eye me-2"></i>Preview
                    </button>
                    <button class="btn btn-sm btn-spor-secondary" onclick="exportReport('resources', 'excel')">
                        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
                    </button>
                    <button class="btn btn-sm btn-spor-primary" onclick="exportReport('resources', 'pdf')">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- Report 4: Critical Tasks -->
        <div class="col-12">
            <div class="spor-card p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-danger-subtle text-danger rounded p-2">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold m-0 text-dark">Raport Task-uri Critice</h5>
                            <small class="text-muted">Task-uri blocked, întârziate, prioritare</small>
                        </div>
                    </div>
                    <span class="badge bg-light text-secondary border" id="criticalLastUpdate">
                        <i class="bi bi-clock me-1"></i>Actualizat acum
                    </span>
                </div>
                
                <div class="mb-3">
                    <p class="text-secondary small mb-2"><strong>Include:</strong></p>
                    <ul class="text-secondary small mb-0" style="list-style: none; padding-left: 0;">
                        <li><i class="bi bi-check-circle text-success me-2"></i>Task-uri blocked + întârziate</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Top 20 prioritare critică</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Task-uri expirate > 7 zile</li>
                    </ul>
                </div>
                
                <div class="border-top pt-3 mb-3" id="criticalPreview">
                    <div class="row g-3">
                        <div class="col-4">
                            <small class="text-muted d-block">Blocate</small>
                            <strong id="criticalBlocked" class="text-danger"><?= $preview_stats['critical']['blocked'] ?? 0 ?></strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Întârziate</small>
                            <strong id="criticalOverdue" class="text-danger"><?= $preview_stats['critical']['overdue'] ?? 0 ?></strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Critice</small>
                            <strong id="criticalTotal" class="text-warning"><?= $preview_stats['critical']['critical'] ?? 0 ?></strong>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2 border-top pt-3">
                    <button class="btn btn-sm btn-spor-secondary" onclick="previewReport('critical')">
                        <i class="bi bi-eye me-2"></i>Preview
                    </button>
                    <button class="btn btn-sm btn-spor-secondary" onclick="exportReport('critical', 'excel')">
                        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
                    </button>
                    <button class="btn btn-sm btn-spor-primary" onclick="exportReport('critical', 'pdf')">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="previewModalLabel">Preview Raport</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="previewModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Se încarcă...</span>
                    </div>
                    <p class="text-muted mt-3">Se încarcă datele raportului...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-spor-secondary" data-bs-dismiss="modal">Închide</button>
                <button type="button" class="btn btn-spor-primary" id="exportFromPreview">
                    <i class="bi bi-download me-2"></i>Export
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentReportType = null;
let previewModal = null;

document.addEventListener('DOMContentLoaded', function() {
    previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
});

function setQuickFilter(period) {
    // Remove active class from all buttons
    document.querySelectorAll('[data-period]').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Add active to clicked button
    event.target.closest('[data-period]').classList.add('active');
    
    const today = new Date();
    let dateFrom, dateTo = today.toISOString().split('T')[0];
    
    switch(period) {
        case '7days':
            dateFrom = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
            break;
        case '30days':
            dateFrom = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
            break;
        case '3months':
            dateFrom = new Date(today.getTime() - 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
            break;
        case 'custom':
            // Custom dates already set
            return;
    }
    
    document.getElementById('filterDateFrom').value = dateFrom;
    document.getElementById('filterDateTo').value = dateTo;
    
    updatePreviewStats();
}

function toggleCustomDateRange() {
    // Just enable the date inputs
    setQuickFilter('custom');
}

function getFilters() {
    return {
        date_from: document.getElementById('filterDateFrom').value,
        date_to: document.getElementById('filterDateTo').value,
        region_id: document.getElementById('filterRegion').value || null,
    };
}

function updatePreviewStats() {
    // TODO: Implement AJAX call to update preview stats
    // For now, just show loading state
    console.log('Updating preview stats with filters:', getFilters());
}

function previewReport(type) {
    currentReportType = type;
    previewModal.show();
    
    const filters = getFilters();
    const params = new URLSearchParams(filters);
    
    fetch(`<?= site_url('/reports/preview') ?>/${type}?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('previewModalBody').innerHTML = generatePreviewHTML(type, data.data);
                document.getElementById('previewModalLabel').textContent = getReportTitle(type);
                
                // Update export button
                document.getElementById('exportFromPreview').onclick = function() {
                    exportReport(type, 'pdf');
                };
            } else {
                document.getElementById('previewModalBody').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>${data.message || 'Eroare la încărcarea raportului.'}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('previewModalBody').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i>Eroare la încărcarea raportului: ${error.message}
                </div>
            `;
        });
}

function exportReport(type, format) {
    const filters = getFilters();
    const params = new URLSearchParams(filters);
    
    window.location.href = `<?= site_url('/reports/export') ?>/${type}/${format}?${params}`;
}

function getReportTitle(type) {
    const titles = {
        'operational': 'Raport Operațional Regional',
        'contracts': 'Raport Contracte & Performanță',
        'resources': 'Raport Resurse',
        'critical': 'Raport Task-uri Critice',
    };
    return titles[type] || 'Raport';
}

function generatePreviewHTML(type, data) {
    // Simple preview generation - can be enhanced
    let html = '<div class="table-responsive"><table class="table table-sm">';
    
    switch(type) {
        case 'operational':
            html += '<thead><tr><th>Regiune</th><th>Contracte</th><th>Task-uri Active</th><th>Utilizatori</th></tr></thead><tbody>';
            data.regions.forEach(region => {
                html += `<tr>
                    <td>${region.name}</td>
                    <td>${region.contracts_count}</td>
                    <td>${region.active_tasks_count}</td>
                    <td>${region.users_count}</td>
                </tr>`;
            });
            break;
        // Add other cases...
    }
    
    html += '</tbody></table></div>';
    return html;
}
</script>

<?= $this->endSection() ?>

