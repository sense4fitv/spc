<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-dashboard-active-tasks" class="fade-in">
    <a href="<?= site_url('dashboard') ?>" class="btn btn-link text-secondary text-decoration-none p-0 mb-4 fw-medium" style="font-size: 0.85rem;">
        <i class="bi bi-arrow-left me-1"></i> Înapoi la Dashboard
    </a>

    <div class="mb-4">
        <h3 class="fw-bold m-0 text-dark">Sarcini Active</h3>
        <p class="text-muted m-0 mt-1">Toate sarcinile active (neterminate).</p>
    </div>

    <?php if (empty($tasks)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <p class="text-muted mt-3">Nu există sarcini active.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($tasks as $task): ?>
                <div class="col-md-6 col-lg-4">
                    <a href="<?= site_url('tasks/view/' . $task['id']) ?>" class="text-decoration-none">
                        <div class="spor-card p-4 h-100" style="cursor: pointer;">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold text-dark mb-1"><?= esc($task['title']) ?></h6>
                                    <small class="text-muted">#<?= $task['id'] ?></small>
                                </div>
                                <span class="spor-badge <?= $statusBadgeClasses[$task['status']] ?? 'bg-subtle-gray' ?> ms-2">
                                    <?= $statusLabels[$task['status']] ?? ucfirst($task['status']) ?>
                                </span>
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

                            <?php if (!empty($task['deadline'])): ?>
                                <?php
                                $deadlineDate = strtotime($task['deadline']);
                                $isOverdue = $deadlineDate < time() && $task['status'] !== 'completed';
                                ?>
                                <div class="mb-3">
                                    <small class="text-muted d-flex align-items-center gap-1 <?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                        <i class="bi bi-calendar4"></i>
                                        Termen limită: <?= date('d.m.Y', $deadlineDate) ?>
                                        <?php if ($isOverdue): ?>
                                            <span class="badge bg-danger ms-1">Întârziat</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-center pt-3 border-top border-light">
                                <small class="text-muted">
                                    Creat: <?= date('d.m.Y', strtotime($task['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

