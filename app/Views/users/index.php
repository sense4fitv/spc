<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-users" class="fade-in">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark">Utilizatori</h3>
            <p class="text-muted m-0 mt-1">Gestiune conturi și roluri.</p>
        </div>
        <?php if ($canCreate): ?>
            <a class="btn btn-spor-primary" href="<?= site_url('users/create') ?>"><i class="bi bi-person-plus me-2"></i>Adaugă Utilizator</a>
        <?php endif; ?>
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
        <table id="usersTable" class="table" style="width:100%">
            <thead>
                <tr>
                    <th>Nume</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Regiune</th>
                    <?php if ($isManager): ?>
                        <th>Sarcini</th>
                    <?php endif; ?>
                    <th>Status</th>
                    <th class="text-end">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="<?= $isManager ? '7' : '6' ?>" class="text-center text-muted py-4">
                            Nu există utilizatori de afișat.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="fw-bold"><?= esc($user['first_name'] ?? '') ?> <?= esc($user['last_name'] ?? '') ?></td>
                            <td><?= esc($user['email'] ?? '') ?></td>
                            <td>
                                <?php
                                $role = $user['role'] ?? '';
                                $badgeClass = $roleBadgeClasses[$role] ?? 'bg-subtle-gray';
                                $displayName = $roleDisplayNames[$role] ?? ucfirst($role);
                                ?>
                                <span class="spor-badge <?= $badgeClass ?>">
                                    <?= $displayName ?>
                                </span>
                            </td>
                            <td><?= esc($user['region_name'] ?? '-') ?></td>
                            <?php if ($isManager): ?>
                                <td>
                                    <span class="badge bg-secondary"><?= $user['task_count'] ?? 0 ?></span>
                                </td>
                            <?php endif; ?>
                            <td>
                                <?php if (($user['active'] ?? 1) == 1): ?>
                                    <span class="text-success small fw-bold"><i class="bi bi-dot"></i> Activ</span>
                                <?php else: ?>
                                    <span class="text-muted small fw-bold"><i class="bi bi-dot"></i> Inactiv</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if (isset($canEdit) && is_callable($canEdit) && $canEdit($user['id'])): ?>
                                    <a href="<?= site_url('users/edit/' . $user['id']) ?>" class="btn btn-sm btn-light border" title="Editează">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($canDelete && ($user['id'] ?? 0) != session()->get('user_id')): ?>
                                    <button class="btn btn-sm btn-light border text-danger" onclick="confirmDelete(<?= $user['id'] ?>)" title="Șterge">
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
                <p class="mb-0">Ești sigur că vrei să ștergi acest utilizator? Această acțiune nu poate fi anulată.</p>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 pe-4">
                <button type="button" class="btn btn-spor-secondary" data-bs-dismiss="modal">Anulează</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="POST">
                    <button type="submit" class="btn btn-danger">Șterge</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTables only if not already initialized
        if (!$.fn.DataTable.isDataTable('#usersTable')) {
            $('#usersTable').DataTable({
                language: {
                    search: "",
                    searchPlaceholder: "Caută utilizator...",
                    lengthMenu: "Afișează _MENU_ utilizatori",
                    info: "Afișez _START_ - _END_ din _TOTAL_ utilizatori",
                    infoEmpty: "Afișez 0 - 0 din 0 utilizatori",
                    infoFiltered: "(filtrați din _MAX_ utilizatori)",
                    paginate: {
                        first: "«",
                        last: "»",
                        next: "›",
                        previous: "‹"
                    }
                },
                order: [
                    [0, 'asc']
                ],
                pageLength: 25,
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
            });
        }
    });

    function confirmDelete(userId) {
        const deleteForm = document.getElementById('deleteForm');
        deleteForm.action = '<?= site_url('users/delete') ?>/' + userId;

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>

<?= $this->endSection() ?>