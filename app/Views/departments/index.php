<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-departments" class="fade-in">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark">Departamente</h3>
            <p class="text-muted m-0 mt-1">Gestiune departamente organizaționale.</p>
        </div>
        <a class="btn btn-spor-primary" href="<?= site_url('departments/create') ?>"><i class="bi bi-building me-2"></i>Adaugă Departament</a>
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
        <table id="departmentsTable" class="table" style="width:100%">
            <thead>
                <tr>
                    <th>Nume</th>
                    <th>Culoare</th>
                    <th>Utilizatori</th>
                    <th>Sarcini</th>
                    <th class="text-end">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departments)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Nu există departamente de afișat.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($departments as $department): ?>
                        <tr>
                            <td class="fw-bold"><?= esc($department['name'] ?? '') ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 24px; height: 24px; background-color: <?= esc($department['color_code'] ?? '#808080') ?>; border-radius: 4px; border: 1px solid #ddd;"></div>
                                    <span class="small text-muted"><?= esc($department['color_code'] ?? '#808080') ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $department['users_count'] ?? 0 ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= $department['tasks_count'] ?? 0 ?></span>
                            </td>
                            <td class="text-end">
                                <a href="<?= site_url('departments/edit/' . $department['id']) ?>" class="btn btn-sm btn-light border" title="Editează">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button class="btn btn-sm btn-light border text-danger" onclick="confirmDelete(<?= $department['id'] ?>, '<?= esc($department['name'], 'js') ?>')" title="Șterge">
                                    <i class="bi bi-trash"></i>
                                </button>
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
                <p class="mb-0">Ești sigur că vrei să ștergi departamentul "<strong id="departmentName"></strong>"? Această acțiune nu poate fi anulată.</p>
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
        if (!$.fn.DataTable.isDataTable('#departmentsTable')) {
            $('#departmentsTable').DataTable({
                language: {
                    search: "",
                    searchPlaceholder: "Caută departament...",
                    lengthMenu: "Afișează _MENU_ departamente",
                    info: "Afișez _START_ - _END_ din _TOTAL_ departamente",
                    infoEmpty: "Afișez 0 - 0 din 0 departamente",
                    infoFiltered: "(filtrați din _MAX_ departamente)",
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

    function confirmDelete(departmentId, departmentName) {
        document.getElementById('departmentName').textContent = departmentName;
        const deleteForm = document.getElementById('deleteForm');
        deleteForm.action = '<?= site_url('departments/delete') ?>/' + departmentId;

        // Use AJAX for delete
        deleteForm.onsubmit = function(e) {
            e.preventDefault();

            fetch(deleteForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('input[name="<?= csrf_token() ?>"]')?.value || ''
                    },
                    body: new FormData(deleteForm)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Eroare la ștergerea departamentului.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Eroare la ștergerea departamentului.');
                });

            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
        };

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>

<?= $this->endSection() ?>