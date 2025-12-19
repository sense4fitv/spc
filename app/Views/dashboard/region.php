<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
helper('breadcrumbs');
$breadcrumbs = getBreadcrumbsForRegion($region);
?>

<div class="fade-in">
    <!-- Breadcrumbs -->
    <div class="mb-3">
        <?= renderBreadcrumbs($breadcrumbs) ?>
    </div>

    <!-- Region Header -->
    <div class="mb-5 border-bottom border-light pb-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h3 class="fw-bold text-dark mb-1"><?= esc($region['name']) ?></h3>
                <?php if (!empty($region['description'])): ?>
                    <p class="text-muted mb-0"><?= esc($region['description']) ?></p>
                <?php endif; ?>
                <?php if (!empty($region['manager_name'])): ?>
                    <p class="text-muted small mt-2 mb-0">
                        <i class="bi bi-person-fill me-1"></i>Responsabil: <?= esc($region['manager_name']) ?>
                        <?php if (!empty($region['manager_email'])): ?>
                            <a href="mailto:<?= esc($region['manager_email']) ?>" class="text-decoration-none ms-2">
                                <i class="bi bi-envelope"></i>
                            </a>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Region Stats -->
    <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
        <div class="col">
            <div class="spor-card p-4">
                <div class="text-muted fw-medium text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Contracte</div>
                <div class="mt-2">
                    <h3 class="fw-bold m-0"><?= $region['contracts_count'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="spor-card p-4">
                <div class="text-muted fw-medium text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Sarcini Active</div>
                <div class="mt-2">
                    <h3 class="fw-bold m-0"><?= $region['active_tasks_count'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="spor-card p-4">
                <div class="text-muted fw-medium text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Utilizatori</div>
                <div class="mt-2">
                    <h3 class="fw-bold m-0"><?= $region['users_count'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Departments List -->
    <?php if (!empty($departments)): ?>
        <h6 class="fw-bold text-dark mb-3 ps-1">Departamente cu Sarcini Active</h6>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-5">
            <?php foreach ($departments as $department): ?>
                <div class="col">
                    <a href="<?= site_url('/dashboard/department/' . $department['id'] . '/region/' . $region['id']) ?>" class="text-decoration-none">
                        <div class="spor-card interactive p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold mb-1 text-dark"><?= esc($department['name']) ?></h5>
                                    <?php if (!empty($department['head'])): ?>
                                        <div class="text-muted small mt-1">
                                            <i class="bi bi-person-fill me-1"></i>Șef: <?= esc($department['head']['full_name']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted small mt-1">
                                            <i class="bi bi-person-x me-1"></i>Fără șef de departament
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($department['color_code'])): ?>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background-color: <?= esc($department['color_code']) ?>20;">
                                        <div style="width: 20px; height: 20px; background-color: <?= esc($department['color_code']) ?>; border-radius: 50%;"></div>
                                    </div>
                                <?php else: ?>
                                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                                        <i class="bi bi-building text-secondary"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-4 border-top pt-3 border-light">
                                <div>
                                    <div class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 600;">Sarcini Active</div>
                                    <div class="fw-bold text-dark"><?= $department['active_tasks_count'] ?? 0 ?></div>
                                </div>
                                <?php if (($department['overdue_tasks_count'] ?? 0) > 0): ?>
                                    <div>
                                        <div class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 600;">Întârziate</div>
                                        <div class="fw-bold text-danger"><?= $department['overdue_tasks_count'] ?? 0 ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Contracts List -->
    <h6 class="fw-bold text-dark mb-3 ps-1">Contracte Active</h6>
    <div class="row g-4">
        <?php if (empty($contracts)): ?>
            <div class="col-12">
                <div class="spor-card p-4 text-center">
                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-3 mb-0">Nu există contracte în această Sucursală.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($contracts as $contract):
                $statusLabels = [
                    'planning' => 'Planificare',
                    'active' => 'În Execuție',
                    'on_hold' => 'În Așteptare',
                    'completed' => 'Finalizat',
                ];
                $statusBadgeClasses = [
                    'planning' => 'bg-subtle-gray',
                    'active' => 'bg-subtle-blue',
                    'on_hold' => 'bg-subtle-yellow',
                    'completed' => 'bg-subtle-green',
                ];
                $status = $contract['status'] ?? 'planning';
                $statusLabel = $statusLabels[$status] ?? ucfirst($status);
                $statusBadgeClass = $statusBadgeClasses[$status] ?? 'bg-subtle-gray';
            ?>
                <div class="col-12">
                    <a href="<?= site_url('/dashboard/contract/' . $contract['id']) ?>" class="text-decoration-none">
                        <div class="spor-card interactive p-4 d-flex align-items-center justify-content-between flex-wrap gap-4">
                            <div class="d-flex align-items-center gap-4">
                                <div class="bg-light border rounded d-flex align-items-center justify-content-center text-secondary" style="width: 56px; height: 56px;">
                                    <i class="bi bi-file-earmark-text fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold m-0 text-dark"><?= esc($contract['name']) ?></h5>
                                    <?php if (!empty($contract['contract_number'])): ?>
                                        <small class="text-muted"><?= esc($contract['contract_number']) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($contract['client_name'])): ?>
                                        <div class="text-muted small mt-1">Client: <?= esc($contract['client_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-4">
                                <?php if (!empty($contract['progress_percentage'])): ?>
                                    <div class="text-center">
                                        <div class="text-muted small mb-1">Progres</div>
                                        <div class="fw-bold text-dark"><?= $contract['progress_percentage'] ?>%</div>
                                        <div class="progress mt-1" style="width: 80px; height: 4px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $contract['progress_percentage'] ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <span class="spor-badge <?= $statusBadgeClass ?>"><?= $statusLabel ?></span>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>