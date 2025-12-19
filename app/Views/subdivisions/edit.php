<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-edit-subdivision" class="fade-in">
    <a href="<?= site_url('subdivisions') ?>" class="btn btn-link text-secondary text-decoration-none p-0 mb-4 fw-medium" style="font-size: 0.85rem;">
        <i class="bi bi-arrow-left me-1"></i> Înapoi la subdiviziuni
    </a>
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom border-light pb-4">
        <h3 class="fw-bold text-dark m-0">Editează Subdiviziune</h3>
    </div>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="spor-card p-5">
                <?= form_open('subdivisions/update/' . $subdivision['id'], ['id' => 'editSubdivisionForm']) ?>
                <?= csrf_field() ?>

                <!-- Flash Messages -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i><?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('errors')): ?>
                    <?php foreach (session()->getFlashdata('errors') as $field => $error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle me-2"></i><?= esc($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <h6 class="fw-bold text-dark mb-4">Informații Subdiviziune</h6>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Contract</label>
                    <?php
                    // Get contract name for display
                    $contract = (new \App\Models\ContractModel())->find($subdivision['contract_id']);
                    $contractName = $contract ? $contract['name'] : 'Contract #' . $subdivision['contract_id'];
                    ?>
                    <input type="text" class="form-control" value="<?= esc($contractName) ?>" readonly disabled>
                    <input type="hidden" name="contract_id" value="<?= $subdivision['contract_id'] ?>">
                    <div class="form-text text-muted small">Contractul nu poate fi schimbat după crearea subdiviziunii.</div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Cod Subdiviziune <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="<?= old('code', $subdivision['code'] ?? '') ?>" placeholder="Ex: SUB-01" required>
                        <div class="form-text text-muted small">Codul trebuie să fie unic pentru contractul selectat.</div>
                        <?php if ($validation && $validation->hasError('code')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('code') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nume Subdiviziune <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= old('name', $subdivision['name'] ?? '') ?>" placeholder="Ex: Lucrări de construcție" required>
                        <?php if ($validation && $validation->hasError('name')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('name') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="form-label small fw-bold text-muted text-uppercase">Detalii</label>
                    <textarea name="details" class="form-control" rows="3" placeholder="Detalii opționale despre subdiviziune..."><?= old('details', $subdivision['details'] ?? '') ?></textarea>
                    <?php if ($validation && $validation->hasError('details')): ?>
                        <div class="text-danger small mt-1"><?= $validation->getError('details') ?></div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= site_url('subdivisions') ?>" class="btn btn-spor-secondary px-4">Anulează</a>
                    <button type="submit" class="btn btn-spor-primary px-4">Salvează Modificări</button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

