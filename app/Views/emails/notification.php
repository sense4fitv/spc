<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($notification['title']) ?></title>
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

        .notification-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .notification-type.info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .notification-type.success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .notification-type.warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .notification-type.error {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .notification-title {
            font-size: 20px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 16px;
        }

        .notification-message {
            font-size: 15px;
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .notification-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #1f2937;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .notification-button:hover {
            background-color: #374151;
        }

        .email-footer {
            background-color: #f9fafb;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #6b7280;
        }

        .email-footer p {
            margin: 4px 0;
        }

        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }

            .email-body {
                padding: 24px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="email-header">
            <h1>ATLAS by SuperCom</h1>
        </div>

        <div class="email-body">
            <span class="notification-type <?= esc($notification['type']) ?>">
                <?= esc(ucfirst($notification['type'])) ?>
            </span>

            <h2 class="notification-title">
                <?= esc($notification['title']) ?>
            </h2>

            <div class="notification-message">
                <?= nl2br(esc($notification['message'])) ?>
            </div>

            <?php if (!empty($notification['link'])): ?>
                <a href="<?= esc($notification['link'], 'attr') ?>" class="notification-button">
                    Vezi detalii
                </a>
            <?php endif; ?>
        </div>

        <div class="email-footer">
            <p><strong>ATLAS by SuperCom</strong></p>
            <p>Acest email a fost trimis automat. Te rugăm să nu răspunzi la acest email.</p>
        </div>
    </div>
</body>

</html>