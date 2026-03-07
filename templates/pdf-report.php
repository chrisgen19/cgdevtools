<?php defined('ABSPATH') || exit; ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CGDevTools <?php echo esc_html($report_title ?? 'Scan'); ?> Report &mdash; <?php echo esc_html($site_name); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1e293b; padding: 40px; font-size: 14px; }
        .header { border-bottom: 3px solid #1e293b; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { font-size: 24px; margin-bottom: 4px; }
        .header p { color: #64748b; font-size: 13px; }
        .meta { display: flex; gap: 40px; margin-bottom: 30px; }
        .meta-item { }
        .meta-item label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; display: block; margin-bottom: 2px; }
        .meta-item span { font-weight: 600; }
        .summary { display: flex; gap: 20px; margin-bottom: 30px; }
        .summary-card { flex: 1; padding: 16px; border-radius: 8px; text-align: center; }
        .summary-card.pass { background: #f0fdf4; border: 1px solid #bbf7d0; }
        .summary-card.fail { background: #fef2f2; border: 1px solid #fecaca; }
        .summary-card.warn { background: #fffbeb; border: 1px solid #fde68a; }
        .summary-card .number { font-size: 28px; font-weight: 700; }
        .summary-card.pass .number { color: #16a34a; }
        .summary-card.fail .number { color: #dc2626; }
        .summary-card.warn .number { color: #d97706; }
        .summary-card p { font-size: 12px; color: #64748b; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f8fafc; text-align: left; padding: 10px 12px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
        .status { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-pass { background: #dcfce7; color: #16a34a; }
        .status-fail { background: #fee2e2; color: #dc2626; }
        .status-warning { background: #fef3c7; color: #d97706; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 11px; text-align: center; }
        @media print {
            body { padding: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center;margin-bottom:20px;">
        <button onclick="window.print()" style="padding:12px 32px;font-size:16px;cursor:pointer;background:#1e293b;color:#fff;border:none;border-radius:8px;font-weight:600;">
            Save as PDF / Print
        </button>
        <p style="margin-top:8px;color:#64748b;font-size:13px;">Use your browser's "Save as PDF" option in the print dialog.</p>
    </div>

    <div class="header">
        <h1>CGDevTools <?php echo esc_html($report_title ?? 'Scan'); ?> Report</h1>
        <p><?php echo esc_html($report_subtitle ?? 'Environment Assessment'); ?></p>
    </div>

    <div class="meta">
        <div class="meta-item">
            <label>Site</label>
            <span><?php echo esc_html($site_name); ?></span>
        </div>
        <div class="meta-item">
            <label>URL</label>
            <span><?php echo esc_html($site_url); ?></span>
        </div>
        <div class="meta-item">
            <label>Scan Date</label>
            <span><?php echo esc_html($timestamp); ?></span>
        </div>
        <div class="meta-item">
            <label>Generated</label>
            <span><?php echo esc_html(current_time('Y-m-d H:i:s')); ?></span>
        </div>
    </div>

    <?php
    $pass = $fail = $warn = 0;
    foreach ($results as $r) {
        match ($r['status']) {
            'pass'    => $pass++,
            'fail'    => $fail++,
            'warning' => $warn++,
            default   => null,
        };
    }
    $total = count($results);
    $score = $total > 0 ? round(($pass / $total) * 100) : 0;
    ?>

    <div class="summary">
        <div class="summary-card pass">
            <div class="number"><?php echo esc_html($pass); ?></div>
            <p>Passed</p>
        </div>
        <div class="summary-card fail">
            <div class="number"><?php echo esc_html($fail); ?></div>
            <p>Failed</p>
        </div>
        <div class="summary-card warn">
            <div class="number"><?php echo esc_html($warn); ?></div>
            <p>Warnings</p>
        </div>
        <div class="summary-card" style="background:#f8fafc;border:1px solid #e2e8f0;">
            <div class="number" style="color:#1e293b;"><?php echo esc_html($score); ?>%</div>
            <p>Readiness Score</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:80px;">Status</th>
                <th>Check</th>
                <th>Details</th>
                <th style="width:100px;">Category</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result) : ?>
                <tr>
                    <td>
                        <span class="status status-<?php echo esc_attr($result['status']); ?>">
                            <?php echo esc_html(strtoupper($result['status'] === 'warning' ? 'WARN' : $result['status'])); ?>
                        </span>
                    </td>
                    <td><strong><?php echo esc_html($result['label']); ?></strong></td>
                    <td><?php echo esc_html($result['description']); ?></td>
                    <td><?php echo esc_html(ucfirst($result['category'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        Generated by CGDevTools v<?php echo esc_html(CGDEVTOOLS_VERSION); ?> &mdash; <?php echo esc_html($site_url); ?>
    </div>
</body>
</html>
