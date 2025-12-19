<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-my-tasks" class="fade-in">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark">Sarcinile Mele</h3>
            <p class="text-muted m-0 mt-1">Sarcinile asignate sau create de tine.</p>
        </div>
        <a class="btn btn-spor-secondary" href="<?= site_url('tasks') ?>">
            <i class="bi bi-list-ul me-2"></i>Toate Sarcinile
        </a>
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

    <!-- Task Cards Grid by Status -->
    <div class="row g-4">
        <?php foreach ($tasksByStatus as $status => $tasks): ?>
            <?php if (!empty($tasks)): ?>
                <div class="col-12">
                    <div class="mb-3">
                        <h5 class="fw-bold text-dark">
                            <?= $statusLabels[$status] ?? ucfirst($status) ?>
                            <span class="badge bg-secondary ms-2"><?= count($tasks) ?></span>
                        </h5>
                    </div>
                    <div class="row g-3">
                        <?php foreach ($tasks as $task): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="spor-card p-4 h-100" style="cursor: pointer;" onclick="window.location.href='<?= site_url('tasks/view/' . $task['id']) ?>'">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1"><?= esc($task['title']) ?></h6>
                                            <small class="text-muted">#<?= $task['id'] ?></small>
                                        </div>
                                        <?php
                                        $priority = $task['priority'] ?? 'medium';
                                        $priorityBadgeClass = $priorityBadgeClasses[$priority] ?? 'bg-subtle-yellow';
                                        $priorityLabel = $priorityLabels[$priority] ?? ucfirst($priority);
                                        ?>
                                        <span class="spor-badge <?= $priorityBadgeClass ?>"><?= $priorityLabel ?></span>
                                    </div>

                                    <?php if (!empty($task['description'])): ?>
                                        <p class="text-secondary small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            <?= esc(substr($task['description'], 0, 100)) ?><?= strlen($task['description']) > 100 ? '...' : '' ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-1">Contract:</small>
                                        <span class="small fw-medium text-dark"><?= esc($task['contract_name'] ?? '-') ?></span>
                                    </div>

                                    <?php if (!empty($task['assignees'])): ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Asignați:</small>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php foreach (array_slice($task['assignees'], 0, 3) as $assignee): ?>
                                                    <?php
                                                    $assigneeName = trim(($assignee['first_name'] ?? '') . ' ' . ($assignee['last_name'] ?? ''));
                                                    $assigneeInitials = '';
                                                    if (!empty($assignee['first_name'])) $assigneeInitials .= mb_substr($assignee['first_name'], 0, 1);
                                                    if (!empty($assignee['last_name'])) $assigneeInitials .= mb_substr($assignee['last_name'], 0, 1);
                                                    if (empty($assigneeInitials)) $assigneeInitials = mb_substr($assignee['email'] ?? 'U', 0, 2);
                                                    $assigneeInitials = mb_strtoupper($assigneeInitials);
                                                    ?>
                                                    <span class="badge bg-light text-dark border"><?= esc($assigneeName ?: ($assignee['email'] ?? 'Utilizator')) ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($task['assignees']) > 3): ?>
                                                    <span class="badge bg-light text-dark border">+<?= count($task['assignees']) - 3 ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($task['deadline'])): ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-flex align-items-center gap-1">
                                                <i class="bi bi-calendar4"></i>
                                                Deadline: <?= date('d.m.Y', strtotime($task['deadline'])) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between align-items-center pt-3 border-top border-light">
                                        <small class="text-muted">
                                            Creat: <?= date('d.m.Y', strtotime($task['created_at'])) ?>
                                        </small>
                                        <span class="spor-badge <?= $statusBadgeClasses[$status] ?? 'bg-subtle-gray' ?>">
                                            <?= $statusLabels[$status] ?? ucfirst($status) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php
    $totalTasks = array_sum(array_map('count', $tasksByStatus));
    if ($totalTasks === 0):
    ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <p class="text-muted mt-3">Nu ai sarcini asignate sau create.</p>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>