<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-centrala" class="fade-in">
    <div class="mb-5">
        <h3 class="fw-bold m-0 text-dark">Centrala</h3>
        <p class="text-muted m-0 mt-1">Vizualizare administratori și regiuni cu sarcini.</p>
    </div>

    <!-- Flash Messages -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Administrators Section -->
    <div class="mb-5">
        <h5 class="fw-bold text-dark mb-4">Administratori</h5>
        <?php if (empty($admins)): ?>
            <div class="text-center py-4">
                <p class="text-muted">Nu există administratori.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($admins as $admin): ?>
                    <div class="col-md-6 col-lg-4">
                        <a href="<?= site_url('centrala/admin/' . $admin['id']) ?>" class="text-decoration-none">
                            <div class="spor-card p-4 h-100" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';"
                                onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold text-dark mb-1"><?= esc(trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '')) ?: $admin['email']) ?></h6>
                                        <small class="text-muted"><?= esc($admin['email']) ?></small>
                                    </div>
                                    <div class="d-flex flex-column align-items-end gap-1 ms-2">
                                        <span class="spor-badge bg-primary text-white" style="font-size: 1rem; padding: 0.5rem 0.75rem;">
                                            <?= $admin['task_count'] ?>
                                        </span>
                                        <?php if (($admin['overdue_count'] ?? 0) > 0): ?>
                                            <span class="spor-badge bg-danger text-white" style="font-size: 0.85rem; padding: 0.35rem 0.6rem;">
                                                <?= $admin['overdue_count'] ?> întârziate
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Regions Section -->
    <div class="mb-5">
        <h5 class="fw-bold text-dark mb-4">Regiuni</h5>
        <?php if (empty($regions)): ?>
            <div class="text-center py-4">
                <p class="text-muted">Nu există regiuni.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($regions as $region): ?>
                    <div class="col-md-6 col-lg-4">
                        <a href="<?= site_url('centrala/region/' . $region['id']) ?>" class="text-decoration-none">
                            <div class="spor-card p-4 h-100" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';"
                                onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold text-dark mb-1"><?= esc($region['name']) ?></h6>
                                    </div>
                                    <div class="d-flex flex-column align-items-end gap-1 ms-2">
                                        <span class="spor-badge bg-primary text-white" style="font-size: 1rem; padding: 0.5rem 0.75rem;">
                                            <?= $region['task_count'] ?>
                                        </span>
                                        <?php if (($region['overdue_count'] ?? 0) > 0): ?>
                                            <span class="spor-badge bg-danger text-white" style="font-size: 0.85rem; padding: 0.35rem 0.6rem;">
                                                <?= $region['overdue_count'] ?> întârziate
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>