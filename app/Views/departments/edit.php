<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-edit-department" class="fade-in">
    <a href="<?= site_url('departments') ?>" class="btn btn-link text-secondary text-decoration-none p-0 mb-4 fw-medium" style="font-size: 0.85rem;">
        <i class="bi bi-arrow-left me-1"></i> Înapoi la departamente
    </a>
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom border-light pb-4">
        <h3 class="fw-bold text-dark m-0">Editează Departament</h3>
    </div>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="spor-card p-5">
                <?= form_open('departments/update/' . $department['id'], ['id' => 'editDepartmentForm']) ?>
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

                <h6 class="fw-bold text-dark mb-4">Detalii Departament</h6>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Nume Departament <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= old('name', $department['name'] ?? '') ?>" placeholder="Ex: Financiar, Operațional" required>
                    <?php if ($validation && $validation->hasError('name')): ?>
                        <div class="text-danger small mt-1"><?= $validation->getError('name') ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-5">
                    <label class="form-label small fw-bold text-muted text-uppercase">Culoare</label>
                    <div class="d-flex gap-3 align-items-center">
                        <input type="color" name="color_code" class="form-control form-control-color" value="<?= old('color_code', $department['color_code'] ?? '#808080') ?>" title="Alege culoarea">
                        <input type="text" name="color_code_text" id="colorCodeText" class="form-control" value="<?= old('color_code', $department['color_code'] ?? '#808080') ?>" placeholder="#808080" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
                        <div id="colorPreview" style="width: 40px; height: 40px; background-color: <?= old('color_code', $department['color_code'] ?? '#808080') ?>; border-radius: 8px; border: 2px solid #ddd;"></div>
                    </div>
                    <div class="form-text text-muted small">Culoarea folosită pentru identificarea departamentului în sistem. Format: #RRGGBB (ex: #FF5733)</div>
                    <?php if ($validation && $validation->hasError('color_code')): ?>
                        <div class="text-danger small mt-1"><?= $validation->getError('color_code') ?></div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= site_url('departments') ?>" class="btn btn-spor-secondary px-4">Anulează</a>
                    <button type="submit" class="btn btn-spor-primary px-4">Salvează Modificări</button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const colorPicker = document.querySelector('input[name="color_code"]');
        const colorText = document.getElementById('colorCodeText');
        const colorPreview = document.getElementById('colorPreview');

        // Sync color picker with text input
        colorPicker.addEventListener('input', function() {
            colorText.value = this.value.toUpperCase();
            colorPreview.style.backgroundColor = this.value;
        });

        // Sync text input with color picker
        colorText.addEventListener('input', function() {
            const value = this.value.toUpperCase();
            if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                colorPicker.value = value;
                colorPreview.style.backgroundColor = value;
            }
        });

        // Update preview on page load
        colorPreview.style.backgroundColor = colorPicker.value;
    });
</script>

<?= $this->endSection() ?>

