<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-add-contract" class="fade-in">
    <a href="<?= site_url('contracts') ?>" class="btn btn-link text-secondary text-decoration-none p-0 mb-4 fw-medium" style="font-size: 0.85rem;">
        <i class="bi bi-arrow-left me-1"></i> Înapoi la contracte
    </a>
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom border-light pb-4">
        <h3 class="fw-bold text-dark m-0">Adaugă Contract Nou</h3>
    </div>
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="spor-card p-5">
                <?= form_open('contracts/store', ['id' => 'createContractForm']) ?>
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

                <h6 class="fw-bold text-dark mb-4">Informații Contract</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nume Contract <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= old('name') ?>" placeholder="Ex: Contract Servicii IT" required>
                        <?php if ($validation && $validation->hasError('name')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('name') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Număr Contract</label>
                        <input type="text" name="contract_number" class="form-control" value="<?= old('contract_number') ?>" placeholder="Ex: #CN-2024-001">
                        <?php if ($validation && $validation->hasError('contract_number')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('contract_number') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Nume Client</label>
                    <input type="text" name="client_name" class="form-control" value="<?= old('client_name') ?>" placeholder="Ex: Compania ABC SRL">
                    <?php if ($validation && $validation->hasError('client_name')): ?>
                        <div class="text-danger small mt-1"><?= $validation->getError('client_name') ?></div>
                    <?php endif; ?>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Regiune <span class="text-danger">*</span></label>
                        <?php if ($isDirector && $directorRegionId): ?>
                            <select name="region_id" class="form-select" required>
                                <?php foreach ($regions as $regionId => $regionName): ?>
                                    <option value="<?= $regionId ?>" <?= old('region_id', $directorRegionId) == $regionId ? 'selected' : '' ?>>
                                        <?= esc($regionName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-muted small">Regiunea ta: <?= esc(current($regions)) ?></div>
                        <?php else: ?>
                            <select name="region_id" class="form-select" required>
                                <option value="">Selectează regiune...</option>
                                <?php foreach ($regions as $regionId => $regionName): ?>
                                    <option value="<?= $regionId ?>" <?= old('region_id') == $regionId ? 'selected' : '' ?>>
                                        <?= esc($regionName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <?php if ($validation && $validation->hasError('region_id')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('region_id') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Manager Contract</label>
                        <select name="manager_id" class="form-select">
                            <option value="">Nu asigna manager</option>
                            <?php foreach ($managers as $managerId => $managerName): ?>
                                <option value="<?= $managerId ?>" <?= old('manager_id') == $managerId ? 'selected' : '' ?>>
                                    <?= esc($managerName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-muted small">Poți asigna un manager mai târziu.</div>
                        <?php if ($validation && $validation->hasError('manager_id')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('manager_id') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Data Început</label>
                        <input type="date" name="start_date" class="form-control" value="<?= old('start_date') ?>">
                        <?php if ($validation && $validation->hasError('start_date')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('start_date') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Data Sfârșit</label>
                        <input type="date" name="end_date" class="form-control" value="<?= old('end_date') ?>">
                        <?php if ($validation && $validation->hasError('end_date')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('end_date') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Progres (%)</label>
                        <input type="number" name="progress_percentage" class="form-control" value="<?= old('progress_percentage', 0) ?>" min="0" max="100" placeholder="0">
                        <?php if ($validation && $validation->hasError('progress_percentage')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('progress_percentage') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="form-label small fw-bold text-muted text-uppercase">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="planning" <?= old('status', 'planning') === 'planning' ? 'selected' : '' ?>>Planificare</option>
                        <option value="active" <?= old('status') === 'active' ? 'selected' : '' ?>>Activ</option>
                        <option value="on_hold" <?= old('status') === 'on_hold' ? 'selected' : '' ?>>În așteptare</option>
                        <option value="completed" <?= old('status') === 'completed' ? 'selected' : '' ?>>Finalizat</option>
                    </select>
                    <?php if ($validation && $validation->hasError('status')): ?>
                        <div class="text-danger small mt-1"><?= $validation->getError('status') ?></div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= site_url('contracts') ?>" class="btn btn-spor-secondary px-4">Anulează</a>
                    <button type="submit" class="btn btn-spor-primary px-4">Creează Contract</button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

