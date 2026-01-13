<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-create-issue" class="fade-in">
    <a href="<?= site_url('issues') ?>" class="btn btn-link text-secondary text-decoration-none p-0 mb-4 fw-medium" style="font-size: 0.85rem;">
        <i class="bi bi-arrow-left me-1"></i> Înapoi la problematici
    </a>
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom border-light pb-4">
        <h3 class="fw-bold text-dark m-0">Crează Problematică Nouă</h3>
    </div>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="spor-card p-5">
                <?= form_open('issues/store', ['id' => 'createIssueForm']) ?>
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

                <h6 class="fw-bold text-dark mb-4">Informații Problematică</h6>
                
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Titlu <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= old('title') ?>" placeholder="Ex: Problemă cu acces la documente" required>
                    <?php if ($validation && $validation->hasError('title')): ?>
                        <div class="text-danger small mt-1"><?= $validation->getError('title') ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Descriere</label>
                    <textarea name="description" class="form-control" rows="6" placeholder="Descrie problematică în detaliu..."><?= old('description') ?></textarea>
                    <?php if ($validation && $validation->hasError('description')): ?>
                        <div class="text-danger small mt-1"><?= $validation->getError('description') ?></div>
                    <?php endif; ?>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Regiune</label>
                        <select name="region_id" id="select-region" class="form-select">
                            <option value="">Globală (doar Admin)</option>
                            <?php foreach ($regions as $regionId => $regionName): ?>
                                <option value="<?= $regionId ?>" <?= old('region_id') == $regionId ? 'selected' : '' ?>>
                                    <?= esc($regionName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-muted small">
                            <?php if ($roleLevel >= 100): ?>
                                Lasă gol pentru problematică globală (vizibilă doar de admini).
                            <?php else: ?>
                                Selectează regiunea pentru care este problematică.
                            <?php endif; ?>
                        </div>
                        <?php if ($validation && $validation->hasError('region_id')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('region_id') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Departament (Opțional)</label>
                        <select name="department_id" id="select-department" class="form-select">
                            <option value="">Nu specifică departament</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= old('department_id') == $dept['id'] ? 'selected' : '' ?>>
                                    <?= esc($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-muted small">Poți asocia problematică cu un departament specific.</div>
                        <?php if ($validation && $validation->hasError('department_id')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('department_id') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= site_url('issues') ?>" class="btn btn-spor-secondary px-4">Anulează</a>
                    <button type="submit" class="btn btn-spor-primary px-4">Creează Problematică</button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Tom Select for region
    const regionSelect = new TomSelect('#select-region', {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });

    // Initialize Tom Select for department
    const departmentSelect = new TomSelect('#select-department', {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });
});
</script>

<?= $this->endSection() ?>

