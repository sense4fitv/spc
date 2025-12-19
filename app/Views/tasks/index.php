<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-tasks" class="fade-in">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark">Sarcini</h3>
            <p class="text-muted m-0 mt-1">Gestiune sarcini și asigurări.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-spor-secondary" href="<?= site_url('tasks/my-tasks') ?>">
                <i class="bi bi-grid-3x3-gap me-2"></i>Sarcinile mele
            </a>
            <?php if ($canCreate): ?>
                <a class="btn btn-spor-primary" href="<?= site_url('tasks/create') ?>">
                    <i class="bi bi-plus-lg me-2"></i>Sarcină Nouă
                </a>
            <?php endif; ?>
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

    <div class="spor-card p-3">
        <table id="tasksTable" class="table" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Titlu</th>
                    <th>Subdiviziune</th>
                    <th>Contract</th>
                    <th>Asignați</th>
                    <th>Status</th>
                    <th>Prioritate</th>
                    <th>Deadline</th>
                    <th class="text-end">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            Nu există sarcini de afișat.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <tr style="cursor: pointer;" onclick="window.location.href='<?= site_url('tasks/view/' . $task['id']) ?>'">
                            <td class="font-monospace text-muted">#<?= $task['id'] ?></td>
                            <td class="fw-bold"><?= esc($task['title']) ?></td>
                            <td><?= esc($task['subdivision_name'] ?? '-') ?></td>
                            <td><?= esc($task['contract_name'] ?? '-') ?></td>
                            <td>
                                <?php
                                $assignees = $task['assignees_names'] ?? [];
                                if (empty($assignees)):
                                ?>
                                    <span class="text-muted">-</span>
                                <?php else: ?>
                                    <?php
                                    $assigneeNames = array_slice($assignees, 0, 2);
                                    echo esc(implode(', ', $assigneeNames));
                                    if (count($assignees) > 2):
                                    ?>
                                        <span class="text-muted"> +<?= count($assignees) - 2 ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status = $task['status'] ?? 'new';
                                $badgeClass = $statusBadgeClasses[$status] ?? 'bg-subtle-gray';
                                $statusLabel = $statusLabels[$status] ?? ucfirst($status);
                                ?>
                                <span class="spor-badge <?= $badgeClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $priority = $task['priority'] ?? 'medium';
                                $badgeClass = $priorityBadgeClasses[$priority] ?? 'bg-subtle-yellow';
                                $priorityLabel = $priorityLabels[$priority] ?? ucfirst($priority);
                                ?>
                                <span class="spor-badge <?= $badgeClass ?>">
                                    <?= $priorityLabel ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($task['deadline'])): ?>
                                    <?= date('d.m.Y', strtotime($task['deadline'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end" onclick="event.stopPropagation();">
                                <?php if (isset($canEdit) && is_callable($canEdit) && $canEdit($task['id'])): ?>
                                    <a href="<?= site_url('tasks/edit/' . $task['id']) ?>" class="btn btn-sm btn-light border" title="Editează">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (isset($canDelete) && is_callable($canDelete) && $canDelete($task['id'])): ?>
                                    <button class="btn btn-sm btn-light border text-danger" onclick="confirmDelete(<?= $task['id'] ?>)" title="Șterge">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark">Confirmare Ștergere</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-4">
                <p class="mb-0">Ești sigur că vrei să ștergi acestă sarcină? Această acțiune nu poate fi anulată.</p>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 pe-4">
                <button type="button" class="btn btn-spor-secondary" data-bs-dismiss="modal">Anulează</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">Șterge</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTables only if not already initialized
        if (!$.fn.DataTable.isDataTable('#tasksTable')) {
            $('#tasksTable').DataTable({
                language: {
                    search: "",
                    searchPlaceholder: "Caută sarcină...",
                    lengthMenu: "Afișează _MENU_ sarcini",
                    info: "Afișez _START_ - _END_ din _TOTAL_ sarcini",
                    infoEmpty: "Afișez 0 - 0 din 0 sarcini",
                    infoFiltered: "(filtrați din _MAX_ sarcini)",
                    paginate: {
                        first: "«",
                        last: "»",
                        next: "›",
                        previous: "‹"
                    }
                },
                order: [
                    [0, 'desc']
                ],
                pageLength: 25,
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
            });
        }
    });

    function confirmDelete(taskId) {
        const deleteForm = document.getElementById('deleteForm');
        deleteForm.action = '<?= site_url('tasks/delete') ?>/' + taskId;

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>

<?= $this->endSection() ?>