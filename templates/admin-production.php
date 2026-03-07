<?php defined('ABSPATH') || exit;

$last_scan = get_option('cgdevtools_last_production_scan', []);
$results = $last_scan['results'] ?? [];
$timestamp = $last_scan['timestamp'] ?? null;

$pass_count = 0;
$fail_count = 0;
$warn_count = 0;

foreach ($results as $r) {
    match ($r['status']) {
        'pass'    => $pass_count++,
        'fail'    => $fail_count++,
        'warning' => $warn_count++,
        default   => null,
    };
}

$total = count($results);
$score = $total > 0 ? round(($pass_count / $total) * 100) : 0;
?>

<div class="wrap cgdevtools-wrap">
    <h1 class="cgdevtools-title">
        <span class="dashicons dashicons-migrate"></span>
        Dev Tools &mdash; Production Ready Scan
    </h1>

    <p class="description" style="margin-bottom:20px;font-size:14px;">
        This scan verifies that all staging restrictions have been removed and the site is ready for live production deployment.
        It will detect and help you undo all CGDevTools overrides applied during staging.
    </p>

    <div class="cgdevtools-header-actions">
        <button id="cgdevtools-run-production-scan" class="button button-primary button-hero">
            <span class="dashicons dashicons-search"></span> Run Production Scan
        </button>
        <button id="cgdevtools-production-fix-all" class="button button-secondary button-hero" <?php echo empty($results) ? 'disabled' : ''; ?>>
            <span class="dashicons dashicons-admin-generic"></span> Fix All Issues
        </button>
        <?php if (!empty($results)) : ?>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=cgdevtools_export_production_pdf'), 'cgdevtools_export_production_pdf')); ?>"
               class="button button-secondary button-hero">
                <span class="dashicons dashicons-media-document"></span> Export PDF
            </a>
        <?php endif; ?>
    </div>

    <?php if ($timestamp) : ?>
        <p class="cgdevtools-last-scan">
            Last scan: <strong><?php echo esc_html($timestamp); ?></strong>
        </p>
    <?php endif; ?>

    <!-- Score Cards -->
    <div class="cgdevtools-score-cards" id="cgdevtools-production-score-cards" style="<?php echo empty($results) ? 'display:none;' : ''; ?>">
        <div class="cgdevtools-card cgdevtools-card-score">
            <div class="cgdevtools-score-circle" data-score="<?php echo esc_attr($score); ?>">
                <span class="cgdevtools-score-number"><?php echo esc_html($score); ?>%</span>
            </div>
            <p>Production Readiness</p>
        </div>
        <div class="cgdevtools-card cgdevtools-card-pass">
            <span class="cgdevtools-card-number" id="cgdevtools-production-pass-count"><?php echo esc_html($pass_count); ?></span>
            <p>Passed</p>
        </div>
        <div class="cgdevtools-card cgdevtools-card-fail">
            <span class="cgdevtools-card-number" id="cgdevtools-production-fail-count"><?php echo esc_html($fail_count); ?></span>
            <p>Failed</p>
        </div>
        <div class="cgdevtools-card cgdevtools-card-warn">
            <span class="cgdevtools-card-number" id="cgdevtools-production-warn-count"><?php echo esc_html($warn_count); ?></span>
            <p>Warnings</p>
        </div>
    </div>

    <!-- Results Table -->
    <div id="cgdevtools-production-results">
        <?php if (!empty($results)) : ?>
            <table class="cgdevtools-results-table widefat">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Check</th>
                        <th>Details</th>
                        <th>Category</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result) : ?>
                        <tr class="cgdevtools-row-<?php echo esc_attr($result['status']); ?>">
                            <td>
                                <span class="cgdevtools-status cgdevtools-status-<?php echo esc_attr($result['status']); ?>">
                                    <?php echo match ($result['status']) {
                                        'pass'    => 'PASS',
                                        'fail'    => 'FAIL',
                                        'warning' => 'WARN',
                                        default   => '???',
                                    }; ?>
                                </span>
                            </td>
                            <td><strong><?php echo esc_html($result['label']); ?></strong></td>
                            <td><?php echo esc_html($result['description']); ?></td>
                            <td><span class="cgdevtools-category"><?php echo esc_html(ucfirst($result['category'])); ?></span></td>
                            <td>
                                <?php if ($result['status'] !== 'pass' && $result['fixable']) : ?>
                                    <button class="button button-small cgdevtools-production-quick-fix" data-check="<?php echo esc_attr($result['id']); ?>">
                                        Fix
                                    </button>
                                <?php endif; ?>
                                <?php if ($result['status'] !== 'pass' && !empty($result['link'])) : ?>
                                    <a href="<?php echo esc_url($result['link']); ?>" class="button button-small">
                                        <?php echo esc_html($result['link_label'] ?? 'Settings'); ?>
                                    </a>
                                <?php endif; ?>
                                <?php if ($result['status'] === 'pass') : ?>
                                    <span class="dashicons dashicons-yes-alt" style="color:#16a34a;"></span>
                                <?php elseif (empty($result['fixable']) && empty($result['link'])) : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="cgdevtools-empty-state">
                <span class="dashicons dashicons-migrate" style="font-size:48px;width:48px;height:48px;color:#9ca3af;"></span>
                <h2>No production scan results yet</h2>
                <p>Click "Run Production Scan" to check if your site is ready for live deployment.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
