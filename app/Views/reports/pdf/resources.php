<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raport Resurse - ATLAS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            background: #fff;
            padding: 40px;
        }

        .header {
            border-bottom: 3px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .header .subtitle {
            font-size: 14px;
            color: #666;
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 4px;
        }

        .meta-info div {
            font-size: 11px;
        }

        .meta-info strong {
            display: block;
            margin-bottom: 3px;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        thead {
            background: #000;
            color: #fff;
        }

        th {
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }

        td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        .progress-bar {
            width: 80px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
        }

        .progress-fill {
            height: 100%;
            background: #000;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            padding: 15px;
            background: #f5f5f5;
            border-radius: 4px;
            text-align: center;
        }

        .summary-card h3 {
            font-size: 32px;
            font-weight: 700;
            margin: 10px 0 5px;
            color: #000;
        }

        .summary-card label {
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            letter-spacing: 0.5px;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        @media print {
            body {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Raport Resurse</h1>
        <p class="subtitle">Utilizatori activi, workload și top performers</p>
    </div>

    <div class="meta-info">
        <div>
            <strong>Perioadă:</strong>
            <?= date('d.m.Y', strtotime($reportData['period']['from'])) ?> - <?= date('d.m.Y', strtotime($reportData['period']['to'])) ?>
        </div>
        <div>
            <strong>Generat la:</strong>
            <?= date('d.m.Y H:i') ?>
        </div>
        <div>
            <strong>Total Utilizatori:</strong>
            <?= $reportData['summary']['total_users'] ?? 0 ?>
        </div>
    </div>

    <div class="summary">
        <div class="summary-card">
            <label>Total Utilizatori</label>
            <h3><?= $reportData['summary']['total_users'] ?? 0 ?></h3>
        </div>
        <div class="summary-card">
            <label>Task-uri Create</label>
            <h3><?= $reportData['summary']['total_tasks_created'] ?? 0 ?></h3>
        </div>
        <div class="summary-card">
            <label>Task-uri Finalizate</label>
            <h3><?= $reportData['summary']['total_tasks_completed'] ?? 0 ?></h3>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nume</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Regiune</th>
                <th>Task-uri Create</th>
                <th>Task-uri Finalizate</th>
                <th>Task-uri Active</th>
                <th>Workload</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reportData['users'])): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px; color: #999;">
                        Nu există date disponibile pentru perioada selectată.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($reportData['users'] as $user): ?>
                    <tr>
                        <td style="font-weight: 600;"><?= esc($user['name']) ?></td>
                        <td><?= esc($user['email']) ?></td>
                        <td><?= esc(ucfirst($user['role'] ?? '')) ?></td>
                        <td><?= esc($user['region_name'] ?? '-') ?></td>
                        <td><?= $user['tasks_created'] ?? 0 ?></td>
                        <td><?= $user['tasks_completed'] ?? 0 ?></td>
                        <td><?= $user['active_tasks'] ?? 0 ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $user['workload_percentage'] ?? 0 ?>%"></div>
                                </div>
                                <span><?= $user['workload_percentage'] ?? 0 ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Raport generat automat de ATLAS by SuperCom</p>
    </div>
</body>

</html>