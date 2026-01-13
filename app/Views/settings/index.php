<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="view-settings" class="fade-in">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h3 class="fw-bold m-0 text-dark">Setări Aplicatie</h3>
            <p class="text-muted m-0 mt-1">Configurează setările generale ale aplicației.</p>
        </div>
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
                <?= form_open('settings/update', ['id' => 'settingsForm']) ?>
                <?= csrf_field() ?>

                <h6 class="fw-bold text-dark mb-4">Email</h6>

                <?php 
                // Find send_welcome_email setting
                $sendWelcomeEmailSetting = null;
                foreach ($settings as $setting) {
                    if ($setting['key'] === 'send_welcome_email') {
                        $sendWelcomeEmailSetting = $setting;
                        break;
                    }
                }
                ?>

                <?php if ($sendWelcomeEmailSetting): ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <label class="form-label fw-bold text-dark mb-1" for="send_welcome_email">
                                    <?= esc($sendWelcomeEmailSetting['label']) ?>
                                </label>
                                <?php if (!empty($sendWelcomeEmailSetting['description'])): ?>
                                    <p class="text-muted small mb-0"><?= esc($sendWelcomeEmailSetting['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="form-check form-switch ms-3" style="flex-shrink: 0;">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    role="switch" 
                                    id="send_welcome_email" 
                                    value="1"
                                    <?= ($sendWelcomeEmailSetting['value'] === '1') ? 'checked' : '' ?>
                                    style="width: 3rem; height: 1.5rem; cursor: pointer;">
                            </div>
                        </div>
                        <input type="hidden" name="send_welcome_email" id="send_welcome_email_hidden" value="<?= ($sendWelcomeEmailSetting['value'] === '1') ? '1' : '0' ?>">
                    </div>
                <?php endif; ?>

                <hr class="border-light my-4">

                <div class="d-flex justify-content-end gap-3">
                    <button type="submit" class="btn btn-spor-primary px-4">Salvează Setările</button>
                </div>

                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update hidden input when checkbox changes
    const checkbox = document.getElementById('send_welcome_email');
    const hiddenInput = document.getElementById('send_welcome_email_hidden');
    
    if (checkbox && hiddenInput) {
        checkbox.addEventListener('change', function() {
            hiddenInput.value = this.checked ? '1' : '0';
        });
    }
});
</script>

<?= $this->endSection() ?>

