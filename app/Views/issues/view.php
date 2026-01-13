<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-issue-details" class="fade-in">
    <a href="<?= site_url('issues') ?>" class="btn btn-link text-secondary text-decoration-none p-0 mb-4 fw-medium" style="font-size: 0.85rem;">
        <i class="bi bi-arrow-left me-1"></i> Înapoi la problematici
    </a>
    <div class="row g-5">
        <div class="col-lg-8">
            <!-- Issue Header -->
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="spor-badge <?= $statusBadgeClasses[$issue['status']] ?? 'bg-subtle-blue' ?>">
                        <?= $statusLabels[$issue['status']] ?? ucfirst($issue['status']) ?>
                    </span>
                    <?php if (!empty($issue['region'])): ?>
                        <span class="spor-badge bg-info text-white">
                            <i class="bi bi-geo-alt me-1"></i><?= esc($issue['region']['name']) ?>
                        </span>
                    <?php else: ?>
                        <span class="spor-badge bg-warning text-dark">
                            <i class="bi bi-globe me-1"></i>Globală
                        </span>
                    <?php endif; ?>
                    <?php if ($canEdit || $canArchive): ?>
                        <div class="ms-auto d-flex gap-2">
                            <?php if ($canEdit): ?>
                                <a href="<?= site_url('issues/edit/' . $issue['id']) ?>" class="btn btn-sm btn-spor-secondary">
                                    <i class="bi bi-pencil me-1"></i>Editează
                                </a>
                            <?php endif; ?>
                            <?php if ($canArchive): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="archiveIssue(<?= $issue['id'] ?>)">
                                    <i class="bi bi-archive me-1"></i>Arhivează
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <h2 class="fw-bold mb-1 text-dark"><?= esc($issue['title']) ?></h2>
                <small class="text-muted">
                    Creat de <strong><?= esc($issue['creator_name'] ?? 'Necunoscut') ?></strong>
                    la <?= date('d M Y, H:i', strtotime($issue['created_at'])) ?>
                    <?php if ($issue['updated_at'] != $issue['created_at']): ?>
                        • Actualizat: <?= date('d M Y, H:i', strtotime($issue['updated_at'])) ?>
                    <?php endif; ?>
                </small>
            </div>

            <!-- Issue Description -->
            <div class="mb-5">
                <h6 class="fw-bold text-dark mb-3">Descriere</h6>
                <?php if (!empty($issue['description'])): ?>
                    <div class="text-secondary" style="line-height: 1.8; font-size: 0.95rem;">
                        <?= nl2br(esc($issue['description'])) ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Fără descriere.</p>
                <?php endif; ?>
            </div>

            <!-- Issue Metadata (only if department exists) -->
            <?php if (!empty($issue['department'])): ?>
                <div class="mb-5">
                    <div class="spor-card p-3">
                        <small class="text-muted d-block mb-1">Departament</small>
                        <span class="fw-bold text-dark"><?= esc($issue['department']['name']) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Files Section -->
            <div class="mb-5 border-top border-light pt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-paperclip me-2"></i>Documente (<?= count($issue['files'] ?? []) ?>)
                    </h6>
                    <?php if ($canUploadFiles): ?>
                        <button class="btn btn-sm btn-spor-secondary" onclick="document.getElementById('fileUploadInput').click()">
                            <i class="bi bi-upload me-2"></i>Upload Document
                        </button>
                        <input type="file" id="fileUploadInput" multiple style="display: none;" onchange="handleFileUpload(event)">
                    <?php endif; ?>
                </div>
                <div id="filesContainer" class="d-flex flex-column gap-2">
                    <?php if (empty($issue['files'])): ?>
                        <p class="text-muted small">Nu există documente încărcate.</p>
                    <?php else: ?>
                        <?php foreach ($issue['files'] as $file): ?>
                            <div class="spor-card p-3 d-flex justify-content-between align-items-center" id="file-<?= $file['id'] ?>">
                                <div class="d-flex align-items-center gap-3">
                                    <?php
                                    $fileIcon = 'bi-file-earmark';
                                    $fileIconBg = 'bg-light';
                                    $mimeType = $file['file_type'] ?? '';
                                    if (strpos($mimeType, 'pdf') !== false) {
                                        $fileIcon = 'bi-file-earmark-pdf';
                                        $fileIconBg = 'bg-danger-subtle text-danger';
                                    } elseif (strpos($mimeType, 'image') !== false) {
                                        $fileIcon = 'bi-file-earmark-image';
                                        $fileIconBg = 'bg-primary-subtle text-primary';
                                    } elseif (strpos($mimeType, 'word') !== false) {
                                        $fileIcon = 'bi-file-earmark-word';
                                        $fileIconBg = 'bg-info-subtle text-info';
                                    } elseif (strpos($mimeType, 'excel') !== false || strpos($mimeType, 'spreadsheet') !== false) {
                                        $fileIcon = 'bi-file-earmark-excel';
                                        $fileIconBg = 'bg-success-subtle text-success';
                                    }
                                    ?>
                                    <div class="d-flex align-items-center justify-content-center <?= $fileIconBg ?> rounded" style="width: 36px; height: 36px;">
                                        <i class="bi <?= $fileIcon ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-medium text-dark" style="font-size: 0.9rem;"><?= esc($file['filename']) ?></div>
                                        <div class="small text-muted">
                                            <?= $file['file_size'] ? number_format($file['file_size'] / 1024, 1) . ' KB' : '' ?>
                                            • Încărcat de <?= esc(trim(($file['first_name'] ?? '') . ' ' . ($file['last_name'] ?? ''))) ?>
                                            la <?= date('d.m.Y H:i', strtotime($file['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <a href="<?= site_url('issues/' . $issue['id'] . '/download-file/' . $file['id']) ?>" class="btn btn-link text-secondary p-0" title="Download">
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Comments Section (Forum Style) -->
            <div class="border-top border-light pt-4">
                <h6 class="fw-bold text-dark mb-4">
                    <i class="bi bi-chat-dots me-2"></i>Discuții (<?= count($issue['comments'] ?? []) ?>)
                </h6>
                <div id="commentsContainer">
                    <?php if (empty($issue['comments'])): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-chat display-6 text-muted"></i>
                            <p class="text-muted mt-3">Nu există comentarii. Fii primul care comentează!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($issue['comments'] as $comment): ?>
                            <div class="spor-card p-4 mb-3">
                                <div class="d-flex gap-3">
                                    <?php
                                    $authorName = trim(($comment['first_name'] ?? '') . ' ' . ($comment['last_name'] ?? ''));
                                    $initials = '';
                                    if (!empty($comment['first_name'])) $initials .= mb_substr($comment['first_name'], 0, 1);
                                    if (!empty($comment['last_name'])) $initials .= mb_substr($comment['last_name'], 0, 1);
                                    if (empty($initials)) $initials = mb_substr($comment['email'] ?? 'U', 0, 2);
                                    $initials = mb_strtoupper($initials);
                                    ?>
                                    <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 40px; height: 40px;">
                                        <?= $initials ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-baseline gap-2 mb-2">
                                            <span class="fw-bold text-dark"><?= esc($authorName ?: ($comment['email'] ?? 'Utilizator')) ?></span>
                                            <span class="text-muted small">
                                                <?= date('d M Y, H:i', strtotime($comment['created_at'])) ?>
                                            </span>
                                        </div>
                                        <p class="text-secondary m-0" style="font-size: 0.95rem; line-height: 1.7; white-space: pre-wrap;"><?= esc($comment['comment']) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($canComment): ?>
                    <div class="spor-card p-4 mt-4">
                        <h6 class="fw-bold text-dark mb-3">Adaugă un comentariu</h6>
                        <div class="position-relative">
                            <textarea id="commentInput" class="form-control" rows="4" placeholder="Scrie comentariul tău..."></textarea>
                            <div class="d-flex justify-content-end mt-3">
                                <button class="btn btn-spor-primary" onclick="addComment()">
                                    <i class="bi bi-send me-2"></i>Trimite Comentariu
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="spor-card p-4 mb-4" style="height: auto;">
                <h6 class="fw-bold text-dark mb-4">Informații</h6>

                <div class="mb-4">
                    <label class="small text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">Status</label>
                    <div class="p-2 border border-light rounded bg-light">
                        <span class="spor-badge <?= $statusBadgeClasses[$issue['status']] ?? 'bg-subtle-blue' ?>">
                            <?= $statusLabels[$issue['status']] ?? ucfirst($issue['status']) ?>
                        </span>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="small text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">Creator</label>
                    <div class="p-2 border border-light rounded bg-light">
                        <div class="fw-medium text-dark"><?= esc($issue['creator_name'] ?? 'Necunoscut') ?></div>
                        <?php if (!empty($issue['creator']['email'])): ?>
                            <small class="text-muted"><?= esc($issue['creator']['email']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="small text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">Statistici</label>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Comentarii:</span>
                            <span class="fw-bold text-dark"><?= count($issue['comments'] ?? []) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Documente:</span>
                            <span class="fw-bold text-dark"><?= count($issue['files'] ?? []) ?></span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($issue['comments'])): ?>
                    <?php
                    $lastComment = end($issue['comments']);
                    $lastCommentDate = $lastComment['created_at'];
                    ?>
                    <div class="mb-0">
                        <label class="small text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">Ultimul Comentariu</label>
                        <div class="p-2 border border-light rounded bg-light">
                            <small class="text-muted">
                                <?= date('d M Y, H:i', strtotime($lastCommentDate)) ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const issueId = <?= $issue['id'] ?>;

    function addComment() {
        const commentInput = document.getElementById('commentInput');
        const comment = commentInput.value.trim();

        if (!comment) {
            alert('Te rugăm să introduci un comentariu.');
            return;
        }

        fetch(`<?= site_url('issues') ?>/${issueId}/comment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `<?= csrf_token() ?>=<?= csrf_hash() ?>&comment=${encodeURIComponent(comment)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    commentInput.value = '';
                    location.reload(); // Reload page to show new comment
                } else {
                    alert(data.message || 'Eroare la adăugarea comentariului.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Eroare la adăugarea comentariului.');
            });
    }

    function handleFileUpload(event) {
        const files = event.target.files;
        if (!files || files.length === 0) return;

        const formData = new FormData();
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        fetch(`<?= site_url('issues') ?>/${issueId}/upload-file`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload page to show new files
                } else {
                    const errorMsg = data.errors && data.errors.length > 0 ?
                        data.errors.join(', ') :
                        (data.message || 'Eroare la încărcarea fișierelor.');
                    alert(errorMsg);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Eroare la încărcarea fișierelor.');
            });
    }

    function archiveIssue(id) {
        if (!confirm('Ești sigur că vrei să arhivezi această problematică?')) {
            return;
        }

        fetch(`<?= site_url('issues') ?>/archive/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `<?= csrf_token() ?>=<?= csrf_hash() ?>`
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.json();
                }
            })
            .then(data => {
                if (data && data.success) {
                    window.location.href = '<?= site_url('issues') ?>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                location.reload();
            });
    }
</script>

<?= $this->endSection() ?>