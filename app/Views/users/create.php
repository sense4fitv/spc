<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-add-user" class="fade-in">
    <a href="<?= site_url('users') ?>" class="btn btn-link text-secondary text-decoration-none p-0 mb-4 fw-medium" style="font-size: 0.85rem;">
        <i class="bi bi-arrow-left me-1"></i> Înapoi la utilizatori
    </a>
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom border-light pb-4">
        <h3 class="fw-bold text-dark m-0">Adaugă Utilizator Nou</h3>
    </div>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="spor-card p-5">
                <?= form_open('users/store', ['id' => 'createUserForm']) ?>
                <?= csrf_field() ?>

                <h6 class="fw-bold text-dark mb-4">Detalii Cont</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Prenume</label>
                        <input type="text" name="first_name" class="form-control" value="<?= old('first_name') ?>" placeholder="Ex: Ion" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nume de familie</label>
                        <input type="text" name="last_name" class="form-control" value="<?= old('last_name') ?>" placeholder="Ex: Popescu" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Adresă Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-light text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" value="<?= old('email') ?>" placeholder="nume.prenume@spor.ro" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Telefon (Opțional)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-light text-muted"><i class="bi bi-telephone"></i></span>
                        <input type="tel" name="phone" class="form-control" value="<?= old('phone') ?>" placeholder="Ex: +40 123 456 789" maxlength="20">
                    </div>
                    <div class="form-text text-muted small">Număr de telefon pentru contact direct</div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Rol în sistem</label>
                        <select name="role" class="form-select" required>
                            <option value="">Selectează...</option>
                            <?php foreach ($roles as $roleCode): ?>
                                <option value="<?= $roleCode ?>" <?= old('role') === $roleCode ? 'selected' : '' ?>>
                                    <?= $roleDisplayNames[$roleCode] ?? ucfirst($roleCode) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Regiune</label>
                        <select name="region_id" class="form-select">
                            <option value="">Fără regiune (Super User)</option>
                            <?php foreach ($regions as $regionId => $regionName): ?>
                                <option value="<?= $regionId ?>" <?= old('region_id') == $regionId ? 'selected' : '' ?>>
                                    <?= esc($regionName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Departamente (Opțional)</label>
                    <select name="departments[]" id="select-departments" class="form-select" multiple>
                        <?php foreach ($departments as $deptId => $deptName): ?>
                            <option value="<?= $deptId ?>">
                                <?= esc($deptName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-muted small">Poți selecta mai multe departamente. Dacă nu selectezi niciunul, utilizatorul va fi super user.</div>
                </div>

                <?php if (isset($isAdmin) && $isAdmin): ?>
                    <hr class="border-light my-4">
                    <h6 class="fw-bold text-dark mb-4">Șef de Departament</h6>
                    <div class="mb-4">
                        <div class="form-text text-muted small mb-3">Poți atribui utilizatorul ca șef de departament pentru o regiune. Doar utilizatorii cu rol Manager sau superior pot fi șefi de departament.</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Regiune pentru Departament</label>
                                <select name="department_head_region_id" id="department-head-region" class="form-select">
                                    <option value="">Selectează regiune...</option>
                                    <?php foreach ($regions as $regionId => $regionName): ?>
                                        <option value="<?= $regionId ?>" <?= old('department_head_region_id') == $regionId ? 'selected' : '' ?>>
                                            <?= esc($regionName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Departament</label>
                                <select name="department_head_department_id" id="department-head-department" class="form-select">
                                    <option value="">Selectează departament...</option>
                                    <?php foreach ($departments as $deptId => $deptName): ?>
                                        <option value="<?= $deptId ?>" <?= old('department_head_department_id') == $deptId ? 'selected' : '' ?>>
                                            <?= esc($deptName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-text text-muted small">Notă: Un departament poate avea un singur șef pe regiune.</div>
                    </div>
                <?php endif; ?>

                <hr class="border-light my-4">
                <h6 class="fw-bold text-dark mb-4">Securitate</h6>
                <div class="row g-3 mb-5">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Parolă Temporară</label>
                        <input type="text" class="form-control" value="Generată automat" readonly>
                        <div class="form-text text-muted small">Utilizatorul va primi o parolă temporară pe email și va fi obligat să o schimbe la prima logare.</div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= site_url('users') ?>" class="btn btn-spor-secondary px-4">Anulează</a>
                    <button type="submit" class="btn btn-spor-primary px-4">Creează Cont</button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Tom Select for departments
        new TomSelect('#select-departments', {
            plugins: ['remove_button'],
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    });
</script>

<?= $this->endSection() ?>