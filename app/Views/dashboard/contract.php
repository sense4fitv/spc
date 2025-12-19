<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
helper('breadcrumbs');
$breadcrumbs = getBreadcrumbsForContract($contract);
?>

<div class="fade-in">
    <!-- Breadcrumbs -->
    <div class="mb-3">
        <?= renderBreadcrumbs($breadcrumbs) ?>
    </div>

    <!-- Contract Header -->
    <div class="mb-5 border-bottom border-light pb-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="d-flex gap-2 align-items-center mb-2">
                    <?php if (!empty($contract['contract_number'])): ?>
                        <span class="badge bg-light text-secondary border font-monospace fw-normal"><?= esc($contract['contract_number']) ?></span>
                    <?php endif; ?>
                    <?php
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
                    <span class="spor-badge <?= $statusBadgeClass ?>"><?= $statusLabel ?></span>
                </div>
                <h3 class="fw-bold text-dark mb-1"><?= esc($contract['name']) ?></h3>
                <?php if (!empty($contract['client_name'])): ?>
                    <p class="text-muted mb-2">Client: <?= esc($contract['client_name']) ?></p>
                <?php endif; ?>
                <?php if (!empty($contract['start_date']) || !empty($contract['end_date'])): ?>
                    <p class="text-muted small mb-0">
                        <?php if (!empty($contract['start_date'])): ?>
                            <i class="bi bi-calendar-event me-1"></i>Start: <?= date('d.m.Y', strtotime($contract['start_date'])) ?>
                        <?php endif; ?>
                        <?php if (!empty($contract['end_date'])): ?>
                            <span class="ms-3"><i class="bi bi-calendar-x me-1"></i>Final: <?= date('d.m.Y', strtotime($contract['end_date'])) ?></span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
            <?php if (!empty($contract['progress_percentage'])): ?>
                <div class="text-center">
                    <div class="text-muted small mb-1">Progres Total</div>
                    <div class="fw-bold text-dark" style="font-size: 1.5rem;"><?= $contract['progress_percentage'] ?>%</div>
                    <div class="progress mt-2" style="width: 120px; height: 8px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $contract['progress_percentage'] ?>%"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Subdivisions Grid -->
    <h6 class="fw-bold text-dark mb-3 ps-1">Subdiviziuni & Faze</h6>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (empty($subdivisions)): ?>
            <div class="col-12">
                <div class="spor-card p-4 text-center">
                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-3 mb-0">Nu există subdiviziuni pentru acest contract.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($subdivisions as $subdivision): ?>
                <div class="col">
                    <a href="<?= site_url('/dashboard/subdivision/' . $subdivision['id']) ?>" class="text-decoration-none">
                        <div class="spor-card interactive p-4 h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between mb-3">
                                <?php if (!empty($subdivision['code'])): ?>
                                    <span class="badge bg-light text-secondary border font-monospace fw-normal"><?= esc($subdivision['code']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($subdivision['tasks_count']) && $subdivision['tasks_count'] > 0): ?>
                                    <div class="d-flex gap-1">
                                        <span class="rounded-circle bg-success" style="width: 8px; height: 8px; margin-top: 6px;"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h5 class="fw-bold text-dark mb-2"><?= esc($subdivision['name']) ?></h5>
                            <?php if (!empty($subdivision['details'])): ?>
                                <p class="text-secondary small mb-4 flex-grow-1" style="line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= esc($subdivision['details']) ?>
                                </p>
                            <?php endif; ?>
                            <div class="border-top pt-3 d-flex justify-content-between align-items-center border-light mt-auto">
                                <small class="fw-bold text-dark">
                                    <i class="bi bi-check2-square me-1"></i>
                                    <?= $subdivision['tasks_count'] ?? 0 ?> Sarcini
                                </small>
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