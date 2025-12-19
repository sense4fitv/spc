<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-subdivisions" class="fade-in">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark">Subdiviziuni</h3>
            <p class="text-muted m-0 mt-1">Gestiune subdiviziuni ale contractelor.</p>
        </div>
        <?php if ($canCreate): ?>
            <a class="btn btn-spor-primary" href="<?= site_url('subdivisions/create') ?>"><i class="bi bi-plus-circle me-2"></i>Adaugă Subdiviziune</a>
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
        <table id="subdivisionsTable" class="table" style="width:100%">
            <thead>
                <tr>
                    <th>Cod</th>
                    <th>Nume</th>
                    <th>Contract</th>
                    <th>Regiune</th>
                    <th>Sarcini</th>
                    <th>Detalii</th>
                    <th class="text-end">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subdivisions)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Nu există subdiviziuni de afișat.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($subdivisions as $subdivision): ?>
                        <tr>
                            <td class="fw-bold"><?= esc($subdivision['code'] ?? '') ?></td>
                            <td><?= esc($subdivision['name'] ?? '') ?></td>
                            <td>
                                <?= esc($subdivision['contract_name'] ?? '-') ?>
                                <?php if (!empty($subdivision['contract_number'])): ?>
                                    <br><small class="text-muted"><?= esc($subdivision['contract_number']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($subdivision['region_name'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-info"><?= $subdivision['tasks_count'] ?? 0 ?></span>
                            </td>
                            <td>
                                <?php if (!empty($subdivision['details'])): ?>
                                    <span class="text-muted small"><?= esc(substr($subdivision['details'], 0, 50)) ?><?= strlen($subdivision['details']) > 50 ? '...' : '' ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if (is_callable($canEdit) && $canEdit($subdivision['id'])): ?>
                                    <a href="<?= site_url('subdivisions/edit/' . $subdivision['id']) ?>" class="btn btn-sm btn-light border" title="Editează">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (is_callable($canDelete) && $canDelete($subdivision['id'])): ?>
                                    <button class="btn btn-sm btn-light border text-danger" onclick="confirmDelete(<?= $subdivision['id'] ?>, '<?= esc($subdivision['code'], 'js') ?>')" title="Șterge">
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
                <p class="mb-0">Ești sigur că vrei să ștergi subdiviziunea "<strong id="subdivisionCode"></strong>"? Această acțiune nu poate fi anulată.</p>
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
        if (!$.fn.DataTable.isDataTable('#subdivisionsTable')) {
            $('#subdivisionsTable').DataTable({
                language: {
                    search: "",
                    searchPlaceholder: "Caută subdiviziune...",
                    lengthMenu: "Afișează _MENU_ subdiviziuni",
                    info: "Afișez _START_ - _END_ din _TOTAL_ subdiviziuni",
                    infoEmpty: "Afișez 0 - 0 din 0 subdiviziuni",
                    infoFiltered: "(filtrați din _MAX_ subdiviziuni)",
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

    function confirmDelete(subdivisionId, subdivisionCode) {
        document.getElementById('subdivisionCode').textContent = subdivisionCode;
        const deleteForm = document.getElementById('deleteForm');
        deleteForm.action = '<?= site_url('subdivisions/delete') ?>/' + subdivisionId;

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
                        alert(data.message || 'Eroare la ștergerea subdiviziunii.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Eroare la ștergerea subdiviziunii.');
                });

            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
        };

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>

<?= $this->endSection() ?>