<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bun venit în ATLAS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background-color: #f3f4f6;
        }

        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: #ffffff;
            padding: 24px 32px;
            text-align: center;
        }

        .email-header h1 {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .email-body {
            padding: 32px;
        }

        .welcome-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            background-color: #10b981;
            color: #ffffff;
            margin-bottom: 16px;
        }

        .credentials-box {
            background-color: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
        }

        .credential-item {
            margin-bottom: 16px;
        }

        .credential-item:last-child {
            margin-bottom: 0;
        }

        .credential-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .credential-value {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            font-family: 'Courier New', monospace;
            background-color: #ffffff;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #1f2937;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 16px;
            text-align: center;
        }

        .button:hover {
            background-color: #374151;
        }

        .email-footer {
            background-color: #f9fafb;
            padding: 20px 32px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }

        .warning-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            border-radius: 4px;
            margin: 20px 0;
        }

        .warning-box p {
            margin: 0;
            font-size: 14px;
            color: #92400e;
        }

        h2 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
        }

        p {
            margin-bottom: 12px;
            color: #4b5563;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="email-header">
            <h1>ATLAS by SuperCom</h1>
        </div>

        <div class="email-body">
            <span class="welcome-badge">Bun venit!</span>

            <h2>Salut<?= !empty($user['first_name']) ? ' ' . esc($user['first_name']) : '' ?>!</h2>

            <p>Contul tău a fost creat în sistemul ATLAS. Acum poți accesa platforma folosind datele de mai jos.</p>

            <div class="credentials-box">
                <div class="credential-item">
                    <div class="credential-label">Adresă Email</div>
                    <div class="credential-value"><?= esc($user['email']) ?></div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Parolă Temporară</div>
                    <div class="credential-value"><?= esc($temporary_password) ?></div>
                </div>
            </div>

            <div class="warning-box">
                <p><strong>⚠️ Important:</strong> Te rugăm să îți setezi o parolă nouă după prima autentificare. Parola temporară este valabilă timp de 24 de ore.</p>
            </div>

            <p><strong>Pași pentru prima autentificare:</strong></p>
            <ol style="margin-left: 20px; margin-bottom: 20px; color: #4b5563;">
                <li>Accesează platforma folosind email-ul și parola temporară de mai sus</li>
                <li>Vei fi redirecționat automat către pagina de setare parolă</li>
                <li>Alege o parolă sigură (minim 8 caractere, incluzând litere, cifre și simboluri)</li>
                <li>După ce îți setezi parola, contul tău va fi activat automat</li>
            </ol>

            <div style="text-align: center;">
                <a href="<?= esc($set_password_url) ?>" class="button">Setează Parola</a>
            </div>

            <p style="margin-top: 24px; font-size: 14px; color: #6b7280;">
                Dacă butonul nu funcționează, copiază și deschide acest link în browser:<br>
                <a href="<?= esc($set_password_url) ?>" style="color: #1f2937; word-break: break-all;"><?= esc($set_password_url) ?></a>
            </p>
        </div>

        <div class="email-footer">
            <p>Acest email a fost trimis automat de sistemul ATLAS</p>
            <p>Dacă nu ai solicitat acest cont, te rugăm să ignori acest email.</p>
            <p style="margin-top: 12px;">© <?= date('Y') ?> Supercom - Toate drepturile rezervate</p>
        </div>
    </div>
</body>

</html>