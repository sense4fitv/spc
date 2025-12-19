<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-regions" class="fade-in">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark">Regiuni</h3>
            <p class="text-muted m-0 mt-1">Gestiune regiuni și directori regionali.</p>
        </div>
        <a class="btn btn-spor-primary" href="<?= site_url('regions/create') ?>"><i class="bi bi-geo-alt me-2"></i>Adaugă Regiune</a>
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
        <table id="regionsTable" class="table" style="width:100%">
            <thead>
                <tr>
                    <th>Nume</th>
                    <th>Director Regional</th>
                    <th>Utilizatori</th>
                    <th>Contracte</th>
                    <th>Descriere</th>
                    <th class="text-end">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($regions)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Nu există regiuni de afișat.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($regions as $region): ?>
                        <tr>
                            <td class="fw-bold"><?= esc($region['name'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($region['manager_name'])): ?>
                                    <?= esc($region['manager_name']) ?>
                                    <?php if (!empty($region['manager_email'])): ?>
                                        <br><small class="text-muted"><?= esc($region['manager_email']) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $region['users_count'] ?? 0 ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= $region['contracts_count'] ?? 0 ?></span>
                            </td>
                            <td>
                                <?php if (!empty($region['description'])): ?>
                                    <span class="text-muted small"><?= esc(substr($region['description'], 0, 50)) ?><?= strlen($region['description']) > 50 ? '...' : '' ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= site_url('regions/edit/' . $region['id']) ?>" class="btn btn-sm btn-light border" title="Editează">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button class="btn btn-sm btn-light border text-danger" onclick="confirmDelete(<?= $region['id'] ?>, '<?= esc($region['name'], 'js') ?>')" title="Șterge">
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
                <p class="mb-0">Ești sigur că vrei să ștergi regiunea "<strong id="regionName"></strong>"? Această acțiune nu poate fi anulată.</p>
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
        if (!$.fn.DataTable.isDataTable('#regionsTable')) {
            $('#regionsTable').DataTable({
                language: {
                    search: "",
                    searchPlaceholder: "Caută regiune...",
                    lengthMenu: "Afișează _MENU_ regiuni",
                    info: "Afișez _START_ - _END_ din _TOTAL_ regiuni",
                    infoEmpty: "Afișez 0 - 0 din 0 regiuni",
                    infoFiltered: "(filtrați din _MAX_ regiuni)",
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

    function confirmDelete(regionId, regionName) {
        document.getElementById('regionName').textContent = regionName;
        const deleteForm = document.getElementById('deleteForm');
        deleteForm.action = '<?= site_url('regions/delete') ?>/' + regionId;

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
                    alert(data.message || 'Eroare la ștergerea regiunii.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Eroare la ștergerea regiunii.');
            });
            
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
        };

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>

<?= $this->endSection() ?>

