<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-edit-task" class="fade-in">
    <a href="<?= site_url('tasks/view/' . $task['id']) ?>" class="btn btn-link text-secondary text-decoration-none p-0 mb-4 fw-medium" style="font-size: 0.85rem;">
        <i class="bi bi-arrow-left me-1"></i> Înapoi la task
    </a>
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom border-light pb-4">
        <h3 class="fw-bold text-dark m-0">Editează Task</h3>
    </div>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="spor-card p-5">
                <?= form_open('tasks/update/' . $task['id'], ['id' => 'editTaskForm']) ?>
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

                <h6 class="fw-bold text-dark mb-4">Informații Task</h6>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Subdiviziune <span class="text-danger">*</span></label>
                    <select name="subdivision_id" id="select-subdivision" class="form-select" required>
                        <option value="">Selectează subdiviziune...</option>
                        <?php foreach ($subdivisions as $subdivisionId => $subdivisionName): ?>
                            <option value="<?= $subdivisionId ?>" <?= old('subdivision_id', $task['subdivision_id']) == $subdivisionId ? 'selected' : '' ?>>
                                <?= esc($subdivisionName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($validation && $validation->hasError('subdivision_id')): ?>
                        <div class="text-danger small mt-1"><?= $validation->getError('subdivision_id') ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Titlu <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= old('title', $task['title']) ?>" placeholder="Ex: Predare Planuri Structura" required>
                    <?php if ($validation && $validation->hasError('title')): ?>
                        <div class="text-danger small mt-1"><?= $validation->getError('title') ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Descriere</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Descriere detaliată a task-ului..."><?= old('description', $task['description']) ?></textarea>
                    <?php if ($validation && $validation->hasError('description')): ?>
                        <div class="text-danger small mt-1"><?= $validation->getError('description') ?></div>
                    <?php endif; ?>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Prioritate <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            <option value="">Selectează...</option>
                            <?php foreach (['low' => 'Scăzută', 'medium' => 'Medie', 'high' => 'Ridicată', 'critical' => 'Critică'] as $priorityCode => $priorityLabel): ?>
                                <option value="<?= $priorityCode ?>" <?= old('priority', $task['priority'] ?? 'medium') === $priorityCode ? 'selected' : '' ?>>
                                    <?= $priorityLabel ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($validation && $validation->hasError('priority')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('priority') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Deadline</label>
                        <input type="datetime-local" name="deadline" class="form-control" value="<?= old('deadline', $task['deadline'] ? date('Y-m-d\TH:i', strtotime($task['deadline'])) : '') ?>">
                        <?php if ($validation && $validation->hasError('deadline')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('deadline') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="border-light my-4">

                <h6 class="fw-bold text-dark mb-4">Asignări</h6>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Utilizatori Asignați</label>
                    <select name="assignees[]" id="select-assignees" class="form-select" multiple>
                        <?php foreach ($users as $userId => $userName): ?>
                            <option value="<?= $userId ?>" <?= in_array($userId, old('assignees', $currentAssignees)) ? 'selected' : '' ?>>
                                <?= esc($userName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-muted small">Poți selecta unul sau mai mulți utilizatori care vor fi notificați.</div>
                </div>

                <div class="mb-5">
                    <label class="form-label small fw-bold text-muted text-uppercase">Departamente (Opțional)</label>
                    <select name="departments[]" id="select-departments" class="form-select" multiple>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= in_array($dept['id'], old('departments', $currentDepartments)) ? 'selected' : '' ?>>
                                <?= esc($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-muted small">Poți selecta mai multe departamente asociate task-ului.</div>
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= site_url('tasks/view/' . $task['id']) ?>" class="btn btn-spor-secondary px-4">Anulează</a>
                    <button type="submit" class="btn btn-spor-primary px-4">Salvează Modificări</button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Tom Select for subdivisions
        new TomSelect('#select-subdivision', {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });

        // Initialize Tom Select for assignees
        new TomSelect('#select-assignees', {
            plugins: ['remove_button'],
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });

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

