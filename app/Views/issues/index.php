<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-issues" class="fade-in">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark">Problematice</h3>
            <p class="text-muted m-0 mt-1">Gestiune problematice și discuții.</p>
        </div>
        <?php if ($canCreate): ?>
            <a class="btn btn-spor-primary" href="<?= site_url('issues/create') ?>">
                <i class="bi bi-plus-circle me-2"></i>Crează Problematică Nouă
            </a>
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

    <!-- Search and Filter Section -->
    <div class="spor-card p-4 mb-4">
        <form method="GET" action="<?= site_url('issues') ?>" id="searchForm">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label small text-muted">Căutare</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Caută după titlu sau descriere..." 
                           value="<?= esc($search ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label small text-muted">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Toate</option>
                        <option value="open" <?= ($statusFilter ?? '') === 'open' ? 'selected' : '' ?>>Deschisă</option>
                        <option value="answered" <?= ($statusFilter ?? '') === 'answered' ? 'selected' : '' ?>>Răspuns</option>
                        <option value="closed" <?= ($statusFilter ?? '') === 'closed' ? 'selected' : '' ?>>Închisă</option>
                        <option value="archived" <?= ($statusFilter ?? '') === 'archived' ? 'selected' : '' ?>>Arhivată</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-spor-primary w-100">
                        <i class="bi bi-search me-2"></i>Caută
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Issues Cards Grid -->
    <?php if (empty($issues)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <p class="text-muted mt-3">Nu există problematici de afișat.</p>
            <?php if ($canCreate): ?>
                <a href="<?= site_url('issues/create') ?>" class="btn btn-spor-primary mt-3">
                    <i class="bi bi-plus-circle me-2"></i>Crează Prima Problematică
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($issues as $issue): ?>
                <div class="col-md-6 col-lg-4 issue-card" 
                     data-title="<?= esc(strtolower($issue['title'])) ?>"
                     data-status="<?= esc($issue['status']) ?>">
                    <div class="spor-card p-4 h-100" style="cursor: pointer;" 
                         onclick="window.location.href='<?= site_url('issues/view/' . $issue['id']) ?>'">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark mb-1"><?= esc($issue['title']) ?></h6>
                                <small class="text-muted">#<?= $issue['id'] ?></small>
                            </div>
                            <span class="spor-badge <?= $statusBadgeClasses[$issue['status']] ?? 'bg-subtle-blue' ?> ms-2">
                                <?= $statusLabels[$issue['status']] ?? ucfirst($issue['status']) ?>
                            </span>
                        </div>

                        <?php if (!empty($issue['description'])): ?>
                            <p class="text-secondary small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= esc(substr($issue['description'], 0, 100)) ?><?= strlen($issue['description']) > 100 ? '...' : '' ?>
                            </p>
                        <?php endif; ?>

                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Regiune:</small>
                            <span class="small fw-medium text-dark">
                                <?= $issue['region_name'] ? esc($issue['region_name']) : '<span class="text-warning">Globală</span>' ?>
                            </span>
                        </div>

                        <?php if (!empty($issue['department_name'])): ?>
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Departament:</small>
                                <span class="small fw-medium text-dark"><?= esc($issue['department_name']) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3 d-flex gap-3 text-muted small">
                            <?php if ($issue['comments_count'] > 0): ?>
                                <span>
                                    <i class="bi bi-chat-dots me-1"></i>
                                    <?= $issue['comments_count'] ?> <?= $issue['comments_count'] == 1 ? 'comentariu' : 'comentarii' ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($issue['files_count'] > 0): ?>
                                <span>
                                    <i class="bi bi-paperclip me-1"></i>
                                    <?= $issue['files_count'] ?> <?= $issue['files_count'] == 1 ? 'fișier' : 'fișiere' ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top border-light">
                            <small class="text-muted">
                                <i class="bi bi-person me-1"></i>
                                <?= esc($issue['creator_name'] ?? 'Necunoscut') ?>
                            </small>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>
                                <?= date('d.m.Y', strtotime($issue['created_at'])) ?>
                            </small>
                        </div>

                        <?php if (!empty($issue['last_comment_date']) && $issue['last_comment_date'] != $issue['created_at']): ?>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-chat me-1"></i>
                                    Ultimul comentariu: <?= date('d.m.Y H:i', strtotime($issue['last_comment_date'])) ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Client-side filtering (optional enhancement)
    const searchInput = document.getElementById('search');
    const statusSelect = document.getElementById('status');
    const issueCards = document.querySelectorAll('.issue-card');

    if (searchInput && statusSelect) {
        function filterIssues() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedStatus = statusSelect.value;

            issueCards.forEach(card => {
                const title = card.dataset.title || '';
                const status = card.dataset.status || '';

                const matchesSearch = !searchTerm || title.includes(searchTerm);
                const matchesStatus = !selectedStatus || status === selectedStatus;

                card.style.display = (matchesSearch && matchesStatus) ? 'block' : 'none';
            });
        }

        // Only enable client-side filtering if no form submission needed
        // For now, we'll rely on form submission
    }
});
</script>

<?= $this->endSection() ?>

