<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATLAS by SuperCom - Autentificare</title>

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
                    <i class="bi bi-grid-fill"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">Bine ai revenit</h4>
                <p class="text-secondary small">Autentificare în platforma ATLAS by SuperCom</p>
            </div>

            <!-- Error Alert -->
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-error mb-3" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-exclamation-circle-fill me-2 flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <div class="alert-title">Eroare la autentificare</div>
                            <div class="alert-message"><?= session()->getFlashdata('error') ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Success Alert -->
            <div class="alert alert-success mb-4 d-none" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-check-circle-fill me-2 flex-shrink-0"></i>
                    <div class="flex-grow-1">
                        <div class="alert-title">Autentificare reușită</div>
                        <div class="alert-message">Bine ai venit! Te redirecționăm către dashboard.</div>
                    </div>
                </div>
            </div>

            <form action="<?= site_url('auth/login') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="mb-4">
                    <label class="form-label text-secondary small fw-bold text-uppercase">Email</label>
                    <!-- Removed Icon, Clean Input -->
                    <input type="email" name="email" class="form-control" placeholder="nume@companie.ro" value="<?= old('email') ?>" required>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label text-secondary small fw-bold text-uppercase m-0">Parolă</label>
                        <a href="#" class="text-decoration-none small text-secondary fw-medium hover-dark">Ai uitat parola?</a>
                    </div>
                    <!-- Password with Toggle -->
                    <div class="input-group">
                        <input type="password" name="password" class="form-control password-input" id="passwordInput" placeholder="••••••••" required>
                        <button class="btn btn-toggle-password" type="button" onclick="togglePassword()">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="remember">
                    <label class="form-check-label text-secondary small" for="remember">
                        Ține-mă minte pe acest dispozitiv
                    </label>
                </div>

                <button type="submit" class="btn btn-spor-primary">Autentificare</button>
            </form>
        </div>
        <div class="bg-light p-3 text-center border-top border-light">
            <p class="text-secondary small m-0">Nu ai cont? <a href="#" class="text-dark fw-bold text-decoration-none">Contactează Adminul</a></p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('passwordInput');
            const eyeIcon = document.getElementById('eyeIcon');

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