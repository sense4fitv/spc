<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raport Task-uri Critice - ATLAS</title>
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
            border-bottom: 3px solid #dc3545;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #dc3545;
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
            background: #fff3cd;
            border-left: 4px solid #ffc107;
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
            background: #dc3545;
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

        .priority-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-critical {
            background: #f8d7da;
            color: #721c24;
        }

        .priority-high {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-blocked {
            background: #f8d7da;
            color: #721c24;
        }

        .status-overdue {
            background: #fff3cd;
            color: #856404;
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
        }

        .summary-card label {
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            letter-spacing: 0.5px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin: 30px 0 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dc3545;
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
        <h1>Raport Task-uri Critice</h1>
        <p class="subtitle">Task-uri blocked, întârziate și prioritare</p>
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
            <strong>⚠️ Atenție:</strong>
            Aceste task-uri necesită acțiune imediată
        </div>
    </div>

    <div class="summary">
        <div class="summary-card">
            <label>Task-uri Blocate</label>
            <h3 style="color: #dc3545;"><?= $reportData['summary']['total_blocked'] ?? 0 ?></h3>
        </div>
        <div class="summary-card">
            <label>Task-uri Întârziate</label>
            <h3 style="color: #ffc107;"><?= $reportData['summary']['total_overdue'] ?? 0 ?></h3>
        </div>
        <div class="summary-card">
            <label>Task-uri Critice</label>
            <h3 style="color: #dc3545;"><?= $reportData['summary']['total_critical'] ?? 0 ?></h3>
        </div>
    </div>

    <?php if (!empty($reportData['blocked_tasks'])): ?>
        <h2 class="section-title">Task-uri Blocate</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Titlu</th>
                    <th>Contract</th>
                    <th>Regiune</th>
                    <th>Prioritate</th>
                    <th>Zile Blocat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['blocked_tasks'] as $task): ?>
                    <tr>
                        <td>#<?= $task['id'] ?></td>
                        <td style="font-weight: 600;"><?= esc($task['title']) ?></td>
                        <td><?= esc($task['contract_name'] ?? '-') ?></td>
                        <td><?= esc($task['region_name'] ?? '-') ?></td>
                        <td>
                            <span class="priority-badge priority-<?= $task['priority'] ?? 'medium' ?>">
                                <?= ucfirst($task['priority'] ?? 'medium') ?>
                            </span>
                        </td>
                        <td><?= $task['days_blocked'] ?? 0 ?> zile</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (!empty($reportData['overdue_tasks'])): ?>
        <h2 class="section-title">Task-uri Întârziate (> 7 zile)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Titlu</th>
                    <th>Contract</th>
                    <th>Regiune</th>
                    <th>Prioritate</th>
                    <th>Deadline</th>
                    <th>Zile Întârziat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['overdue_tasks'] as $task): ?>
                    <tr>
                        <td>#<?= $task['id'] ?></td>
                        <td style="font-weight: 600;"><?= esc($task['title']) ?></td>
                        <td><?= esc($task['contract_name'] ?? '-') ?></td>
                        <td><?= esc($task['region_name'] ?? '-') ?></td>
                        <td>
                            <span class="priority-badge priority-<?= $task['priority'] ?? 'medium' ?>">
                                <?= ucfirst($task['priority'] ?? 'medium') ?>
                            </span>
                        </td>
                        <td><?= $task['deadline'] ? date('d.m.Y', strtotime($task['deadline'])) : '-' ?></td>
                        <td style="color: #dc3545; font-weight: 600;"><?= $task['days_overdue'] ?? 0 ?> zile</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (!empty($reportData['critical_tasks'])): ?>
        <h2 class="section-title">Task-uri cu Prioritate Critică</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Titlu</th>
                    <th>Contract</th>
                    <th>Regiune</th>
                    <th>Deadline</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['critical_tasks'] as $task): ?>
                    <tr>
                        <td>#<?= $task['id'] ?></td>
                        <td style="font-weight: 600;"><?= esc($task['title']) ?></td>
                        <td><?= esc($task['contract_name'] ?? '-') ?></td>
                        <td><?= esc($task['region_name'] ?? '-') ?></td>
                        <td><?= $task['deadline'] ? date('d.m.Y', strtotime($task['deadline'])) : '-' ?></td>
                        <td>
                            <span class="status-badge status-<?= $task['status'] ?? 'new' ?>">
                                <?= ucfirst($task['status'] ?? 'new') ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (empty($reportData['blocked_tasks']) && empty($reportData['overdue_tasks']) && empty($reportData['critical_tasks'])): ?>
        <div style="text-align: center; padding: 50px; color: #28a745;">
            <p style="font-size: 16px; margin-bottom: 10px;">✓ Nu există task-uri critice pentru perioada selectată.</p>
            <p style="font-size: 12px; color: #666;">Toate task-urile sunt în regulă!</p>
        </div>
    <?php endif; ?>

    <div class="footer">
        <p>Raport generat automat de ATLAS by SuperCom</p>
    </div>
</body>

</html>