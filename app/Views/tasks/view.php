<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-task-details" class="fade-in">
    <a href="<?= site_url('tasks') ?>" class="btn btn-link text-secondary text-decoration-none p-0 mb-4 fw-medium" style="font-size: 0.85rem;">
        <i class="bi bi-arrow-left me-1"></i> Înapoi la sarcini
    </a>
    <div class="row g-5">
        <div class="col-lg-8">
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <?php
                    $priority = $task['priority'] ?? 'medium';
                    $priorityBadgeClass = [
                        'low' => 'bg-subtle-green',
                        'medium' => 'bg-subtle-yellow',
                        'high' => 'bg-subtle-orange',
                        'critical' => 'bg-subtle-red',
                    ][$priority] ?? 'bg-subtle-yellow';
                    $priorityLabel = $priorityLabels[$priority] ?? ucfirst($priority);

                    $status = $task['status'] ?? 'new';
                    $statusBadgeClass = [
                        'new' => 'bg-subtle-gray',
                        'in_progress' => 'bg-subtle-blue',
                        'blocked' => 'bg-subtle-red',
                        'review' => 'bg-subtle-yellow',
                        'completed' => 'bg-subtle-green',
                    ][$status] ?? 'bg-subtle-gray';
                    $statusLabel = $statusLabels[$status] ?? ucfirst($status);
                    ?>
                    <span class="spor-badge <?= $priorityBadgeClass ?>"><?= $priorityLabel ?></span>
                    <span class="spor-badge <?= $statusBadgeClass ?>"><?= $statusLabel ?></span>
                </div>
                <h2 class="fw-bold mb-1 text-dark"><?= esc($task['title']) ?></h2>
                <small class="text-muted">
                    Creat: <?= date('d M Y', strtotime($task['created_at'])) ?> •
                    Actualizat: <?= date('d M Y H:i', strtotime($task['updated_at'])) ?>
                </small>
            </div>
            <div class="mb-5">
                <h6 class="fw-bold text-dark mb-2">Descriere</h6>
                <p class="text-secondary" style="line-height: 1.7; font-size: 0.95rem;">
                    <?= !empty($task['description']) ? nl2br(esc($task['description'])) : '<span class="text-muted">Fără descriere.</span>' ?>
                </p>
            </div>
            <div class="mb-5 border-top border-light pt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark m-0">Atașamente (<?= count($task['files'] ?? []) ?>)</h6>
                    <?php if ($canUploadFiles): ?>
                        <button class="btn btn-sm btn-spor-secondary" onclick="document.getElementById('fileUploadInput').click()">
                            <i class="bi bi-upload me-2"></i>Upload
                        </button>
                        <input type="file" id="fileUploadInput" multiple style="display: none;" onchange="handleFileUpload(event)">
                    <?php endif; ?>
                </div>
                <div id="filesContainer" class="d-flex flex-column gap-2">
                    <?php if (empty($task['files'])): ?>
                        <p class="text-muted small">Nu există fișiere atașate.</p>
                    <?php else: ?>
                        <?php foreach ($task['files'] as $file): ?>
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
                                            • <?= esc($file['first_name'] ?? '') ?> <?= esc($file['last_name'] ?? '') ?>
                                        </div>
                                    </div>
                                </div>
                                <a href="<?= site_url('tasks/files/download/' . $file['id']) ?>" class="btn btn-link text-secondary p-0" title="Download">
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="border-top border-light pt-4">
                <h6 class="fw-bold text-dark mb-4">Comentarii (<?= count($task['comments'] ?? []) ?>)</h6>
                <div id="commentsContainer">
                    <?php if (empty($task['comments'])): ?>
                        <p class="text-muted small">Nu există comentarii.</p>
                    <?php else: ?>
                        <?php foreach ($task['comments'] as $comment): ?>
                            <div class="d-flex gap-3 mb-4">
                                <?php
                                $authorName = trim(($comment['first_name'] ?? '') . ' ' . ($comment['last_name'] ?? ''));
                                $initials = '';
                                if (!empty($comment['first_name'])) $initials .= mb_substr($comment['first_name'], 0, 1);
                                if (!empty($comment['last_name'])) $initials .= mb_substr($comment['last_name'], 0, 1);
                                if (empty($initials)) $initials = mb_substr($comment['email'] ?? 'U', 0, 2);
                                $initials = mb_strtoupper($initials);
                                ?>
                                <div class="bg-light border rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 text-secondary fw-bold small" style="width: 32px; height: 32px;">
                                    <?= $initials ?>
                                </div>
                                <div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?= esc($authorName ?: ($comment['email'] ?? 'Utilizator')) ?></span>
                                        <span class="text-muted small" data-time="<?= $comment['created_at'] ?>"></span>
                                    </div>
                                    <p class="text-secondary m-0" style="font-size: 0.9rem; line-height: 1.5;"><?= nl2br(esc($comment['comment'])) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if ($canComment): ?>
                    <div class="mt-4 position-relative">
                        <input type="text" id="commentInput" class="form-control rounded-pill pe-5 py-2" placeholder="Scrie un comentariu..." onkeypress="if(event.key === 'Enter') addComment()">
                        <button class="btn btn-dark rounded-circle position-absolute top-50 end-0 translate-middle-y me-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" onclick="addComment()">
                            <i class="bi bi-arrow-up-short"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="spor-card p-4 mb-4" style="height: auto;">
                <h6 class="fw-bold text-dark mb-4">Proprietăți</h6>
                <?php if (isset($canChangeStatus) && $canChangeStatus): ?>
                    <div class="mb-4">
                        <label class="small text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">Status</label>
                        <select id="statusSelect" class="form-select" onchange="updateStatus(this.value)">
                            <?php foreach ($availableStatuses as $statusCode => $statusLabel): ?>
                                <option value="<?= $statusCode ?>" <?= $task['status'] === $statusCode ? 'selected' : '' ?>>
                                    <?= $statusLabel ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if (!isset($availableStatuses[$task['status']])): ?>
                                <option value="<?= $task['status'] ?>" selected><?= $statusLabels[$task['status']] ?? ucfirst($task['status']) ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="mb-4">
                        <label class="small text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">Status</label>
                        <div class="p-2 border border-light rounded bg-light">
                            <span class="spor-badge <?= $statusBadgeClass ?>"><?= $statusLabel ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="mb-4">
                    <label class="small text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">Asignați</label>
                    <div class="d-flex flex-column gap-2">
                        <?php if (empty($task['assignees'])): ?>
                            <span class="small text-muted">Fără asignați</span>
                        <?php else: ?>
                            <?php foreach ($task['assignees'] as $assignee): ?>
                                <?php
                                $assigneeName = trim(($assignee['first_name'] ?? '') . ' ' . ($assignee['last_name'] ?? ''));
                                $assigneeInitials = '';
                                if (!empty($assignee['first_name'])) $assigneeInitials .= mb_substr($assignee['first_name'], 0, 1);
                                if (!empty($assignee['last_name'])) $assigneeInitials .= mb_substr($assignee['last_name'], 0, 1);
                                if (empty($assigneeInitials)) $assigneeInitials = mb_substr($assignee['email'] ?? 'U', 0, 2);
                                $assigneeInitials = mb_strtoupper($assigneeInitials);
                                $assigneeId = $assignee['id'] ?? 0;
                                ?>
                                <div class="d-flex align-items-center gap-2 p-2 border border-light rounded bg-light assignee-item"
                                    style="cursor: pointer; transition: all 0.2s;"
                                    data-assignee='<?= htmlspecialchars(json_encode([
                                                        'id' => $assignee['id'] ?? 0,
                                                        'first_name' => $assignee['first_name'] ?? '',
                                                        'last_name' => $assignee['last_name'] ?? '',
                                                        'email' => $assignee['email'] ?? '',
                                                        'phone' => $assignee['phone'] ?? null,
                                                        'role' => $assignee['role'] ?? '',
                                                        'full_name' => $assigneeName ?: ($assignee['email'] ?? 'Utilizator'),
                                                        'initials' => $assigneeInitials,
                                                    ]), ENT_QUOTES, 'UTF-8') ?>'
                                    onmouseover="this.style.backgroundColor='#f8f9fa'; this.style.borderColor='#cbd5e1'"
                                    onmouseout="this.style.backgroundColor='#fff'; this.style.borderColor='#e2e8f0'">
                                    <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center small fw-bold" style="width: 24px; height: 24px;">
                                        <?= $assigneeInitials ?>
                                    </div>
                                    <span class="small fw-bold text-dark"><?= esc($assigneeName ?: ($assignee['email'] ?? 'Utilizator')) ?></span>
                                    <i class="bi bi-info-circle text-secondary ms-auto" style="font-size: 0.75rem;"></i>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="small text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">Deadline</label>
                    <?php if (!empty($task['deadline'])): ?>
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-calendar4"></i>
                            <span class="fw-medium font-monospace small"><?= date('d M Y', strtotime($task['deadline'])) ?></span>
                        </div>
                    <?php else: ?>
                        <span class="small text-muted">Fără deadline</span>
                    <?php endif; ?>
                </div>
                <div class="mb-4">
                    <label class="small text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">Contract</label>
                    <div class="small text-secondary"><?= esc($task['contract']['name'] ?? '-') ?></div>
                </div>
                <?php if (!empty($task['contract_manager'])): ?>
                    <div class="mb-4">
                        <label class="small text-secondary fw-bold text-uppercase mb-2" style="font-size: 0.7rem;">Manager Contract</label>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-person-circle text-secondary"></i>
                                <span class="fw-medium text-dark small"><?= esc($task['contract_manager']['full_name']) ?></span>
                            </div>
                            <?php if (!empty($task['contract_manager']['email'])): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-envelope text-secondary"></i>
                                    <a href="mailto:<?= esc($task['contract_manager']['email'], 'attr') ?>" class="text-decoration-none small text-primary">
                                        <?= esc($task['contract_manager']['email']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($task['contract_manager']['phone'])): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-telephone text-secondary"></i>
                                    <a href="tel:<?= esc($task['contract_manager']['phone'], 'attr') ?>" class="text-decoration-none small text-primary">
                                        <?= esc($task['contract_manager']['phone']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="<?= $canEdit ? 'mb-0' : 'mb-4' ?>">
                    <label class="small text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">Subdiviziune</label>
                    <div class="small text-secondary"><?= esc($task['subdivision']['name'] ?? '-') ?></div>
                </div>
                <?php if ($canEdit): ?>
                    <div class="mt-4 pt-3 border-top border-light">
                        <a href="<?= site_url('tasks/edit/' . $task['id']) ?>" class="btn btn-sm btn-spor-secondary w-100">
                            <i class="bi bi-pencil me-2"></i>Editează Task
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($task['activity_logs'])): ?>
                <div class="spor-card p-4 mb-0" style="height: auto;">
                    <h6 class="fw-bold text-dark mb-3">Istoric Activitate</h6>
                    <div class="d-flex flex-column gap-3">
                        <?php
                        $logs = array_slice($task['activity_logs'], 0, 10);
                        foreach ($logs as $log):
                        ?>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="small fw-bold text-dark"><?= esc($log['first_name'] ?? '') ?> <?= esc($log['last_name'] ?? 'Sistem') ?></span>
                                    <span class="small text-muted" data-time="<?= $log['created_at'] ?>"></span>
                                </div>
                                <p class="small text-secondary m-0"><?= esc($log['description'] ?? $log['action_type']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const taskId = <?= $task['id'] ?>;
    const csrfToken = '<?= csrf_hash() ?>';

    function addComment() {
        const commentInput = document.getElementById('commentInput');
        const comment = commentInput.value.trim();

        if (!comment) {
            return;
        }

        fetch(`<?= site_url('tasks') ?>/${taskId}/comment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `comment=${encodeURIComponent(comment)}&<?= csrf_token() ?>=${csrfToken}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    commentInput.value = '';
                    location.reload(); // Reload to show new comment
                } else {
                    alert(data.message || 'Eroare la adăugarea comentariului.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Eroare la adăugarea comentariului.');
            });
    }

    function updateStatus(newStatus) {
        if (!confirm('Ești sigur că vrei să schimbi statusul task-ului?')) {
            // Reset select to current status
            document.getElementById('statusSelect').value = '<?= $task['status'] ?>';
            return;
        }

        fetch(`<?= site_url('tasks') ?>/${taskId}/update-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `status=${encodeURIComponent(newStatus)}&<?= csrf_token() ?>=${csrfToken}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload to show updated status
                } else {
                    alert(data.message || 'Eroare la actualizarea status-ului.');
                    // Reset select
                    document.getElementById('statusSelect').value = '<?= $task['status'] ?>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Eroare la actualizarea status-ului.');
                // Reset select
                document.getElementById('statusSelect').value = '<?= $task['status'] ?>';
            });
    }

    function handleFileUpload(event) {
        const files = event.target.files;
        if (!files || files.length === 0) return;

        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }
        formData.append('<?= csrf_token() ?>', csrfToken);

        fetch(`<?= site_url('tasks') ?>/${taskId}/upload-file`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload to show new files
                } else {
                    alert(data.message || 'Eroare la încărcarea fișierelor.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Eroare la încărcarea fișierelor.');
            });

        // Reset input
        event.target.value = '';
    }

    function timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'acum';
        if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm urmă';
        if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h urmă';
        if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + ' zile urmă';
        return date.toLocaleDateString('ro-RO');
    }

    // Update all time ago elements on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-time]').forEach(element => {
            const dateString = element.getAttribute('data-time');
            element.textContent = timeAgo(dateString);
        });

        // Add click handlers for assignee items
        document.querySelectorAll('.assignee-item').forEach(item => {
            item.addEventListener('click', function() {
                const assigneeData = JSON.parse(this.getAttribute('data-assignee') || '{}');
                showAssigneeDetails(assigneeData);
            });
        });
    });

    // Role display names mapping
    const roleDisplayNames = {
        'admin': 'Administrator',
        'director': 'Director Regional',
        'manager': 'Manager Contract',
        'executant': 'Executant',
        'auditor': 'Auditor'
    };

    function showAssigneeDetails(assignee) {
        // Populate modal with assignee data
        document.getElementById('assigneeModalTitle').innerHTML = `
            <div class="d-flex align-items-center gap-3">
                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 48px; height: 48px; font-size: 1.2rem;">
                    ${assignee.initials}
                </div>
                <div>
                    <h5 class="mb-0 fw-bold">${assignee.full_name || 'Utilizator'}</h5>
                    ${assignee.role ? `<span class="badge bg-secondary small">${roleDisplayNames[assignee.role] || assignee.role}</span>` : ''}
                </div>
            </div>
        `;

        // Email
        const emailHtml = assignee.email ? `
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-envelope text-secondary"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Email</div>
                    <a href="mailto:${assignee.email}" class="text-decoration-none text-primary fw-medium">${assignee.email}</a>
                </div>
            </div>
        ` : '';

        // Phone
        const phoneHtml = assignee.phone ? `
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-telephone text-secondary"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Telefon</div>
                    <a href="tel:${assignee.phone}" class="text-decoration-none text-primary fw-medium">${assignee.phone}</a>
                </div>
            </div>
        ` : `
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-telephone text-secondary"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Telefon</div>
                    <span class="text-muted">Nu este disponibil</span>
                </div>
            </div>
        `;

        document.getElementById('assigneeModalBody').innerHTML = emailHtml + phoneHtml;

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('assigneeDetailsModal'));
        modal.show();
    }
</script>

<!-- Assignee Details Modal -->
<div class="modal fade" id="assigneeDetailsModal" tabindex="-1" aria-labelledby="assigneeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0" id="assigneeModalTitle">
                <!-- Populated by JavaScript -->
            </div>
            <div class="modal-body pt-4" id="assigneeModalBody">
                <!-- Populated by JavaScript -->
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 pe-4">
                <button type="button" class="btn btn-spor-secondary" data-bs-dismiss="modal">Închide</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>