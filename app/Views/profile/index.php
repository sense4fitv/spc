<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-profile" class="fade-in">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark">Profilul Meu</h3>
            <p class="text-muted m-0 mt-1">Gestionează informațiile personale.</p>
        </div>
        <a href="<?= site_url('tasks/my-tasks') ?>" class="btn btn-spor-primary">
            <i class="bi bi-check2-square me-2"></i>Sarcinile Mele
        </a>
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

    <?php if (session()->getFlashdata('errors')): ?>
        <?php foreach (session()->getFlashdata('errors') as $field => $error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= esc($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="spor-card p-5">
                <?= form_open('profile/update', ['id' => 'profileForm']) ?>
                <?= csrf_field() ?>

                <h6 class="fw-bold text-dark mb-4">Informații Personale</h6>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Prenume <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control" 
                               value="<?= old('first_name', $user['first_name'] ?? '') ?>" 
                               placeholder="Prenume" required>
                        <?php if ($validation && $validation->hasError('first_name')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('first_name') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nume <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control" 
                               value="<?= old('last_name', $user['last_name'] ?? '') ?>" 
                               placeholder="Nume" required>
                        <?php if ($validation && $validation->hasError('last_name')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('last_name') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Email</label>
                    <input type="email" class="form-control" value="<?= esc($user['email'] ?? '') ?>" disabled>
                    <div class="form-text text-muted small">Email-ul nu poate fi modificat.</div>
                </div>

                <?php if (!empty($user['phone'])): ?>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Telefon</label>
                        <input type="text" class="form-control" value="<?= esc($user['phone']) ?>" disabled>
                        <div class="form-text text-muted small">Telefonul nu poate fi modificat de aici.</div>
                    </div>
                <?php endif; ?>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Rol</label>
                    <input type="text" class="form-control" value="<?= esc($user['role'] ?? '') ?>" disabled>
                    <div class="form-text text-muted small">Rolul nu poate fi modificat de aici.</div>
                </div>

                <hr class="border-light my-4">

                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= site_url('dashboard') ?>" class="btn btn-spor-secondary px-4">Anulează</a>
                    <button type="submit" class="btn btn-spor-primary px-4">Salvează Modificările</button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

