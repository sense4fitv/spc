<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATLAS by SuperCom - Setare Parolă</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= site_url('assets/css/auth_styles.css') ?>">
</head>

<body>

    <div class="spor-card">
        <div class="p-5">
            <div class="text-center mb-5">
                <div class="logo-container mx-auto">
                    <i class="bi bi-lock-fill"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">Setare Parolă</h4>
                <p class="text-secondary small">Introduceți parola nouă pentru contul dvs.</p>
            </div>

            <!-- Error Alert -->
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-error mb-3" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-exclamation-circle-fill me-2 flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <div class="alert-title">Eroare</div>
                            <div class="alert-message"><?= session()->getFlashdata('error') ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>


            <!-- Success Alert -->
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success mb-3" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-check-circle-fill me-2 flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <div class="alert-title">Succes</div>
                            <div class="alert-message"><?= session()->getFlashdata('success') ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form action="<?= site_url('auth/set-password') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= esc($token ?? '') ?>">

                <div class="mb-4">
                    <label class="form-label text-secondary small fw-bold text-uppercase">Parolă Nouă</label>
                    <div class="input-group">
                        <input type="password" name="password" class="form-control password-input" id="passwordInput" placeholder="••••••••" required>
                        <button class="btn btn-toggle-password" type="button" onclick="togglePassword('passwordInput', 'eyeIcon')">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary small fw-bold text-uppercase">Confirmă Parola</label>
                    <div class="input-group">
                        <input type="password" name="password_confirm" class="form-control password-input" id="passwordConfirmInput" placeholder="••••••••" required>
                        <button class="btn btn-toggle-password" type="button" onclick="togglePassword('passwordConfirmInput', 'eyeIconConfirm')">
                            <i class="bi bi-eye" id="eyeIconConfirm"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-spor-primary">Setează Parola</button>
            </form>
        </div>
        <div class="bg-light p-3 text-center border-top border-light">
            <p class="text-secondary small m-0"><a href="<?= site_url('auth/login') ?>" class="text-dark fw-bold text-decoration-none">Înapoi la autentificare</a></p>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(iconId);

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = "password";
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        }
    </script>
</body>

</html>