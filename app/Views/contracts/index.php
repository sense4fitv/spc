<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-contracts" class="fade-in">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark">Contracte</h3>
            <p class="text-muted m-0 mt-1">Gestiune contracte și subdiviziuni.</p>
        </div>
        <?php if ($canCreate): ?>
            <a class="btn btn-spor-primary" href="<?= site_url('contracts/create') ?>"><i class="bi bi-file-earmark-plus me-2"></i>Adaugă Contract</a>
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
        <table id="contractsTable" class="table" style="width:100%">
            <thead>
                <tr>
                    <th>Nume Contract</th>
                    <th>Număr Contract</th>
                    <th>Client</th>
                    <th>Regiune</th>
                    <th>Manager</th>
                    <th>Status</th>
                    <th>Progres</th>
                    <th>Subdiviziuni</th>
                    <th class="text-end">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contracts)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            Nu există contracte de afișat.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contracts as $contract): ?>
                        <tr>
                            <td class="fw-bold"><?= esc($contract['name'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($contract['contract_number'])): ?>
                                    <span class="badge bg-light text-dark"><?= esc($contract['contract_number']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($contract['client_name'] ?? '-') ?></td>
                            <td><?= esc($contract['region_name'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($contract['manager_name'])): ?>
                                    <?= esc($contract['manager_name']) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status = $contract['status'] ?? 'planning';
                                $statusClasses = [
                                    'planning' => 'bg-secondary',
                                    'active' => 'bg-success',
                                    'on_hold' => 'bg-warning',
                                    'completed' => 'bg-info',
                                ];
                                $statusLabels = [
                                    'planning' => 'Planificare',
                                    'active' => 'Activ',
                                    'on_hold' => 'În așteptare',
                                    'completed' => 'Finalizat',
                                ];
                                $statusClass = $statusClasses[$status] ?? 'bg-secondary';
                                $statusLabel = $statusLabels[$status] ?? ucfirst($status);
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress" style="width: 80px; height: 8px;">
                                        <div class="progress-bar" role="progressbar" style="width: <?= $contract['progress_percentage'] ?? 0 ?>%" aria-valuenow="<?= $contract['progress_percentage'] ?? 0 ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted"><?= $contract['progress_percentage'] ?? 0 ?>%</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $contract['subdivisions_count'] ?? 0 ?></span>
                            </td>
                            <td class="text-end">
                                <?php if (is_callable($canEdit) && $canEdit($contract['id'])): ?>
                                    <a href="<?= site_url('contracts/edit/' . $contract['id']) ?>" class="btn btn-sm btn-light border" title="Editează">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (is_callable($canDelete) && $canDelete($contract['id'])): ?>
                                    <button class="btn btn-sm btn-light border text-danger" onclick="confirmDelete(<?= $contract['id'] ?>, '<?= esc($contract['name'], 'js') ?>')" title="Șterge">
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
                <p class="mb-0">Ești sigur că vrei să ștergi contractul "<strong id="contractName"></strong>"? Această acțiune nu poate fi anulată.</p>
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
        if (!$.fn.DataTable.isDataTable('#contractsTable')) {
            $('#contractsTable').DataTable({
                language: {
                    search: "",
                    searchPlaceholder: "Caută contract...",
                    lengthMenu: "Afișează _MENU_ contracte",
                    info: "Afișez _START_ - _END_ din _TOTAL_ contracte",
                    infoEmpty: "Afișez 0 - 0 din 0 contracte",
                    infoFiltered: "(filtrați din _MAX_ contracte)",
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

    function confirmDelete(contractId, contractName) {
        document.getElementById('contractName').textContent = contractName;
        const deleteForm = document.getElementById('deleteForm');
        deleteForm.action = '<?= site_url('contracts/delete') ?>/' + contractId;

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
                    alert(data.message || 'Eroare la ștergerea contractului.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Eroare la ștergerea contractului.');
            });
            
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
        };

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>

<?= $this->endSection() ?>

