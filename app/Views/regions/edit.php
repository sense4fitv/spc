<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-edit-region" class="fade-in">
    <a href="<?= site_url('regions') ?>" class="btn btn-link text-secondary text-decoration-none p-0 mb-4 fw-medium" style="font-size: 0.85rem;">
        <i class="bi bi-arrow-left me-1"></i> Înapoi la regiuni
    </a>
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom border-light pb-4">
        <h3 class="fw-bold text-dark m-0">Editează Regiune</h3>
    </div>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="spor-card p-5">
                <?= form_open('regions/update/' . $region['id'], ['id' => 'editRegionForm']) ?>
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

                <h6 class="fw-bold text-dark mb-4">Detalii Regiune</h6>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Nume Regiune <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= old('name', $region['name'] ?? '') ?>" placeholder="Ex: Muntenia Sud" required>
                    <?php if ($validation && $validation->hasError('name')): ?>
                        <div class="text-danger small mt-1"><?= $validation->getError('name') ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Descriere</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Descriere opțională a regiunii..."><?= old('description', $region['description'] ?? '') ?></textarea>
                    <?php if ($validation && $validation->hasError('description')): ?>
                        <div class="text-danger small mt-1"><?= $validation->getError('description') ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-5">
                    <label class="form-label small fw-bold text-muted text-uppercase">Director Regional</label>
                    <select name="manager_id" class="form-select">
                        <option value="">Nu asigna director</option>
                        <?php foreach ($directors as $director): ?>
                            <option value="<?= $director['id'] ?>" <?= old('manager_id', $region['manager_id'] ?? '') == $director['id'] ? 'selected' : '' ?>>
                                <?= esc($director['first_name'] ?? '') ?> <?= esc($director['last_name'] ?? '') ?> (<?= esc($director['email'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-muted small">Poți schimba sau elimina directorul regional oricând.</div>
                    <?php if ($validation && $validation->hasError('manager_id')): ?>
                        <div class="text-danger small mt-1"><?= $validation->getError('manager_id') ?></div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= site_url('regions') ?>" class="btn btn-spor-secondary px-4">Anulează</a>
                    <button type="submit" class="btn btn-spor-primary px-4">Salvează Modificări</button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

