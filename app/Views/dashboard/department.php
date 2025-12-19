<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
helper('breadcrumbs');
$breadcrumbs = getBreadcrumbsForDepartment($department);
?>

<div class="fade-in">
    <!-- Breadcrumbs -->
    <div class="mb-3">
        <?= renderBreadcrumbs($breadcrumbs) ?>
    </div>

    <!-- Department Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <div class="d-flex gap-2 align-items-center mb-2">
                <?php if (!empty($department['region'])): ?>
                    <span class="badge bg-light text-dark border"><?= esc($department['region']['name']) ?></span>
                <?php endif; ?>
                <?php if (!empty($department['color_code'])): ?>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; background-color: <?= esc($department['color_code']) ?>20;">
                        <div style="width: 12px; height: 12px; background-color: <?= esc($department['color_code']) ?>; border-radius: 50%;"></div>
                    </div>
                <?php endif; ?>
            </div>
            <h3 class="fw-bold text-dark mb-1"><?= esc($department['name']) ?></h3>
            <?php if (!empty($department['head'])): ?>
                <p class="text-muted mb-0 mt-2">
                    <i class="bi bi-person-fill me-1"></i>Șef de Departament: <?= esc($department['head']['full_name']) ?>
                    <?php if (!empty($department['head']['email'])): ?>
                        <a href="mailto:<?= esc($department['head']['email']) ?>" class="text-decoration-none ms-2">
                            <i class="bi bi-envelope"></i>
                        </a>
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <p class="text-muted mb-0 mt-2">
                    <i class="bi bi-person-x me-1"></i>Fără șef de departament asignat
                </p>
            <?php endif; ?>
        </div>
        <div>
            <span class="spor-badge bg-subtle-blue"><?= count($tasks) ?> Sarcini Active</span>
            <?php if (($department['overdue_tasks_count'] ?? 0) > 0): ?>
                <span class="spor-badge bg-subtle-red ms-2"><?= $department['overdue_tasks_count'] ?> Întârziate</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tasks Grid -->
    <div class="row g-4">
        <?php if (empty($tasks)): ?>
            <div class="col-12">
                <div class="spor-card p-5 text-center">
                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-3 mb-0">Nu există sarcini active pentru acest departament în această Sucursală.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($tasks as $task):
                $priority = $task['priority'] ?? 'medium';
                $status = $task['status'] ?? 'new';

                $priorityBadgeClass = $priorityBadgeClasses[$priority] ?? 'bg-subtle-yellow';
                $priorityLabel = $priorityLabels[$priority] ?? ucfirst($priority);

                $statusBadgeClass = $statusBadgeClasses[$status] ?? 'bg-subtle-gray';
                $statusLabel = $statusLabels[$status] ?? ucfirst($status);
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

                        <?php if (!empty($task['assignees'])): ?>
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Asignați:</small>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach (array_slice($task['assignees'], 0, 2) as $assignee):
                                        $assigneeName = trim(($assignee['first_name'] ?? '') . ' ' . ($assignee['last_name'] ?? ''));
                                        $assigneeName = $assigneeName ?: ($assignee['email'] ?? 'Utilizator');
                                    ?>
                                        <span class="badge bg-light text-dark border small"><?= esc($assigneeName) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($task['assignees']) > 2): ?>
                                        <span class="badge bg-light text-dark border small">+<?= count($task['assignees']) - 2 ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($task['deadline'])):
                            $deadlineDate = strtotime($task['deadline']);
                            $isOverdue = $deadlineDate < time() && $status !== 'completed';
                        ?>
                            <div class="mb-3">
                                <small class="text-muted d-flex align-items-center gap-1 <?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                    <i class="bi bi-calendar4"></i>
                                    Deadline: <?= date('d.m.Y', $deadlineDate) ?>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge bg-danger-subtle text-danger small">Întârziat</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center border-top pt-3 border-light mt-auto">
                            <span class="spor-badge <?= $statusBadgeClass ?>">
                                <?= $statusLabel ?>
                            </span>
                            <i class="bi bi-arrow-right text-muted"></i>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>